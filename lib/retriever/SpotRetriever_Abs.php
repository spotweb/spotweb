<?php
abstract class SpotRetriever_Abs {
		protected $_server;
		protected $_spotnntp;
		protected $_db;
		protected $_settings;
		protected $_debug;
		protected $_retro;
		
		private $_msgdata;

		/*
		 * Returns the status in either xml or text format 
		 */
		abstract function displayStatus($cat, $txt);
		
		/*
		 * Actual processing of the headers
		 */
		abstract function process($hdrList, $curMsg, $increment, $timer);
		
		/*
		 * Remove any extraneous reports from the database because we assume
		 * the highest messgeid in the database is the latest on the server.
		 */
		abstract function updateLastRetrieved($highestMessageId);
		
		/*
		 * returns the name of the group we are expected to retrieve messages from
		 */
		abstract function getGroupName();
		
		/*
		 * Highest articleid for the implementation in the database
		 */
		abstract function getMaxArticleId();
		
		/*
		 * Returns the highest messageid in the database
		 */
		abstract function getMaxMessageId();
		
		/*
		 * default ctor 
		 */
		function __construct($server, SpotDb $db, SpotSettings $settings, $debug, $retro) {
			$this->_server = $server;
			$this->_db = $db;
			$this->_settings = $settings;
			$this->_debug = $debug;
			$this->_retro = $retro;
		} # ctor

		function debug($s) {
			if ($this->_debug) {
				echo 'DEBUG: ' . microtime(true) . ':' . $s . PHP_EOL;
			} # if
		} # debug
		
		function connect($group) {
			# if an retriever instance is already running, stop this one
			if ($this->_db->isRetrieverRunning($this->_server['host'])) {
				throw new RetrieverRunningException();
			} # if
			
			/*
			 * and notify the system we are running
			 */
			$this->_db->setRetrieverRunning($this->_server['host'], true);

			# and fireup the nntp connection
			$this->displayStatus("lastretrieve", $this->_db->getLastUpdate($this->_server['host']));
			$this->displayStatus("start", $this->_server['host']);
			$this->_spotnntp = new SpotNntp($this->_server);
			$this->_msgdata = $this->_spotnntp->selectGroup($group);
			
			return $this->_msgdata;
		} # connect
		

		/*
		 * Given a list of messageids, check if we can find the corresponding
		 * articlenumber on the NNTP server. 
		 */
		function searchMessageid($messageIdList) {
			$this->debug('searchMessageId=' . serialize($messageIdList));
			
			if (empty($messageIdList) || $this->_retro) {
				return 0;
			} # if
				
			$this->displayStatus('searchmsgid', '');
			
			$found = false;
			$decrement = 5000;
			$curMsg = $this->_msgdata['last'];

			# start searching 
			while (($curMsg >= $this->_msgdata['first']) && (!$found)) {
				# Reset timelimit
				set_time_limit(120);			
				
				$curMsg = max(($curMsg - $decrement), $this->_msgdata['first'] - 1);

				# get the list of headers (XHDR)
				$hdrList = $this->_spotnntp->getMessageIdList($curMsg - 1, ($curMsg + $decrement));
				$this->debug('getMessageIdList returned=' . serialize($hdrList));
				
				/*
				 * Reverse the list with messageids because we assume we are at a recent
				 * run and the last retrieved messageid should be on the top of the list
				 * somewhere
				 */
				$hdrList = array_reverse($hdrList, true);

				echo 'Searching from ' . ($curMsg -1) . ' to ' . ($curMsg + $decrement) . PHP_EOL;
				
 				foreach($hdrList as $msgNum => $msgId) {
					if (isset($messageIdList[$msgId])) {
						$curMsg = $msgNum;
						$found = true;
						break;
					} # if
				} # for
			} # while

			$this->debug('getMessageIdList loop finished, found = ' . $found);
			$this->debug('getMessageIdList loop finished, curMsg = ' . $curMsg);
			
			return $curMsg;
		} # searchMessageId
		
		/*
		 * Process all headers in $increment pieces and call the corresponding
		 * actual implementation
		 */
		function loopTillEnd($curMsg, $increment = 1000) {
			$processed = 0;
			$headersProcessed = 0;
			$highestMessageId = '';
			
			# make sure we handle articlenumber wrap arounds
			if ($curMsg < $this->_msgdata['first']) {
				$curMsg = $this->_msgdata['first'];
			} # if

			$this->displayStatus("groupmessagecount", ($this->_msgdata['last'] - $this->_msgdata['first']));
			$this->displayStatus("firstmsg", $this->_msgdata['first']);
			$this->displayStatus("lastmsg", $this->_msgdata['last']);
			$this->displayStatus("curmsg", $curMsg);
			$this->displayStatus("", "");

			while ($curMsg < $this->_msgdata['last']) {
				$timer = microtime(true);
				
				# get the list of headers (XOVER)
				$hdrList = $this->_spotnntp->getOverview($curMsg, ($curMsg + $increment));

				$saveCurMsg = $curMsg;
				# If no spots were found, just manually increase the
				# messagenumber with the increment to make sure we advance
				if ((count($hdrList) < 1) || ($hdrList[count($hdrList)-1]['Number'] < $curMsg)) {
					$curMsg += $increment;
				} else {
					$curMsg = ($hdrList[count($hdrList)-1]['Number'] + 1);
				} # else
				
				# run the processing method
				$processOutput = $this->process($hdrList, $saveCurMsg, $curMsg, $timer);
				$processed += $processOutput['count'];
				$headersProcessed += $processOutput['headercount'];
				$highestMessageId = $processOutput['lastmsgid'];

				# reset the start time to prevent a another retriever from starting
				# during the intial retrieve which can take many hours 
				$this->_db->setRetrieverRunning($this->_server['host'], true);
			} # while
			
			# we are done updating, make sure that if the newsserver deleted 
			# earlier retrieved messages, we remove them from our database
			if ($highestMessageId != '') {
				$this->debug('loopTillEnd() finished, highestMessageId = ' . $highestMessageId);
				$this->updateLastRetrieved($highestMessageId);
			} # if
	
			$this->displayStatus("totalprocessed", $processed);
			return $headersProcessed;
		} # loopTillEnd()

		function quit() {
			# notify the system we are not running anymore
			$this->_db->setRetrieverRunning($this->_server['host'], false);
			
			# and disconnect
			$this->_spotnntp->quit();
			$this->displayStatus("done", "");
		} # quit()
		
		function perform() {
			/*
			 * try to connect to the usenet server and select the group
			 */
			$msgdata = $this->connect($this->getGroupName());
			
			/*
			 * Ask the implementation class for the highest articleid
			 * found on the system
			 */
			$curMsg = $this->getMaxArticleId();

			/*
			 * If this usenet server is new for us, we just assume
			 * we have to start from zero. Else we do a lookup from
			 * the messageid to find the correct articlenumber.
			 *
			 * We cannot just use the articlenumber because the NNTP
			 * spec allows a server to renumber of course.
			 */
			if (($curMsg != 0) && (!$this->_retro)) {
				$curMsg = $this->searchMessageId($this->getMaxMessageId());
				
				if ($this->_server['buggy']) {
					$curMsg = max(1, $curMsg - 15000);
				} # if
			} # if

			/*
			 * and actually start looping till we retrieved all headers or articles
			 */
			$newProcessedCount = $this->loopTillEnd($curMsg, $this->_settings->get('retrieve_increment'));
			
			/* 
			 * and cleanup
			 */
			$this->quit();
			$this->_db->setLastUpdate($this->_server['host']);
			
			return $newProcessedCount;
		} # perform
		
} # class SpotRetriever
