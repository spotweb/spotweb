<?php
abstract class SpotRetriever_Abs {
		protected $_server;
		protected $_spotnntp;
		
		private $_msgdata;

		/*
		 * Geef de status weer in category/text formaat. Beide zijn vrij te bepalen
		 */
		abstract function displayStatus($cat, $txt);
		
		/*
		 * De daadwerkelijke processing van de headers
		 */
		abstract function process($hdrList, $curMsg, $increment);
		
		
		/*
		 * NNTP Server waar geconnet moet worden
		 */
		function __construct($server) {
			$this->_server = $server;
		} # ctor
		
		function connect($group) {
			$this->displayStatus("start", "");
			$this->_spotnntp = new SpotNntp($this->_server['host'],
									 $this->_server['enc'],
									 $this->_server['port'],
									 $this->_server['user'],
									 $this->_server['pass']);
			$this->_spotnntp->connect();
			$this->_msgdata = $this->_spotnntp->selectGroup($group);
			
			return $this->_msgdata;
		} # connect
		

		/*
		 * Haal de headers op en zorg dat ze steeds verwerkt worden
		 */
		function loopTillEnd($curMsg, $increment = 1000) {
			$processed = 0;
			
			$this->displayStatus("groupmessagecount", ($this->_msgdata['last'] - $this->_msgdata['first']));
			$this->displayStatus("firstmsg", $this->_msgdata['first']);
			$this->displayStatus("lastmsg", $this->_msgdata['last']);
			$this->displayStatus("curmsg", $curMsg);
			$this->displayStatus("", "");

			# make sure we handle articlenumber wrap arounds
			if ($curMsg < $this->_msgdata['first']) {
				$curMsg = $this->_msgdata['first'];
			} # if

			while ($curMsg < $this->_msgdata['last']) {
				# get the list of headers (XOVER)
				$hdrList = $this->_spotnntp->getOverview($curMsg, ($curMsg + $increment));
				
				# If no spots were found, just manually increase the
				# messagenumber with the increment to make sure we advance
				if ((count($hdrList) < 1) || ($hdrList[0]['Number'] < $curMsg)) {
					$curMsg += $increment;
				} else {
					$curMsg = ($hdrList[0]['Number'] + 1);
				} # else

				# run the processing method
				$processed += $this->process($hdrList, $curMsg, $increment);
			} # while
	
			$this->displayStatus("totalprocessed", $processed);
		} # loopTillEnd()

		function quit() {
			$this->_spotnntp->quit();
			$this->displayStatus("done", "");
		} # quit()
} # class SpotRetriever