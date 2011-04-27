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
		function __construct($server, SpotDb $db, SpotSettings $settings, $rsakeys, $outputType, $retrieveFull) {
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
					case 'start'			: echo "Retrieving new Spots from server " . $txt . "..." . PHP_EOL; break;
					case 'done'				: echo "Finished retrieving spots." . PHP_EOL . PHP_EOL; break;
					case 'dbcount'			: echo "Spots in database:	" . $txt . "" . PHP_EOL; break;
					case 'groupmessagecount': echo "Appr. Message count: 	" . $txt . "" . PHP_EOL; break;
					case 'firstmsg'			: echo "First message number:	" . $txt . "" . PHP_EOL; break;
					case 'lastmsg'			: echo "Last message number:	" . $txt . "" . PHP_EOL; break;
					case 'curmsg'			: echo "Current message:	" . $txt . "" . PHP_EOL; break;
					case 'progress'			: echo "Retrieving " . $txt; break;
					case 'hdrparsed'		: echo " (parsed " . $txt . " headers, "; break;
					case 'fullretrieved'	: echo $txt . " full, "; break;
					case 'verified'			: echo "verified " . $txt . ", "; break;
					case 'modcount'			: echo "moderated " . $txt . ", "; break;
					case 'skipcount'		: echo "skipped " . $txt . " of "; break;
					case 'loopcount'		: echo $txt . " total messages)" . PHP_EOL; break;
					case 'totalprocessed'	: echo "Processed a total of " . $txt . " spots" . PHP_EOL; break;
					case 'searchmsgid'		: echo "Looking for articlenumber for messageid" . PHP_EOL; break;
					case ''					: echo PHP_EOL; break;
					
					default					: echo $cat . $txt;
				} # switch
			} else {
			
				switch($cat) {
					case 'start'			: echo "<spots>"; break;
					case 'done'				: echo "</spots>"; break;
					case 'dbcount'			: echo "<dbcount>" . $txt . "</dbcount>"; break;
					case 'totalprocessed'	: echo "<totalprocessed>" . $txt . "</totalprocessed>"; break;
					case 'skipcount'		: echo "<totalskipped> " . $txt . "</totalskipped>"; break;
					case 'totalremoved'		: echo "<totalremoved>" . $txt . "</totalremoved>"; break;
					default					: break;
				} # switch
			} # else xmloutput
		} # displayStatus
		
		/*
		 * Wis alle spots welke in de database zitten met een hoger id dan dat wij
		 * opgehaald hebben.
		 */
		function updateLastRetrieved($highestMessageId) {
			$this->_db->removeExtraSpots($highestMessageId);
		} # updateLastRetrieved
		
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
			$lastProcessedId = '';

			# pak onze lijst met messageid's, en kijk welke er al in de database zitten
			$dbIdList = $this->_db->matchSpotMessageIds($hdrList);
#var_dump($hdrList);

			# en loop door elke header heen
			$spotParser = new SpotParser();
			
			foreach($hdrList as $msgid => $msgheader) {
				# Reset timelimit
				set_time_limit(120);

				# messageid to check
				$msgId = substr($msgheader['Message-ID'], 1, -1);
				
				# definieer een paar booleans zodat we niet steeds een array lookup moeten doen
				# en de code wat duidelijker is
				$header_isInDb = isset($dbIdList['spot'][$msgId]);
				$fullspot_isInDb = isset($dbIdList['fullspot'][$msgId]);

				# als we de spot overview nog niet in de database hebben, haal hem dan op, 
				# ook als de fullspot er nog niet is, moeten we dit doen want een aantal velden
				# die wel in de header zitten, zitten niet in de database (denk aan 'keyid')
				if ((!$header_isInDb) || (!$fullspot_isInDb)) {
					$hdrsRetrieved++;
					$spot = $spotParser->parseXover($msgheader['Subject'], 
													$msgheader['From'], 
													$msgheader['Date'],
													$msgheader['Message-ID'],
													$this->_rsakeys);

													
					# als er een parse error was, negeren we de spot volledig, ook niet-
					# verified spots gooien we weg.
					if (($spot === false) || (!$spot['verified'])){
						continue;
					} # if

					if ($spot['keyid'] == 2) {
						$commandAr = explode(' ', strtolower($spot['title']));
						$validCommands = array('delete', 'dispose', 'remove');
						
						# FIXME: Message-ID kan ook van een comment zijn,
						# onderstaande code gaat uit van een spot.

						# is dit een geldig commando?
						if (in_array($commandAr[0], $validCommands) !== false) {
							switch($this->_settings->get('spot_moderation')) {
								case 'disable'	: break;
								case 'markspot'	: $this->_db->markSpotModerated($commandAr[1]); break;
								default			: $this->_db->deleteSpot($commandAr[1]); break;
							} # switch
							
							$modCount++;
						} # if
						
					} else {
						# Oudere spots niet toevoegen, hoeven we het later ook niet te verwijderen
						if ($this->_settings->get('retention') > 0 && $spot['stamp'] < time()-($this->_settings->get('retention') * 24 * 60 * 60)) {
							$skipCount++;
						} elseif ($spot['stamp'] < $this->_settings->get('retrieve_newer_than')) { 
							$skipCount++;
						} else {
							# Hier kijken we alleen of de spotheader niet bestaat
							if (!$header_isInDb) {
								$this->_db->addSpot($spot);

								# definieer de header als al ontvangen, we moeten ook de 
								# msgid lijst updaten omdat soms een messageid meerdere 
								# keren per xover mee komt ...
								$dbIdList['spot'][$msgId] = 1;
								$header_isInDb = true;
								$lastProcessedId = $msgId;

								if ($spot['wassigned']) {
									$signedCount++;
								} # if
							} # if 
						} # if
					
					} # else
				} else {
					$lastProcessedId = $msgId;
				} # else

				# We willen enkel de volledige spot ophalen als de header in de database zit, omdat 
				# we dat hierboven eventueel doen, is het enkel daarop checken voldoende
				if (($header_isInDb)  &&		# header moet in db zitten
					(!$fullspot_isInDb)) 		# maar de fullspot niet
				   {
					#
					# We gebruiken altijd XOVER, dit is namelijk handig omdat eventueel ontbrekende
					# artikel nummers (en soms zijn dat er duizenden) niet hoeven op te vragen, nu
					# vragen we enkel de de headers op van de artikelen die er daadwerkelijk zijn
					#
					# KeyID 2 is een 'moderator' post en kan dus niet getrieved worden
					#
					if (($this->_retrieveFull) && ($spot['keyid'] != 2)) {
						$fullSpot = array();
						try {
							$fullsRetrieved++;
							$fullSpot = $this->_spotnntp->getFullSpot($msgId);
					
							# en voeg hem aan de database toe
							$this->_db->addFullSpot($fullSpot);
							$fullspot_isInDb = true;
							# we moeten ook de msgid lijst updaten omdat soms een messageid meerdere 
							# keren per xover mee komt ...
							$dbIdList['fullspot'][$msgId] = 1;
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

			return array('count' => count($hdrList), 'lastmsgid' => $lastProcessedId);
		} # process()
	
} # class SpotRetriever_Spots
