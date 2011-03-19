<?php
error_reporting(E_ALL & ~8192 & ~E_USER_WARNING);	# 8192 == E_DEPRECATED maar PHP < 5.3 heeft die niet

# Risky warning, might trip up some stuff
if (@!file_exists(getcwd() . '/' . basename($argv[0]))) {
	echo "It appears you are not running retrieve.php from its own directory. Please CHDIR to my directoy and run again!" . PHP_EOL;
	die();
} # if

require_once "settings.php";
require_once "lib/exceptions/ParseSpotXmlException.php";
require_once "lib/SpotDb.php";
require_once "lib/SpotReq.php";
require_once "lib/SpotParser.php";
require_once "lib/SpotNntp.php";
require_once "lib/retriever/SpotRetriever_Spots.php";
require_once "lib/retriever/SpotRetriever_Comments.php";
require_once "lib/imexport/Spot_SpotMapping.php";

# in safe mode, max execution time cannot be set, warn the user
if (ini_get('safe_mode') ) {
	echo "WARNING: PHP safemode is enabled, maximum execution cannot be reset! Turn off safemode if this causes problems" . PHP_EOL . PHP_EOL;
} # if

if ((isset($argc)) && ($argc > 1) && ($argv[1] == '--export')) {
	if (!$settings['retrieve_full']) {
		die("Databases zonder retrieve_full kunnen we niet importeren dus exporteren is disabled.");
	} # if
	
	try {
		$db = new SpotDb($settings['db']);
		$db->connect();
	
		$fp = fopen('export-db.csv', 'w');
		$spotCount = $db->getSpotCount();
		for ($i = 0; $i < $spotCount; $i = $i + 5000) { 	
			$spots = $db->getSpots($i / 5000, 5000, '', array('field' => 'id', 'direction' => 'asc'), true);
			
			foreach($spots as $spot) {
				$mappedSpot = array();

				# We exporteren alleen als we alle velden hebben
				if (count($spot) == count(Spot_SpotMapping::$fieldMapping)) {
					foreach(Spot_SpotMapping::$fieldMapping as $key => $value) {
						$mappedSpot[$value] = $spot[$key];
					} # foreach

					fputcsv($fp, $mappedSpot);
				} # if
			} # foreach
		} # for
		fclose($fp);
	} 
	catch(Exception $x) {
		die("Error exporting data: " . $x->getMessage() . PHP_EOL);
	} # catch
	
	exit;
} # export


if ((isset($argc)) && ($argc > 1) && ($argv[1] == '--import')) {
	try {
		$db = new SpotDb($settings['db']);
		$db->connect();
	
		$fp = fopen('export-db.csv', 'r');
		while (($line = fgetcsv($fp)) !== FALSE) {
			$mappedSpot = array();
			foreach($line as $key => $value) {
				$mappedSpot[Spot_SpotMapping::$valueMapping[$key]] = $value;
			} # foreach

			$db->addSpot($line, $line);
		} # while
		
		fclose($fp);
	} 
	catch(Exception $x) {
		die("Error exporting data: " . $x->getMessage() . PHP_EOL);
	} # catch
	
	exit;
} # import

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

## Als we forceren om de "already running" check te bypassen, doe dat dan
if ((isset($argc)) && ($argc > 1) && ($argv[1] == '--force')) {
	$db->setRetrieverRunning($settings['nntp_hdr']['host'], false);
} # if

## Spots
try {

	$retriever = new SpotRetriever_Spots($settings['nntp_hdr'], 
										 $db, 
										 $settings,										 
										 $settings['rsa_keys'], 
										 $req->getDef('output', ''),
										 $settings['retrieve_full']);
	$msgdata = $retriever->connect($settings['hdr_group']);
	$retriever->displayStatus('dbcount', $db->getSpotCount());
	
	$curMsg = $db->getMaxArticleId($settings['nntp_hdr']['host']);
	if ($curMsg != 0) {
		echo "DEBUG: Op zoek naar messageid: " . $db->getMaxMessageId('headers') . PHP_EOL;
		
		$curMsg = $retriever->searchMessageId($db->getMaxMessageId('headers'));

		echo "DEBUG: Gevonden op: " . $curMsg;
		die();
	} # if

	$retriever->loopTillEnd($curMsg, $settings['retrieve_increment']);
	$retriever->quit();
	$db->setLastUpdate($settings['nntp_hdr']['host']);
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
	if ($settings['retrieve_comments']) {
		$retriever = new SpotRetriever_Comments($settings['nntp_hdr'], 
												$db,
												$settings,
												$req->getDef('output', ''));
		$msgdata = $retriever->connect($settings['comment_group']);

		$curMsg = $db->getMaxArticleId('comments');
		if ($curMsg != 0) {
			$curMsg = $retriever->searchMessageId($db->getMaxMessageId('comments'));
		} # if

		$retriever->loopTillEnd($curMsg, $settings['retrieve_increment']);
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
	if ($settings['retention'] > 0) {
		$db->deleteSpotsRetention($settings['retention']);
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
