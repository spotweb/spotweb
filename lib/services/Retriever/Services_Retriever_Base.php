<?php

abstract class Services_Retriever_Base
{
    protected $_settings;
    protected $_force;
    protected $_retro;

    /**
     * @var Dao_Base_UsenetState
     */
    protected $_usenetStateDao;

    /**
     * @var Services_Nntp_Engine
     */
    protected $_svcNntpText = null;

    /**
     * @var Services_Nntp_Engine
     */
    protected $_svcNntpBin = null;

    protected $_textServer;
    protected $_binServer;

    private $_msgdata;

    /*
     * Returns the status in either xml or text format
     */
    abstract public function displayStatus($cat, $txt);

    /*
     * Actual processing of the headers
     */
    abstract public function process($hdrList, $curArtNr, $increment, $timer);

    /*
     * Remove any extraneous reports from the database because we assume
     * the highest messgeid in the database is the latest on the server.
     */
    abstract public function removeTooNewRecords($highestMessageId);

    /*
     * returns the name of the group we are expected to retrieve messages from
     */
    abstract public function getGroupName();

    /*
     * Highest articleid for the implementation in the database
     */
    abstract public function getLastArticleNumber();

    /*
     * Returns the highest messageid in the database (in Dao_UsenetState)
     */
    abstract public function getLastMessageId();

    /*
     * Returns the last messageid's retrieved lately
     */
    abstract public function getRecentRetrievedMessageIdList();

    /*
     * default ctor
     */
    public function __construct(Dao_Factory $daoFactory, Services_Settings_Container $settings, $force, $retro)
    {
        $this->_daoFactory = $daoFactory;
        $this->_settings = $settings;
        $this->_retro = $retro;
        $this->_force = $force;

        $this->_textServer = $settings->get('nntp_hdr');
        $this->_binServer = $settings->get('nntp_nzb');

        /*
         * Create the specific DAO objects
         */
        $this->_usenetStateDao = $daoFactory->getUsenetStateDao();
        $this->_usenetStateDao->initialize();
        /*
         * Create the service objects for both the NNTP binary group and the
         * textnews group. We only create a basic NNTP_Engine object, but we
         * don't create any higher level objects
         */
        $this->_svcNntpText = Services_Nntp_EnginePool::pool($this->_settings, 'hdr');
        $this->_svcNntpBin = Services_Nntp_EnginePool::pool($this->_settings, 'bin');
    }

    // ctor

    public function connect(array $groupList)
    {
        // if an retriever instance is already running, stop this one
        if ((!$this->_force) && ($this->_usenetStateDao->isRetrieverRunning())) {
            throw new RetrieverRunningException();
        } // if

        /*
         * and notify the system we are running
         */
        $this->_usenetStateDao->setRetrieverRunning(true);

        // and fireup the nntp connection
        if (!Services_Signing_Base::factory() instanceof Services_Signing_Openssl) {
            $this->displayStatus('slowphprsa', '');
        } // if
        $this->displayStatus('lastretrieve', $this->_usenetStateDao->getLastUpdate(Dao_UsenetState::State_Spots));
        $this->displayStatus('start', $this->_textServer['host']);

        /*
         * Select the group. We don't need the bin selectGroup() command per se, as
         * we use articleid's there. We do however want to select it, because
         * the sendNoop() call uses a selectgroup and some usenet servers require it.
         */
        $this->_msgdata = $this->_svcNntpText->selectGroup($groupList['text']);
        if (!empty($groupList['bin'])) {
            $this->_svcNntpBin->selectGroup($groupList['bin']);
        } // if

        return $this->_msgdata;
    }

    // connect

    /*
     * Given a list of messageids, check if we can find the corresponding
     * articlenumber on the NNTP server.
     */
    public function searchMessageId($lastArticleNr, $lastMessageId, $messageIdList)
    {
        SpotDebug::msg(SpotDebug::TRACE, 'searchMessageId='.serialize($messageIdList));

        /*
         * If no messageid's are stored in the database,
         * start the retrieval from zero
         */
        if (empty($messageIdList)) {
            return 0;
        } // if

        /*
         * We now request the articlenumber from the usenet server,
         * if we get the same messageid back, we assume all is well and
         * we can just continue where we left off.
         */
        if ($this->_svcNntpText->getMessageIdByArticleNumber($lastArticleNr) == $lastMessageId) {
            return $lastArticleNr;
        } // if

        $this->displayStatus('searchmsgid', '');

        $found = false;
        $decrement = 5000;
        $curArtNr = $this->_msgdata['last'];

        // start searching
        while (($curArtNr >= $this->_msgdata['first']) && (!$found)) {
            // Reset timelimit
            set_time_limit(120);

            $curArtNr = max(($curArtNr - $decrement), $this->_msgdata['first'] - 1);

            // get the list of headers (XHDR) from the usenet server
            $hdrList = $this->_svcNntpText->getMessageIdList($curArtNr - 1, ($curArtNr + $decrement));
            SpotDebug::msg(SpotDebug::TRACE, 'getMessageIdList returned='.serialize($hdrList));

            // Show what we are doing
            $this->displayStatus('searchmsgidstatus', ($curArtNr - 1).' to '.($curArtNr + $decrement));

            /*
             * Reverse the list with messageids because we assume we are at a recent
             * run and the last retrieved messageid should be on the top of the list
             * somewhere
             */
            $hdrList = array_reverse($hdrList, true);

            foreach ($hdrList as $msgNum => $msgId) {
                if (isset($messageIdList[$msgId])) {
                    $curArtNr = $msgNum;
                    $found = true;
                    break;
                } // if
            } // for
        } // while

            SpotDebug::msg(SpotDebug::DEBUG, 'getMessageIdList loop finished, found = '.$found);
        SpotDebug::msg(SpotDebug::DEBUG, 'getMessageIdList loop finished, curArtNr = '.$curArtNr);

        return $curArtNr;
    }

