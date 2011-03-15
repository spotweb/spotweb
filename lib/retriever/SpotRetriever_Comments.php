<?php
require_once "SpotRetriever_Abs.php";

class SpotRetriever_Comments extends SpotRetriever_Abs {
		private $_outputType;

		/**
		 * server - de server waar naar geconnect moet worden
		 * db - database object
		 * rsakeys = array van rsa keys
		 */
		function __construct($server, $db, $settings, $outputType) {
			parent::__construct($server, $db, $settings);			
			
			$this->_outputType = $outputType;
		} # ctor
		
		/*
		 * Geef de status weer in category/text formaat. Beide zijn vrij te bepalen
		 */
		function displayStatus($cat, $txt) {
			if ($this->_outputType != 'xml') {
				switch($cat) {
					case 'start'			: echo "Retrieving new comments from server..." . PHP_EOL; break;
					case 'done'				: echo "Finished retrieving comments." . PHP_EOL . PHP_EOL; break;
					case 'groupmessagecount': echo "Appr. Message count: 	" . $txt . "" . PHP_EOL; break;
					case 'firstmsg'			: echo "First message number:	" . $txt . "" . PHP_EOL; break;
					case 'lastmsg'			: echo "Last message number:	" . $txt . "" . PHP_EOL; break;
					case 'curmsg'			: echo "Current message:	" . $txt . "" . PHP_EOL; break;
					case 'progress'			: echo "Retrieving " . $txt; break;
					case 'loopcount'		: echo ", found " . $txt . " comments" . PHP_EOL; break;
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
		 * De daadwerkelijke processing van de headers
		 */
		function process($hdrList, $curMsg, $endMsg) {
			$this->displayStatus("progress", ($curMsg) . " till " . ($endMsg));
		
			$this->_db->beginTransaction();
			$signedCount = 0;
			foreach($hdrList as $msgid => $msgheader) {
				# Reset timelimit
				set_time_limit(120);			

				# strip de reference van de <>'s
				$commentId = substr($msgheader['Message-ID'], 1, strlen($msgheader['Message-ID']) - 2);
				
				# fix de references, niet alle news servers geven die goed door
				$msgIdParts = explode(".", $commentId);
				$msgheader['References'] = $msgIdParts[0] . substr($commentId, strpos($commentId, '@'));

				# voeg spot aan db toe
				$this->_db->addCommentRef($commentId,
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
