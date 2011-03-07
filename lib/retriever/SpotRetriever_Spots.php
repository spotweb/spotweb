<?php
require_once "SpotRetriever_Abs.php";

class SpotRetriever_Spots extends SpotRetriever_Abs {
		private $_rsakeys;
		private $_outputType;
		private $_retrieveFull;

		/**
		 * server - de server waar naar geconnect moet worden
		 * db - database object
		 * rsakeys = array van rsa keys
		 */
		function __construct($server, $db, $rsakeys, $outputType, $retrieveFull) {
			parent::__construct($server, $db);
			
			$this->_rsakeys = $rsakeys;
			$this->_outputType = $outputType;
			$this->_retrieveFull = $retrieveFull;
		} # ctor


		/*
		 * Geef de status weer in category/text formaat. Beide zijn vrij te bepalen
		 */
		function displayStatus($cat, $txt) {
			if ($this->_outputType != 'xml') {
				switch($cat) {
					case 'start'			: echo "Retrieving new Spots from server...\r\n"; break;
					case 'done'				: echo "Finished retrieving spots.\r\n\r\n"; break;
					case 'dbcount'			: echo "Spots in database:	" . $txt . "\r\n"; break;
					case 'groupmessagecount': echo "Appr. Message count: 	" . $txt . "\r\n"; break;
					case 'firstmsg'			: echo "First message number:	" . $txt . "\r\n"; break;
					case 'lastmsg'			: echo "Last message number:	" . $txt . "\r\n"; break;
					case 'curmsg'			: echo "Current message:	" . $txt . "\r\n"; break;
					case 'progress'			: echo "Retrieving " . $txt; break;
					case 'verified'			: echo " (verified " . $txt . ", of "; break;
					case 'loopcount'		: echo $txt . " spots)\r\n"; break;
					case 'totalprocessed'	: echo "Processed a total of " . $txt . " spots\r\n"; break;
					case ''					: echo "\r\n"; break;
					
					default					: echo $cat . $txt;
				} # switch
			} else {
			
				switch($cat) {
					case 'start'			: echo "<spots>"; break;
					case 'done'				: echo "</spots>"; break;
					case 'dbcount'			: echo "<dbcount>" . $txt . "</dbcount>"; break;
					case 'totalprocessed'	: echo "<totalprocessed>" . $txt . "</totalprocessed>"; break;
					default					: break;
				} # switch
			} # else xmloutput
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
					#
					# We gebruiken altijd XOVER, dit is namelijk handig omdat eventueel ontbrekende
					# artikel nummers (en soms zijn dat er duizenden) niet hoeven op te vragen, nu
					# vragen we enkel de de headers op van de artikelen die er daadwerkelijk zijn
					#
					if ($this->_retrieveFull) {
						try {
							$fullSpot = $this->_spotnntp->getFullSpot(substr($msgheader['Message-ID'], 1, -1));
						} 
						catch(ParseSportXmlException $x) {
							$fullSpot = array();
						} 
						catch(Exception $x) {
							# messed up index aan de kant van de server ofzo? iig, dit gebeurt. soms, if so,
							# swallow the error
							if ($x->getMessage() == 'No such article found') {
								;
							} else {
								throw $x;
							} # else
						} # catch
					} else {
						$fullSpot = array();
					} # if
				
					# en voeg hem aan de database toe
					$this->_db->addSpot($spot, $fullSpot);
				} # if
				
				if ($spot['Verified']) {
					if ($spot['WasSigned']) {
						$signedCount++;
					} # if
				} # if
			} # foreach

			if (count($hdrList) > 0) {
				$this->displayStatus("verified", $signedCount);
				$this->displayStatus("loopcount", count($hdrList));
			} else {
				$this->displayStatus("verified", 0);
				$this->displayStatus("loopcount", 0);
			} # else

			$this->_db->setMaxArticleid($this->_server['host'], $curMsg);
			$this->_db->commitTransaction();				
			
			return count($hdrList);
		} # process()
	
} # class SpotRetriever_Spots