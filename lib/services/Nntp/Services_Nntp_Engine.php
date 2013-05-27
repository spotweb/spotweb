<?php

class Services_Nntp_Engine {
    private $_server;
    private $_user;
    private $_pass;
    private $_serverenc;
    private $_serverport;

    /**
     * Registers how many errors there have been for this connection
     * @var int
     */
    private $_connectionErrors = 0;
    /**
     * Actual Net_NNTP's connection class
     *
     * @var Net_NNTP_Client
     */
    private $_nntp;
    private $_connected;
    private $_currentgroup;

    /*
     * Instantiate a new Service NNTP engine object
     */
    function __construct(array $server) {
        $this->_connected = false;
        $this->_server = $server['host'];
        $this->_serverenc = $server['enc'];
        $this->_serverport = $server['port'];
        $this->_user = $server['user'];
        $this->_pass = $server['pass'];
        $this->_connectionErrors = 0;

        $this->_nntp = new Net_NNTP_Client();
    } # ctor


    /**
     * Register an operation as failed
     */
    private function registerError($exception) {
        /*
         * Some exceptions are "final" and will not recover when
         * just tried again.
         *
         * Error code 403 - 'No such article' - is one of them.
         *
         * If these happen, immediatly increase to the highest possible
         * errors
         */
        if ($exception->getCode() == 430) {
            $this->_connectionErrors = 9999;

            return;
        } # if

        $this->_connectionErrors++;

        sleep($this->_connectionErrors);
    } # registerError

    /**
     * Returns true when this connection has had too many errors
     *
     * @return bool
     */
    private function tooManyErrors() {
        return ($this->_connectionErrors > 2);
    } # tooManyErrors

    /**
    /*
     * Select a group as active group
     */
    public function selectGroup($group) {
        $this->connect();

        $this->_currentgroup = $group;
        return $this->_nntp->selectGroup($this->_currentgroup);
    } # selectGroup()

    /*
     * Returns an overview (XOVER) from first id to lastid
     */
    public function getOverview($first, $last) {
        $this->connect();

        try {
            return $this->_nntp->getOverview($first . '-' . $last);
        } catch (Exception $x) {
            $this->registerError($x);

            /**
             * Try this operation again, but make sure we are not overloading
             * the NNTP server with useless requests
             */
            if ($this->tooManyErrors()) {
                throw $x;
            } else {
                return $this->getOverview($first, last);
            } # else
        } # catch
    } # getOverview()

    /*
     * Get a list of messageid's within a range, same as XOVER
     * but only for messageids
     */
    public function getMessageIdList($first, $last) {
        $this->connect();

        try {
            return $this->_nntp->getHeaderField('Message-ID', ($first . '-' . $last));
        } catch (Exception $x) {
            $this->registerError($x);

            /**
             * Try this operation again, but make sure we are not overloading
             * the NNTP server with useless requests
             */
            if ($this->tooManyErrors()) {
                throw $x;
            } else {
                return $this->getMessageIdList($first, $last);
            } # else
        } # catch
    } # getMessageIdList()

    /*
     * Disconnect from the server if we are connected
     */
    public function quit() {
        if (!$this->_connected) {
            return ;
        } # if

        try {
            $this->_nntp->disconnect();
            $this->_connected = false;
        }
        catch(Exception $x) {
            // dummy, we dont care about exceptions during quitting time
        } # catch
    } # quit()

    /*
     * Sends a no-operation to the usenet server to keep the
     * connection alive
     */
    public function sendNoop() {
        if (!$this->_connected) {
            return ;
        } # if

        /* The NNTP protocol has no proper noop command, this will do fine */
        if (!empty($this->_currentgroup)) {
            $this->selectGroup($this->_currentgroup);
        } # if
    } # sendnoop()

