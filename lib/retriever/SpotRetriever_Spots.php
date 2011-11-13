<?php
class SpotRetriever_Spots extends SpotRetriever_Abs {
		private $_rsakeys;
		private $_outputType;
		private $_retrieveFull;
		private $_prefetch_image;
		private $_prefetch_nzb;

		/**
		 * server - de server waar naar geconnect moet worden
		 * db - database object
		 * rsakeys = array van rsa keys
		 */
		function __construct($server, SpotDb $db, SpotSettings $settings, $rsakeys, $outputType, $retrieveFull, $debug, $retro) {
			parent::__construct($server, $db, $settings, $debug, $retro);
			
			$this->_rsakeys = $rsakeys;
			$this->_outputType = $outputType;
			$this->_retrieveFull = $retrieveFull;
			$this->_prefetch_image = $this->_settings->get('prefetch_image');
			$this->_prefetch_nzb = $this->_settings->get('prefetch_nzb');
			$this->_recompress_nzb = $this->_settings->get('recompress_nzb');
		} # ctor


		/*
		 * Geef de status weer in category/text formaat. Beide zijn vrij te bepalen
		 */
		function displayStatus($cat, $txt) {
			if ($this->_outputType != 'xml') {
				switch($cat) {
					case 'start'			: echo strftime("Last retrieve at %c") . PHP_EOL .  "Retrieving new Spots from server " . $txt . "..." . PHP_EOL; break;
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
					case 'loopcount'		: echo $txt . " total messages)"; break;
					case 'timer'			: echo " in " . $txt . " seconds" . PHP_EOL; break;
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
			$this->debug('Highest messageid found: ' . $highestMessageId);
			
			$this->_db->removeExtraSpots($highestMessageId);
		} # updateLastRetrieved
		
		/*
		 * De daadwerkelijke processing van de headers
		 */
		function process($hdrList, $curMsg, $endMsg) {
			$this->displayStatus("progress", ($curMsg) . " till " . ($endMsg));

			$signedCount = 0;
			$hdrsRetrieved = 0;
			$fullsRetrieved = 0;
			$modCount = 0;
			$skipCount = 0;
			$lastProcessedId = '';
			$fullSpotDbList = array();
			$spotDbList = array();
			$moderationList = array();
			$timer = microtime(true);

			# Bepaal onze retention stamp
			if ($this->_settings->get('retention') > 0) {
				$retentionStamp = time() - ($this->_settings->get('retention') * 24 * 60 * 60);
			} else {
				$retentionStamp = 0;
			} # else
			$this->debug('retentionStamp=' . $retentionStamp);
			$this->debug('hdrList=' . serialize($hdrList));
			
			# pak onze lijst met messageid's, en kijk welke er al in de database zitten
			$dbIdList = $this->_db->matchSpotMessageIds($hdrList);

			$this->debug('dbIdList=' . serialize($dbIdList));

			# en loop door elke header heen
			$spotParser = new SpotParser();

			# if we need to fetch images or nzb files, we need an spotsoverview instance
			if ($this->_retrieveFull && ($this->_prefetch_image) || ($this->_prefetch_nzb)) {
				$spotsOverview = new SpotsOverview($this->_db, $this->_settings);
				$spotsOverview->setActiveRetriever(true);
				$nntp_nzb = ($this->_settings->get('nntp_hdr') == $this->_settings->get('nntp_nzb')) ? $this->_spotnntp : new SpotNntp($this->_settings->get('nntp_nzb'));
			} # if
			
			foreach($hdrList as $msgid => $msgheader) {
				$this->debug('foreach-loop, start. msgId= ' . $msgid);

				/*
				 * We keep track whether we actually fetched this header and fullspot
				 * to add it to the database, because only then we can update the
				 * titel from the spots titel or rely on our database to fetch
				 * the fullspot
				 */
				$didFetchHeader = false;
				$didFetchFullSpot = false;
				
				# Reset timelimit
				set_time_limit(120);

				# messageid to check
				$msgId = substr($msgheader['Message-ID'], 1, -1);
				
				# definieer een paar booleans zodat we niet steeds een array lookup moeten doen
				# en de code wat duidelijker is
				$header_isInDb = isset($dbIdList['spot'][$msgId]);
				$fullspot_isInDb = isset($dbIdList['fullspot'][$msgId]);
				
				# als we de spot overview nog niet in de database hebben, haal hem dan op, 
				# ook als de fullspot er nog niet is (of we in retro modus draaien), 
				# moeten we dit doen want een aantal velden die wel in de header zitten, 
				# zitten niet in de database (denk aan 'keyid')
				if (!$header_isInDb || ((!$fullspot_isInDb || $this->_retro) && $this->_retrieveFull)) {
					$hdrsRetrieved++;
					$this->debug('foreach-loop, parsingXover, start. msgId= ' . $msgid);
					$spot = $spotParser->parseXover($msgheader['Subject'], 
													$msgheader['From'], 
													$msgheader['Date'],
													$msgheader['Message-ID'],
													$this->_rsakeys);
					$this->debug('foreach-loop, parsingXover, done. msgId= ' . $msgid);
												
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
							$moderationList[] = $commandAr[1];
							$modCount++;
						} # if
						
					} else {
						# Oudere spots niet toevoegen, hoeven we het later ook niet te verwijderen
						if ($retentionStamp > 0 && $spot['stamp'] < $retentionStamp) {
							$skipCount++;
						} elseif ($spot['stamp'] < $this->_settings->get('retrieve_newer_than')) { 
							$skipCount++;
						} else {
							# Hier kijken we alleen of de spotheader niet bestaat
							if (!$header_isInDb) {
								$spotDbList[] = $spot;

								# definieer de header als al ontvangen, we moeten ook de 
								# msgid lijst updaten omdat soms een messageid meerdere 
								# keren per xover mee komt ...
								$dbIdList['spot'][$msgId] = 1;
								$header_isInDb = true;
								$lastProcessedId = $msgId;
								$didFetchHeader = true;

								if ($spot['wassigned']) {
									$signedCount++;
								} # if
							} # if 
						} # if
					
					} # else
				} else {
					$lastProcessedId = $msgId;
				} # else

				# We willen enkel de volledige spot ophalen als de header in de database zit
				if ($header_isInDb &&			# header moet in db zitten
					!$fullspot_isInDb)	 		# maar de fullspot niet
				   {
					# We gebruiken altijd XOVER, dit is namelijk handig omdat eventueel ontbrekende
					# artikel nummers (en soms zijn dat er duizenden) niet hoeven op te vragen, nu
					# vragen we enkel de de headers op van de artikelen die er daadwerkelijk zijn
					if ($this->_retrieveFull) {
						$fullSpot = array();
						try {
							$fullsRetrieved++;
							$this->debug('foreach-loop, getFullSpot, start. msgId= ' . $msgId);
							$fullSpot = $this->_spotnntp->getFullSpot($msgId);
							$this->debug('foreach-loop, getFullSpot, done. msgId= ' . $msgId);
							
							# en voeg hem aan de database toe
							$fullSpotDbList[] = $fullSpot;
							$fullspot_isInDb = true;
							$didFetchFullSpot = true;
							
							# we moeten ook de msgid lijst updaten omdat soms een messageid meerdere 
							# keren per xover mee komt ...
							$dbIdList['fullspot'][$msgId] = 1;
							
							# Overwrite the spots' title because the fullspot contains the title in
							# UTF-8 format.
							# We also overwrite the spotterid from the spotsfull because the spotterid
							# is only in the header in more recent spots.
							if ($didFetchHeader) {
								$spotDbList[count($spotDbList) - 1]['title'] = $fullSpot['title'];
								$spotDbList[count($spotDbList) - 1]['spotterid'] = $fullSpot['spotterid'];
							} # if
						} 
						catch(ParseSpotXmlException $x) {
							; # swallow error
						} 
						catch(Exception $x) {
							# messed up index aan de kant van de server ofzo? iig, dit gebeurt. soms, if so,
							# dit is de "No such article" error
							# swallow the error
							if ($x->getCode() == 430) {
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

				if ($this->_retrieveFull && $header_isInDb && ($this->_prefetch_image || $this->_prefetch_nzb)) {
					try {
						# Als we in retro modus draaien kan het zijn dat fullSpot al in de database zat en daarom niet is opgehaald
						if (!$didFetchFullSpot) {
							$fullSpot = $this->_db->getFullSpot($msgId, SPOTWEB_ANONYMOUS_USERID);
							$fullSpot = array_merge($spotParser->parseFull($fullSpot['fullxml']), $fullSpot);
						} # if

						# image prefetchen
						if ($this->_prefetch_image) {
							# Het plaatje van spots die ouder zijn dan 30 dagen en de image op het web hebben staan prefetchen we niet
							if (is_array($fullSpot['image']) || ($fullSpot['stamp'] > (int) time()-30*24*60*60)) {
								$this->debug('foreach-loop, getImage(), start. msgId= ' . $msgId);
								$spotsOverview->getImage($fullSpot, $nntp_nzb);
								$this->debug('foreach-loop, getImage(), done. msgId= ' . $msgId);
							} # if
						} # if

						# NZB prefetchen
						if ($this->_prefetch_nzb) {
							if (!empty($fullSpot['nzb']) && $fullSpot['stamp'] > 1290578400) {
								$this->debug('foreach-loop, getNzb(), start. msgId= ' . $msgId);
								$spotsOverview->getNzb($fullSpot, $nntp_nzb, $this->_recompress_nzb);
								$this->debug('foreach-loop, getNzb(), done. msgId= ' . $msgId);
							} # if
						} # if
					}
					catch(ParseSpotXmlException $x) {
						; # swallow error
					} 
					catch(Exception $x) {
						# messed up index aan de kant van de server ofzo? iig, dit gebeurt. soms, if so,
						# dit is de "No such article" error
						# swallow the error
						if ($x->getCode() == 430) {
							;
						} 
						# als de XML niet te parsen is, niets aan te doen
						elseif ($x->getMessage() == 'String could not be parsed as XML') {
							;
						} else {
							throw $x;
						} # else
					} # catch
				} # if prefetch image and/or nzb

				$this->debug('foreach-loop, done. msgId= ' . $msgid);
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
			$this->displayStatus("timer", round(microtime(true) - $timer, 2));

			/* 
			 * Add the spots to the database and update the last article
			 * number found
			 */
			$this->_db->addSpots($spotDbList, $fullSpotDbList);
			$this->debug('added Spots, spotDbList=' . serialize($spotDbList));
			$this->debug('added Spots, fullSpotDbList=' . serialize($fullSpotDbList));

			/*
			 * Actually act on the moderation settings. We cannot process this inline
			 * because a spot can be added and moderated within the same iteration
			 */
			foreach($moderationList as $moderateId) {
				switch($this->_settings->get('spot_moderation')) {
					case 'disable'	: break;
					case 'markspot'	: $this->_db->markSpotModerated($moderateId); break;
					default			: $this->_db->deleteSpot($moderateId); break;
				} # switch
			} # foreach
			
			# update the maximum article id
			if ($this->_retro) {
				$this->_db->setMaxArticleid('spots_retro', $endMsg);
			} else {
				$this->_db->setMaxArticleid($this->_server['host'], $endMsg);
			} # if
			$this->debug('loop finished, setMaxArticleId=' . serialize($endMsg));
			
			return array('count' => count($hdrList), 'headercount' => $hdrsRetrieved, 'lastmsgid' => $lastProcessedId);
		} # process()
	
} # class SpotRetriever_Spots
