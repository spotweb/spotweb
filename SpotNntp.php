<?php
require_once "Net/NNTP/Client.php";

class SpotNntp {
		private $_server;
		private $_user;
		private $_pass;
		private $_serverenc;
		private $_serverport;
		
		private $_error;
		private $_nntp;
		
		function __construct($server, $serverenc, $serverport, $user, $pass) {
			$error = '';
			
			$this->_server = $server;
			$this->_serverenc = $serverenc;
			$this->_serverport = $serverport;
			$this->_user = $user;
			$this->_pass = $pass;
			
			# Set pear error handling to be used by exceptions
			PEAR::setErrorHandling(PEAR_ERROR_EXCEPTION);			
			$this->_nntp = new Net_NNTP_Client();
		} # ctor
		
		function selectGroup($group) {
			return $this->_nntp->selectGroup($group);
		} # selectGroup()
		
		function getOverview($first, $last) {
			$hdrList = $this->_nntp->getOverview($first . '-' . $last);
			$hdrList = array_reverse($hdrList);
			
			return $hdrList;
		} # getOverview()
		
		function quit() {
			try {
				$this->_nntp->quit();
			} catch(Exception $x) {
				// dummy, we dont care about exceptions during quitting time
			}
		} # quit()
		
		function getHeader($msgid) {
			return $this->_nntp->getHeader($msgid);
		} # getHeader()

		function getBody($msgid) {
			return $this->_nntp->getBody($msgid);
		} # getBody	()
		
		function connect() {
			$ret = $this->_nntp->connect($this->_server, $this->_serverenc, $this->_serverport);
			if (!empty($this->_user)) {
				$authed = $this->_nntp->authenticate($this->_user, $this->_pass);
			} # if
		} # connect()
} # class SpotNntp