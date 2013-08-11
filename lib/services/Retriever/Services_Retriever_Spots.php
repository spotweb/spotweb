<?php
class Services_Retriever_Spots extends Services_Retriever_Base {
    /**
     * @var array
     */
    private $_rsakeys;
    /**
     * @var boolean
     */
    private $_retrieveFull;
    /**
     * @var boolean
     */
    private $_prefetch_image;
    /**
     * @var boolean
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
     * @var Dao_Base_Cache
     */
    private $_cacheDao;
    /**
     * @var Dao_Base_ModeratedRingBuffer
     */
    private $_modListDao;

		/**
		 * Server is the server array we are expecting to connect to
		 * db - database object
		 */
		function __construct(Dao_Factory $daoFactory, Services_Settings_Base $settings, $debug, $force, $retro) {
			parent::__construct($daoFactory, $settings, $debug, $force, $retro);
			
			$this->_rsakeys = $this->_settings->get('rsa_keys');
			$this->_retrieveFull = $this->_settings->get('retrieve_full');
			$this->_prefetch_image = $this->_settings->get('prefetch_image');
			$this->_prefetch_nzb = $this->_settings->get('prefetch_nzb');

			$this->_spotDao = $daoFactory->getSpotDao();
			$this->_commentDao = $daoFactory->getCommentDao();
			$this->_cacheDao = $daoFactory->getCacheDao();
            $this->_modListDao = $daoFactory->getModeratedRingBufferDao();
			$this->_svcSpotParser = new Services_Format_Parsing();

			# if we need to fetch images or nzb files, we need several service objects
			if ($this->_retrieveFull || $this->_prefetch_image || $this->_prefetch_nzb) {
				/*
				 * NNTP Spot Reading engine
				 */
				$this->_svcNntpTextReading = new Services_Nntp_SpotReading($this->_svcNntpText);
				$this->_svcNntpBinReading = new Services_Nntp_SpotReading($this->_svcNntpBin);

				$this->_svcProvNzb = new Services_Providers_Nzb($this->_cacheDao,
														 $this->_svcNntpBinReading);
				$this->_svcProvImage = new Services_Providers_SpotImage(new Services_Providers_Http($this->_cacheDao),
																 $this->_svcNntpBinReading,
											  					 $this->_cacheDao);
			} # if
		} # ctor

		/*
		 * Returns the status in either xml or text format 
		 */
		function displayStatus($cat, $txt) {
				switch($cat) {
					case 'start'			: echo "Retrieving new Spots from server " . $txt . "..." . PHP_EOL; break;
					case 'lastretrieve'		: echo strftime("Last retrieve at %c", $txt) . PHP_EOL; break;
					case 'done'				: echo "Finished retrieving spots." . PHP_EOL . PHP_EOL; break;
					case 'groupmessagecount': echo "Appr. Message count: 	" . $txt . "" . PHP_EOL; break;
					case 'firstmsg'			: echo "First message number:	" . $txt . "" . PHP_EOL; break;
					case 'lastmsg'			: echo "Last message number:	" . $txt . "" . PHP_EOL; break;
					case 'curartnr'			: echo "Current article number:	" . $txt . "" . PHP_EOL; break;
					case 'progress'			: echo "Retrieving " . $txt; break;
					case 'hdrparsed'		: echo " (parsed: " . $txt . ", "; break;
                    case 'hdrindbcount'     : echo "in DB: " . $txt . ", "; break;
                    case 'fullretrieved'	: echo "full: " . $txt . ", "; break;
					case 'verified'			: echo "signed: " . $txt . ", "; break;
                    case 'invalidcount'     : echo "invalid: " . $txt . ", "; break;
					case 'modcount'			: echo "mod: " . $txt . ", "; break;
					case 'skipcount'		: echo "rtntn.skip: " . $txt . ", "; break;
					case 'loopcount'		: echo "total: " . $txt . ")"; break;
					case 'timer'			: echo " in " . $txt . " seconds" . PHP_EOL; break;
					case 'totalprocessed'	: echo "Processed a total of " . $txt . " spots" . PHP_EOL; break;
					case 'searchmsgid'		: echo "Looking for articlenumber for messageid" . PHP_EOL; break;
					case 'searchmsgidstatus': echo "Searching from " . $txt . PHP_EOL; break;
                    case 'slowphprsa'       : echo 'WARNING: Using slow PHP based RSA, please enable the PHP OpenSSL extension whenever possible' . PHP_EOL; break;
					case ''					: echo PHP_EOL; break;
					
					default					: echo $cat . $txt;
				} # switch
		} # displayStatus
		