    // searchMessageId

    /*
     * Process all headers in $increment pieces and call the corresponding
     * actual implementation
     */
    public function loopTillEnd($curArticleNr, $increment = 1000)
    {
        $processed = 0;
        $headersProcessed = 0;
        $highestMessageId = '';

        // make sure we handle articlenumber wrap arounds
        if ($curArticleNr < $this->_msgdata['first']) {
            $curArticleNr = $this->_msgdata['first'];
        } // if

        $this->displayStatus('groupmessagecount', ($this->_msgdata['last'] - $this->_msgdata['first']));
        $this->displayStatus('firstmsg', $this->_msgdata['first']);
        $this->displayStatus('lastmsg', $this->_msgdata['last']);
        $this->displayStatus('curartnr', $curArticleNr);
        $this->displayStatus('', '');

        SpotTiming::start(__CLASS__.'::'.__FUNCTION__.':whileLoop');
        while ($curArticleNr < $this->_msgdata['last']) {
            $timer = microtime(true);

            // get the list of headers (XOVER)
            SpotTiming::start(__CLASS__.'::'.__FUNCTION__.':getOverview');
            $hdrList = $this->_svcNntpText->getOverview($curArticleNr, ($curArticleNr + $increment));
            SpotTiming::stop(__CLASS__.'::'.__FUNCTION__.':getOverview');

            $saveCurArtNr = $curArticleNr;
            // If no spots were found, just manually increase the
            // messagenumber with the increment to make sure we advance
            if ((count($hdrList) < 1) || ($hdrList[count($hdrList) - 1]['Number'] < $curArticleNr)) {
                $curArticleNr += $increment;
            } else {
                $curArticleNr = ($hdrList[count($hdrList) - 1]['Number'] + 1);
            } // else

            // run the processing method
            SpotTiming::start(__CLASS__.'::'.__FUNCTION__.':callProcess');
            $processOutput = $this->process($hdrList, $saveCurArtNr, $curArticleNr, $timer);
            SpotTiming::stop(__CLASS__.'::'.__FUNCTION__.':callProcess');
            $processed += $processOutput['count'];
            $headersProcessed += $processOutput['headercount'];
            $highestMessageId = $processOutput['lastmsgid'];

            // reset the start time to prevent a another retriever from starting
            // during the intial retrieve which can take many hours
            $this->_usenetStateDao->setRetrieverRunning(true);

            /*
             * Make sure if we run with timing on, we do not fetch too many
             * spots as that would make us run out of memory
             */
            if (($processed > 3000) && (SpotTiming::isEnabled())) {
                break;
            } // if
        } // while
            SpotTiming::stop(__CLASS__.'::'.__FUNCTION__.':whileLoop');

        // we are done updating, make sure that if the newsserver deleted
        // earlier retrieved messages, we remove them from our database
        if ($highestMessageId != '') {
            SpotDebug::msg(SpotDebug::DEBUG, 'loopTillEnd() finished, highestMessageId = '.$highestMessageId);
            $this->removeTooNewRecords($highestMessageId);
        } // if

        $this->displayStatus('totalprocessed', $processed);

        return $headersProcessed;
    }

    // loopTillEnd()

    public function quit()
    {
        // notify the system we are not running anymore
        $this->_usenetStateDao->setRetrieverRunning(false);

        // and disconnect
        if (!is_null($this->_svcNntpText)) {
            $this->_svcNntpText->quit();
        } // if

        if (!is_null($this->_svcNntpBin)) {
            $this->_svcNntpBin->quit();
        } // if

        $this->displayStatus('done', '');
    }

    // quit()

    public function perform()
    {
        SpotTiming::start(__CLASS__.'::'.__FUNCTION__);

        /*
         * try to connect to the usenet server and select the group
         */
        $this->connect($this->getGroupName());

        /*
         * If the user requested a 'retro' retrieve, lets
         * just reset the messageid and articlenumber in the
         * database
         */
        if ($this->_retro) {
            $curArtNr = 0;
        } else {
            /*
             * Ask the implementation class for the highest articleid
             * found on the system
             */
            $curArtNr = $this->getLastArticleNumber();
        } // if

        /*
         * If our database is empty, we just assume
         * we have to start from zero. Else we do a lookup from
         * the messageid to find the correct articlenumber.
         *
         * We cannot just use the articlenumber because the NNTP
         * spec allows a server to renumber of course.
         */
        if ($curArtNr != 0) {
            $curArtNr = $this->searchMessageId(
                $this->getLastArticleNumber(),
                $this->getLastMessageId(),
                $this->getRecentRetrievedMessageIdList()
            );

            if ($this->_textServer['buggy']) {
                $curArtNr = max(1, $curArtNr - 15000);
            } // if
        } // if

            /*
             * and actually start looping till we retrieved all headers or articles
             */
        SpotTiming::start(__CLASS__.'::loopTillEnd()');
        $newProcessedCount = $this->loopTillEnd($curArtNr, $this->_settings->get('retrieve_increment'));
        SpotTiming::stop(__CLASS__.'::loopTillEnd()');

        /*
         * and cleanup
         */
        $this->quit();
        $this->_usenetStateDao->setLastUpdate(Dao_UsenetState::State_Spots);

        SpotTiming::stop(__CLASS__.'::'.__FUNCTION__);

        return $newProcessedCount;
    }

    // perform
} // Services_Retriever_Base
