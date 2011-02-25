<?php
error_reporting(E_ALL & ~E_DEPRECATED);

require_once "settings.php";
require_once "db.php";
require_once "SpotParser.php";
require_once "SpotNntp.php";

$db = new db($settings['db']);

$spotnntp = new SpotNntp($settings['nntp_hdr']['host'],
						 $settings['nntp_hdr']['enc'],
						 $settings['nntp_hdr']['port'],
						 $settings['nntp_hdr']['user'],
						 $settings['nntp_hdr']['pass']);
if ($spotnntp->connect()) {
	$msgdata = $spotnntp->selectGroup($settings['hdr_group']);
	if ($msgdata === false) {
		echo "Error getting group: " . $spotnntp->getError();
		exit;
	} # if

	# Determine wether we want 
	$curMsg = $db->getMaxArticleId($settings['nntp_hdr']['host']);
	
	# in safe mode, max execution time cannot be set, warn the user
	if (ini_get('safe_mode') ) {
		echo "WARNING: PHP safemode is enabled, maximum execution cannot be reset! Turn off safemode if this causes problems\r\n\r\n";
	} # if
	
	# make sure we handle articlenumber wrap arounds
	if ($curMsg < $msgdata['first']) {
		$curMsg = $msgdata['first'];
	} # if
	
	echo "Appr. Message count: " . ($msgdata['last'] - $msgdata['first']) . "\r\n";
	echo "Last message number: " . $msgdata['last'] . "\r\n";
	echo "Spots in database:   " . $db->getSpotCount() . "\r\n";
	echo "Current message:     " . $curMsg . "\r\n";
	echo "\r\n";
	
	$increment = 1000;
	while ($curMsg < $msgdata['last']) {
		# Show some status message
		echo "Retrieving:          " . ($curMsg) . " till " . ($curMsg + $increment) ;

		# get the list of headers (XOVER)
		$hdrList = $spotnntp->getOverview($curMsg, ($curMsg + $increment));
		if ($hdrList === false) {
			echo "\r\n\r\nError retrieving message list: " . $spotnntp->getError() . "\r\n";
			break;
		} # if
				
		$db->beginTransaction();
		$signedCount = 0;
		foreach($hdrList as $msgid => $msgheader) {
			# Reset timelimit
			set_time_limit(120);			
			
			$spotParser = new SpotParser();
			$spot = $spotParser->parseXover($msgheader['Subject'], 
											$msgheader['From'], 
											$msgheader['Message-ID'],
											$settings['rsa_keys']);
											
			if (($spot != null) && ($spot['Verified'])) {
				$db->addSpot($spot);
			} # if
			
			if ($spot['Verified']) {
				if ($spot['WasSigned']) {
					$signedCount++;
				} # if
			} # if
		} # foreach

		if (count($hdrList) > 0) {
			echo ", verified " . $signedCount . " signatures of " . count($hdrList) . " valid spots (" . count($hdrList) . " msgs)\r\n";
		} else {
			echo ", no valid spots found in this message range...\r\n";
		} # else
		
		# If no spots were found, just manually increase the 
		# messagenumber with the increment to make sure we advance
		if ((count($hdrList) < 1) || ($hdrList[0]['Number'] < $curMsg)) {
			$curMsg += $increment;
		} else {
			$curMsg = ($hdrList[0]['Number'] + 1);
		} # else

		$db->setMaxArticleid($settings['nntp_hdr']['host'], $curMsg);
		$db->endTransaction();
	} # while

	try {
		$spotnntp->quit();
	} catch(Exception $x) {}
	
} else {
	echo "\r\n";
	echo "Unable to logon or connect to NNTP server, check NNTP settings: \r\n";
	die($spotnntp->getError() . "\r\n\r\n");
	
} # if
