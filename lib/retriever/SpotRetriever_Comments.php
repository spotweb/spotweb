<?php
class SpotRetriever_Comments extends SpotRetriever_Abs {
		private $_outputType;
		private $_retrieveFull;

		/**
		 * server - de server waar naar geconnect moet worden
		 * db - database object
		 * rsakeys = array van rsa keys
		 */
		function __construct($server, SpotDb $db, SpotSettings $settings, $outputType, $retrieveFull, $debug, $retro) {
			parent::__construct($server, $db, $settings, $debug, $retro);
			
			$this->_outputType = $outputType;
			$this->_retrieveFull = $retrieveFull;
		} # ctor
		
		/*
		 * Geef de status weer in category/text formaat. Beide zijn vrij te bepalen
		 */
		function displayStatus($cat, $txt) {
			if ($this->_outputType != 'xml') {
				switch($cat) {
					case 'start'			: echo "Retrieving new comments from server " . $txt . "..." . PHP_EOL; break;
					case 'done'				: echo "Finished retrieving comments." . PHP_EOL . PHP_EOL; break;
					case 'groupmessagecount': echo "Appr. Message count: 	" . $txt . "" . PHP_EOL; break;
					case 'firstmsg'			: echo "First message number:	" . $txt . "" . PHP_EOL; break;
					case 'lastmsg'			: echo "Last message number:	" . $txt . "" . PHP_EOL; break;
					case 'curmsg'			: echo "Current message:	" . $txt . "" . PHP_EOL; break;
					case 'progress'			: echo "Retrieving " . $txt; break;
					case 'loopcount'		: echo ", found " . $txt . " comments"; break;
					case 'timer'			: echo " in " . $txt . " seconds" . PHP_EOL; break;
					case 'totalprocessed'	: echo "Processed a total of " . $txt . " comments" . PHP_EOL; break;
					case 'searchmsgid'		: echo "Looking for articlenumber for messageid" . PHP_EOL; break;
					case ''					: echo PHP_EOL; break;
					
					default					: echo $cat . $txt;
				} # switch
			} else {

				switch($cat) {
					case 'start'			: echo "<comments>"; break;
					case 'done'				: echo "</comments>"; break;
					case 'totalprocessed'	: echo "<totalprocessed>" . $txt . "</totalprocessed>"; break;
					default					: break;
				} # switch
			} # xml output
		} # displayStatus

		/*
		 * Wis alle spots welke in de database zitten met een hoger id dan dat wij
		 * opgehaald hebben.
		 */
		function updateLastRetrieved($highestMessageId) {
			$this->_db->removeExtraComments($highestMessageId);
		} # updateLastRetrieved
		
