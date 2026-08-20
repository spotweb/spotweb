<?php

class Services_Retriever_CommentsPipelined
{
    private $_settings;
    private $_force;
    private $_retro;
    private $_spotDao;
    private $_commentDao;
    private $_usenetStateDao;
    private $_transport;
    private $_sink;
    private $_parser;
    private $_statusTarget;
    private $_textServer;
    private $_msgdata;
    private $_pipelineWindow = Services_Nntp_PipelinedTransport::DEFAULT_PIPELINE_WINDOW;
    private $_fallbackWarned = false;

    public function __construct(
        Dao_Factory $daoFactory,
        Services_Settings_Container $settings,
        $force,
        $retro,
        Services_Nntp_PipelinedTransport $transport,
        Services_Retriever_CommentsSink $sink,
        $statusTarget
    ) {
        $this->_settings = $settings;
        $this->_force = $force;
        $this->_retro = $retro;
        $this->_spotDao = $daoFactory->getSpotDao();
        $this->_commentDao = $daoFactory->getCommentDao();
        $this->_usenetStateDao = $daoFactory->getUsenetStateDao();
        $this->_transport = $transport;
        $this->_sink = $sink;
        $this->_parser = new Services_Retriever_CommentsArticleParser();
        $this->_statusTarget = $statusTarget;
        $this->_textServer = $settings->get('nntp_hdr');
    }

    public function perform()
    {
        SpotTiming::start(__CLASS__.'::'.__FUNCTION__);

        $this->connect();

        if ($this->_retro) {
            $curArtNr = 0;
        } else {
            $curArtNr = $this->getLastArticleNumber();
        }

        if ($curArtNr != 0) {
            $curArtNr = $this->searchMessageId(
                $this->getLastArticleNumber(),
                $this->getLastMessageId(),
                $this->getRecentRetrievedMessageIdList()
            );

            if ($this->_textServer['buggy']) {
                $curArtNr = max(1, $curArtNr - 15000);
            }
        }

        SpotTiming::start(__CLASS__.'::loopTillEnd()');
        $newProcessedCount = $this->loopTillEnd($curArtNr, $this->_settings->get('retrieve_increment'));
        SpotTiming::stop(__CLASS__.'::loopTillEnd()');

        $this->quit();
        $this->_usenetStateDao->setLastUpdate(Dao_UsenetState::State_Spots);

        SpotTiming::stop(__CLASS__.'::'.__FUNCTION__);

        return $newProcessedCount;
    }

    public function connect()
    {
        if (!Services_Signing_Base::factory() instanceof Services_Signing_Openssl) {
            $this->displayStatus('slowphprsa', '');
        }
        $this->displayStatus('lastretrieve', $this->_usenetStateDao->getLastUpdate(Dao_UsenetState::State_Spots));
        $this->displayStatus('start', $this->_textServer['host']);

        $this->_msgdata = $this->_transport->selectGroup($this->_settings->get('comment_group'));

        return $this->_msgdata;
    }

    public function searchMessageId($lastArticleNr, $lastMessageId, $messageIdList)
    {
        SpotDebug::msg(SpotDebug::TRACE, 'pipelined searchMessageId='.serialize($messageIdList));

        if (empty($messageIdList)) {
            return 0;
        }

        if ($this->_transport->getMessageIdByArticleNumber($lastArticleNr) == $lastMessageId) {
            return $lastArticleNr;
        }

        $this->displayStatus('searchmsgid', '');

        $found = false;
        $decrement = 5000;
        $curArtNr = $this->_msgdata['last'];

        while (($curArtNr >= $this->_msgdata['first']) && (!$found)) {
            set_time_limit(120);

            $curArtNr = max($curArtNr - $decrement, $this->_msgdata['first'] - 1);
            $hdrList = $this->_transport->getMessageIdList($curArtNr - 1, $curArtNr + $decrement);

            $this->displayStatus('searchmsgidstatus', ($curArtNr - 1).' to '.($curArtNr + $decrement));

            $hdrList = array_reverse($hdrList, true);
            foreach ($hdrList as $msgNum => $msgId) {
                if (isset($messageIdList[$msgId])) {
                    $curArtNr = $msgNum;
                    $found = true;
                    break;
                }
            }
        }

        SpotDebug::msg(SpotDebug::DEBUG, 'pipelined getMessageIdList loop finished, found = '.$found);
        SpotDebug::msg(SpotDebug::DEBUG, 'pipelined getMessageIdList loop finished, curArtNr = '.$curArtNr);

        return $curArtNr;
    }