		/*
		 * Remove any extraneous spots from the database because we assume
		 * the highest messgeid in the database is the latest on the server.
		 */
		function removeTooNewRecords($highestMessageId) {
			$this->debug('Highest messageid found: ' . $highestMessageId);

			/*
			 * Remove any extraneous spots from the database because we assume
			 * the highest messgeid in the database is the latest on the server.
			 *
			 * If the server is marked as buggy, the last 'x' amount of spot are
			 * always checked so we do not have to do this 
			 */
			if (!$this->_textServer['buggy']) {
				$this->_spotDao->removeExtraSpots($highestMessageId);
			} # if
		} # removeTooNewRecords

		/*
		 * Actually process the retrieved headers from XOVER
		 */
		function process($hdrList, $curArtNr, $increment, $timer) {
			$this->displayStatus("progress", ($curArtNr) . " till " . ($increment));

			$signedCount = 0;
			$hdrsParsed = 0;
			$fullsRetrieved = 0;
            $invalidCount = 0;
			$msgCounter = 0;
			$modCount = 0;
            $headerInDbCount = 0;
			$skipCount = 0;
			$lastProcessedId = '';
			$fullSpotDbList = array();
			$spotDbList = array();
			$moderationList = array();
			$processingStartTime = time();

			/*
			 * Determine the cutoff date (unixtimestamp) from whereon we do not want to 
			 * load the spots
			 */
			if ($this->_settings->get('retention') > 0) {
				$retentionStamp = time() - ($this->_settings->get('retention') * 24 * 60 * 60);
			} else {
				$retentionStamp = 0;
			} # else
			$this->debug('retentionStamp=' . $retentionStamp);
			$this->debug('hdrList=' . serialize($hdrList));
			
			/**
			 * We ask the database to match our messageid's we just retrieved with
			 * the list of id's we have just retrieved from the server
			 */
            SpotTiming::start(__CLASS__ . '::' . __FUNCTION__ . ':matchSpotMessageIds');
			$dbIdList = $this->_spotDao->matchSpotMessageIds($hdrList);
            SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__ . ':matchSpotMessageIds');

			$this->debug('dbIdList=' . serialize($dbIdList));

            /*
             * We get a list of spots which have been blacklisted before,
             * we do this because when the 'buggy' flag is set, we else keep
             * retrieving the same spots, nzb's and images over and over again
             */
            $preModdedList = $this->_modListDao->matchAgainst($hdrList);

