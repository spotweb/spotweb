<?php

class Services_Retriever_Comments extends Services_Retriever_Base
{
    private $_svcNntpTextReading;
    private $_retrieveFull;

    /**
     * Server is the server array we are expecting to connect to
     * db - database object.
     */
    public function __construct(Dao_Factory $daoFactory, Services_Settings_Container $settings, $force, $retro)
    {
        parent::__construct($daoFactory, $settings, $force, $retro);

        $this->_spotDao = $daoFactory->getSpotDao();
        $this->_commentDao = $daoFactory->getCommentDao();

        $this->_svcNntpTextReading = new Services_Nntp_SpotReading($this->_svcNntpText);
        $this->_retrieveFull = $this->_settings->get('retrieve_full_comments');
    }

    // ctor

    /*
     * Returns the status in either xml or text format
     */
    public function displayStatus($cat, $txt)
    {
        switch ($cat) {
                    case 'start': echo 'Retrieving new comments from server '.$txt.'...'.PHP_EOL; break;
                    case 'lastretrieve': echo strftime('Last retrieve at %c', $txt).PHP_EOL; break;
                    case 'done': echo 'Finished retrieving comments.'.PHP_EOL.PHP_EOL; break;
                    case 'groupmessagecount': echo 'Appr. Message count: 	'.$txt.''.PHP_EOL; break;
                    case 'firstmsg': echo 'First message number:	'.$txt.''.PHP_EOL; break;
                    case 'lastmsg': echo 'Last message number:	'.$txt.''.PHP_EOL; break;
                    case 'curartnr': echo 'Current article number:	'.$txt.''.PHP_EOL; break;
                    case 'progress': echo 'Retrieving '.$txt; break;
                    case 'loopcount': echo ', found '.$txt.' comments'; break;
                    case 'timer': echo ' in '.$txt.' seconds'.PHP_EOL; break;
                    case 'totalprocessed': echo 'Processed a total of '.$txt.' comments'.PHP_EOL; break;
                    case 'searchmsgid': echo 'Looking for articlenumber for messageid'.PHP_EOL; break;
                    case 'searchmsgidstatus': echo 'Searching from '.$txt.PHP_EOL; break;
                    case 'slowphprsa': echo 'WARNING: Using slow PHP based RSA, please enable OpenSSL whenever possible'; break;
                    case '': echo PHP_EOL; break;

                    default: echo $cat.$txt;
                } // switch
    }

    // displayStatus

    /*
     * Remove any extraneous reports from the database because we assume
     * the highest messgeid in the database is the latest on the server.
     */
    public function removeTooNewRecords($highestMessageId)
    {
        /*
         * Remove any extraneous comments from the database because we assume
         * the highest messgeid in the database is the latest on the server.
         *
         * If the server is marked as buggy, the last 'x' amount of comments are
         * always checked so we do not have to do this
         */
        if (!$this->_textServer['buggy']) {
            $this->_commentDao->removeExtraComments($highestMessageId);
        } // if
    }

    // removeTooNewRecords

