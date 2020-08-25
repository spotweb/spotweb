<?php

class Services_Nntp_Engine
{
    private $_server;
    private $_user;
    private $_pass;
    private $_serverenc;
    private $_verifyname;
    private $_serverport;

    /**
     * Registers how many errors there have been for this connection.
     *
     * @var float
     */
    private $_connectionErrors = 0;

    /**
     * Actual Net_NNTP's connection class.
     *
     * @var Net_NNTP_Client
     */
    private $_nntp;
    private $_connected;
    private $_currentgroup;

    /*
     * Instantiate a new Service NNTP engine object
     */
    public function __construct(array $server)
    {
        $this->_connected = false;
        $this->_server = $server['host'];
        $this->_serverenc = $server['enc'];
        if (isset($server['verifyname'])) {
            $this->_verifyname = $server['verifyname'];
        } else {
            $this->_verifyname = true;
        }
        $this->_serverport = $server['port'];
        $this->_user = $server['user'];
        $this->_pass = $server['pass'];
        $this->_connectionErrors = 0;

        $this->_nntp = new Net_NNTP_Client();
    }

    // ctor

    /**
     * Register an operation as failed.
     */
    private function registerError($exception)
    {
        SpotDebug::msg(SpotDebug::TRACE, __CLASS__.'->registerError() called: '.$exception->getMessage());
        /*
         * Some exceptions are "final" and will not recover when
         * just tried again.
         *
         * Error code 430 - 'No such article' - is one of them.
         *
         * If these happen, immediatly increase to the highest possible
         * errors
         */
        if ($exception->getCode() == 430) {
            SpotDebug::msg(SpotDebug::TRACE, __CLASS__.'->registerError() was: article not found');

            $this->_connectionErrors = 9999;

            return;
        } // if

        $this->_connectionErrors++;

        /*
         * Disconnect from the server, reconnect and
         * sleep in between
         */
        $this->quit();
        $this->_nntp = new Net_NNTP_Client();
        sleep($this->_connectionErrors);

        SpotDebug::msg(SpotDebug::TRACE, __CLASS__.'->registerError() errorCount is now: '.$this->_connectionErrors);

        // reconnect by selecting the group again
        if (!empty($this->_currentgroup)) {
            $this->selectGroup($this->_currentgroup);
        } // if
    }

    // registerError

    /**
     * Register a try of a command.
     */
    private function registerTryCommand()
    {
        /*
         * We decrease with 0.10 points, so that each 10 sucessful commands
         * give us another retry.
         */
        $this->_connectionErrors = max(0, $this->_connectionErrors - 0.10);
    }

    // registerTryCommand

    /**
     * Sometimes we want to be able to reset the errorcount
     * from outside, mostly in the case of error 430 (No Such Article),
     * as this error is handled by an upper layer.
     */
    public function resetErrorCount()
    {
        SpotDebug::msg(SpotDebug::TRACE, __CLASS__.'->resetErrorCount()');

        $this->_connectionErrors = 0;
    }

    // resetErrorCount

    /**
     * Returns true when this connection has had too many errors.
     *
     * @return bool
     */
    private function tooManyErrors()
    {
        SpotDebug::msg(SpotDebug::TRACE, __CLASS__.'->tooManyErrors() == '.$this->_connectionErrors);

        return $this->_connectionErrors > 3;
    }

    // tooManyErrors

    /**
     * /*
     * Select a group as active group.
     */
    public function selectGroup($group)
    {
        SpotDebug::msg(SpotDebug::TRACE, __CLASS__.'->selectGroup('.$group.')');
        $this->connect();

        $this->_currentgroup = $group;

        return $this->_nntp->selectGroup($this->_currentgroup);
    }

    // selectGroup()

    /*
     * Returns an overview (XOVER) from first id to lastid
     */
    public function getOverview($first, $last)
    {
        SpotDebug::msg(SpotDebug::TRACE, __CLASS__.'->getOverView('.$first.','.$last.')');
        $this->connect();

        try {
            $this->registerTryCommand();

            $headerList = $this->_nntp->getOverview($first.'-'.$last);

            /*
             * Remove the <> around the messageid
             */
            foreach ($headerList as $k => $v) {
                $headerList[$k]['Message-ID'] = substr($headerList[$k]['Message-ID'], 1, -1);
                $headerList[$k]['References'] = substr($headerList[$k]['References'], 1, -1);
            } // foreach

            return $headerList;
        } catch (Exception $x) {
            $this->registerError($x);

            /**
             * Try this operation again, but make sure we are not overloading
             * the NNTP server with useless requests.
             */
            if ($this->tooManyErrors()) {
                throw $x;
            } else {
                return $this->getOverview($first, $last);
            } // else
        } // catch
    }

