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

require_once "lib/SpotTranslation.php";
require_once "lib/SpotClassAutoload.php";
require_once "settings.php";
require_once "lib/SpotTiming.php";
require_once "lib/exceptions/ParseSpotXmlException.php";
require_once "lib/exceptions/NntpException.php";

# disable timing, met alle queries die er draaien loopt dat uit op een te grote memory usage
SpotTiming::disable();

# Initialize translation to english 
SpotTranslation::initialize('en_US');

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
	
	# Add the user's ip addres, we need it for sending notifications
	$userSession['session'] = array('ipaddr' => '');
} else {
	$userSession['user'] = $db->getUser(SPOTWEB_ADMIN_USERID);
	$userSession['security'] = new SpotSecurity($db, $settings, $userSession['user'], '');
	$userSession['session'] = array('ipaddr' => '');
} # if

if ($req->getDef('output', '') == 'xml') {
	echo "<xml>";
} # if

# We vragen de nntp_hdr settings alvast op
$settings_nntp_hdr = $settings->get('nntp_hdr');
if (empty($settings_nntp_hdr['host'])) {
	die("Unable to continue: You did not setup any newsserver yet." . PHP_EOL);
} # if
	
## Als we forceren om de "already running" check te bypassen, doe dat dan
if ((isset($argc)) && ($argc > 1) && ($argv[1] == '--force')) {
	$db->setRetrieverRunning($settings_nntp_hdr['host'], false);
} # if

## Moeten we debugloggen? Kan alleen als geen --force opgegeven wordt
$debugLog = ((isset($argc)) && ($argc > 1) && ($argv[1] == '--debug'));

## RETRO MODE! Hiermee kunnen we de fullspots, fullcomments en/of cache achteraf ophalen
$retroMode = ((isset($argc)) && ($argc > 1) && ($argv[1] == '--retro'));

## Retention cleanup
try {
	if ($settings->get('retention') > 0 && !$retroMode) {
		$db->deleteSpotsRetention($settings->get('retention'));
	} # if
} catch(Exception $x) {
	echo PHP_EOL . PHP_EOL;
	echo 'SpotWeb v' . SPOTWEB_VERSION . ' on PHP v' . PHP_VERSION . ' crashed' . PHP_EOL . PHP_EOL;
	echo "Fatal error occured while cleaning up messages due to retention:" . PHP_EOL;
	echo "  " . $x->getMessage() . PHP_EOL;
	echo PHP_EOL . PHP_EOL;
	echo $x->getTraceAsString();
	echo PHP_EOL . PHP_EOL;
	die();
} # catch