    /*
     * Actually process the retrieved headers from XOVER
     */
    public function process($hdrList, $curArtNr, $increment, $timer)
    {
        $this->displayStatus('progress', ($curArtNr).' till '.($increment));

        $lastProcessedId = '';
        $lastProcessedArtNr = 0;
        $commentDbList = [];
        $fullCommentDbList = [];

        /*
         * Determine the cutoff date (unixtimestamp) from whereon we do not want to
         * load the spots
         */
        if ($this->_settings->get('retention') > 0) {
            $retentionStamp = time() - ($this->_settings->get('retention') * 24 * 60 * 60);
        } else {
            $retentionStamp = 0;
        } // else

        /**
         * We ask the database to match our messageid's we just retrieved with
         * the list of id's we have just retrieved from the server.
         */
        $dbIdList = $this->_commentDao->matchCommentMessageIds($hdrList);

        /*
         * We keep a seperate list of messageid's for updating the amount of
         * comments each spot.
         */
        $spotMsgIdList = [];

        /*
         * and a different list for comments with a rating, this way we wont
         * calculcate the rating for a spot when a comments has no rating
         */
        $spotMsgIdRatingList = [];

        // Process each header
        foreach ($hdrList as $msgheader) {
            SpotDebug::msg(SpotDebug::DEBUG, 'foreach-loop: iter-start');

            // Reset timelimit
            set_time_limit(120);

            // strip the <>'s from the reference
            $commentId = $msgheader['Message-ID'];
            $artNr = $msgheader['Number'];

            SpotDebug::msg(SpotDebug::DEBUG, 'foreach-loop: processing: '.$commentId.', artNr='.$artNr);

            /*
             * We prepare some variables to we don't have to perform an array
             * lookup for each check and the code is easier to read.
             */
            $header_isInDb = isset($dbIdList['comment'][$commentId]);
            $fullcomment_isInDb = isset($dbIdList['fullcomment'][$commentId]);

            SpotDebug::msg(SpotDebug::DEBUG, 'foreach-loop: headerIsInDb: '.
                                    (int) $header_isInDb.', fullComment='.
                                    (int) $fullcomment_isInDb.', retrieveFull= '.
                                    (int) $this->_retrieveFull);

            /*
             * Do we have the comment in the database already? If not, lets process it
             */
            if (!$header_isInDb || (!$fullcomment_isInDb && $this->_retrieveFull)) {
                /*
                 * Because not all usenet servers pass the reference field properly,
                 * we manually create this reference field by using the messageid of
                 * the comment
                 */
                $msgIdParts = explode('.', $commentId);
                $msgheader['References'] = $msgIdParts[0].substr($commentId, strpos($commentId, '@'));
                $msgheader['stamp'] = strtotime($msgheader['Date']);
                $msgheader['Subject'] = mb_convert_encoding($msgheader['Subject'], 'ASCII', 'ASCII');
                SpotDebug::msg(SpotDebug::TRACE, 'foreach-loop: msgHeader='.serialize($msgheader));

                /*
                 * Don't add older comments than specified for the retention stamp
                 */
                if (($retentionStamp > 0) && ($msgheader['stamp'] < $retentionStamp) && ($this->_settings->get('retentiontype') == 'everything')) {
                    continue;
                } // if

                if ($msgheader['stamp'] < $this->_settings->get('retrieve_newer_than')) {
                    continue;
                } // if

                /*
                 * Newer kind of comments contain a rating, if we think this comment
                 * is such a comment, extract the rating
                 */
                if (count($msgIdParts) == 5) {
                    $msgheader['rating'] = (int) $msgIdParts[1];

                    /*
                     * Some older comments contain an non-numeric string
                     * on this position. Make sure this is an number else
                     * reset to zero (no rating given)
                     */
                    if (!is_numeric($msgIdParts[1])) {
                        $msgheader['rating'] = 0;
                    } // if
                } else {
                    $msgheader['rating'] = 0;
                } // if

                /*
                 * Determine whether we need to add the header to the database
                 * and extract the required fields
                 */
                if (!$header_isInDb) {
                    $commentDbList[] = ['messageid' => $commentId,
                        'nntpref'                   => $msgheader['References'],
                        'stamp'                     => $msgheader['stamp'],
                        'rating'                    => $msgheader['rating'], ];

                    /*
                     * Some buggy NNTP servers give us the same messageid
                     * in one XOVER statement, hence we update the list of
                     * messageid's we already have retrieved and are ready
                     * to be added to the database
                     */
                    $dbIdList['comment'][$commentId] = 1;
                    $spotMsgIdList[$msgheader['References']] = 1;

                    /*
                     * If this comment contains a rating, mark the spot to
                     * have it's rating be recalculated
                     */
                    if ($msgheader['rating'] >= 1 && $msgheader['rating'] <= 10) {
                        $spotMsgIdRatingList[$msgheader['References']] = 1;
                    } // if

                    $header_isInDb = true;
                    $lastProcessedId = $commentId;
                    $lastProcessedArtNr = $artNr;
                    $didFetchHeader = true;
                } // if
            } else {
                $lastProcessedId = $commentId;
                $lastProcessedArtNr = $artNr;
            } // else

            /*
             * We don't want to retrieve the full comment body if we don't have the header
             * in the database. Because we try to add headers in the above code we just have
             * to check if the header is in the database.
             *
             * We cannot collapse this code with the header fetching code because we want to
             * be able to add the full body to a system after all the headers are retrieved
             */
            if (($header_isInDb) &&			// header should be in the db
                    (!$fullcomment_isInDb)) {		// but if we already have the full comment, skip
                    /*
                     * Don't add older fullcomments than specified for the retention stamp
                     */
                if (($retentionStamp > 0) && (strtotime($msgheader['Date']) < $retentionStamp)) {
                    continue;
                } // if

                if ($this->_retrieveFull) {
                    try {
                        SpotDebug::msg(SpotDebug::DEBUG, 'foreach-loop: readFullComment start:'.$commentId);
                        $fullComment = $this->_svcNntpTextReading->readComments([['messageid' => $commentId]]);
                        SpotDebug::msg(SpotDebug::DEBUG, 'foreach-loop: readFullComment finished:'.$commentId);

                        // Add this comment to the datbase and mark it as such
                        $fullCommentDbList[] = $fullComment;
                        $fullcomment_isInDb = true;

                        /*
                         * Some buggy NNTP servers give us the same messageid
                         * in one XOVER statement, hence we update the list of
                         * messageid's we already have retrieved and are ready
                         * to be added to the database
                         */
                        $dbIdList['fullcomment'][$commentId] = 1;
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
            } // if fullcomment is not in db yet

                SpotDebug::msg(SpotDebug::DEBUG, 'foreach-loop: iter-stop');
        } // foreach
        SpotDebug::msg(SpotDebug::DEBUG, 'foreach-loop: done');

        if (count($hdrList) > 0) {
            $this->displayStatus('loopcount', count($hdrList));
        } else {
            $this->displayStatus('loopcount', 0);
        } // else
        $this->displayStatus('timer', round(microtime(true) - $timer, 2));

        /*
         * Add the comments to the database and update the last article
         * number found
         */
        $fullComments = [];
        while ($fullComment = array_shift($fullCommentDbList)) {
            $fullComments = array_merge($fullComments, $fullComment);
        } // while

        $this->_commentDao->addComments($commentDbList, $fullComments);

        // update the maximum article id
        if (!empty($lastProcessedId) && ($lastProcessedArtNr > 0)) {
            $this->_usenetStateDao->setMaxArticleId(Dao_UsenetState::State_Comments, $lastProcessedArtNr, $lastProcessedId);
        } // if

        /*
         * Recalculate the average spotrating and update the amount
         * of unverified comments
         */
        $this->_spotDao->updateSpotRating($spotMsgIdRatingList);
        $this->_spotDao->updateSpotCommentCount($spotMsgIdList);

        return ['count' => count($hdrList), 'headercount' => count($hdrList), 'lastmsgid' => $lastProcessedId];
    }

    // process()

    /*
     * returns the name of the group we are expected to retrieve messages from
     */
    public function getGroupName()
    {
        return ['text' => $this->_settings->get('comment_group'),
            'bin'      => '', ];
    }

    // getGroupName

    /*
     * Last retrieved articlenumber as stored in the database
     */
    public function getLastArticleNumber()
    {
        return $this->_usenetStateDao->getLastArticleNumber(Dao_UsenetState::State_Comments);
    }

    // getLastArticleNumber

    /*
     * Last retrieved messageid as stored in the database
     */
    public function getLastMessageId()
    {
        return $this->_usenetStateDao->getLastMessageId(Dao_UsenetState::State_Comments);
    }

    // getLastMessageId

    /**
     * Returns a list of messageid's where we can search for.
     *
     * @return array
     */
    public function getRecentRetrievedMessageIdList()
    {
        return $this->_spotDao->getMaxMessageId('comments');
    }

    // getRecentRetrievedMessageIdList
} // Services_Retriever_Comments