            SpotTiming::start(__CLASS__ . '::' . __FUNCTION__ . ':forEach');
            foreach($hdrList as $msgheader) {
                SpotTiming::start(__CLASS__ . '::' . __FUNCTION__ . ':forEach-to-ParseHeader');
				$msgCounter++;
				$this->debug('foreach-loop, start. msgId= ' . $msgCounter);

				/* 
				 * Keep te usenet server alive when processing is slow.
				 */
				if (($processingStartTime - time()) > 30) {
					$this->_svcNntpText->sendNoop();
					$this->_svcNntpBin->sendNoop();

					$processingStartTime = time();
				} # if

				/*
				 * We keep track whether we actually fetched this header and fullspot
				 * to add it to the database, because only then we can update the
				 * title from the spots title or rely on our database to fetch
				 * the fullspot
				 */
				$didFetchHeader = false;
				$didFetchFullSpot = false;
				
				# Reset timelimit
				set_time_limit(120);

				# messageid to check
				$msgId = substr($msgheader['Message-ID'], 1, -1);

                /*
                 * If this message was already deleted in a previous run,
                 * les not even consider it
                 */
                if (isset($preModdedList[$msgId])) {
                    $skipCount++;
                    continue;
                } # if

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
					$this->debug('foreach-loop, parsingXover, start. msgId= ' . $msgCounter);
                    SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__ . ':forEach-to-ParseHeader');
                    SpotTiming::start(__CLASS__ . '::' . __FUNCTION__ . ':parseHeader');
					$spot = $this->_svcSpotParser->parseHeader($msgheader['Subject'],
															$msgheader['From'], 
															$msgheader['Date'],
															$msgheader['Message-ID'],
															$this->_rsakeys);
                    SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__ . ':parseHeader');
					$this->debug('foreach-loop, parsingXover, done. msgId= ' . $msgCounter);

					/*
					 * When a parse error occurred, we ignore the spot, also unverified
					 * spots are ignored
					 */
					if (($spot === false) || (!$spot['verified'])){
                        $invalidCount++;
						continue;
					} # if

					/*
					 * Special moderator commands always have keyid 2
					 */
					if ($spot['keyid'] == 2) {
						$commandAr = explode(' ', $spot['title']);
						$validCommands = array('delete', 'dispose', 'remove');

						# is this one of the defined valid commands?
						if (in_array(strtolower($commandAr[0]), $validCommands) !== false) {
							$moderationList[$commandAr[1]] = 1;
							$modCount++;
						} # if
						
					} else {
						/*
						 * Don't add spots older than specified for the retention stamp
						 */
						if (($retentionStamp > 0) && ($spot['stamp'] < $retentionStamp) && ($this->_settings->get('retentiontype') == 'everything')) {
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
								$didFetchHeader = true;

								if ($spot['wassigned']) {
									$signedCount++;
								} # if
							} # if 
						} # if
					
					} # else
				} else {
					$lastProcessedId = $msgId;
                    $headerInDbCount++;
				} # else

                SpotTiming::start(__CLASS__ . '::' . __FUNCTION__ . ':forEach-getFullSpot');

				/*
				 * We don't want to retrieve the fullspot if we don't have the header
				 * in the database. Because we try to add headers in the above code we just have
				 * to check if the header is in the database.
				 *
				 * We cannot collapse this code with the header fetching code because we want to
				 * be able to add the fullspot to a system after all the headers are retrieved
				 */
				if ($header_isInDb &&			# header must be in the db
					!$fullspot_isInDb)	 		# but the fullspot should not
				   {
					/*
					 * Don't add older fullspots than specified for the retention stamp
					 */
					if (($retentionStamp > 0) && (strtotime($msgheader['Date']) < $retentionStamp)) {
						continue;
					} # if

					if ($this->_retrieveFull) {
						$fullSpot = array();
						try {
							$fullsRetrieved++;
							$this->debug('foreach-loop, getFullSpot, start. msgId= ' . $msgId);
							$fullSpot = $this->_svcNntpTextReading->readFullSpot($msgId);
							$this->debug('foreach-loop, getFullSpot, done. msgId= ' . $msgId);

							# add this spot to the database
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
							} # if
						}
						catch(ParseSpotXmlException $x) {
							; # swallow error
						}
						catch(Exception $x) {
							/**
							 * Sometimes we get an 'No such article' error for a header we just retrieved,
							 * if we want to retrieve the full article. This is messed up, but let's just
							 * swallow the error
							 */
							if ($x->getCode() == 430) {
								;
							}
							# if the XML is unparseable, don't bother complaining about it
							elseif ($x->getMessage() == 'String could not be parsed as XML') {
								;
							} else {
								throw $x;
							} # else
						} # catch

					} # if retrievefull
				} # if fullspot is not in db yet

                SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__ . ':forEach-getFullSpot');
                SpotTiming::start(__CLASS__ . '::' . __FUNCTION__ . ':forEach-getNzbOrImage');

                if ($this->_retrieveFull && $header_isInDb && ($this->_prefetch_image || $this->_prefetch_nzb)) {
					try {
						/*
						 * If we are running in 'retro' mode, it is possible both the header and spot are in the
						 * database already, however -- we need the information from the fullspot so we retrieve it
						 * again
						 */
						if (!$didFetchFullSpot) {
                            SpotTiming::start(__CLASS__ . '::' . __FUNCTION__ . ':daoGetFullSpot');
                            $fullSpot = $this->_spotDao->getFullSpot($msgId, SPOTWEB_ANONYMOUS_USERID);
							$fullSpot = array_merge($this->_svcSpotParser->parseFull($fullSpot['fullxml']), $fullSpot);
                            SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__ . ':daoGetFullSpot');
						} # if

						/*
						 * Prefetch (cache) the spots' image
						 */
                        SpotTiming::start(__CLASS__ . '::' . __FUNCTION__ . ':forEach-getImage');
						if ($this->_prefetch_image) {
                            $this->debug('foreach-loop, getImage(), start. msgId= ' . $msgId);

                            if (!$this->_svcProvImage->hasCachedSpotImage($fullSpot)) {
                                $this->_svcProvImage->fetchSpotImage($fullSpot);
                            } # if

                            $this->debug('foreach-loop, getImage(), done. msgId= ' . $msgId);
						} # if
                        SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__ . ':forEach-getImage');

                        SpotTiming::start(__CLASS__ . '::' . __FUNCTION__ . ':forEach-getNzb');
                        /*
                         * Prefetch (cache) the spots' NZB file
                         */
						if ($this->_prefetch_nzb) {
							/*
							 * Only do so if we can expect an NZB file
							 */
							if (!empty($fullSpot['nzb']) && $fullSpot['stamp'] > 1290578400) {
								$this->debug('foreach-loop, getNzb(), start. msgId= ' . $msgId);

                                if (!$this->_svcProvNzb->hasCachedNzb($fullSpot)) {
                                    $this->_svcProvNzb->fetchNzb($fullSpot);
                                } # if
								$this->debug('foreach-loop, getNzb(), done. msgId= ' . $msgId);
							} # if
						} # if
                        SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__ . ':forEach-getNzb');
					}
					catch(ParseSpotXmlException $x) {
						; # swallow error
					}
					catch(Exception $x) {
						/**
						 * Sometimes we get an 'No such article' error for a header we just retrieved,
						 * if we want to retrieve the full article. This is messed up, but let's just
						 * swallow the error
						 */
						if ($x->getCode() == 430) {
							;
						} 
							# if the XML is unparseable, don't bother complaining about it
						elseif ($x->getMessage() == 'String could not be parsed as XML') {
							;
						} else {
							throw $x;
						} # else
					} # catch
				} # if prefetch image and/or nzb

                SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__ . ':forEach-getNzbOrImage');
				$this->debug('foreach-loop, done. msgId= ' . $msgCounter);
			} # foreach
            SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__ . ':forEach');

			if (count($hdrList) > 0) {
				$this->displayStatus("hdrparsed", $hdrsParsed);
                $this->displayStatus("hdrindbcount", $headerInDbCount);
                $this->displayStatus("verified", $signedCount);
                $this->displayStatus("invalidcount", $invalidCount);
                $this->displayStatus("skipcount", $skipCount);
                $this->displayStatus("modcount", $modCount);
				$this->displayStatus("fullretrieved", $fullsRetrieved);
				$this->displayStatus("loopcount", count($hdrList));
			} else {
				$this->displayStatus("hdrparsed", 0);
                $this->displayStatus("hdrindbcount", 0);
                $this->displayStatus("verified", 0);
                $this->displayStatus("invalidcount", 0);
                $this->displayStatus("skipcount", 0);
                $this->displayStatus("modcount", 0);
				$this->displayStatus("fullretrieved", 0);
				$this->displayStatus("loopcount", 0);
			} # else

			/* 
			 * Add the spots to the database and update the last article
			 * number found
			 */
			$this->_spotDao->addSpots($spotDbList, $fullSpotDbList);
			$this->debug('added Spots, spotDbList=' . serialize($spotDbList));
			$this->debug('added Spots, fullSpotDbList=' . serialize($fullSpotDbList));

			/*
			 * Actually act on the moderation settings. We cannot process this inline
			 * because a spot can be added and moderated within the same iteration
			 */
			switch($this->_settings->get('spot_moderation')) {
				case 'disable'	: break;
				case 'markspot'	: {
					$this->_commentDao->markCommentsModerated($moderationList); 
					$this->_spotDao->markSpotsModerated($moderationList); 
					
					break;
				} # case 'markspot' 
				default			: { 
					$this->_spotDao->removeSpots($moderationList); 
					$this->_commentDao->removeComments($moderationList);

                    /*
                     * If the spots actually get removed, we want to make
                     * sure we write the deleted spots down. This prevents
                     * us from retrieving and deleting them over and over again
                     */
                    $this->_modListDao->addToRingBuffer($moderationList);
					
					break;
				} # default
			} # switch
			
			# update the maximum article id
            if (count($spotDbList) > 0) {
                $this->_usenetStateDao->setMaxArticleId(Dao_UsenetState::State_Spots, $lastProcessedId, $increment);
            } # if
			$this->debug('loop finished, setMaxArticleId=' . serialize($increment));

            /*
             * And remove old list of moderated spots
             */
            $this->_modListDao->deleteOldest();

			$this->displayStatus("timer", round(microtime(true) - $timer, 2));

			return array('count' => count($hdrList), 'headercount' => $hdrsParsed, 'lastmsgid' => $lastProcessedId);
		} # process()

		/*
		 * returns the name of the group we are expected to retrieve messages from
		 */
		function getGroupName() {
			return array('text' => $this->_settings->get('hdr_group'),
						 'bin' => $this->_settings->get('hdr_group'));
		} # getGroupName

        /*
         * Last retrieved articlenumber as stored in the database
         */
        function getLastArticleNumber() {
            return $this->_usenetStateDao->getLastArticleNumber(Dao_UsenetState::State_Spots);
        } # getLastArticleNumber

        /*
         * Last retrieved messageid as stored in the database
         */
        function getLastMessageId() {
            return $this->_usenetStateDao->getLastMessageId(Dao_UsenetState::State_Spots);
        } # getLastMessageId

        /**
         * Returns a list of messageid's where we can search for
         *
         * @return array
         */
        function getRecentRetrievedMessageIdList() {
            return $this->_spotDao->getMaxMessageId('headers');
        } # getRecentRetrievedMessageIdList

} # Services_Retriever_Spots
