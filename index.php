<?php
error_reporting(E_ALL & ~8192 & ~E_USER_WARNING);	# 8192 == E_DEPRECATED maar PHP < 5.3 heeft die niet

require_once "lib/SpotDb.php";
require_once "lib/SpotReq.php";
require_once "lib/SpotParser.php";
require_once "lib/SpotsOverview.php";
require_once "SpotCategories.php";
require_once "lib/SpotNntp.php";

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
} # initialize()

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

function template($tpl, $params = array()) {
	extract($GLOBALS['site'], EXTR_REFS);
	extract($params, EXTR_REFS);
	
	require_once($settings['tpl_path'] . $tpl . '.inc.php');
} # template

function categoriesToJson() {
	echo "[";
	
	$hcatList = array();
	foreach(SpotCategories::$_head_categories as $hcat_key => $hcat_val) {
		$hcatTmp = '{"title": "' . $hcat_val . '", "isFolder": true, "key": "cat' . $hcat_key . '",	"children": [' ;
				
		$subcatDesc = array();
		foreach(SpotCategories::$_subcat_descriptions[$hcat_key] as $sclist_key => $sclist_desc) {
			$subcatTmp = '{"title": "' . $sclist_desc . '", "isFolder": true, "hideCheckbox": true, "key": "cat' . $hcat_key . '_' . $sclist_key . '", "unselectable": false, "children": [';
			# echo ".." . $sclist_desc . " <br>";

			$catList = array();
			foreach(SpotCategories::$_categories[$hcat_key][$sclist_key] as $key => $val) {
				if ((strlen($val) != 0) && (strlen($key) != 0)) {
					$catList[] = '{"title": "' . $val . '", "icon": false, "key":"'. 'cat' . $hcat_key . '_' . $sclist_key.$key .'"}';
				} # if
			} # foreach
			$subcatTmp .= join(",", $catList);
			
			$subcatDesc[] = $subcatTmp . "]}";
		} # foreach

		$hcatList[] = $hcatTmp . join(",", $subcatDesc) . "]}";
	} # foreach	
	
	echo join(",", $hcatList);
	echo "]";
} # categoriesToJson

#- main() -#
initialize();
extract($site, EXTR_REFS);

switch($site['page']) {
	case 'index' : {

		$db = openDb();
		$spotsOverview = new SpotsOverview($db);
		$filter = $spotsOverview->filterToQuery($req->getDef('search', $settings['index_filter']));

		# Haal de offset uit de URL en zet deze als startid voor de volgende zoektocht
		# Als de offset niet in de url staat, zet de waarde als 0, het is de eerste keer
		# dat de index pagina wordt aangeroepen
		$pageNr = $req->getDef('page', 0);
		$nextPage = $pageNr + 1;
		if ($nextPage == 1) {
			$prevPage = -1;
		} else {
			$prevPage = max($pageNr - 1, 0);
		} # else
		
		# laad de spots
		$spotsTmp = $spotsOverview->loadSpots($pageNr, $prefs['perpage'], $filter);
		$spots = $spotsTmp['list'];

		$spotCnt = count($spots);
		for ($i = 0; $i < $spotCnt; $i++) {
			if (isset($settings['sabnzbd']['apikey'])) {
				$spots[$i]['sabnzbdurl'] = sabnzbdurl($spots[$i]);
			} # if

			$spots[$i]['searchurl'] = makesearchurl($spots[$i]);
		} # for

		# als er geen volgende pagina is, ook niet tonen
		if (!$spotsTmp['hasmore']) {
			$nextPage = -1;
		} # if
		
		# zet de page title
		$pagetitle .= "overzicht";

		#- display stuff -#
		template('header');
		template('filters', array('search' => $req->getDef('search', array()),
								  'filters' => $settings['filters']));
		template('spots', array('spots' => $spots,
		                        'nextPage' => $nextPage,
								'prevPage' => $prevPage,
								'activefilter' => $req->getDef('search', $settings['index_filter'])));
		template('footer');
		break;
	} # case index
	
	case 'catsjson' : {
		categoriesToJson();
		break;
	} # catsjson

	case 'getspot' : {
		try {
			$spotnntp = new SpotNntp($settings['nntp_hdr']['host'],
									 $settings['nntp_hdr']['enc'],
									 $settings['nntp_hdr']['port'],
									 $settings['nntp_hdr']['user'],
									 $settings['nntp_hdr']['pass']);
			$spotnntp->connect();
			$header = $spotnntp->getFullSpot($req->getDef('messageid', ''));
			
			$xmlar['spot'] = $header['info'];
			$xmlar['messageid'] = $req->getDef('messageid', '');
			$xmlar['spot']['sabnzbdurl'] = sabnzbdurl($xmlar['spot']);
			$xmlar['spot']['searchurl'] = makesearchurl($xmlar['spot']);
			$xmlar['spot']['messageid'] = $xmlar['messageid'];
			$xmlar['spot']['userid'] = $header['userid'];
			$xmlar['spot']['verified'] = $header['verified'];

			# Vraag een lijst op met alle comments messageid's
			$db = openDb();
			$commentList = $db->getCommentRef($xmlar['messageid']);
			$comments = $spotnntp->getComments($commentList);
			
			# zet de page title
			$pagetitle .= "spot: " . $xmlar['spot']['title'];
		
			#- display stuff -#
			template('header');
			template('spotinfo', array('spot' => $xmlar['spot'], 'rawspot' => $xmlar, 'comments' => $comments));
			template('footer');
			
			break;
		} 
		catch (Exception $x) {
			die($x->getMessage());
		} # else
	} # getspot
	
	case 'getnzb' : {
		try {
			$hdr_spotnntp = new SpotNntp($settings['nntp_hdr']['host'],
										$settings['nntp_hdr']['enc'],
										$settings['nntp_hdr']['port'],
										$settings['nntp_hdr']['user'],
										$settings['nntp_hdr']['pass']);
			if ($settings['nntp_hdr']['host'] == $settings['nntp_nzb']['host']) {
				$hdr_spotnntp->connect();
				$nzb_spotnntp = $hdr_spotnntp;
			} else {
				$nzb_spotnntp = new SpotNntp($settings['nntp_nzb']['host'],
											$settings['nntp_nzb']['enc'],
											$settings['nntp_nzb']['port'],
											$settings['nntp_nzb']['user'],
											$settings['nntp_nzb']['pass']);
				$hdr_spotnntp->connect(); 
				$nzb_spotnntp->connect(); 
			} # else
		
			$xmlar = $hdr_spotnntp->getFullSpot($req->getDef('messageid', ''));
			$nzb = $nzb_spotnntp->getNzb($xmlar['info']['segment']);
			
			if ($settings['nzb_download_local'] == true)
			{
				$myFile = $settings['nzb_local_queue_dir'] .$xmlar['info']['title'] . ".nzb";
				$fh = fopen($myFile, 'w') or die("Unable to open file");
				fwrite($fh, $nzb);
				fclose($fh);
				echo "NZB toegevoegd aan queue : ".$myFile;
			} else {
				Header("Content-Type: application/x-nzb");
				Header("Content-Disposition: attachment; filename=\"" . $xmlar['info']['title'] . ".nzb\"");
				echo $nzb;
			}
		} 
		catch(Exception $x) {
			die($x->getMessage());
		} # catch
		
		break;
	} # getnzb 
}

