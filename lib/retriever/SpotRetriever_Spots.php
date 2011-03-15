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
		function __construct($server, $db, $settings, $rsakeys, $outputType, $retrieveFull) {
			parent::__construct($server, $db, $settings);
			
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
					case 'hdrparsed'		: echo " (parsed " . $txt . " headers, "; break;
					case 'fullretrieved'	: echo $txt . " full, "; break;
					case 'verified'			: echo "verified " . $txt . ", "; break;
					case 'modcount'			: echo "moderated " . $txt . " of "; break;
					case 'skipcount'		: echo "skipped " . $txt . " of "; break;
					case 'loopcount'		: echo $txt . " total messages)\r\n"; break;
					case 'totalprocessed'	: echo "Processed a total of " . $txt . " spots\r\n"; break;
					case 'searchmsgid'		: echo "Looking for articlenumber for messageid\r\n"; break;
					case ''					: echo "\r\n"; break;
					
					default					: echo $cat . $txt;
				} # switch
			} else {
			
				switch($cat) {
					case 'start'			: echo "<spots>"; break;
					case 'done'				: echo "</spots>"; break;
					case 'dbcount'			: echo "<dbcount>" . $txt . "</dbcount>"; break;
					case 'totalprocessed'	: echo "<totalprocessed>" . $txt . "</totalprocessed>"; break;
					case 'totalremoved'		: echo "<totalremoved>" . $txt . "</totalremoved>"; break;
					default					: break;
				} # switch
			} # else xmloutput
		} # displayStatus
		
		/*
		 * De daadwerkelijke processing van de headers
		 */
		function process($hdrList, $curMsg, $endMsg) {
			$this->displayStatus("progress", ($curMsg) . " till " . ($endMsg));
		
			$this->_db->beginTransaction();
			$signedCount = 0;
			$hdrsRetrieved = 0;
			$fullsRetrieved = 0;
			$modCount = 0;
			$skipCount = 0;
			
			# pak onze lijst met messageid's, en kijk welke er al in de database zitten
			$t = microtime(true);
			$dbIdList = $this->_db->matchMessageIds($hdrList);
			
			# en loop door elke header heen
			foreach($hdrList as $msgid => $msgheader) {
				# Reset timelimit
				set_time_limit(120);

				# messageid to check
				$msgId = substr($msgheader['Message-ID'], 1, -1);

				# als we de spot overview nog niet in de database hebben, haal hem dan op
				if (!in_array($msgId, $dbIdList['spot'])) {
					$hdrsRetrieved++;
					
					$spotParser = new SpotParser();
					$spot = $spotParser->parseXover($msgheader['Subject'], 
													$msgheader['From'], 
													$msgheader['Message-ID'],
													$this->_rsakeys);
													
					if ($spot['Verified']) {
						if ($spot['KeyID'] == 2) {
							$commandAr = explode(' ', strtolower($spot['Title']));
							$validCommands = array('delete', 'dispose', 'remove');
							
							# is dit een geldig commando?
							if (array_search($commandAr[0], $validCommands) !== false) {
								switch($this->_settings['spot_moderation']) {
									case 'disable'	: break;
									case 'markspot'	: $this->_db->markSpotModerated($commandAr[1]);
									default			: $this->_db->deleteSpot($commandAr[1]);
								} # switch
								
								$modCount++;
							} # if
							
						} else {
							// Oudere spots niet toevoegen, hoeven we het later ook niet te verwijderen
							if ($this->_settings['retention'] > 0 && $spot['Stamp'] < time()-($this->_settings['retention'] * 24 * 60 * 60)) {
								$skipCount++;
							} else {
								$this->_db->addSpot($spot);
								$dbIdList['spot'][] = $msgId;
							
								if ($spot['WasSigned']) {
									$signedCount++;
								} # if
							} # if
						} # else
					} # if
				} else {
					# anders halen we hem uit de database want we hebben die nodig
					$spot = $this->_db->getFullSpot($msgId);
				} # else

				# We willen enkel de volledige spot ophalen als de header in de database zit, omdat 
				# we dat hierboven eventueel doen, is het enkel daarop checken voldoende
				if ((in_array($msgId, $dbIdList['spot'])) &&   # header moet in db zitten
				   (!in_array($msgId, $dbIdList['fullspot']))) # maar de fullspot niet
				   {
					#
					# We gebruiken altijd XOVER, dit is namelijk handig omdat eventueel ontbrekende
					# artikel nummers (en soms zijn dat er duizenden) niet hoeven op te vragen, nu
					# vragen we enkel de de headers op van de artikelen die er daadwerkelijk zijn
					#
					# KeyID 2 is een 'moderator' post en kan dus niet getrieved worden
					#
					if (($this->_retrieveFull) && ($spot['KeyID'] != 2)) {
						$fullSpot = array();
						try {
							$fullsRetrieved++;
							$fullSpot = $this->_spotnntp->getFullSpot(substr($msgheader['Message-ID'], 1, -1));
					
							# en voeg hem aan de database toe
							$this->_db->addFullSpot($fullSpot);
						} 
						catch(ParseSpotXmlException $x) {
							; # swallow error
						} 
						catch(Exception $x) {
							# messed up index aan de kant van de server ofzo? iig, dit gebeurt. soms, if so,
							# swallow the error
							if ($x->getMessage() == 'No such article found') {
								;
							} 
							# als de XML niet te parsen is, niets aan te doen
							elseif ($x->getMessage() == 'String could not be parsed as XML') {
								;
							} else {
								throw $x;
							} # else
						} # catch
						
					} # if retrievefull
				} # if fullspot is not in db yet
			} # foreach

			if (count($hdrList) > 0) {
				$this->displayStatus("hdrparsed", $hdrsRetrieved);
				$this->displayStatus("fullretrieved", $fullsRetrieved);
				$this->displayStatus("verified", $signedCount);
				$this->displayStatus("modcount", $modCount);
				$this->displayStatus("skipcount", $skipCount);
				$this->displayStatus("loopcount", count($hdrList));
			} else {
				$this->displayStatus("hdrparsed", 0);
				$this->displayStatus("fullretrieved", 0);
				$this->displayStatus("verified", 0);
				$this->displayStatus("modcount", 0);
				$this->displayStatus("skipcount", 0);
				$this->displayStatus("loopcount", 0);
			} # else

			$this->_db->setMaxArticleid($this->_server['host'], $curMsg);
			$this->_db->commitTransaction();				
			
			return count($hdrList);
		} # process()
	
} # class SpotRetriever_Spots