    // getOverview()

    /*
     * Get a single messageid given a single articlenumber
     */
    public function getMessageIdByArticleNumber($artNr)
    {
        SpotDebug::msg(SpotDebug::TRACE, __CLASS__.'->getMessageIdByArticleNumber('.$artNr.')');
        $this->connect();

        try {
            $this->registerTryCommand();

            return substr($this->_nntp->getHeaderField('Message-ID', $artNr), 1, -1);
        } catch (Exception $x) {
            $this->registerError($x);

            /**
             * Try this operation again, but make sure we are not overloading
             * the NNTP server with useless requests.
             */
            if ($this->tooManyErrors()) {
                throw $x;
            } else {
                return $this->getMessageIdByArticleNumber($artNr);
            } // else
        } // catch
    }

    // getMessageIdByArticleNumber

    /*
     * Get a list of messageid's within a range, same as XOVER
     * but only for messageids
     */
    public function getMessageIdList($first, $last)
    {
        SpotDebug::msg(SpotDebug::TRACE, __CLASS__.'->getMessageIdList('.$first.','.$last.')');
        $this->connect();

        try {
            $this->registerTryCommand();

            return $this->_nntp->getHeaderField('Message-ID', ($first.'-'.$last));
        } catch (Exception $x) {
            $this->registerError($x);

            /**
             * Try this operation again, but make sure we are not overloading
             * the NNTP server with useless requests.
             */
            if ($this->tooManyErrors()) {
                throw $x;
            } else {
                return $this->getMessageIdList($first, $last);
            } // else
        } // catch
    }

    // getMessageIdList()

    /*
     * Disconnect from the server if we are connected
     */
    public function quit()
    {
        SpotDebug::msg(SpotDebug::TRACE, __CLASS__.'->quit()');

        if (!$this->_connected) {
            return;
        } // if

        try {
            $this->_nntp->disconnect();
        } catch (Exception $x) {
            // dummy, we dont care about exceptions during quitting time
        } // catch

        $this->_connected = false;
    }

    // quit()

    /*
     * Sends a no-operation to the usenet server to keep the
     * connection alive
     */
    public function sendNoop()
    {
        SpotDebug::msg(SpotDebug::TRACE, __CLASS__.'->sendNoop()');

        if (!$this->_connected) {
            return;
        } // if

        /* The NNTP protocol has no proper noop command, this will do fine */
        if (!empty($this->_currentgroup)) {
            $this->selectGroup($this->_currentgroup);
        } // if
    }

    // sendnoop()

    /*
     * Post an article to the server, $article should be an 2-element
     * array with head and body as elements
     */
    public function post($article)
    {
        SpotDebug::msg(SpotDebug::TRACE, __CLASS__.'->post() -> '.serialize($article));
        $this->connect();
        /*
         * We cannot run post() directly because it would
         * trigger the autoloader
         */
        $tmpError = $this->_nntp->cmdPost();
        if ($tmpError) {
            return $this->_nntp->cmdPost2($article);
        } else {
            return $tmpError;
        } // else
    }

    // post()

    /*
     * Returns the header of an messageid
     */
    public function getHeader($msgid)
    {
        SpotDebug::msg(SpotDebug::TRACE, __CLASS__.'->getHeader('.$msgid.')');
        $this->connect();

        try {
            $this->registerTryCommand();

            return $this->_nntp->getHeader($msgid);
        } catch (Exception $x) {
            $this->registerError($x);

            echo PHP_EOL.'getHeader(): Failed to retrieve article: '.$msgid.PHP_EOL;

            /**
             * Try this operation again, but make sure we are not overloading
             * the NNTP server with useless requests.
             */
            if ($this->tooManyErrors()) {
                throw $x;
            } else {
                return $this->getHeader($msgid);
            } // else
        } // catch
    }

    // getHeader()

    /*
     * Returns the body of an messageid
     */
    public function getBody($msgid)
    {
        SpotDebug::msg(SpotDebug::TRACE, __CLASS__.'->getBody('.$msgid.')');
        $this->connect();

        try {
            $this->registerTryCommand();

            return $this->_nntp->getBody($msgid);
        } catch (Exception $x) {
            $this->registerError($x);

            //echo PHP_EOL.'getBody(): Failed to retrieve article: '.$msgid.PHP_EOL;

            /**
             * Try this operation again, but make sure we are not overloading
             * the NNTP server with useless requests.
             */
            if ($this->tooManyErrors()) {
                throw $x;
            } else {
                return $this->getBody($msgid);
            } // else
        } // catch
    }