    public function loopTillEnd($curArticleNr, $increment = 1000)
    {
        $processed = 0;
        $headersProcessed = 0;
        $highestMessageId = '';

        if ($curArticleNr < $this->_msgdata['first']) {
            $curArticleNr = $this->_msgdata['first'];
        }

        $this->displayStatus('groupmessagecount', $this->_msgdata['last'] - $this->_msgdata['first']);
        $this->displayStatus('firstmsg', $this->_msgdata['first']);
        $this->displayStatus('lastmsg', $this->_msgdata['last']);
        $this->displayStatus('curartnr', $curArticleNr);
        $this->displayStatus('', '');

        SpotTiming::start(__CLASS__.'::'.__FUNCTION__.':whileLoop');
        while ($curArticleNr < $this->_msgdata['last']) {
            $timer = microtime(true);

            SpotTiming::start(__CLASS__.'::'.__FUNCTION__.':getOverview');
            $hdrList = $this->_transport->getOverview($curArticleNr, $curArticleNr + $increment);
            SpotTiming::stop(__CLASS__.'::'.__FUNCTION__.':getOverview');

            $saveCurArtNr = $curArticleNr;
            if ((count($hdrList) < 1) || ($hdrList[count($hdrList) - 1]['Number'] < $curArticleNr)) {
                $curArticleNr += $increment;
            } else {
                $curArticleNr = ($hdrList[count($hdrList) - 1]['Number'] + 1);
            }

            SpotTiming::start(__CLASS__.'::'.__FUNCTION__.':callProcess');
            $processOutput = $this->process($hdrList, $saveCurArtNr, $curArticleNr, $timer);
            SpotTiming::stop(__CLASS__.'::'.__FUNCTION__.':callProcess');

            $processed += $processOutput['count'];
            $headersProcessed += $processOutput['headercount'];
            $highestMessageId = $processOutput['lastmsgid'];

            if (($processed > 3000) && SpotTiming::isEnabled()) {
                break;
            }
        }
        SpotTiming::stop(__CLASS__.'::'.__FUNCTION__.':whileLoop');

        if ($highestMessageId != '') {
            SpotDebug::msg(SpotDebug::DEBUG, 'pipelined loopTillEnd() finished, highestMessageId = '.$highestMessageId);
            $this->removeTooNewRecords($highestMessageId);
        }

        $this->displayStatus('totalprocessed', $processed);

        return $headersProcessed;
    }

