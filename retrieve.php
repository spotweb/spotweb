<?php
error_reporting(E_ALL & ~8192 & ~E_USER_WARNING);	# 8192 == E_DEPRECATED maar PHP < 5.3 heeft die niet

require_once "settings.php";
require_once "db.php";
require_once "SpotParser.php";
require_once "SpotNntp.php";
require_once "SpotRetriever.php";

function cbRetrieveSpots($hdrList, $curMsg) {
	global $db, $settings;
	
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

	$db->setMaxArticleid($settings['nntp_hdr']['host'], $curMsg);
	$db->commitTransaction();
} # cbRetrieveSpots


function cbRetrieveComments($hdrList, $curMsg) {
	global $db, $settings;
	
	$db->beginTransaction();
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
		$db->addCommentRef($msgid,
						   $msgidNumber[2],
						   $msgheader['References']);
	} # foreach

	if (count($hdrList) > 0) {
		echo ", added  " . count($hdrList) . " comments\r\n";
	} else {
		echo ", no valid comments found in this message range...\r\n";
	} # else

	$db->setMaxArticleid('comments', $curMsg);
	$db->commitTransaction();
} # cbRetrieveComments

# in safe mode, max execution time cannot be set, warn the user
if (ini_get('safe_mode') ) {
	echo "WARNING: PHP safemode is enabled, maximum execution cannot be reset! Turn off safemode if this causes problems\r\n\r\n";
} # if

if (!isset($settings['retrieve_increment'])) {
	echo "WARNING: Parameter retrieve_increment is missing in settings.php, please add and run again.";
	die();
}

$db = new db($settings['db']);
if (!$db->connect()) {
	die($db->getError() . "\r\n");
} # if
echo "Spots in database:   " . $db->getSpotCount() . "\r\n";

## Spots
$retriever = new SpotRetriever($settings['nntp_hdr']);
if (! ($msgdata = $retriever->connect($settings['hdr_group']))) {
	echo "\r\n";
	echo "Unable to logon or connect to NNTP server, check NNTP settings: \r\n";
	die($retriever->getError() . "\r\n\r\n");
} # if

$curMsg = $db->getMaxArticleId($settings['nntp_hdr']['host']);
$retriever->loopTillEnd($curMsg, 'cbRetrieveSpots', $settings['retrieve_increment']);
$retriever->quit();

## Spots
echo "Done retrieving spots, retrieving comments...\r\n";

$retriever = new SpotRetriever($settings['nntp_hdr']);
if (! ($msgdata = $retriever->connect($settings['comment_group']))) {
	echo "\r\n";
	echo "Unable to logon or connect to NNTP server, check NNTP settings: \r\n";
	die($retriever->getError() . "\r\n\r\n");
} # if

$curMsg = $db->getMaxArticleId('comments');
$retriever->loopTillEnd($curMsg, 'cbRetrieveComments', $settings['retrieve_increment']);
$retriever->quit();