    // getBody	()

    /*
     * Connect to the newsserver and authenticate
     * if necessary
     */
    public function connect()
    {
        SpotDebug::msg(SpotDebug::TRACE, __CLASS__.'->connect()');
        /*
         * Store the username and password in it, we will not put it in member variables
         * because they might show up in a stack trace.
         *
         * We keep an array of tmpUser and tmpPass because when different servers are
         * used for diffferent types of retrieval we need to be able to store seperate passwords
         */
        static $tmpUser;
        static $tmpPass;

        // dummy operation
        if ($this->_connected) {
            return;
        } // if

        // if an empty hostname is provided, abort
        if (empty($this->_server)) {
            throw new NntpException('Servername is empty', -1);
        }  // if

        // if a portnumber is empty, abort
        if ((!is_numeric($this->_serverport)) || ($this->_serverport < 1)) {
            throw new NntpException('A server portnumber has to be entered', -1);
        }  // if

        // if the type of SSL is invalid, abort
        if (($this->_serverenc !== false) && (strtolower($this->_serverenc) !== 'ssl') && (strtolower($this->_serverenc) !== 'tls')) {
            throw new NntpException('Invalid encryption method specified ('.$this->_serverenc.')', -1);
        }  // if

        $this->_connected = true;

        /*
         * Erase username/password so it won't show up in any stacktrace
         *
         * Because this class can be reused (e - reconnected) without
         * reconstructing it, we cannot simple
         */
        if (($this->_user !== '*FILTERED*') && ($this->_pass !== '*FILTERED*')) {
            $tmpUser[$this->_server] = $this->_user;
            $tmpPass[$this->_server] = $this->_pass;

            $this->_user = '*FILTERED*';
            $this->_pass = '*FILTERED*';
        } // if

        try {
            $ret = $this->_nntp->connect($this->_server, $this->_serverenc, $this->_serverport, 10, $this->_verifyname);
            if ($ret === false) {
                throw new NntpException('Error while connecting to server (server did not respond)', -1);
            } // if

            SpotDebug::msg(SpotDebug::TRACE, __CLASS__.'->connect called, trying to authenticate?: ', ['user' => $tmpUser[$this->_server], 'pass' => $tmpPass[$this->_server]]);

            if (!empty($tmpUser[$this->_server])) {
                $authed = $this->_nntp->authenticate($tmpUser[$this->_server], $tmpPass[$this->_server]);
            } // if
        } catch (Exception $x) {
            throw new NntpException($x->getMessage(), $x->getCode());
        }
    }

    // connect()

    /*
     * Returns a full article divided between an
     * header and body part
     */
    public function getArticle($msgId)
    {
        SpotDebug::msg(SpotDebug::TRACE, __CLASS__.'->getArticle('.$msgId.')');
        $this->connect();

        $result = ['header' => [], 'body' => []];

        try {
            $this->registerTryCommand();

            // Fetch the article
            $art = $this->_nntp->getArticle($msgId);
        } catch (Exception $x) {
            $this->registerError($x);

            echo PHP_EOL.'getArticle(): Failed to retrieve article: '.$msgId.PHP_EOL;
            /**
             * Try this operation again, but make sure we are not overloading
             * the NNTP server with useless requests.
             */
            if ($this->tooManyErrors()) {
                throw $x;
            } else {
                return $this->getArticle($msgId);
            } // else
        } // catch

        /*
         * Now we will split it in both a body and an array, this
         * way it is much easier to work with
         */
        $i = 0;
        $lnCount = count($art);
        while (($i < $lnCount) && ($art[$i] != '')) {
            $result['header'][] = $art[$i];
            $i++;
        } // while
        $i++;

        while ($i < $lnCount) {
            $result['body'][] = $art[$i];
            $i++;
        } // while

        return $result;
    }

    // getArticle

    /*
     * validates wether can succesfully connect to the usenet
     * server
     */
    public function validateServer()
    {
        SpotDebug::msg(SpotDebug::TRACE, __CLASS__.'->validateServer()');
        /*
         * We need to select a group, because authentication
         * is not always entered but sometimes required
         */
        $this->selectGroup('free.pt');

        $this->quit();
    }

    // validateServer
} // Services_Nntp_Engine
