<?php
class SpotRetriever_Reports extends SpotRetriever_Abs {
		private $_outputType;

		/**
		 * Server is the server array we are expecting to connect to
		 * db - database object
		 */
		function __construct($server, SpotDb $db, SpotSettings $settings, $outputType, $debug) {
			parent::__construct($server, $db, $settings, $debug, false);
			
			$this->_outputType = $outputType;
		} # ctor
		
		/*
		 * Returns the status in either xml or text format 
		 */
		function displayStatus($cat, $txt) {
			if ($this->_outputType != 'xml') {
				switch($cat) {
					case 'start'			: echo "Retrieving new reports from server " . $txt . "..." . PHP_EOL; break;
					case 'lastretrieve'		: echo strftime("Last retrieve at %c", $txt) . PHP_EOL; break;
					case 'done'			: echo "Finished retrieving reports." . PHP_EOL . PHP_EOL; break;
					case 'groupmessagecount': echo "Appr. Message count: 	" . $txt . "" . PHP_EOL; break;
					case 'firstmsg'			: echo "First message number:	" . $txt . "" . PHP_EOL; break;
					case 'lastmsg'			: echo "Last message number:	" . $txt . "" . PHP_EOL; break;
					case 'curmsg'			: echo "Current message:	" . $txt . "" . PHP_EOL; break;
					case 'progress'			: echo "Retrieving " . $txt; break;
					case 'loopcount'		: echo ", found " . $txt . " reports"; break;
					case 'timer'			: echo " in " . $txt . " seconds" . PHP_EOL; break;
					case 'totalprocessed'		: echo "Processed a total of " . $txt . " reports" . PHP_EOL; break;
					case 'searchmsgid'		: echo "Looking for articlenumber for messageid" . PHP_EOL; break;
					case ''				: echo PHP_EOL; break;
					
					default				: echo $cat . $txt;
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
		 * Remove any extraneous reports from the database because we assume
		 * the highest messgeid in the database is the latest on the server.
		 */
		function updateLastRetrieved($highestMessageId) {
			/*
			 * Remove any extraneous reports from the database because we assume
			 * the highest messgeid in the database is the latest on the server.
			 *
			 * If the server is marked as buggy, the last 'x' amount of repors are
			 * always checked so we do not have to do this 
			 */
			if (!$this->_server['buggy']) {
				$this->_db->removeExtraReports($highestMessageId);
			} # if
		} # updateLastRetrieved
		
		/*
		 * Actually process the retrieved headers from XOVER
		 */
		function process($hdrList, $curMsg, $endMsg, $timer) {
			$this->displayStatus("progress", ($curMsg) . " till " . ($endMsg));
		
			$signedCount = 0;
			$lastProcessedId = '';
			$reportDbList = array();

			/**
			 * We ask the database to match our messageid's we just retrieved with
			 * the list of id's we have just retrieved from the server
			 */
			$dbIdList = $this->_db->matchReportMessageIds($hdrList);

			/*
			 * We keep a seperate list of messageid's for updating the amount of
			 * reports for each spot.
			 */
			$spotMsgIdList = array();
			
			# Process each header
			foreach($hdrList as $msgid => $msgheader) {
				# Reset timelimit
				set_time_limit(120);			

				# strip the <>'s from the reference
				$reportId = substr($msgheader['Message-ID'], 1, strlen($msgheader['Message-ID']) - 2);

				# Prepare the report to be added to the server when the report isn't in the database yet
				if (!isset($dbIdList[$reportId])) {
					$lastProcessedId = $reportId;
					
					# Extract the keyword and the messageid its reporting about
					$tmpSubject = explode(' ', $msgheader['Subject']);
					if (count($tmpSubject) > 2) {
						$msgheader['keyword'] = $tmpSubject[0];
						$msgheader['References'] = substr($tmpSubject[1], 1, strlen($tmpSubject[1]) - 2);
						$spotMsgIdList[$msgheader['References']] = 1;

						# prepare the spot to be added to the server
						$reportDbList[] = array('messageid' => $reportId,
												 'fromhdr' => utf8_encode($msgheader['From']),
												 'keyword' => utf8_encode($msgheader['keyword']),
												 'nntpref' => $msgheader['References']);
					} # if

					/*
					 * Some buggy NNTP servers give us the same messageid
					 * in once XOVER statement, hence we update the list of
					 * messageid's we already have retrieved and are ready
					 * to be added to the database
					 */
					$dbIdList[$reportId] = 1;
				} # if
			} # foreach

			if (count($hdrList) > 0) {
				$this->displayStatus("loopcount", count($hdrList));
			} else {
				$this->displayStatus("loopcount", 0);
			} # else
			$this->displayStatus("timer", round(microtime(true) - $timer, 2));

			# update the last retrieved article			
			$this->_db->addReportRefs($reportDbList);
			$this->_db->setMaxArticleid('reports', $endMsg);
			
			# Calculate the amount of reports for a spot
			$this->_db->updateSpotReportCount($spotMsgIdList);
			
			return array('count' => count($hdrList), 'headercount' => count($hdrList), 'lastmsgid' => $lastProcessedId);
		} # process()
		
		/*
		 * returns the name of the group we are expected to retrieve messages from
		 */
		function getGroupName() {
			return $this->_settings->get('report_group');
		} # getGroupName

		/*
		 * Highest articleid for the implementation in the database
		 */
		function getMaxArticleId() {
			return $this->_db->getMaxArticleid('reports');
		} # getMaxArticleId

		/*
		 * Returns the highest messageid in the database
		 */
		function getMaxMessageId() {
			return $this->_db->getMaxMessageId('reports');
		} # getMaxMessageId
		
} # class SpotRetriever_Reports
