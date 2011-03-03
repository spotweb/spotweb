<?php
require_once "SpotRetriever_Abs.php";

class SpotRetriever_Spots extends SpotRetriever_Abs {
		private $_db;
		private $_rsakeys;

		/**
		 * server - de server waar naar geconnect moet worden
		 * db - database object
		 * rsakeys = array van rsa keys
		 */
		function __construct($server, $db, $rsakeys) {
			parent::__construct($server);
			
			$this->_db = $db;
			$this->_rsakeys = $rsakeys;
		} # ctor


		/*
		 * Geef de status weer in category/text formaat. Beide zijn vrij te bepalen
		 */
		function displayStatus($cat, $txt) {
			switch($cat) {
				case 'groupmessagecount': echo "Appr. Message count: 	" . $txt . "\r\n"; break;
				case 'firstmsg'			: echo "First message number:	" . $txt . "\r\n"; break;
				case 'lastmsg'			: echo "Last message number:	" . $txt . "\r\n"; break;
				case 'curmsg'			: echo "Current message:	" . $txt . "\r\n"; break;
				case 'progress'			: echo "Retrieving " . $txt; break;
				case 'verified'			: echo " (verified " . $txt . ", of "; break;
				case 'totalmessages'	: echo $txt . " spots)\r\n"; break;
				case ''					: echo "\r\n"; break;
				
				default					: echo $cat . $txt;
			} # switch
		} # displayStatus
		
		/*
		 * De daadwerkelijke processing van de headers
		 */
		function process($hdrList, $curMsg, $increment) {
			$this->displayStatus("progress", ($curMsg) . " till " . ($curMsg + $increment));
		
			$this->_db->beginTransaction();
			$signedCount = 0;
			foreach($hdrList as $msgid => $msgheader) {
				# Reset timelimit
				set_time_limit(120);			
				
				$spotParser = new SpotParser();
				$spot = $spotParser->parseXover($msgheader['Subject'], 
												$msgheader['From'], 
												$msgheader['Message-ID'],
												$this->_rsakeys);
												
				if (($spot != null) && ($spot['Verified'])) {
					$this->_db->addSpot($spot);
				} # if
				
				if ($spot['Verified']) {
					if ($spot['WasSigned']) {
						$signedCount++;
					} # if
				} # if
			} # foreach

			if (count($hdrList) > 0) {
				$this->displayStatus("verified", $signedCount);
				$this->displayStatus("totalmessages", count($hdrList));
			} else {
				$this->displayStatus("verified", 0);
				$this->displayStatus("totalmessages", 0);
			} # else

			$this->_db->setMaxArticleid($this->_server['host'], $curMsg);
			$this->_db->commitTransaction();				
		} # process()
	
} # class SpotRetriever_Spots