    public function process($hdrList, $curArtNr, $increment, $timer)
    {
        $this->displayStatus('progress', $curArtNr.' till '.$increment);

        $lastProcessedId = '';
        $lastProcessedArtNr = 0;
        $commentDbList = [];
        $fullCommentDbList = [];
        $articleQueue = [];

        if ($this->_settings->get('retention') > 0) {
            $retentionStamp = time() - ($this->_settings->get('retention') * 24 * 60 * 60);
        } else {
            $retentionStamp = 0;
        }

        $dbIdList = $this->_sink->matchCommentMessageIds($hdrList);
        $spotMsgIdList = [];
        $spotMsgIdRatingList = [];

        foreach ($hdrList as $msgheader) {
            SpotDebug::msg(SpotDebug::DEBUG, 'pipelined foreach-loop: iter-start');
            set_time_limit(120);

            $commentId = $msgheader['Message-ID'];
            $artNr = $msgheader['Number'];
            $header_isInDb = isset($dbIdList['comment'][$commentId]);
            $fullcomment_isInDb = isset($dbIdList['fullcomment'][$commentId]);

            if (!$header_isInDb || (!$fullcomment_isInDb && $this->_settings->get('retrieve_full_comments'))) {
                $msgIdParts = explode('.', $commentId);
                $msgheader['References'] = $msgIdParts[0].substr($commentId, strpos($commentId, '@'));
                $msgheader['stamp'] = strtotime($msgheader['Date']);
                $msgheader['Subject'] = mb_convert_encoding($msgheader['Subject'], 'ASCII', 'ASCII');

                if (($retentionStamp > 0) && ($msgheader['stamp'] < $retentionStamp) && ($this->_settings->get('retentiontype') == 'everything')) {
                    continue;
                }

                if ($msgheader['stamp'] < $this->_settings->get('retrieve_newer_than')) {
                    continue;
                }

                if (count($msgIdParts) == 5) {
                    $msgheader['rating'] = (int) $msgIdParts[1];
                    if (!is_numeric($msgIdParts[1])) {
                        $msgheader['rating'] = 0;
                    }
                } else {
                    $msgheader['rating'] = 0;
                }

                if (!$header_isInDb) {
                    $commentDbList[] = [
                        'messageid' => $commentId,
                        'nntpref'   => $msgheader['References'],
                        'stamp'     => $msgheader['stamp'],
                        'rating'    => $msgheader['rating'],
                    ];

                    $dbIdList['comment'][$commentId] = 1;
                    $spotMsgIdList[$msgheader['References']] = 1;
                    if ($msgheader['rating'] >= 1 && $msgheader['rating'] <= 10) {
                        $spotMsgIdRatingList[$msgheader['References']] = 1;
                    }

                    $header_isInDb = true;
                    $lastProcessedId = $commentId;
                    $lastProcessedArtNr = $artNr;
                }
            } else {
                $lastProcessedId = $commentId;
                $lastProcessedArtNr = $artNr;
            }

            if ($header_isInDb && (!$fullcomment_isInDb)) {
                if (($retentionStamp > 0) && (strtotime($msgheader['Date']) < $retentionStamp)) {
                    continue;
                }

                if ($this->_settings->get('retrieve_full_comments')) {
                    $articleQueue[$commentId] = $commentId;
                }
            }

            SpotDebug::msg(SpotDebug::DEBUG, 'pipelined foreach-loop: iter-stop');
        }

        if (!empty($articleQueue)) {
            $fullCommentDbList = $this->readFullComments(array_values($articleQueue));
        }

        if (count($hdrList) > 0) {
            $this->displayStatus('loopcount', count($hdrList));
        } else {
            $this->displayStatus('loopcount', 0);
        }
        $this->displayStatus('timer', round(microtime(true) - $timer, 2));

        $this->_sink->commitBatch(
            $commentDbList,
            $fullCommentDbList,
            $spotMsgIdList,
            $spotMsgIdRatingList,
            $lastProcessedArtNr,
            $lastProcessedId
        );

        return ['count' => count($hdrList), 'headercount' => count($hdrList), 'lastmsgid' => $lastProcessedId];
    }

    private function readFullComments(array $messageIds)
    {
        try {
            $results = $this->_transport->fetchArticlesPipelined($messageIds, $this->_pipelineWindow);
        } catch (Exception $x) {
            if ($this->_pipelineWindow > 1) {
                $this->_pipelineWindow = 1;
                if (!$this->_fallbackWarned) {
                    $this->displayStatus('pipelinedfallback', 'Pipelined ARTICLE failed; retrying comments retrieval with window 1 for this run.');
                    $this->_fallbackWarned = true;
                }
                $this->_transport->withFreshConnection();
                $results = $this->_transport->fetchArticlesPipelined($messageIds, $this->_pipelineWindow);
            } else {
                throw $x;
            }
        }

        $comments = [];
        foreach ($results as $result) {
            $this->_sink->recordArticleStatus($result->messageId, $result->code, $result->message);
            if (!$result->found()) {
                continue;
            }

            try {
                $comments[] = $this->_parser->parse($result->messageId, $result->article());
            } catch (Exception $x) {
                /*
                 * Keep legacy behaviour from Services_Nntp_SpotReading::readComments():
                 * malformed/invalid individual comments are ignored, not fatal.
                 */
            }
        }

        return $comments;
    }

    public function removeTooNewRecords($highestMessageId)
    {
        if (!$this->_textServer['buggy']) {
            $this->_commentDao->removeExtraComments($highestMessageId);
        }
    }

    public function quit()
    {
        $this->_transport->quit();
        $this->displayStatus('done', '');
    }

    public function getLastArticleNumber()
    {
        return $this->_usenetStateDao->getLastArticleNumber(Dao_UsenetState::State_Comments);
    }

    public function getLastMessageId()
    {
        return $this->_usenetStateDao->getLastMessageId(Dao_UsenetState::State_Comments);
    }

    public function getRecentRetrievedMessageIdList()
    {
        return $this->_spotDao->getMaxMessageId('comments');
    }

    private function displayStatus($cat, $txt)
    {
        $this->_statusTarget->displayStatus($cat, $txt);
    }
}
