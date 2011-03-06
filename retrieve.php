<?php
error_reporting(E_ALL & ~8192 & ~E_USER_WARNING);	# 8192 == E_DEPRECATED maar PHP < 5.3 heeft die niet

require_once "settings.php";
require_once "lib/SpotDb.php";
require_once "lib/SpotReq.php";
require_once "lib/SpotParser.php";
require_once "lib/SpotNntp.php";
require_once "lib/retriever/SpotRetriever_Spots.php";
require_once "lib/retriever/SpotRetriever_Comments.php";


# in safe mode, max execution time cannot be set, warn the user
if (ini_get('safe_mode') ) {
	echo "WARNING: PHP safemode is enabled, maximum execution cannot be reset! Turn off safemode if this causes problems\r\n\r\n";
} # if

if (!isset($settings['retrieve_increment'])) {
	echo "WARNING: Parameter retrieve_increment is missing in settings.php, please add and run again.";
	die();
}

$req = new SpotReq();
$req->initialize();

if ($req->getDef('output', '') == 'xml') {
	echo "<xml>";
} # if

try {
	$db = new SpotDb($settings['db']);
	$db->connect();
} 
catch(Exception $x) {
	die("Unable to connect to database: " . $x->getMessage() . "\r\n");
} # catch

## Als we forceren om de "already running" check te bypassen, doe dat dan
if ((isset($argc)) && ($argc > 1) && ($argv[1] == '--force')) {
	$db->setRetrieverRunning($settings['nntp_hdr']['host'], false);
} # if


## Spots
try {
	$curMsg = $db->getMaxArticleId($settings['nntp_hdr']['host']);

	$retriever = new SpotRetriever_Spots($settings['nntp_hdr'], 
										 $db, 
										 $settings['rsa_keys'], 
										 $req->getDef('output', ''));
	$msgdata = $retriever->connect($settings['hdr_group']);
	$retriever->displayStatus('dbcount', $db->getSpotCount());
	$retriever->loopTillEnd($curMsg, $settings['retrieve_increment']);
	$retriever->quit();
} 
catch(RetrieverRunningException $x) {
	echo "\r\n\r\n";
	echo "retriever.php draait al, geef de parameter '--force' mee om te forceren.\r\n";
}
catch(Exception $x) {
	echo "\r\n\r\n";
	echo "Fatal error occured retrieving messages: \r\n";
	echo "  " . $x->getMessage() . "\r\n\r\n";
	die();
} # catch

## Comments
try {
	$retriever = new SpotRetriever_Comments($settings['nntp_hdr'], 
											$db,
											$req->getDef('output', ''));
	$msgdata = $retriever->connect($settings['comment_group']);

	$curMsg = $db->getMaxArticleId('comments');
	$retriever->loopTillEnd($curMsg, $settings['retrieve_increment']);
	$retriever->quit();
}
catch(RetrieverRunningException $x) {
	echo "\r\n\r\n";
	echo "retriever.php draait al, geef de parameter '--force' mee om te forceren.\r\n";
} catch(Exception $x) {
	echo "\r\n\r\n";
	echo "Fatal error occured retrieving messages: \r\n";
	echo "  " . $x->getMessage() . "\r\n\r\n";
	die();
} # catch


if ($req->getDef('output', '') == 'xml') {
	echo "</xml>";
} # if
