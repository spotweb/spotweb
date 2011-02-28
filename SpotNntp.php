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
		
		function getError() {
			return $this->_error;
		} # getError()
		
		function selectGroup($group) {
			$msgInfo = false;
			try {
				$msgInfo = $this->_nntp->selectGroup($group);
			} 
			catch (Exception $x) {
				$this->_error = $x->getMessage();
				return false;
			} # catch
			
			return $msgInfo;
		} # selectGroup()
		
		function getOverview($first, $last) {
			$hdrList = false;
			try {
				$hdrList = $this->_nntp->getOverview($first . '-' . $last);
				$hdrList = array_reverse($hdrList);
			}
			catch(Exception $x) {
				$this->_error = $x->getMessage();
				return false;
			} # catch
			
			return $hdrList;
		} # getOverview()
		
		function quit() {
			try {
				$this->_nntp->quit();
			} 
			catch(Exception $x) {
				$this->_error = $x->getMessage();
				return false;
			} # catch
			
			return true;
		} # quit()
		
		function getHeader($msgid) {
			try {
				return $this->_nntp->getHeader($msgid);
			}
			catch(Exception $x) {
				$this->_error = $x->getMessage();
				return false;
			} # catch
		} # getHeader()

		function getBody($msgid) {
			try {
				return $this->_nntp->getBody($msgid);
			}
			catch(Exception $x) {
				$this->_error = $x->getMessage();
				return false;
			} # catch
		} # getBody	()
		
		function connect() {
			try {
				$ret = $this->_nntp->connect($this->_server, $this->_serverenc, $this->_serverport);
				if (!empty($this->_user)) {
					$authed = $this->_nntp->authenticate($this->_user, $this->_pass);
				} # if
			}
			catch(Exception $x) {
				$this->_error = $x->getMessage();
				return false;
			} # catch

			return true;
		} # connect()
} # class SpotNntp