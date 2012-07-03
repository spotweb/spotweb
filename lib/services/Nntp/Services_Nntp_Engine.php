<?php

class Services_Nntp_Engine {
		private $_server;
		private $_user;
		private $_pass;
		private $_serverenc;
		private $_serverport;
		
		private $_error;
		private $_nntp;
		private $_connected;
		private $_currentgroup;

		/*
		 * Instantiate a new Service NNTP engine object
		 */
		function __construct(array $server) { 
			$error = '';
			
			$this->_connected = false;
			$this->_server = $server['host'];
			$this->_serverenc = $server['enc'];
			$this->_serverport = $server['port'];
			$this->_user = $server['user'];
			$this->_pass = $server['pass'];

			$this->_nntp = new Net_NNTP_Client();
		} # ctor

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
			return $this->_nntp->getOverview($first . '-' . $last);
		} # getOverview()

		/*
		 * Get a list of messageid's within a range, same as XOVER
		 * but only for messageids
		 */
		public function getMessageIdList($first, $last) {
			$this->connect();
			$hdrList = $this->_nntp->getHeaderField('Message-ID', ($first . '-' . $last));

			return $hdrList;
		} # getMessageIdList()
		
		/*
		 * Disconnect from the server if we are connected
		 */
		public function quit() {
			if (!$this->_connected) {
				return ;
			} # if
			
			try {
				$this->_nntp->quit();
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

			return $this->_nntp->getHeader($msgid);
		} # getHeader()

		/*
		 * Returns the body of an messageid
		 */
		public function getBody($msgid) {
			$this->connect();

			return $this->_nntp->getBody($msgid);
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
			
			# Fetch the article
			$art = $this->_nntp->getArticle($msgId);
			
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
			 * We need to select a group, because authenticatin
			 * is not always entered but sometimes required
			 */
			$this->selectGroup('free.pt');
			
			$this->quit();
		} # validateServer
	
} # Services_Nntp_Engine