		/*
		 * De daadwerkelijke processing van de headers
		 */
		function process($hdrList, $curMsg, $endMsg) {
			$this->displayStatus("progress", ($curMsg) . " till " . ($endMsg));
		
			$signedCount = 0;
			$lastProcessedId = '';
			$commentDbList = array();
			$fullCommentDbList = array();
			$timer = microtime(true);

			# Bepaal onze retention stamp
			if ($this->_settings->get('retention') > 0) {
				$retentionStamp = time() - ($this->_settings->get('retention') * 24 * 60 * 60);
			} else {
				$retentionStamp = 0;
			} # else

			# pak onze lijst met messageid's, en kijk welke er al in de database zitten
			$dbIdList = $this->_db->matchCommentMessageIds($hdrList);
			
			# we houden een aparte lijst met spot messageids bij zodat we dat extracten
			# niet meer in de db laag moeten doen
			$spotMsgIdList = array();
			# en een aparte lijst met spot messageids die een rating bevatten. Zo
			# hoeven we bij comments zonder rating niet te herberekenen
			$spotMsgIdRatingList = array();
			
			# en loop door elke header heen
			foreach($hdrList as $msgid => $msgheader) {
				# Reset timelimit
				set_time_limit(120);			

				# strip de reference van de <>'s
				$commentId = substr($msgheader['Message-ID'], 1, strlen($msgheader['Message-ID']) - 2);

				# definieer een paar booleans zodat we niet steeds een array lookup moeten doen
				# en de code wat duidelijker is
				$header_isInDb = isset($dbIdList['comment'][$commentId]);
				$fullcomment_isInDb = isset($dbIdList['fullcomment'][$commentId]);

				# als we de comment nog niet in de database hebben, haal hem dan op
				if (!$header_isInDb || (!$fullcomment_isInDb && $this->_retrieveFull)) {
					# fix de references, niet alle news servers geven die goed door
					$msgIdParts = explode(".", $commentId);
					$msgheader['References'] = $msgIdParts[0] . substr($commentId, strpos($commentId, '@'));

					$commentStamp = strtotime($msgheader['Date']);
					# Oudere comments niet toevoegen
					if (($retentionStamp > 0 && $commentStamp < $retentionStamp) || $commentStamp < $this->_settings->get('retrieve_newer_than')) {
						continue;
					} # if

					# als dit een nieuw soort comment is met rating vul die dan ook op
					if (count($msgIdParts) == 5) {
						$msgheader['rating'] = (int) $msgIdParts[1];

						# Sommige oudere comments bevatten een niet-numerieke
						# string op deze positie, dus we controleren nog even
						# of het puur een getal is wat er staat.
						if (!is_numeric($msgIdParts[1])) {
							$msgheader['rating'] = 0;
						} # if
					} else {
						$msgheader['rating'] = 0;
					} # if

					# Hier kijken we alleen of de commentheader niet bestaat
					if (!$header_isInDb) {
						$commentDbList[] = array('messageid' => $commentId,
												 'nntpref' => $msgheader['References'],
												 'rating' => $msgheader['rating']);

						# we moeten ook de msgid lijst updaten omdat 
						# soms een messageid meerdere keren per xover mee komt
						$dbIdList['comment'][$commentId] = 1;
						$spotMsgIdList[] = $msgheader['References'];

						# als dit comment een rating bevat voegen we hem aan de 
						# msg lijst toe voor ratings
						if ($msgheader['rating'] >= 1 && $msgheader['rating'] <= 10) {
							$spotMsgIdRatingList[] = $msgheader['References'];
						} # if

						$header_isInDb = true;
						$lastProcessedId = $commentId;
						$didFetchHeader = true;
					} # if
				} else {
					$lastProcessedId = $commentId;
				} # else

				# We willen enkel de volledige comment ophalen als de header in de database zit, omdat 
				# we dat hierboven eventueel doen, is het enkel daarop checken voldoende
				if (($header_isInDb) &&			# header moet in db zitten
					(!$fullcomment_isInDb))		# maar de fullcomment niet
				   {

					if ($this->_retrieveFull) {
						$fullComment = array();
						try {
							$fullComment = $this->_spotnntp->getComments(array(array('messageid' => $commentId)));

							# en voeg hem aan de database toe
							$fullCommentDbList[] = $fullComment;
							$fullcomment_isInDb = true;
							# we moeten ook de msgid lijst updaten omdat soms een messageid meerdere 
							# keren per xover mee komt ...
							$dbIdList['fullcomment'][$msgid] = 1;
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
				} # if fullcomment is not in db yet
			} # foreach

			if (count($hdrList) > 0) {
				$this->displayStatus("loopcount", count($hdrList));
			} else {
				$this->displayStatus("loopcount", 0);
			} # else
			$this->displayStatus("timer", round(microtime(true) - $timer, 2));

			/* 
			 * Add the comments to the database and update the last article
			 * number found
			 */
			$fullComments = array();
			while($fullComment=array_shift($fullCommentDbList)) {
				$fullComments = array_merge($fullComments, $fullComment);
			} # while

			if ($this->_retro) {
				$this->_db->setMaxArticleid('comments_retro', $endMsg);
			} else {
				$this->_db->setMaxArticleid('comments', $endMsg);
			} # if
			$this->_db->addComments($commentDbList, $fullComments);

			# herbereken de gemiddelde spotrating, en update het 
			# aantal niet geverifieerde comments
			$this->_db->updateSpotRating($spotMsgIdRatingList);
			$this->_db->updateSpotCommentCount($spotMsgIdList);
			
			return array('count' => count($hdrList), 'headercount' => count($hdrList), 'lastmsgid' => $lastProcessedId);
		} # process()
		
} # class SpotRetriever_Comments