## Spots
try {
	$rsaKeys = $settings->get('rsa_keys');
	$retriever = new SpotRetriever_Spots($settings_nntp_hdr, 
										 $db, 
										 $settings,										 
										 $rsaKeys, 
										 $req->getDef('output', ''),
										 $settings->get('retrieve_full'),
										 $debugLog,
										 $retroMode);
	$msgdata = $retriever->connect($settings->get('hdr_group'));
	$retriever->displayStatus('dbcount', $db->getSpotCount(''));

	if ($retroMode) {
		$curMsg = $db->getMaxArticleId('spots_retro');
	} else {
		$curMsg = $db->getMaxArticleId($settings_nntp_hdr['host']);
	} # if
	
	if ($curMsg != 0 && !$retroMode) {
		$curMsg = $retriever->searchMessageId($db->getMaxMessageId('headers'));
		
		if ($settings_nntp_hdr['buggy']) {
			$curMsg = max(1, $curMsg - 15000);
		} # if
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
	echo 'SpotWeb v' . SPOTWEB_VERSION . ' on PHP v' . PHP_VERSION . ' crashed' . PHP_EOL . PHP_EOL;
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
	echo 'SpotWeb v' . SPOTWEB_VERSION . ' on PHP v' . PHP_VERSION . ' crashed' . PHP_EOL . PHP_EOL;
	echo "Fatal error occured retrieving messages:" . PHP_EOL;
	echo "  " . $x->getMessage() . PHP_EOL;
	echo PHP_EOL . PHP_EOL;
	echo $x->getTraceAsString();
	echo PHP_EOL . PHP_EOL;
	die();
} # catch

## Creating filter counts
if ($newSpotCount > 0) {
	$spotsOverview = new SpotsOverview($db, $settings);
	echo 'Calculating how many spots are new';
	$spotsOverview->cacheNewSpotCount();
	echo ', done.' . PHP_EOL;
} # if

## Comments
try {
	$newCommentCount = 0;
	if ($settings->get('retrieve_comments')) {
		$retriever = new SpotRetriever_Comments($settings_nntp_hdr, 
												$db,
												$settings,
												$req->getDef('output', ''),
												$settings->get('retrieve_full_comments'),
												$debugLog,
												$retroMode);
		$msgdata = $retriever->connect($settings->get('comment_group'));

		if ($retroMode) {
			$curMsg = $db->getMaxArticleId('comments_retro');
		} else {
			$curMsg = $db->getMaxArticleId('comments');
		} # if

		if ($settings_nntp_hdr['buggy']) {
			$curMsg = max(1, $curMsg - 15000);
		} # if

		if ($curMsg != 0 && !$retroMode) {
			$curMsg = $retriever->searchMessageId($db->getMaxMessageId('comments'));
		} # if

		$newCommentCount = $retriever->loopTillEnd($curMsg, $settings->get('retrieve_increment'));
		$retriever->quit();
	} # if
}
catch(NntpException $x) {
	echo PHP_EOL . PHP_EOL;
	echo 'SpotWeb v' . SPOTWEB_VERSION . ' on PHP v' . PHP_VERSION . ' crashed' . PHP_EOL . PHP_EOL;
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
	echo 'SpotWeb v' . SPOTWEB_VERSION . ' on PHP v' . PHP_VERSION . ' crashed' . PHP_EOL . PHP_EOL;
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
	if ($settings->get('retrieve_reports') && !$retroMode) {
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

		if ($settings_nntp_hdr['buggy']) {
			$curMsg = max(1, $curMsg - 15000);
		} # if
		
		$newReportCount = $retriever->loopTillEnd($curMsg, $settings->get('retrieve_increment'));
		$retriever->quit();
	} # if
}
catch(NntpException $x) {
	echo PHP_EOL . PHP_EOL;
	echo 'SpotWeb v' . SPOTWEB_VERSION . ' on PHP v' . PHP_VERSION . ' crashed' . PHP_EOL . PHP_EOL;
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
	echo 'SpotWeb v' . SPOTWEB_VERSION . ' on PHP v' . PHP_VERSION . ' crashed' . PHP_EOL . PHP_EOL;
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
	echo 'SpotWeb v' . SPOTWEB_VERSION . ' on PHP v' . PHP_VERSION . ' crashed' . PHP_EOL . PHP_EOL;
	echo "Fatal error occured while cleaning up lists:" . PHP_EOL;
	echo "  " . $x->getMessage() . PHP_EOL;
	echo PHP_EOL . PHP_EOL;
	echo $x->getTraceAsString();
	echo PHP_EOL . PHP_EOL;
	die();
} # catch

## cache cleanup
try {
	if (!$retroMode) {
		$db->cleanCache(30);
	} # if
} catch(Exception $x) {
	echo PHP_EOL . PHP_EOL;
	echo 'SpotWeb v' . SPOTWEB_VERSION . ' on PHP v' . PHP_VERSION . ' crashed' . PHP_EOL . PHP_EOL;
	echo "Fatal error occured while cleaning up cache:" . PHP_EOL;
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
		list($http_code, $blacklist) = $spotsOverview->getFromWeb($settings->get('blacklist_url'), false, 30*60);

		if ($http_code == 304) {
			echo "Blacklist not modified, no need to update" . PHP_EOL;
		} elseif (strpos($blacklist['content'],">")) {
			echo "Error, blacklist does not have expected layout!" . PHP_EOL;
		} else {
			# update de blacklist
			$blacklistarray = explode(chr(10),$blacklist['content']);
			
			# Perform a very small snaity check on the blacklist
			if ((count($blacklistarray) > 5) && (strlen($blacklistarray[0]) < 10)) {
				$updateblacklist = $db->updateExternalBlacklist($blacklistarray);
				echo "Finished updating blacklist. Added " . $updateblacklist['added'] . ", removed " . $updateblacklist['removed'] . ", skipped " . $updateblacklist['skipped'] . " of " . count($blacklistarray) . " lines." . PHP_EOL;
			} else {
				echo "Blacklist is probably corrupt, skipping" . PHP_EOL;
			} # else				
		}
	} catch(Exception $x) {
		echo "Fatal error occured while updating blacklist:" . PHP_EOL;
		echo "  " . $x->getMessage() . PHP_EOL;
		echo PHP_EOL . PHP_EOL;
		echo $x->getTraceAsString();
		echo PHP_EOL . PHP_EOL;
	}
} # if

## Statistics
if ($settings->get('prepare_statistics') && $newSpotCount > 0) {
	$spotsOverview = new SpotsOverview($db, $settings);
	$spotImage = new SpotImage($db);
	$spotsOverview->setActiveRetriever(true);

	echo "Starting to create statistics " . PHP_EOL;
	foreach ($spotImage->getValidStatisticsLimits() as $limitValue => $limitName) {
		# Reset timelimit
		set_time_limit(60);

		foreach($settings->get('system_languages') as $language => $name) {
			foreach ($spotImage->getValidStatisticsGraphs() as $graphValue => $graphName) {
				$spotsOverview->getStatisticsImage($graphValue, $limitValue, $settings_nntp_hdr, $language);
			} # foreach graph
		} # foreach language
		echo "Finished creating statistics " . $limitName . PHP_EOL;
	} # foreach limit

	echo PHP_EOL;
} # if

# Verstuur notificaties
$spotsNotifications = new SpotNotifications($db, $settings, $userSession);
$spotsNotifications->sendRetrieverFinished($newSpotCount, $newCommentCount, $newReportCount);

if ($req->getDef('output', '') == 'xml') {
	echo "</xml>";
} # if
