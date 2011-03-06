<?php
error_reporting(E_ALL & ~8192 & ~E_USER_WARNING);	# 8192 == E_DEPRECATED maar PHP < 5.3 heeft die niet

require_once "lib/SpotDb.php";
require_once "lib/SpotReq.php";
require_once "lib/SpotParser.php";
require_once "lib/SpotsOverview.php";
require_once "SpotCategories.php";
require_once "lib/SpotNntp.php";

function openDb() {
	extract($GLOBALS['site'], EXTR_REFS);

	# fireup the database
	try {
		$db = new SpotDb($settings['db']);
		$db->connect();
	} 
	catch(Exception $x) {
		die('Unable to open database: ' . $x->getMessage());
	} # catch

	$GLOBALS['site']['db'] = $db;
	
	return $db;
} # openDb

function initialize() {
	require_once "settings.php";
	$settings = $GLOBALS['settings'];

	# we define some preferences, later these could be
	# user specific or stored in a cookie or something
	$prefs = array('perpage' => 100);
	if (isset($settings['prefs'])) {
		$prefs = array_merge($prefs, $settings['prefs']);
	} # if
		
	# helper functions for passed variables
	$req = new SpotReq();
	$req->initialize();

	# gather the current page
	$GLOBALS['site']['page'] = $req->getDef('page', 'index');
	if (array_search($GLOBALS['site']['page'], array('index', 'catsjson', 'getnzb', 'getspot')) === false) {
		$GLOBALS['site']['page'] = 'index';
	} # if
	
	# and put them in an encompassing site object
	$GLOBALS['site']['req'] = $req;
	$GLOBALS['site']['settings'] = $settings;
	$GLOBALS['site']['prefs'] = $prefs;
	$GLOBALS['site']['pagetitle'] = 'SpotWeb - ';
	$GLOBALS['site']['db'] = openDb();
} # initialize()


function sabnzbdurl($spot) {
	extract($GLOBALS['site'], EXTR_REFS);

	# alleen draaien als we gedefinieerd zijn
	if ((!isset($settings['sabnzbd'])) | (!isset($settings['sabnzbd']['apikey'])) | (!isset($settings['sabnzbd']['categories']))) {
		return '';
	} # if
	
	# fix de category
	$spot['category'] = (int) $spot['category'];
	
	# find een geschikte category
	$category = $settings['sabnzbd']['categories'][$spot['category']]['default'];

	foreach($spot['subcatlist'] as $cat) {
		if (isset($settings['sabnzbd']['categories'][$spot['category']][$cat])) {
			$category = $settings['sabnzbd']['categories'][$spot['category']][$cat];
		} # if
	} # foreach
	
	# en creeer die sabnzbd url
	$tmp = $settings['sabnzbd']['url'];
	$tmp = str_replace('$SABNZBDHOST', $settings['sabnzbd']['host'], $tmp);
	$tmp = str_replace('$NZBURL', urlencode($settings['sabnzbd']['spotweburl'] . '?page=getnzb&messageid='. $spot['messageid']), $tmp);
	$tmp = str_replace('$SPOTTITLE', urlencode($spot['title']), $tmp);
	$tmp = str_replace('$SANZBDCAT', $category, $tmp);
	$tmp = str_replace('$APIKEY', $settings['sabnzbd']['apikey'], $tmp);

	return $tmp;
} # sabnzbdurl

function makesearchurl($spot) {
	extract($GLOBALS['site'], EXTR_REFS);

	if (!isset($spot['filename'])) {
		$tmp = str_replace('$SPOTFNAME', $spot['title'], $settings['search_url']);
	} else {
		$tmp = str_replace('$SPOTFNAME', $spot['filename'], $settings['search_url']);
	} # else 

	return $tmp;
} # makesearchurl

function showPage($page) {
	extract($GLOBALS['site'], EXTR_REFS);

	require_once "lib/page/SpotPage_" . $page . ".php";
	
	$className = "SpotPage_" . $page;
	
	$page = new $className($db, $settings, $prefs, $req);
	$page->render();
} # showPage()


#- main() -#
initialize();
extract($site, EXTR_REFS);
showPage($site['page']);
