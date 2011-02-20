<?php
require_once "settings.php";
require_once "db.php";
require_once "SpotParser.php";
require_once "SpotNntp.php";

$db = new db($settings['sqlite3_path']);

$spotnntp = new SpotNntp($settings['nntp_host'],
						 $settings['nntp_enc'],
						 $settings['nntp_port'],
						 $settings['nntp_user'],
						 $settings['nntp_pass']);
if ($spotnntp->connect()) {
	$msgdata = $spotnntp->selectGroup($settings['hdr_group']);
	if ($msgdata === false) {
		echo "Error getting group: " . $spotnntp->getError();
		break;
	} # if

	# Determine wether we want 
	$curMsg = $db->getMaxArticleId($settings['nntp_host']);
	if ($curMsg < $msgdata['first']) {
		$curMsg = $msgdata['first'];
	} # if
	
	echo "Message count:       " . ($msgdata['last'] - $msgdata['first']) . "\r\n";
	echo "Last message number: " . $msgdata['last'] . "\r\n";
	echo "Current message:     " . $curMsg . "\r\n";
			
	$increment = 10000;
	while ($curMsg < $msgdata['last']) {
		# extend timeout
		set_time_limit(30);
		
		# Show some status message
		echo "Retrieving:          " . ($curMsg) . " till " . ($curMsg + $increment) . "\r\n";

		# get the list of headers (XOVER)
		$hdrList = $spotnntp->getOverview($curMsg, ($curMsg + $increment));
		if ($hdrList === false) {
			echo "Error retrieving message list: " . $spotnntp->getError();
			break;
		} # if
				
		$db->beginTransaction();
		foreach($hdrList as $msgid => $msgheader) {
			$spotParser = new SpotParser();
			$spot = $spotParser->parseXover($msgheader['Subject'], 
											$msgheader['From'], 
											$msgheader['Message-ID'],
											$settings['rsa_keys']);

			if (($spot != null) && ($spot['Verified'])) {
				$db->addSpot($spot);
			} # if
		} # foreach
				
		# If no spots were found, just manually increase the 
		# messagenumber with the increment to make sure we advance
		if ((count($hdrList) < 1) || ($hdrList[0]['Number'] < $curMsg)) {
			$curMsg += $increment;
		} else {
			$curMsg = ($hdrList[0]['Number'] + 1);
		} # else

		$db->setMaxArticleid($settings['nntp_host'], $curMsg);
		$db->endTransaction();
	} # while

	$spotnntp->quit();
} else {
	echo "\r\n";
	echo "Unable to logon or connect to NNTP server, check NNTP settings: \r\n";
	die($spotnntp->getError() . "\r\n\r\n");
	
} # if
