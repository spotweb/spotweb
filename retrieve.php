<?php
error_reporting(E_ALL & ~8192 & ~E_USER_WARNING);	# 8192 == E_DEPRECATED maar PHP < 5.3 heeft die niet
error_reporting(E_ALL & E_STRICT);

require_once "settings.php";
require_once "lib/SpotDb.php";
require_once "SpotParser.php";
require_once "SpotNntp.php";
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

try {
	$db = new SpotDb($settings['db']);
	$db->connect();
} 
catch(Exception $x) {
	die("Unable to connect to database: " . $x->getMessage() . "\r\n");
} # catch

echo "Spots in database:   " . $db->getSpotCount() . "\r\n";

## Spots
try {
	$curMsg = $db->getMaxArticleId($settings['nntp_hdr']['host']);

	$retriever = new SpotRetriever_Spots($settings['nntp_hdr'], $db, $settings['rsa_keys']);
	$msgdata = $retriever->connect($settings['hdr_group']);
	$retriever->loopTillEnd($curMsg, $settings['retrieve_increment']);
	$retriever->quit();
} 
catch(Exception $x) {
	echo "\r\n\r\n";
	echo "Fatal error occured retrieving messages: \r\n";
	echo "  " . $x->getMessage() . "\r\n\r\n";
	die();
} # catch

## Spots
echo "Done retrieving spots, retrieving comments...\r\n";

try {
	$retriever = new SpotRetriever_Comments($settings['nntp_hdr'], $db);
	$msgdata = $retriever->connect($settings['comment_group']);

	$curMsg = $db->getMaxArticleId('comments');
	$retriever->loopTillEnd($curMsg, $settings['retrieve_increment']);
	$retriever->quit();
} catch(Exception $x) {
	echo "\r\n\r\n";
	echo "Fatal error occured retrieving messages: \r\n";
	echo "  " . $x->getMessage() . "\r\n\r\n";
	die();
} # catch
