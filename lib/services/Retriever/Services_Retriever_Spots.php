<?php

class Services_Retriever_Spots extends Services_Retriever_Base
{
    /**
     * @var array
     */
    private $_rsakeys;
    /**
     * @var bool
     */
    private $_retrieveFull;
    /**
     * @var bool
     */
    private $_prefetch_image;
    /**
     * @var bool
     */
    private $_prefetch_nzb;

    /**
     * @var Services_Nntp_SpotReading
     */
    private $_svcNntpTextReading;
    /**
     * @var Services_Nntp_SpotReading
     */
    private $_svcNntpBinReading;
    /**
     * @var Services_Providers_Nzb
     */
    private $_svcProvNzb;
    /**
     * @var Services_Providers_SpotImage
     */
    private $_svcProvImage;
    /**
     * @var Services_Format_Parsing
     */
    private $_svcSpotParser;

    /**
     * @var Dao_Base_Spot
     */
    private $_spotDao;
    /**
     * @var Dao_Base_Comment
     */
    private $_commentDao;
    /**
     * @var Dao_Cache
     */
    private $_cacheDao;
    /**
     * @var Dao_ModeratedRingBuffer
     */
    private $_modListDao;

    /**
     * Server is the server array we are expecting to connect to
     * db - database object.
     */
    public function __construct(Dao_Factory $daoFactory, Services_Settings_Container $settings, $force, $retro)
    {
        parent::__construct($daoFactory, $settings, $force, $retro);

        $this->_rsakeys = $this->_settings->get('rsa_keys');
        $this->_retrieveFull = $this->_settings->get('retrieve_full');
        $this->_prefetch_image = $this->_settings->get('prefetch_image');
        $this->_prefetch_nzb = $this->_settings->get('prefetch_nzb');

        $this->_spotDao = $daoFactory->getSpotDao();
        $this->_commentDao = $daoFactory->getCommentDao();
        $this->_cacheDao = $daoFactory->getCacheDao();
        $this->_modListDao = $daoFactory->getModeratedRingBufferDao();
        $this->_svcSpotParser = new Services_Format_Parsing();

        // if we need to fetch images or nzb files, we need several service objects
        if ($this->_retrieveFull || $this->_prefetch_image || $this->_prefetch_nzb) {
            /*
             * NNTP Spot Reading engine
             */
            $this->_svcNntpTextReading = new Services_Nntp_SpotReading($this->_svcNntpText);
            $this->_svcNntpBinReading = new Services_Nntp_SpotReading($this->_svcNntpBin);

            $this->_svcProvNzb = new Services_Providers_Nzb(
                $this->_cacheDao,
                $this->_svcNntpBinReading
            );
            $this->_svcProvImage = new Services_Providers_SpotImage(
                new Services_Providers_Http($this->_cacheDao),
                $this->_svcNntpBinReading,
                $this->_cacheDao
            );
        } // if
    }

    // ctor

    /**
     * returns whether we are under extreme memory pressure or not.
     */
    public function hasMemoryPressure()
    {
        $val = trim(ini_get('memory_limit'));
        $last = strtolower($val[strlen($val) - 1]);
        $val = filter_var($val, FILTER_SANITIZE_NUMBER_INT);
        switch ($last) {
            // The 'G' modifier is available since PHP 5.1.0
            case 'g':
                $val *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $val *= 1024 * 1024;
                break;
            case 'k':
                $val *= 1024;
                break;
        }

        /*
         * Setting memory_limit to -1 means PHP has no memory
         * limit of its own, if so, we are never under memory
         * pressure.
         */
        if ($val < 0) {
            return false;
        } // if

        /*
         * If we have less than 16mbyte of free memory, start flushing to disk
         */
        return memory_get_usage(true) > ($val - (1024 * 1024 * 16));
    }

    // hasMemoryPressure

