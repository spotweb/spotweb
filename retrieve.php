<?php
error_reporting(E_ALL & ~8192 & ~E_USER_WARNING);	# 8192 == E_DEPRECATED maar PHP < 5.3 heeft die niet

if (@!file_exists(getcwd() . '/' . basename($argv[0]))) {
	chdir(__DIR__);
} # if

require_once "settings.php";
require_once "lib/SpotTiming.php";
require_once "lib/exceptions/ParseSpotXmlException.php";
require_once "lib/SpotDb.php";
require_once "lib/SpotReq.php";
require_once "lib/SpotParser.php";
require_once "lib/SpotNntp.php";
require_once "lib/SpotSettings.php";
require_once "lib/retriever/SpotRetriever_Spots.php";
require_once "lib/retriever/SpotRetriever_Comments.php";
require_once "lib/imexport/Spot_SpotMapping.php";

# in safe mode, max execution time cannot be set, warn the user
if (ini_get('safe_mode') ) {
	echo "WARNING: PHP safemode is enabled, maximum execution cannot be reset! Turn off safemode if this causes problems" . PHP_EOL . PHP_EOL;
} # if

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
	die("Unable to connect to database: " . $x->getMessage() . PHP_EOL);
} # catch

# Controleer dat we niet een schema upgrade verwachten
if (!$db->schemaValid()) {
	die("Database schema is gewijzigd, draai upgrade-db.php aub" . PHP_EOL);
} # if

# Creer het settings object
$settings = SpotSettings::singleton($db, $settings);

# We vragen de nntp_hdr settings alvast op
$settings_nntp_hdr = $settings->get('nntp_hdr');
	
## Als we forceren om de "already running" check te bypassen, doe dat dan
if ((isset($argc)) && ($argc > 1) && ($argv[1] == '--force')) {
	$db->setRetrieverRunning($settings_nntp_hdr['host'], false);
} # if

## Spots
try {
	$rsaKeys = $settings->get('rsa_keys');
	$retriever = new SpotRetriever_Spots($settings_nntp_hdr, 
										 $db, 
										 $settings,										 
										 $rsaKeys, 
										 $req->getDef('output', ''),
										 $settings->get('retrieve_full'));
	$msgdata = $retriever->connect($settings->get('hdr_group'));
	$retriever->displayStatus('dbcount', $db->getSpotCount(''));
	
	$curMsg = $db->getMaxArticleId($settings_nntp_hdr['host']);
	if ($curMsg != 0) {
		$curMsg = $retriever->searchMessageId($db->getMaxMessageId('headers'));
	} # if

	$retriever->loopTillEnd($curMsg, $settings->get('retrieve_increment'));
	$retriever->quit();
	$db->setLastUpdate($settings_nntp_hdr['host']);
} 
catch(RetrieverRunningException $x) {
	echo PHP_EOL . PHP_EOL;
	echo "retriever.php draait al, geef de parameter '--force' mee om te forceren." . PHP_EOL;
}
catch(Exception $x) {
	echo PHP_EOL . PHP_EOL;
	echo "Fatal error occured retrieving messages:" . PHP_EOL;
	echo "  " . $x->getMessage() . PHP_EOL . PHP_EOL;
	die();
} # catch

## Comments
try {
	if ($settings->get('retrieve_comments')) {
		$retriever = new SpotRetriever_Comments($settings_nntp_hdr, 
												$db,
												$settings,
												$req->getDef('output', ''));
		$msgdata = $retriever->connect($settings->get('comment_group'));

		$curMsg = $db->getMaxArticleId('comments');
		if ($curMsg != 0) {
			$curMsg = $retriever->searchMessageId($db->getMaxMessageId('comments'));
		} # if

		$retriever->loopTillEnd($curMsg, $settings->get('retrieve_increment'));
		$retriever->quit();
	} # if
}
catch(RetrieverRunningException $x) {
	echo PHP_EOL . PHP_EOL;
	echo "retriever.php draait al, geef de parameter '--force' mee om te forceren." . PHP_EOL;
} catch(Exception $x) {
	echo PHP_EOL . PHP_EOL;
	echo "Fatal error occured retrieving messages:" . PHP_EOL;
	echo "  " . $x->getMessage() . PHP_EOL . PHP_EOL;
	die();
} # catch

## Retention cleanup
try {
	if ($settings->get('retention') > 0) {
		$db->deleteSpotsRetention($settings->get('retention'));
	} # if
} catch(Exception $x) {
	echo PHP_EOL . PHP_EOL;
	echo "Fatal error occured retrieving messages:" . PHP_EOL;
	echo "  " . $x->getMessage() . PHP_EOL . PHP_EOL;
	die();
} # catch

if ($req->getDef('output', '') == 'xml') {
	echo "</xml>";
} # if
