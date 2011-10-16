<?php
class SpotRetriever_Reports extends SpotRetriever_Abs {
		private $_outputType;

		/**
		 * server - de server waar naar geconnect moet worden
		 * db - database object
		 */
		function __construct($server, SpotDb $db, SpotSettings $settings, $outputType) {
			parent::__construct($server, $db, $settings);			
			
			$this->_outputType = $outputType;
		} # ctor
		
		/*
		 * Geef de status weer in category/text formaat. Beide zijn vrij te bepalen
		 */
		function displayStatus($cat, $txt) {
			if ($this->_outputType != 'xml') {
				switch($cat) {
					case 'start'			: echo "Retrieving new reports from server " . $txt . "..." . PHP_EOL; break;
					case 'done'				: echo "Finished retrieving reports." . PHP_EOL . PHP_EOL; break;
					case 'groupmessagecount': echo "Appr. Message count: 	" . $txt . "" . PHP_EOL; break;
					case 'firstmsg'			: echo "First message number:	" . $txt . "" . PHP_EOL; break;
					case 'lastmsg'			: echo "Last message number:	" . $txt . "" . PHP_EOL; break;
					case 'curmsg'			: echo "Current message:	" . $txt . "" . PHP_EOL; break;
					case 'progress'			: echo "Retrieving " . $txt; break;
					case 'loopcount'		: echo ", found " . $txt . " reports" . PHP_EOL; break;
					case 'totalprocessed'	: echo "Processed a total of " . $txt . " reports" . PHP_EOL; break;
					case 'searchmsgid'		: echo "Looking for articlenumber for messageid" . PHP_EOL; break;
					case ''					: echo PHP_EOL; break;
					
					default					: echo $cat . $txt;
				} # switch
			} else {

				switch($cat) {
					case 'start'			: echo "<reports>"; break;
					case 'done'				: echo "</reports>"; break;
					case 'totalprocessed'	: echo "<totalprocessed>" . $txt . "</totalprocessed>"; break;
					default					: break;
				} # switch
			} # xml output
		} # displayStatus

		/*
		 * Wis alle reports welke in de database zitten met een hoger id dan dat wij
		 * opgehaald hebben.
		 */
		function updateLastRetrieved($highestMessageId) {
			$this->_db->removeExtraReports($highestMessageId);
		} # updateLastRetrieved
		
		/*
		 * De daadwerkelijke processing van de headers
		 */
		function process($hdrList, $curMsg, $endMsg) {
			$this->displayStatus("progress", ($curMsg) . " till " . ($endMsg));
		
			$signedCount = 0;
			$lastProcessedId = '';
			$reportDbList = array();
			
			# pak onze lijst met messageid's, en kijk welke er al in de database zitten
			$dbIdList = $this->_db->matchReportMessageIds($hdrList);
			
			# we houden een aparte lijst met spot messageids bij zodat we dat extracten
			# niet meer in de db laag moeten doen
			$spotMsgIdList = array();
			
			# en loop door elke header heen
			foreach($hdrList as $msgid => $msgheader) {
				# Reset timelimit
				set_time_limit(120);			

				# strip de reference van de <>'s
				$reportId = substr($msgheader['Message-ID'], 1, strlen($msgheader['Message-ID']) - 2);

				# als we de report nog niet in de database hebben, haal hem dan op
				if (!isset($dbIdList[$reportId])) {
					$lastProcessedId = $reportId;
					
					# Extract the keyword and the messageid its reporting about
					$tmpSubject = explode(' ', $msgheader['Subject']);
					if (count($tmpSubject) > 2) {
						$msgheader['keyword'] = $tmpSubject[0];
						$msgheader['References'] = substr($tmpSubject[1], 1, strlen($tmpSubject[1]) - 2);
						$spotMsgIdList[] = $msgheader['References'];

						# voeg spot aan db toe
						$reportDbList[] = array('messageid' => $reportId,
												 'fromhdr' => $msgheader['From'],
												 'keyword' => $msgheader['keyword'],
												 'nntpref' => $msgheader['References']);
					} # if

					# we moeten ook de msgid lijst updaten omdat 
					# soms een messageid meerdere keren per xover mee komt
					$dbIdList[$reportId] = 1;
				} # if
			} # foreach

			if (count($hdrList) > 0) {
				$this->displayStatus("loopcount", count($hdrList));
			} else {
				$this->displayStatus("loopcount", 0);
			} # else

			# update the last retrieved article			
			$this->_db->addReportRefs($reportDbList);
			$this->_db->setMaxArticleid('reports', $endMsg);
			
			# herbereken het aantal reports in spotnet
			$this->_db->updateSpotReportCount($spotMsgIdList);
			
			return array('count' => count($hdrList), 'headercount' => count($hdrList), 'lastmsgid' => $lastProcessedId);
		} # process()
		
} # class SpotRetriever_Reports