    /*
     * Returns the status in either xml or text format
     */
    public function displayStatus($cat, $txt)
    {
        switch ($cat) {
                    case 'start': echo 'Retrieving new Spots from server '.$txt.'...'.PHP_EOL; break;
                    case 'lastretrieve': echo strftime('Last retrieve at %c', $txt).PHP_EOL; break;
                    case 'done': echo 'Finished retrieving spots.'.PHP_EOL.PHP_EOL; break;
                    case 'groupmessagecount': echo 'Appr. Message count: 	'.$txt.''.PHP_EOL; break;
                    case 'firstmsg': echo 'First message number:	'.$txt.''.PHP_EOL; break;
                    case 'lastmsg': echo 'Last message number:	'.$txt.''.PHP_EOL; break;
                    case 'curartnr': echo 'Current article number:	'.$txt.''.PHP_EOL; break;
                    case 'progress': echo 'Retrieving '.$txt; break;
                    case 'hdrparsed': echo ' (parsed: '.$txt.', '; break;
                    case 'hdrindbcount': echo 'in DB: '.$txt.', '; break;
                    case 'fullretrieved': echo 'full: '.$txt.', '; break;
                    case 'verified': echo 'signed: '.$txt.', '; break;
                    case 'invalidcount': echo 'invalid: '.$txt.', '; break;
                    case 'modcount': echo 'mod: '.$txt.', '; break;
                    case 'skipcount': echo 'rtntn.skip: '.$txt.', '; break;
                    case 'loopcount': echo 'total: '.$txt.')'; break;
                    case 'timer': echo ' in '.$txt.' seconds'.PHP_EOL; break;
                    case 'totalprocessed': echo 'Processed a total of '.$txt.' spots'.PHP_EOL; break;
                    case 'searchmsgid': echo 'Looking for articlenumber for messageid'.PHP_EOL; break;
                    case 'searchmsgidstatus': echo 'Searching from '.$txt.PHP_EOL; break;
                    case 'slowphprsa': echo 'WARNING: Using slow PHP based RSA, please enable the PHP OpenSSL extension whenever possible'.PHP_EOL; break;
                    case '': echo PHP_EOL; break;

                    default: echo $cat.$txt;
                } // switch
    }

    // displayStatus

    /*
     * Remove any extraneous spots from the database because we assume
     * the highest messgeid in the database is the latest on the server.
     */
    public function removeTooNewRecords($highestMessageId)
    {
        SpotDebug::msg(SpotDebug::DEBUG, 'Highest messageid found: '.$highestMessageId);

        /*
         * Remove any extraneous spots from the database because we assume
         * the highest messgeid in the database is the latest on the server.
         *
         * If the server is marked as buggy, the last 'x' amount of spot are
         * always checked so we do not have to do this
         */
        if (!$this->_textServer['buggy']) {
            $this->_spotDao->removeExtraSpots($highestMessageId);
        } // if
    }

    // removeTooNewRecords