    /*
     * Post an article to the server, $article should be an 2-element
     * array with head and body as elements
     */
    public function post($article) {
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
        } # else
    } # post()

    /*
     * Returns the header of an messageid
     */
    public function getHeader($msgid) {
        $this->connect();

        try {
            return $this->_nntp->getHeader($msgid);
        } catch (Exception $x) {
            $this->registerError($x);

            /**
             * Try this operation again, but make sure we are not overloading
             * the NNTP server with useless requests
             */
            if ($this->tooManyErrors()) {
                throw $x;
            } else {
                return $this->getHeader($msgid);
            } # else
        } # catch

    } # getHeader()

    /*
     * Returns the body of an messageid
     */
    public function getBody($msgid) {
        $this->connect();

        try {
            return $this->_nntp->getBody($msgid);
        } catch (Exception $x) {
            $this->registerError($x);

            /**
             * Try this operation again, but make sure we are not overloading
             * the NNTP server with useless requests
             */
            if ($this->tooManyErrors()) {
                throw $x;
            } else {
                return $this->getBody($msgid);
            } # else
        } # catch

    } # getBody	()

    /*
     * Connect to the newsserver and authenticate
     * if necessary
     */
    public function connect() {
        /*
         * Store the username and password in it,
         * we will not put it in member variables
         * because they might show up in a stack
         * trace
         */
        static $tmpUser;
        static $tmpPass;

        # dummy operation
        if ($this->_connected) {
            return ;
        } # if

        # if an empty hostname is provided, abort
        if (empty($this->_server)) {
            throw new NntpException('Servername is empty', -1);
        }  # if

        # if a portnumber is empty, abort
        if ((!is_numeric($this->_serverport)) || ($this->_serverport < 1)) {
            throw new NntpException('A server portnumber has to be entered', -1);
        }  # if

        # if the type of SSL is invalid, abort
        if (($this->_serverenc !== false) && (strtolower($this->_serverenc) !== 'ssl') && (strtolower($this->_serverenc) !== 'tls')) {
            throw new NntpException('Invalid encryption method specified (' . $this->_serverenc . ')', -1);
        }  # if

        $this->_connected = true;

        /*
         * Erase username/password so it won't show up in any stacktrace
         *
         * Because this class can be reused (e - reconnected) without
         * reconstructing it, we cannot simple
         */
        if (($this->_user !== '*FILTERED*') && ($this->_pass !== '*FILTERED*')) {
            $tmpUser = $this->_user;
            $tmpPass = $this->_pass;

            $this->_user = '*FILTERED*';
            $this->_pass = '*FILTERED*';
        } # if

        try {
            $ret = $this->_nntp->connect($this->_server, $this->_serverenc, $this->_serverport, 10);
            if ($ret === false) {
                throw new NntpException('Error while connecting to server (server did not respond)', -1);
            } # if

            if (!empty($tmpUser)) {
                $authed = $this->_nntp->authenticate($tmpUser, $tmpPass);
            } # if

        } catch(Exception $x){
            throw new NntpException($x->getMessage(), $x->getCode());
        }
    } # connect()

    /*
     * Returns a full article divided between an
     * header and body part
     */
    public function getArticle($msgId) {
        $this->connect();

        $result = array('header' => array(), 'body' => array());


        try {
            # Fetch the article
            $art = $this->_nntp->getArticle($msgId);
        } catch (Exception $x) {
            $this->registerError($x);

            /**
             * Try this operation again, but make sure we are not overloading
             * the NNTP server with useless requests
             */
            if ($this->tooManyErrors()) {
                throw $x;
            } else {
                return $this->getArticle($msgid);
            } # else
        } # catch

        /*
         * Now we will split it in both a body and an array, this
         * way it is much easier to work with
         */
        $i = 0;
        $lnCount = count($art);
        while( ($i < $lnCount) && ($art[$i] != '')) {
            $result['header'][] = $art[$i];
            $i++;
        } # while
        $i++;

        while($i < $lnCount) {
            $result['body'][] = $art[$i];
            $i++;
        } # while

        return $result;
    } # getArticle

    /*
     * validates wether can succesfully connect to the usenet
     * server
     */
    public function validateServer() {
        /*
         * We need to select a group, because authentication
         * is not always entered but sometimes required
         */
        $this->selectGroup('free.pt');

        $this->quit();
    } # validateServer

} # Services_Nntp_Engine
