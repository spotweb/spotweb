<?php
require_once "settings.php";
require_once "db.php";
require_once "SpotParser.php";
require_once "SpotNntp.php";

class SpotRetriever {
		private $_server;
		private $_error;
		private $_spotnntp;
		private $_msgdata;
		
		function __construct($server) {
			$this->_server = $server;
		} # ctor
		
		function getError() {
			return $this->_error;
		} # getError()
		
		function connect($group) {
			$this->_spotnntp = new SpotNntp($this->_server['host'],
									 $this->_server['enc'],
									 $this->_server['port'],
									 $this->_server['user'],
									 $this->_server['pass']);
			if ($this->_spotnntp->connect()) {
				$this->_msgdata = $this->_spotnntp->selectGroup($group);
				if ($this->_msgdata === false) {
					$this->_error = "Error getting group: " . $this->_spotnntp->getError();
					return false;
				} # if
			} else {
				$this->_error = $this->_spotnntp->getError();
				return false;
			} # else
			
			return $this->_msgdata;
		} # connect
		
		
		function loopTillEnd($curMsg, $callback) {
			echo "Appr. Message count: " . ($this->_msgdata['last'] - $this->_msgdata['first']) . "\r\n";
			echo "First message number:" . $this->_msgdata['first'] . "\r\n";
			echo "Last message number: " . $this->_msgdata['last'] . "\r\n";
			echo "Current message:     " . $curMsg . "\r\n";
			echo "\r\n";

			# make sure we handle articlenumber wrap arounds
			if ($curMsg < $this->_msgdata['first']) {
				$curMsg = $this->_msgdata['first'];
			} # if

			$increment = 1000;
			while ($curMsg < $this->_msgdata['last']) {
				# Show some status message
				echo "Retrieving:          " . ($curMsg) . " till " . ($curMsg + $increment) ;

				# get the list of headers (XOVER)
				$hdrList = $this->_spotnntp->getOverview($curMsg, ($curMsg + $increment));
				if ($hdrList === false) {
					echo "\r\n\r\nError retrieving message list: " . $this->_spotnntp->getError() . "\r\n";
					break;
				} # if
				
				# If no spots were found, just manually increase the 
				# messagenumber with the increment to make sure we advance
				if ((count($hdrList) < 1) || ($hdrList[0]['Number'] < $curMsg)) {
					$curMsg += $increment;
				} else {
					$curMsg = ($hdrList[0]['Number'] + 1);
				} # else

				# run the callback method
				$callback($hdrList, $curMsg);				
			} # while
	
		} # loopTillEnd()

		function quit() {
			$this->_spotnntp->quit();
		} # quit()
		
} # class SpotRetriever