    /*
     * Actually process the retrieved headers from XOVER
     */
    public function process($hdrList, $curArtNr, $increment, $timer)
    {
        $this->displayStatus('progress', ($curArtNr).' till '.($increment));

        $signedCount = 0;
        $hdrsParsed = 0;
        $fullsRetrieved = 0;
        $invalidCount = 0;
        $msgCounter = 0;
        $modCount = 0;
        $headerInDbCount = 0;
        $skipCount = 0;
        $lastProcessedId = '';
        $lastProcessedArtNr = 0;
        $fullSpotDbList = [];
        $spotDbList = [];
        $moderationList = [];
        $processingStartTime = time();

        /*
         * Determine the cutoff date (unixtimestamp) from whereon we do not want to
         * load the spots
         */
        if ($this->_settings->get('retention') > 0) {
            $retentionStamp = time() - ($this->_settings->get('retention') * 24 * 60 * 60);
        } else {
            $retentionStamp = 0;
        } // else
        SpotDebug::msg(SpotDebug::DEBUG, 'retentionStamp='.$retentionStamp);
        SpotDebug::msg(SpotDebug::TRACE, 'hdrList='.serialize($hdrList));

        /**
         * We ask the database to match our messageid's we just retrieved with
         * the list of id's we have just retrieved from the server.
         */
        SpotTiming::start(__CLASS__.'::'.__FUNCTION__.':matchSpotMessageIds');
        $dbIdList = $this->_spotDao->matchSpotMessageIds($hdrList);
        SpotTiming::stop(__CLASS__.'::'.__FUNCTION__.':matchSpotMessageIds');
        SpotTiming::start(__CLASS__.'::'.__FUNCTION__.':getMassCacheRecords');
        $cachedIdList = $this->_cacheDao->getMassCacheRecords($hdrList);
        SpotTiming::stop(__CLASS__.'::'.__FUNCTION__.':getMassCacheRecords');

        SpotDebug::msg(SpotDebug::TRACE, __CLASS__.'::'.__FUNCTION__, ['dbIdList' => $dbIdList]);

        /*
         * We get a list of spots which have been blacklisted before,
         * we do this because when the 'buggy' flag is set, we else keep
         * retrieving the same spots, nzb's and images over and over again
         */
        $preModdedList = $this->_modListDao->matchAgainst($hdrList);

        SpotTiming::start(__CLASS__.'::'.__FUNCTION__.':forEach');
        foreach ($hdrList as $msgheader) {
            SpotTiming::start(__CLASS__.'::'.__FUNCTION__.':forEach-to-ParseHeader');
            $msgCounter++;
            SpotDebug::msg(SpotDebug::DEBUG, 'foreach-loop, start. msgId= '.$msgCounter);

            /*
             * Keep te usenet server alive when processing is slow.
             */
            if (($processingStartTime - time()) > 30) {
                $this->_svcNntpText->sendNoop();
                $this->_svcNntpBin->sendNoop();

                $processingStartTime = time();
            } // if

            /*
             * We keep track whether we actually fetched this header and fullspot
             * to add it to the database, because only then we can update the
             * title from the spots title or rely on our database to fetch
             * the fullspot
             */
            $didFetchHeader = false;
            $didFetchFullSpot = false;

            // Reset timelimit
            set_time_limit(120);

            // messageid to check
            $msgId = $msgheader['Message-ID'];
            $artNr = $msgheader['Number'];

            /*
             * If this message was already deleted in a previous run,
             * les not even consider it
             */
            if (isset($preModdedList[$msgId])) {
                SpotTiming::stop(__CLASS__.'::'.__FUNCTION__.':forEach-to-ParseHeader');

                $skipCount++;
                continue;
            } // if

            /*
             * We prepare some variables to we don't have to perform an array
             * lookup for each check and the code is easier to read.
             */
            $header_isInDb = isset($dbIdList['spot'][$msgId]);
            $fullspot_isInDb = isset($dbIdList['fullspot'][$msgId]);

            /*
             * If the spotheader is not yet added to the database, parse the header
             * information.
             *
             * If the header is present, but we don't have the fullspot yet or we are
             * running in 'retro' mode, parse the header as well because some fields
             * are only in the header and not in the full.
             *
             * We need some of those fields (for example KeyID)
             */
            if (!$header_isInDb || ((!$fullspot_isInDb || $this->_retro) && $this->_retrieveFull)) {
                $hdrsParsed++;
                SpotDebug::msg(SpotDebug::TRACE, 'foreach-loop, parsingXover, start. msgId= '.$msgCounter);
                SpotTiming::start(__CLASS__.'::'.__FUNCTION__.':parseHeader');
                $spot = $this->_svcSpotParser->parseHeader(
                    $msgheader['Subject'],
                    $msgheader['From'],
                    $msgheader['Date'],
                    $msgheader['Message-ID'],
                    $this->_rsakeys
                );
                SpotTiming::stop(__CLASS__.'::'.__FUNCTION__.':parseHeader');
                SpotDebug::msg(SpotDebug::TRACE, 'foreach-loop, parsingXover, done. msgId= '.$msgCounter);

                /*
                 * When a parse error occurred, we ignore the spot, also unverified
                 * spots are ignored
                 */
                if (($spot === false) || (!$spot['verified'])) {
                    SpotTiming::stop(__CLASS__.'::'.__FUNCTION__.':forEach-to-ParseHeader');

                    $invalidCount++;
                    continue;
                } // if

                /*
                 * Special moderator commands always have keyid 2 or 7
                 */

                $commandAr = explode(' ', $spot['title']);
                $validCommands = ['delete', 'dispose', 'remove'];

                if ($spot['keyid'] == 2) {

                        // is this one of the defined valid commands?
                    if (in_array(strtolower($commandAr[0]), $validCommands) !== false) {
                        //$moderationList[$commandAr[1]] = 1;
                        $moderationList[$commandAr[1]] = ['spotterid' => $spot['spotterid'], 'stamp' => $spot['stamp']];
                        $modCount++;
                    } // if
                } else {
                    /*
                     * Don't add spots older than specified for the retention stamp
                     */
                    if (($retentionStamp > 0) && ($spot['stamp'] < $retentionStamp) && ($this->_settings->get('retentiontype') == 'everything')) {
                        SpotTiming::stop(__CLASS__.'::'.__FUNCTION__.':forEach-to-ParseHeader');

                        $skipCount++;
                        continue;
                    } elseif ($spot['stamp'] < $this->_settings->get('retrieve_newer_than')) {
                        $skipCount++;
                    } else {
                        /*
                         * Do we have the header in the database? If not, lets add it
                         */
                        if (!$header_isInDb) {
                            $spotDbList[] = $spot;

                            /*
                             * Some buggy NNTP servers give us the same messageid
                             * in one XOVER statement, hence we update the list of
                             * messageid's we already have retrieved and are ready
                             * to be added to the database
                             */
                            $dbIdList['spot'][$msgId] = 1;
                            $header_isInDb = true;
                            $lastProcessedId = $msgId;
                            $lastProcessedArtNr = $artNr;
                            $didFetchHeader = true;

                            if ($spot['wassigned']) {
                                $signedCount++;
                            } // if
                        } // if
                    } // if
                } // else
            } else {
                $lastProcessedId = $msgId;
                $lastProcessedArtNr = $artNr;
                $headerInDbCount++;
            } // else

            SpotTiming::start(__CLASS__.'::'.__FUNCTION__.':forEach-getFullSpot');

            /*
             * We don't want to retrieve the fullspot if we don't have the header
             * in the database. Because we try to add headers in the above code we just have
             * to check if the header is in the database.
             *
             * We cannot collapse this code with the header fetching code because we want to
             * be able to add the fullspot to a system after all the headers are retrieved
             */
            if ($header_isInDb &&			// header must be in the db
                    !$fullspot_isInDb) {	 		// but the fullspot should not
                    /*
                     * Don't add older fullspots than specified for the retention stamp
                     */
                if (($retentionStamp > 0) && (strtotime($msgheader['Date']) < $retentionStamp)) {
                    SpotTiming::stop(__CLASS__.'::'.__FUNCTION__.':forEach-to-ParseHeader');
                    SpotTiming::stop(__CLASS__.'::'.__FUNCTION__.':forEach-getFullSpot');

                    continue;
                } // if

                if ($this->_retrieveFull) {
                    $fullSpot = [];

                    try {
                        $fullsRetrieved++;
                        SpotDebug::msg(SpotDebug::TRACE, 'foreach-loop, getFullSpot, start. msgId= '.$msgId);
                        $fullSpot = $this->_svcNntpTextReading->readFullSpot($msgId);
                        SpotDebug::msg(SpotDebug::TRACE, 'foreach-loop, getFullSpot, done. msgId= '.$msgId);

                        // did we fail to parse the spot? if so, skip this one
                        if (empty($fullSpot)) {
                            $invalidCount++;
                            continue;
                        } // if

                        // add this spot to the database
                        $fullSpotDbList[] = $fullSpot;
                        $fullspot_isInDb = true;
                        $didFetchFullSpot = true;

                        /*
                         * Some buggy NNTP servers give us the same messageid
                         * in the same XOVER statement, hence we update the list of
                         * messageid's we already have retrieved and are ready
                         * to be added to the database
                         */
                        $dbIdList['fullspot'][$msgId] = 1;

                        /*
                         * Overwrite the spots' title because the fullspot contains the title in
                         * UTF-8 format.
                         * We also overwrite the spotterid from the spotsfull because the spotterid
                         * is only in the header in more recent spots.
                         */
                        if ($didFetchHeader) {
                            $spotDbList[count($spotDbList) - 1]['title'] = $fullSpot['title'];
                            $spotDbList[count($spotDbList) - 1]['spotterid'] = $fullSpot['spotterid'];
                        } // if
                    } catch (ParseSpotXmlException $x) {
                        // swallow error
                    } catch (Exception $x) {
                        /**
                         * Sometimes we get an 'No such article' error for a header we just retrieved,
                         * if we want to retrieve the full article. This is messed up, but let's just
                         * swallow the error.
                         */
                        if ($x->getCode() == 430) {
                            /*
                             * Reset error count, so other errors are actually re-tried
                             */
                            $this->_svcNntpText->resetErrorCount();
                            $this->_svcNntpBin->resetErrorCount();
                        }
                        // if the XML is unparseable, don't bother complaining about it
                        elseif ($x->getMessage() == 'String could not be parsed as XML') {
                        } else {
                            throw $x;
                        } // else
                    } // catch
                } // if retrievefull
            } // if fullspot is not in db yet

                SpotTiming::stop(__CLASS__.'::'.__FUNCTION__.':forEach-getFullSpot');
            SpotTiming::start(__CLASS__.'::'.__FUNCTION__.':forEach-getNzbOrImage');

            /*
             * If both the image and the NZB file are already in the cache,
             * or we are set to not prefetch them, don't bother to retrieve
             * the full spot either from the database
             */
            $needPrefetch = ($this->_prefetch_image || $this->_prefetch_nzb);
            if ((!$this->_retrieveFull) || (!$header_isInDb)) {
                $needPrefetch = false;
            } // if

            if ($needPrefetch) {
                $needPrefetch = (!isset($cachedIdList[Dao_Cache::SpotImage][$msgId])) ||
                                    (!isset($cachedIdList[Dao_Cache::SpotNzb][$msgId]));
            } // if

            if ($needPrefetch) {
                try {
                    /*
                     * If we are running in 'retro' mode, it is possible both the header and spot are in the
                     * database already, however -- we need the information from the fullspot so we retrieve it
                     * again
                     */
                    if (!$didFetchFullSpot) {
                        SpotTiming::start(__CLASS__.'::'.__FUNCTION__.':daoGetFullSpot');
                        $fullSpot = $this->_spotDao->getFullSpot($msgId, SPOTWEB_ANONYMOUS_USERID);
                        SpotTiming::start(__CLASS__.'::'.__FUNCTION__.':retrieveParseFullSpot');
                        $fullSpot = array_merge($this->_svcSpotParser->parseFull($fullSpot['fullxml']), $fullSpot);
                        SpotTiming::stop(__CLASS__.'::'.__FUNCTION__.':retrieveParseFullSpot', []);
                        SpotTiming::stop(__CLASS__.'::'.__FUNCTION__.':daoGetFullSpot');
                    } // if

                    /*
                     * Prefetch (cache) the spots' image
                     */
                    SpotTiming::start(__CLASS__.'::'.__FUNCTION__.':forEach-getImage');
                    if ($this->_prefetch_image) {
                        SpotDebug::msg(SpotDebug::TRACE, 'foreach-loop, getImage(), start. msgId= '.$msgId);

                        if (!isset($cachedIdList[Dao_Cache::SpotImage][$fullSpot['messageid']])) {
                            $this->_svcProvImage->fetchSpotImage($fullSpot);
                        } // if

                        SpotDebug::msg(SpotDebug::TRACE, 'foreach-loop, getImage(), done. msgId= '.$msgId);
                    } // if
                    SpotTiming::stop(__CLASS__.'::'.__FUNCTION__.':forEach-getImage');

                    SpotTiming::start(__CLASS__.'::'.__FUNCTION__.':forEach-getNzb');
                    /*
                     * Prefetch (cache) the spots' NZB file
                     */
                    if ($this->_prefetch_nzb) {
                        /*
                         * Only do so if we can expect an NZB file
                         */
                        if (!empty($fullSpot['nzb']) && $fullSpot['stamp'] > 1290578400) {
                            SpotDebug::msg(SpotDebug::TRACE, 'foreach-loop, getNzb(), start. msgId= '.$msgId);

                            if (!isset($cachedIdList[Dao_Cache::SpotNzb][$fullSpot['messageid']])) {
                                $this->_svcProvNzb->fetchNzb($fullSpot);
                            } // if
                            SpotDebug::msg(SpotDebug::TRACE, 'foreach-loop, getNzb(), done. msgId= '.$msgId);
                        } // if
                    } // if
                        SpotTiming::stop(__CLASS__.'::'.__FUNCTION__.':forEach-getNzb');
                } catch (ParseSpotXmlException $x) {
                    // swallow error
                } catch (Exception $x) {
                    SpotTiming::stop(__CLASS__.'::'.__FUNCTION__.':retrieveParseFullSpot', []);
                    SpotTiming::stop(__CLASS__.'::'.__FUNCTION__.':daoGetFullSpot');

                    /**
                     * Sometimes we get an 'No such article' error for a header we just retrieved,
                     * if we want to retrieve the full article. This is messed up, but let's just
                     * swallow the error.
                     */
                    if ($x->getCode() == 430) {
                        /*
                         * Reset error count, so other errors are actually re-tried
                         */
                        $this->_svcNntpText->resetErrorCount();
                        $this->_svcNntpBin->resetErrorCount();
                    }
                    // if the XML is unparseable, don't bother complaining about it
                    elseif ($x->getMessage() == 'String could not be parsed as XML') {
                    } else {
                        throw $x;
                    } // else
                } // catch
            } // if prefetch image and/or nzb

                SpotTiming::stop(__CLASS__.'::'.__FUNCTION__.':forEach-getNzbOrImage');
            SpotTiming::stop(__CLASS__.'::'.__FUNCTION__.':forEach-to-ParseHeader');

            /*
             * If we are under memory pressure, flush the cache to disk in advance so we
             * can free up memory. This is slower, but might avoid ballooning memory.
             */
            if ($this->hasMemoryPressure()) {
                SpotDebug::msg(SpotDebug::DEBUG, 'we are under memory pressure, flushing to disk');

                echo 'We are under memory pressure... ';

                $this->_spotDao->addSpots($spotDbList, $fullSpotDbList);

                $spotDbList = [];
                $fullSpotDbList = [];
            } // if

            SpotDebug::msg(SpotDebug::DEBUG, 'foreach-loop, done. msgId= '.$msgCounter);
        } // foreach
        SpotTiming::stop(__CLASS__.'::'.__FUNCTION__.':forEach');

        if (count($hdrList) > 0) {
            $this->displayStatus('hdrparsed', $hdrsParsed);
            $this->displayStatus('hdrindbcount', $headerInDbCount);
            $this->displayStatus('verified', $signedCount);
            $this->displayStatus('invalidcount', $invalidCount);
            $this->displayStatus('skipcount', $skipCount);
            $this->displayStatus('modcount', $modCount);
            $this->displayStatus('fullretrieved', $fullsRetrieved);
            $this->displayStatus('loopcount', count($hdrList));
        } else {
            $this->displayStatus('hdrparsed', 0);
            $this->displayStatus('hdrindbcount', 0);
            $this->displayStatus('verified', 0);
            $this->displayStatus('invalidcount', 0);
            $this->displayStatus('skipcount', 0);
            $this->displayStatus('modcount', 0);
            $this->displayStatus('fullretrieved', 0);
            $this->displayStatus('loopcount', 0);
        } // else

        /*
         * Add the spots to the database and update the last article
         * number found
         */
        $this->_spotDao->addSpots($spotDbList, $fullSpotDbList);
        SpotDebug::msg(SpotDebug::TRACE, 'added Spots, spotDbList='.serialize($spotDbList));
        SpotDebug::msg(SpotDebug::TRACE, 'added Spots, fullSpotDbList='.serialize($fullSpotDbList));

        /*
         * Actually act on the moderation settings. We cannot process this inline
         * because a spot can be added and moderated within the same iteration
         */
        switch ($this->_settings->get('spot_moderation')) {
                case 'disable': break;
                case 'markspot':

                    $moderationList = $this->removeInvalidDisposes($moderationList);
                    $this->_commentDao->markCommentsModerated($moderationList);
                    $this->_spotDao->markSpotsModerated($moderationList);

                    break;
                 // case 'markspot'
                default:
                    $moderationList = $this->removeInvalidDisposes($moderationList);
                    $this->_spotDao->removeSpots($moderationList);
                    $this->_commentDao->removeComments($moderationList);
                    /*
                     * If the spots actually get removed, we want to make
                     * sure we write the deleted spots down. This prevents
                     * us from retrieving and deleting them over and over again
                     */
                    $this->_modListDao->addToRingBuffer($moderationList);

                    break;
                 // default
            } // switch

            // update the maximum article id
        if (!empty($lastProcessedId) && ($lastProcessedArtNr > 0)) {
            $this->_usenetStateDao->setMaxArticleId(Dao_UsenetState::State_Spots, $lastProcessedArtNr, $lastProcessedId);
        } // if
        SpotDebug::msg(SpotDebug::DEBUG, 'loop finished, setMaxArticleId='.serialize($increment));

        /*
         * And remove old list of moderated spots
         */
        $this->_modListDao->deleteOldest();

        $this->displayStatus('timer', round(microtime(true) - $timer, 2));

        return ['count' => count($hdrList), 'headercount' => $hdrsParsed, 'lastmsgid' => $lastProcessedId];
    }

