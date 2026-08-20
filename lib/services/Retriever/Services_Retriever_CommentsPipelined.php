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
    private $_recovery;
    private $_sink;
    private $_parser;
    private $_statusTarget;
    private $_textServer;
    private $_msgdata;
    private $_pipelineWindow = Services_Nntp_PipelinedTransport::DEFAULT_PIPELINE_WINDOW;

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
        $this->_recovery = new Services_Nntp_PipelinedRecovery($transport);
        $this->_sink = $sink;
        $this->_parser = new Services_Retriever_CommentsArticleParser();
        $this->_statusTarget = $statusTarget;
        $this->_textServer = $settings->get('nntp_hdr');
        $this->_pipelineWindow = $this->serverPipelineDepth($this->_textServer);
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

        $items = [];
        $articleQueue = [];

        if ($this->_settings->get('retention') > 0) {
            $retentionStamp = time() - ($this->_settings->get('retention') * 24 * 60 * 60);
        } else {
            $retentionStamp = 0;
        }

        $dbIdList = $this->_sink->matchCommentMessageIds($hdrList);
        foreach ($hdrList as $msgheader) {
            SpotDebug::msg(SpotDebug::DEBUG, 'pipelined foreach-loop: iter-start');
            set_time_limit(120);

            $commentId = $msgheader['Message-ID'];
            $artNr = $msgheader['Number'];
            $header_isInDb = isset($dbIdList['comment'][$commentId]);
            $fullcomment_isInDb = isset($dbIdList['fullcomment'][$commentId]);
            $item = [
                'messageid'          => $commentId,
                'articlenr'          => $artNr,
                'comment'            => null,
                'spotref'            => null,
                'ratingspotref'      => null,
                'need_article'       => false,
                'terminal'           => true,
                'fullcomment'        => null,
                'article_status'     => null,
            ];

            if (!$header_isInDb || (!$fullcomment_isInDb && $this->_settings->get('retrieve_full_comments'))) {
                $msgIdParts = explode('.', $commentId);
                $msgheader['References'] = $msgIdParts[0].substr($commentId, strpos($commentId, '@'));
                $msgheader['stamp'] = strtotime($msgheader['Date']);
                $msgheader['Subject'] = mb_convert_encoding($msgheader['Subject'], 'ASCII', 'ASCII');

                if (($retentionStamp > 0) && ($msgheader['stamp'] < $retentionStamp) && ($this->_settings->get('retentiontype') == 'everything')) {
                    $items[] = $item;
                    continue;
                }

                if ($msgheader['stamp'] < $this->_settings->get('retrieve_newer_than')) {
                    $items[] = $item;
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
                    $item['comment'] = [
                        'messageid' => $commentId,
                        'nntpref'   => $msgheader['References'],
                        'stamp'     => $msgheader['stamp'],
                        'rating'    => $msgheader['rating'],
                    ];

                    $dbIdList['comment'][$commentId] = 1;
                    $item['spotref'] = $msgheader['References'];
                    if ($msgheader['rating'] >= 1 && $msgheader['rating'] <= 10) {
                        $item['ratingspotref'] = $msgheader['References'];
                    }

                    $header_isInDb = true;
                }
            }

            if ($header_isInDb && (!$fullcomment_isInDb)) {
                if (($retentionStamp > 0) && (strtotime($msgheader['Date']) < $retentionStamp)) {
                    $items[] = $item;
                    continue;
                }

                if ($this->_settings->get('retrieve_full_comments')) {
                    $item['need_article'] = true;
                    $item['terminal'] = false;
                    $articleQueue[$commentId] = $commentId;
                }
            }

            $items[] = $item;
            SpotDebug::msg(SpotDebug::DEBUG, 'pipelined foreach-loop: iter-stop');
        }

        $outcome = new Services_Nntp_PipelinedFetchOutcome();
        if (!empty($articleQueue)) {
            $outcome = $this->_recovery->fetchArticles(array_values($articleQueue), $this->_pipelineWindow);
            $this->applyArticleOutcome($items, $outcome);
        }

        if (count($hdrList) > 0) {
            $this->displayStatus('loopcount', count($hdrList));
        } else {
            $this->displayStatus('loopcount', 0);
        }
        $this->displayStatus('timer', round(microtime(true) - $timer, 2));

        $commit = $this->buildContiguousCommit($items);

        $this->_sink->commitBatch(
            $commit['comments'],
            $commit['fullcomments'],
            $commit['spotrefs'],
            $commit['ratingspotrefs'],
            $commit['last_articlenr'],
            $commit['last_messageid']
        );

        if ($outcome->hasUnresolved()) {
            $this->displayStatus('pipelineddeferred', json_encode($outcome->toArray()));
            throw new PipelinedCommentsDeferredException($outcome);
        }

        return ['count' => count($hdrList), 'headercount' => count($hdrList), 'lastmsgid' => $commit['last_messageid']];
    }

    private function applyArticleOutcome(array &$items, Services_Nntp_PipelinedFetchOutcome $outcome)
    {
        $results = [];
        foreach ($outcome->terminalResults() as $result) {
            $results[$result->messageId] = $result;
        }

        $unresolved = array_fill_keys($outcome->unresolvedMessageIds(), true);

        foreach ($items as &$item) {
            if (!$item['need_article']) {
                continue;
            }

            if (isset($unresolved[$item['messageid']])) {
                $item['terminal'] = false;
                continue;
            }

            if (!isset($results[$item['messageid']])) {
                $item['terminal'] = false;
                continue;
            }

            $result = $results[$item['messageid']];
            $item['terminal'] = true;
            $item['article_status'] = ['code' => $result->code, 'message' => $result->message];
            $this->_sink->recordArticleStatus($result->messageId, $result->code, $result->message);

            if (!$result->found()) {
                continue;
            }

            try {
                $item['fullcomment'] = $this->_parser->parse($result->messageId, $result->article());
            } catch (Exception $x) {
                $item['article_status'] = ['code' => 0, 'message' => 'malformed comment payload'];
                $this->_sink->recordArticleStatus($result->messageId, 0, 'malformed comment payload');
            }
        }
    }

    private function buildContiguousCommit(array $items)
    {
        $comments = [];
        $fullComments = [];
        $spotMsgIdList = [];
        $spotMsgIdRatingList = [];
        $lastProcessedId = '';
        $lastProcessedArtNr = 0;

        foreach ($items as $item) {
            if (!$item['terminal']) {
                break;
            }

            if ($item['comment'] !== null) {
                $comments[] = $item['comment'];
            }
            if ($item['fullcomment'] !== null) {
                $fullComments[] = $item['fullcomment'];
            }
            if ($item['spotref'] !== null) {
                $spotMsgIdList[$item['spotref']] = 1;
            }
            if ($item['ratingspotref'] !== null) {
                $spotMsgIdRatingList[$item['ratingspotref']] = 1;
            }
            $lastProcessedId = $item['messageid'];
            $lastProcessedArtNr = $item['articlenr'];
        }

        return [
            'comments'       => $comments,
            'fullcomments'   => $fullComments,
            'spotrefs'       => $spotMsgIdList,
            'ratingspotrefs' => $spotMsgIdRatingList,
            'last_articlenr' => $lastProcessedArtNr,
            'last_messageid' => $lastProcessedId,
        ];
    }

    private function serverPipelineDepth(array $server)
    {
        if (!isset($server['article_pipeline_depth'])) {
            return Services_Nntp_PipelinedTransport::DEFAULT_PIPELINE_WINDOW;
        }

        return Services_Nntp_PipelineDepth::serverValue($server);
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
