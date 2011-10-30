<?php
error_reporting(E_ALL & ~8192 & ~E_USER_WARNING);	# 8192 == E_DEPRECATED maar PHP < 5.3 heeft die niet

if (@!file_exists(getcwd() . '/' . basename($argv[0]))) {
	if (!defined('__DIR__')) {
		class __DIR_CLASS__ {
			function  __toString() {
				$backtrace = debug_backtrace();
				return dirname($backtrace[1]['file']);
			} # __toString
		} # __FILE_CLASS__
		define('__DIR__', new __DIR_CLASS__);
	} # if

	chdir(__DIR__);
} # if

require_once "lib/SpotClassAutoload.php";
require_once "settings.php";
require_once "lib/SpotTiming.php";
require_once "lib/exceptions/ParseSpotXmlException.php";
require_once "lib/exceptions/NntpException.php";

# disable timing, met alle queries die er draaien loopt dat  uit op een hele grote memory usage
SpotTiming::disable();

# in safe mode, max execution time cannot be set, warn the user
if (ini_get('safe_mode') ) {
	echo "WARNING: PHP safemode is enabled, maximum execution cannot be reset! Turn off safemode if this causes problems" . PHP_EOL . PHP_EOL;
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

# Controleer eerst of de settings versie nog wel geldig zijn
if (!$settings->settingsValid()) {
	die("Globale settings zijn gewijzigd, draai upgrade-db.php aub" . PHP_EOL);
} # if

$req = new SpotReq();
$req->initialize($settings);

# We willen alleen uitgevoerd worden door een user die dat mag als
# we via de browser aangeroepen worden. Via console halen we altijd
# het admin-account op
$spotUserSystem = new SpotUserSystem($db, $settings);
if (isset($_SERVER['SERVER_PROTOCOL'])) {
	# Vraag de API key op die de gebruiker opgegeven heeft
	$apiKey = $req->getDef('apikey', '');
	
	$userSession = $spotUserSystem->verifyApi($apiKey);

	if (($userSession == false) || (!$userSession['security']->allowed(SpotSecurity::spotsec_retrieve_spots, ''))) { 
		die("Access denied");
	} # if
} else {
	$userSession['user'] = $db->getUser(SPOTWEB_ADMIN_USERID);
	$userSession['security'] = new SpotSecurity($db, $settings, $userSession['user']);
} # if

if ($req->getDef('output', '') == 'xml') {
	echo "<xml>";
} # if

# We vragen de nntp_hdr settings alvast op
$settings_nntp_hdr = $settings->get('nntp_hdr');
	
## Als we forceren om de "already running" check te bypassen, doe dat dan
if ((isset($argc)) && ($argc > 1) && ($argv[1] == '--force')) {
	$db->setRetrieverRunning($settings_nntp_hdr['host'], false);
} # if

## Moeten we debugloggen? Kan alleen als geen --force opgegeven wordt
$debugLog = ((isset($argc)) && ($argc > 1) && ($argv[1] == '--debug'));

## Spots
try {
	$rsaKeys = $settings->get('rsa_keys');
	$retriever = new SpotRetriever_Spots($settings_nntp_hdr, 
										 $db, 
										 $settings,										 
										 $rsaKeys, 
										 $req->getDef('output', ''),
										 $settings->get('retrieve_full'),
										 $debugLog);
	$msgdata = $retriever->connect($settings->get('hdr_group'));
	$retriever->displayStatus('dbcount', $db->getSpotCount(''));
	
	$curMsg = $db->getMaxArticleId($settings_nntp_hdr['host']);
	if ($curMsg != 0) {
		$curMsg = $retriever->searchMessageId($db->getMaxMessageId('headers'));
	} # if

	$newSpotCount = $retriever->loopTillEnd($curMsg, $settings->get('retrieve_increment'));
	$retriever->quit();
	$db->setLastUpdate($settings_nntp_hdr['host']);
} 
catch(RetrieverRunningException $x) {
	echo PHP_EOL . PHP_EOL;
	die("retriever.php draait al, geef de parameter '--force' mee om te forceren." . PHP_EOL);
}
catch(NntpException $x) {
	echo PHP_EOL . PHP_EOL;
	echo 'SpotWeb v' . SPOTWEB_VERSION . ' crashed' . PHP_EOL . PHP_EOL;
	echo "Fatal error occured while connecting to the newsserver:" . PHP_EOL;
	echo "  (" . $x->getCode() . ") " . $x->getMessage() . PHP_EOL;
	echo PHP_EOL . PHP_EOL;
	echo $x->getTraceAsString();
	echo PHP_EOL . PHP_EOL;

	if (isset($retriever)){
		echo "Updating retrieve status in the database" . PHP_EOL . PHP_EOL;
		$retriever->quit();
	}
	die();
}
catch(Exception $x) {
	echo PHP_EOL . PHP_EOL;
	echo 'SpotWeb v' . SPOTWEB_VERSION . ' crashed' . PHP_EOL . PHP_EOL;
	echo "Fatal error occured retrieving messages:" . PHP_EOL;
	echo "  " . $x->getMessage() . PHP_EOL;
	echo PHP_EOL . PHP_EOL;
	echo $x->getTraceAsString();
	echo PHP_EOL . PHP_EOL;
	die();
} # catch

## Comments
try {
	$newCommentCount = 0;
	if ($settings->get('retrieve_comments')) {
		$retriever = new SpotRetriever_Comments($settings_nntp_hdr, 
												$db,
												$settings,
												$req->getDef('output', ''),
												$settings->get('retrieve_full_comments'),
												$debugLog);
		$msgdata = $retriever->connect($settings->get('comment_group'));

		$curMsg = $db->getMaxArticleId('comments');
		if ($curMsg != 0) {
			$curMsg = $retriever->searchMessageId($db->getMaxMessageId('comments'));
		} # if

		$newCommentCount = $retriever->loopTillEnd($curMsg, $settings->get('retrieve_increment'));
		$retriever->quit();
	} # if
}
catch(NntpException $x) {
	echo PHP_EOL . PHP_EOL;
	echo 'SpotWeb v' . SPOTWEB_VERSION . ' crashed' . PHP_EOL . PHP_EOL;
	echo "Fatal error occured while connecting to the newsserver:" . PHP_EOL;
	echo "  (" . $x->getCode() . ") " . $x->getMessage() . PHP_EOL;
	echo PHP_EOL . PHP_EOL;
	echo $x->getTraceAsString();
	echo PHP_EOL . PHP_EOL;

	if (isset($retriever)){
		echo "Updating retrieve status in the database" . PHP_EOL . PHP_EOL;
		$retriever->quit();
	}
	die();
}
catch(Exception $x) {
	echo PHP_EOL . PHP_EOL;
	echo 'SpotWeb v' . SPOTWEB_VERSION . ' crashed' . PHP_EOL . PHP_EOL;
	echo "Fatal error occured retrieving comments:" . PHP_EOL;
	echo "  " . $x->getMessage() . PHP_EOL . PHP_EOL;
	echo PHP_EOL . PHP_EOL;
	echo $x->getTraceAsString();
	echo PHP_EOL . PHP_EOL;
	die();
} # catch

## Reports
try {
	$newReportCount = 0;
	if ($settings->get('retrieve_reports')) {
		$retriever = new SpotRetriever_Reports($settings_nntp_hdr, 
												$db,
												$settings,
												$req->getDef('output', ''),
												$debugLog);
		$msgdata = $retriever->connect($settings->get('report_group'));

		$curMsg = $db->getMaxArticleId('reports');
		if ($curMsg != 0) {
			$curMsg = $retriever->searchMessageId($db->getMaxMessageId('reports'));
		} # if

		$newReportCount = $retriever->loopTillEnd($curMsg, $settings->get('retrieve_increment'));
		$retriever->quit();
	} # if
}
catch(NntpException $x) {
	echo PHP_EOL . PHP_EOL;
	echo 'SpotWeb v' . SPOTWEB_VERSION . ' crashed' . PHP_EOL . PHP_EOL;
	echo "Fatal error occured while connecting to the newsserver:" . PHP_EOL;
	echo "  (" . $x->getCode() . ") " . $x->getMessage() . PHP_EOL;
	echo PHP_EOL . PHP_EOL;
	echo $x->getTraceAsString();
	echo PHP_EOL . PHP_EOL;

	if (isset($retriever)){
		echo "Updating retrieve status in the database" . PHP_EOL . PHP_EOL;
		$retriever->quit();
	}
	die();
}
catch(Exception $x) {
	echo PHP_EOL . PHP_EOL;
	echo 'SpotWeb v' . SPOTWEB_VERSION . ' crashed' . PHP_EOL . PHP_EOL;
	echo "Fatal error occured retrieving reports:" . PHP_EOL;
	echo "  " . $x->getMessage() . PHP_EOL . PHP_EOL;
	echo PHP_EOL . PHP_EOL;
	echo $x->getTraceAsString();
	echo PHP_EOL . PHP_EOL;
	die();
} # catch

## SpotStateList cleanup
try {
	$db->cleanSpotStateList();
} catch(Exception $x) {
	echo PHP_EOL . PHP_EOL;
	echo 'SpotWeb v' . SPOTWEB_VERSION . ' crashed' . PHP_EOL . PHP_EOL;
	echo "Fatal error occured while cleaning up lists:" . PHP_EOL;
	echo "  " . $x->getMessage() . PHP_EOL;
	echo PHP_EOL . PHP_EOL;
	echo $x->getTraceAsString();
	echo PHP_EOL . PHP_EOL;
	die();
} # catch

## cache cleanup
try {
	$db->cleanCache(30);
} catch(Exception $x) {
	echo PHP_EOL . PHP_EOL;
	echo 'SpotWeb v' . SPOTWEB_VERSION . ' crashed' . PHP_EOL . PHP_EOL;
	echo "Fatal error occured while cleaning up cache:" . PHP_EOL;
	echo "  " . $x->getMessage() . PHP_EOL;
	echo PHP_EOL . PHP_EOL;
	echo $x->getTraceAsString();
	echo PHP_EOL . PHP_EOL;
	die();
} # catch

## Retention cleanup
try {
	if ($settings->get('retention') > 0) {
		$db->deleteSpotsRetention($settings->get('retention'));
	} # if
} catch(Exception $x) {
	echo PHP_EOL . PHP_EOL;
	echo 'SpotWeb v' . SPOTWEB_VERSION . ' crashed' . PHP_EOL . PHP_EOL;
	echo "Fatal error occured while cleaning up messages due to retention:" . PHP_EOL;
	echo "  " . $x->getMessage() . PHP_EOL;
	echo PHP_EOL . PHP_EOL;
	echo $x->getTraceAsString();
	echo PHP_EOL . PHP_EOL;
	die();
} # catch

## External blacklist
$settings_external_blacklist = $settings->get('external_blacklist');
if ($settings_external_blacklist) {
	try {
		$spotsOverview = new SpotsOverview($db, $settings);

		# haal de blacklist op
		list($http_code, $http_headers, $blacklist) = $spotsOverview->getFromWeb($settings->get('blacklist_url'), 30*60, true);
		$blacklistishtml = strpos($blacklist,">");
		$blacklist = str_replace(chr(13),"",$blacklist);
		$blacklistarray = explode(chr(10),$blacklist);
		
		# Is het bestand dat we opgehaald hebben een echte blacklist? 
		if ($http_code == 304) {
			echo "Blacklist not modified, no need to update" . PHP_EOL;
		} elseif ((strlen($blacklistarray[0]) < 4) || (strlen($blacklistarray[0]) > 7) || ($blacklistishtml)) {
			echo "Error, can't update blacklist!" . PHP_EOL;
		} else {
			foreach ($blacklistarray as $blacklistuserid) {
				$db->addExternalToBlacklist($blacklistuserid);
			}
			# verwijder userid's die niet meer op de blacklist staan
			$db->removeOldBlackList();
			echo "Finished updating blacklist" . PHP_EOL;
		}
	} catch(Exception $x) {
		echo "Fatal error occured while updating blacklist:" . PHP_EOL;
		echo "  " . $x->getMessage() . PHP_EOL;
		echo PHP_EOL . PHP_EOL;
		echo $x->getTraceAsString();
		echo PHP_EOL . PHP_EOL;
	}
} elseif ($settings_external_blacklist == "remove") {
	$db->removeOldBlackList();
	echo "Finished removing blacklist" . PHP_EOL;
} # if

# Verstuur notificaties
$spotsNotifications = new SpotNotifications($db, $settings, $userSession);
$spotsNotifications->sendRetrieverFinished($newSpotCount, $newCommentCount, $newReportCount);

if ($req->getDef('output', '') == 'xml') {
	echo "</xml>";
} # if
