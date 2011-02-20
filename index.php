<?php
require_once "db.php";
require_once "req.php";
require_once "SpotParser.php";
require_once "SpotCategories.php";
require_once "SpotNntp.php";

function initialize() {
	require_once "settings.php";
	$settings = $GLOBALS['settings'];

	# we define some preferences, later these could be
	# user specific or stored in a cookie or something
	$prefs['perpage'] = 1000;
		
	# helper functions for passed variables
	$req = new Req();
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
} # initialize()

function openDb() {
	extract($GLOBALS['site'], EXTR_REFS);

	# fireup the database
	$db = new db($settings['sqlite3_path']);

	$GLOBALS['site']['db'] = $db;
	
	return $db;
} # openDb]

function loadSpots($start, $sqlFilter) {
	extract($GLOBALS['site'], EXTR_REFS);
	
	return $db->getSpots($start, $prefs['perpage'], $sqlFilter);
} # loadSpots()

function filterToQuery($search, $dynatree) {
	extract($GLOBALS['site'], EXTR_REFS);
	$filterList = array();

	# dont filter anything
	if (empty($search) && (empty($dynatree))) {
		return '';
	}

	# convert the dynatree list to a list 
	$dyn2search = array();
	if (!empty($dynatree)) {
		$dynaList = explode(',', $dynatree);
		foreach($dynaList as $val) {
			if (substr($val, 0, 3) == 'cat') {
				$val = explode('_', (substr($val, 3) . '_'));

				if (count($val) >= 3) {
					$dyn2search['cat'][$val[0]][] = 'o' . $val[1];
				} else {
					$dyn2search['cat'][$val[0]][] = $val[0];
				}
			} # if
		} # foreach
	} # if

	# merge the actual search array and the categories selected in the
	# tree
	$search = array_merge($search, $dyn2search);
	
	# Add a list of possible head categories
	if ((isset($search['cat'])) && ((is_array($search['cat'])))) {
		$filterList = array();

		foreach($search['cat'] as $catid => $cat) {
			$catid = (int) $catid;
			$tmpStr = "(";
			$tmpStr .= "(category = " . $catid . ")";
			
			# Now start adding the sub categories
			if ((is_array($cat)) && (!empty($cat))) {
				$subcatStr = " AND (";
				$subcatCounter = 0;
					
				foreach($cat as $subcat) {
					# split up the subcat
					$operator = ' LIKE ';
					$seloper = ' OR ';
					
					# OR or AND ?
					if (substr($subcat, 0, 1) == 'a') {
						$seloper = ' AND ';
					} # if
					$subcat = substr($subcat, 1);			
					
					# NOT for this subcategory?
					if (substr($subcat, 0, 1) == '!') {
						$subcat = substr($subcat, 1);
						$operator = ' NOT LIKE ';
					} # if
					
					# extract the category type
					$catType = substr($subcat, 0, 1);
					$subcat = ((int) (substr($subcat, 1))) . '|';

					if (array_search($catType, array('a','b','c','d')) !== false) {
						if ($subcatCounter == 0) {
							$seloper = '';
						} # if
						
						$subcatStr .= $seloper . "subcat" . $catType . $operator . "'%" . $catType . $subcat . "%'";
						$subcatCounter++;
					} # if
				} # foreach subcat
				
				$subcatStr .= ")";
			} # if
			
			if ($subcatCounter > 0) {
				$tmpStr .= $subcatStr;
			} # if
			
			# close the opening parenthesis from this category filter
			$tmpStr .= ")";
			$filterList[] = $tmpStr;
		} # foreach
	} # if

	# Add a list of possible head categories
	$textSearch = '';
	if ((!empty($search['text'])) && ((isset($search['type'])))) {
		$field = 'title';
	
		if ($search['type'] == 'Tag') {
			$field = 'tag';
		} else if ($search['type'] == 'Poster') {
			$field = 'poster';
		} # else
		
		$textSearch .= ' (' . $field . " LIKE '%" . $db->safe($search['text']) . "%')";
	} # if

	if (!empty($filterList)) {
		# echo  '(' . (join(' OR ', $filterList) . ') ' . (empty($textSearch) ? "" : " AND " . $textSearch));
		return '(' . (join(' OR ', $filterList) . ') ' . (empty($textSearch) ? "" : " AND " . $textSearch));
	} else {
		return $textSearch;
	} # if
} # filterToQuery

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
			$subcatTmp = '{"title": "' . $sclist_desc . '", "isFolder": true, "hideCheckbox": true, "unselectable": true, "children": [';
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

		openDb();
		$filter = filterToQuery($req->getDef('search', array()),
								$req->getDef('dynatree-select', array()));
		$spots = loadSpots($prefs['perpage'], $filter);

		#- display stuff -#
		template('header');
		template('filters', array('search' => $req->getDef('search', array())));
		template('spots', array('spots' => $spots));
		template('footer');
		break;
	} # case index
	
	case 'catsjson' : {
		categoriesToJson();
		break;
	} # catsjson

	case 'getspot' : {
		$db = openDb();
		$spot = $db->getSpot(Req::getDef('messageid', ''));
		
		$spot = $spot[0];
		
		$spotnntp = new SpotNntp($settings['nntp_host'],
								 $settings['nntp_enc'],
								 $settings['nntp_port'],
								 $settings['nntp_user'],
								 $settings['nntp_pass']);
		if ($spotnntp->connect()) {
			$header = $spotnntp->getHeader('<' . $spot['messageid'] . '>');
			
			$xml = '';
			if ($header !== false) {
				foreach($header as $str) {
					if (substr($str, 0, 7) == 'X-XML: ') {
						$xml .= substr($str, 7);
					} # if
				} # foreach
			} # if
			
			$spotParser = new SpotParser();
			$xmlar = $spotParser->parseFull($xml);
			$xmlar['messageid'] = Req::getDef('messageid', '');

			#- display stuff -#
			template('header');
			template('spotinfo', array('spot' => $xmlar));
			template('footer');
			
			break;
		} else {
			die("Unable to connect to NNTP server");
		} # else
	} # getspot
	
	case 'getnzb' : {
		$db = openDb();
		$spot = $db->getSpot(Req::getDef('messageid', ''));
		$spot = $spot[0];
		
		$spotnntp = new SpotNntp($settings['nntp_host'],
								 $settings['nntp_enc'],
								 $settings['nntp_port'],
								 $settings['nntp_user'],
								 $settings['nntp_pass']);
		if ($spotnntp->connect()) {
			$header = $spotnntp->getHeader('<' . $spot['messageid'] . '>');
			
			$xml = '';
			if ($header !== false) {
				foreach($header as $str) {
					if (substr($str, 0, 7) == 'X-XML: ') {
						$xml .= substr($str, 7);
					} # if
				} # foreach
			} # if
			
			$spotParser = new SpotParser();
			$xmlar = $spotParser->parseFull($xml);
			
			/* Get the NZB file */
			$nzb = false;
			if (is_array($xmlar['segment'])) {
				foreach($xmlar['segment'] as $seg) {
					$tmp = $spotnntp->getBody("<" . $seg . ">");
					
					if ($tmp !== false) {
						$nzb .= implode("", $tmp);
					} else {
						break;
					} #else
				} # foreach
			} else {
				$tmp = $spotnntp->getBody("<" . $xmlar['segment'] . ">");
				if ($tmp !== false) {
					$nzb .= implode("", $tmp);
				} # if
			} # if
			
			if ($nzb !== false) {
				Header("Content-Type: application/x-nzb");
				Header("Content-Disposition: attachment; filename=\"" . $xmlar['title'] . ".nzb\"");
				echo gzinflate($spotParser->unspecialZipStr($nzb));
			} else {
				echo "Unable to get NZB file: " . $spotnntp->getError();
			} # else
		} # if
		
		break;
	} # getnzb 
}