    // process()

    /*
     * Remove invalid disposes from list
     * This prevents invalid personal disposes from being executed
     * Checked is : dispose spotterid is the same as spot spotterid
     * and timestamp of dispose is not older than 5 days
     */
    public function removeInvalidDisposes($moderationList)
    {
        /* check all dispose messages */
        $tmpArray = $this->_spotDao->getDisposedSpots($moderationList);
        foreach ($tmpArray as $value) {
            if (!empty($moderationList[$value['messageid']]['spotterid'])) {
                if ($moderationList[$value['messageid']]['spotterid'] != $value['spotterid']) {
                    unset($moderationList[$value['messageid']]);
                } else {
                    $stmod = $moderationList[$value['messageid']]['stamp'];
                    $stspot = $value['stamp'];
                    $diff = $stmod - $stspot;
                    if ($diff > 432000) {
                        unset($moderationList[$value['messageid']]);
                    }
                }
            }
        }

        return $moderationList;
    }

    // RemoveInvalidDisposes

    /*
     * returns the name of the group we are expected to retrieve messages from
     */
    public function getGroupName()
    {
        return ['text' => $this->_settings->get('hdr_group'),
            'bin'      => $this->_settings->get('nzb_group'), ];
    }

    // getGroupName

    /*
     * Last retrieved articlenumber as stored in the database
     */
    public function getLastArticleNumber()
    {
        return $this->_usenetStateDao->getLastArticleNumber(Dao_UsenetState::State_Spots);
    }

    // getLastArticleNumber

    /*
     * Last retrieved messageid as stored in the database
     */
    public function getLastMessageId()
    {
        return $this->_usenetStateDao->getLastMessageId(Dao_UsenetState::State_Spots);
    }

    // getLastMessageId

    /**
     * Returns a list of messageid's where we can search for.
     *
     * @return array
     */
    public function getRecentRetrievedMessageIdList()
    {
        return $this->_spotDao->getMaxMessageId('headers');
    }

    // getRecentRetrievedMessageIdList
} // Services_Retriever_Spots
