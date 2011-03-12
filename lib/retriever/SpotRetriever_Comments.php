<?php
require_once "SpotRetriever_Abs.php";

class SpotRetriever_Comments extends SpotRetriever_Abs {
		private $_outputType;

		/**
		 * server - de server waar naar geconnect moet worden
		 * db - database object
		 * rsakeys = array van rsa keys
		 */
		function __construct($server, $db, $outputType) {
			parent::__construct($server, $db);
			
			$this->_outputType = $outputType;
		} # ctor
		
		/*
		 * Geef de status weer in category/text formaat. Beide zijn vrij te bepalen
		 */
		function displayStatus($cat, $txt) {
			if ($this->_outputType != 'xml') {
				switch($cat) {
					case 'start'			: echo "Retrieving new comments from server...\r\n"; break;
					case 'done'				: echo "Finished retrieving comments.\r\n\r\n"; break;
					case 'groupmessagecount': echo "Appr. Message count: 	" . $txt . "\r\n"; break;
					case 'firstmsg'			: echo "First message number:	" . $txt . "\r\n"; break;
					case 'lastmsg'			: echo "Last message number:	" . $txt . "\r\n"; break;
					case 'curmsg'			: echo "Current message:	" . $txt . "\r\n"; break;
					case 'progress'			: echo "Retrieving " . $txt; break;
					case 'loopcount'		: echo ", found " . $txt . " comments\r\n"; break;
					case 'totalprocessed'	: echo "Processed a total of " . $txt . " comments\r\n"; break;
					case 'searchmsgid'		: echo "Looking for articlenumber for messageid\r\n"; break;
					case ''					: echo "\r\n"; break;
					
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
		 * De daadwerkelijke processing van de headers
		 */
		function process($hdrList, $curMsg, $increment) {
			$this->displayStatus("progress", ($curMsg) . " till " . ($curMsg + $increment));
		
			$this->_db->beginTransaction();
			$signedCount = 0;
			foreach($hdrList as $msgid => $msgheader) {
				# Reset timelimit
				set_time_limit(120);			

				# strip de reference van de <>'s en sla het edit nummer apart op
				$msgidParts = explode('@', substr($msgheader['Message-ID'], 1, strlen($msgheader['Message-ID']) - 2));
				$msgidNumber = explode('.', $msgidParts[0]);
				
				if (count($msgidNumber) >= 3) {
					$msgid = $msgidNumber[0] . '.' . $msgidNumber[1] . '@' . $msgidParts[1];
				} else {
					$msgid = $msgidParts[0] . '@' . $msgidParts[1];
					$msgidNumber[2] = 0;
				} # if
				
				# fix de references, niet alle news servers geven die goed door
				$msgheader['References'] = $msgidNumber[0] . '@' . $msgidParts[1];
				
				# voeg spot aan db toe
				$this->_db->addCommentRef($msgid,
								   $msgidNumber[2],
								   $msgheader['References']);
			} # foreach

			if (count($hdrList) > 0) {
				$this->displayStatus("loopcount", count($hdrList));
			} else {
				$this->displayStatus("loopcount", 0);
			} # else

			$this->_db->setMaxArticleid('comments', $curMsg);
			$this->_db->commitTransaction();
			
			return count($hdrList);
		} # process()
		
} # class SpotRetriever_Comments
