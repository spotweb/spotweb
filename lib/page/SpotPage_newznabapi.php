<?php
class SpotPage_newznabapi extends SpotPage_Abs {
	private $_params;

	function __construct(SpotDb $db, SpotSettings $settings, $currentSession, $params) {
		parent::__construct($db, $settings, $currentSession);

		$this->_params = $params;
	} # __construct

	function render() {
		# CAPS function is used to query the server for supported features and the protocol version and other 
		# meta data relevant to the implementation. This function doesn't require the client to provide any
		# login information but can be executed out of "login session".
		if ($this->_params['t'] == "caps" || $this->_params['t'] == "c") {
			$this->caps();
			die();
		} # if

		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_spots_index, '');

		$outputtype = ($this->_params['o'] == "json") ? "json" : "xml";

		switch ($this->_params['t']) {
			case ""			: $this->showApiError(200); break;
			case "search"	:
			case "s"		:
			case "tvsearch"	:
			case "t"		:
			case "movie"	:
			case "m"		: $this->search($outputtype); break;
			default			: $this->showApiError(202);
		} # switch
	} # render()

	function caps() {
		header('Content-Type: text/xml; charset=UTF-8');
		echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?".">" . PHP_EOL;
		echo "<caps>" . PHP_EOL;
		echo "\t<server appversion=\"" . SPOTDB_SCHEMA_VERSION . "\" version=\"0.1\" title=\"Spotweb\" strapline=\"Spotweb API Index\" email=\"" . "spotweb@example.com (Spotweb Index)" . "\" url=\"" . $this->_settings->get('spotweburl') . "\" image=\"" . $this->_settings->get('spotweburl') . "images/spotnet.gif\" />" . PHP_EOL;
		echo "\t<limits max=\"500\" default=\"" . $this->_currentSession['user']['prefs']['perpage'] . "\"/>" . PHP_EOL;
		if ($this->_settings->get('retention') > 0) { echo "\t<retention days=\"" . $this->_settings->get('retention') . "\"/>" . PHP_EOL; }
		echo "\t<registration available=\"no\" open=\"no\"/>" . PHP_EOL . PHP_EOL;

		echo "\t<searching>" . PHP_EOL;
		echo "\t\t<search available=\"yes\"/>" . PHP_EOL;
		echo "\t\t<tv-search available=\"yes\"/>" . PHP_EOL;
		echo "\t\t<movie-search available=\"yes\"/>" . PHP_EOL;
		echo "\t\t<audio-search available=\"yes\"/>" . PHP_EOL;
		echo "\t</searching>" . PHP_EOL . PHP_EOL;
		echo "\t<categories>";

		foreach($this->categories() as $category) {
			echo PHP_EOL . "\t\t<category id=\"" . $category['cat'] . "\" name=\"" . $category['name'] . "\">" . PHP_EOL;

			foreach($category['subcat'] as $name => $subcat) {
				echo "\t\t\t<subcat id=\"" . $subcat . "\" name=\"" . $name . "\"/>" . PHP_EOL;
			} # foreach

			echo "\t\t</category>" . PHP_EOL;
		} # foreach

		echo "\t</categories>" . PHP_EOL;
		echo "</caps>";
	} # caps

	function search($outputtype) {
		$spotsOverview = new SpotsOverview($this->_db, $this->_settings);
		$search = array();

		if ($this->_params['q'] == "" && (($this->_params['t'] == "m" || $this->_params['t'] == "movie") && $this->_params['imdbid'] == "")) $this->showApiError(200);
		if ($this->_params['q'] == "" && (($this->_params['t'] == "t" || $this->_params['t'] == "tvsearch") && $this->_params['rid'] == "")) $this->showApiError(200);

		if (($this->_params['t'] == "t" || $this->_params['t'] == "tvsearch")) {
			$tvrage_content = file_get_contents('http://services.tvrage.com/feeds/showinfo.php?sid=' . $this->_params['rid'] . '/');
			preg_match('/<showname>(.*)<\/showname>/isU', $tvrage_content, $showTitle);
			$tvSearch = "Titel:\"" . trim($showTitle[1]) . "\"";

			if ($this->_params['season'] != "")
				$tvSearch .= (is_numeric($this->_params['season'])) ? ' AND S' . str_pad($this->_params['season'], 2, "0", STR_PAD_LEFT) : ' AND ' . $this->_params['season'];

			if ($this->_params['ep'] != "")
				$tvSearch .= (is_numeric($this->_params['ep'])) ? ' AND E' . str_pad($this->_params['ep'], 2, "0", STR_PAD_LEFT) : ' AND ' . $this->_params['ep'];

			$search['value'][] = $tvSearch;
		} elseif ($this->_params['t'] == "m" || $this->_params['t'] == "movie") {
			$imdb_content = file_get_contents('http://uk.imdb.com/title/tt' . $this->_params['imdbid'] . '/');
			preg_match('/<title>(.*) - IMDb<\/title>/isU', $imdb_content, $movieTitle);
			$movieTitle = preg_replace('/\([0-9]+\)/', '', $movieTitle[1]);
			$search['value'][] = "Titel:\"" . trim($movieTitle) . "\"";
		} else {
			$search['value'][] = "Titel:" . $this->_params['q'];
		} # else

		if ($this->_params['maxage'] != "" && is_numeric($this->_params['maxage']))
			$search['value'][] = "date:>:-" . $this->_params['maxage'] . "days";

		$search['tree'] = $this->nabcat2spotcat($this->_params['cat']);

		$limit = $this->_currentSession['user']['prefs']['perpage'];
		if ($this->_params['limit'] != "" && is_numeric($this->_params['limit']) && $this->_params['limit'] < 500)
			$limit = $this->_params['limit'];

		$pageNr = ($this->_params['offset'] != "" && is_numeric($this->_params['offset'])) ? $this->_params['offset'] : 0;
		$offset = $pageNr*$limit;

		$parsedSearch = $spotsOverview->filterToQuery($search, $this->_currentSession);
		$spots = $spotsOverview->loadSpots($this->_currentSession['user']['userid'],
						$pageNr,
						$limit,
						$parsedSearch,
						array('field' => 'stamp', 'direction' => 'DESC'));

		$this->showResults($spots, $offset, $outputtype);
	} # search

	function showResults($spots, $offset, $outputtype) {
		$nzbhandling = $this->_settings->get('nzbhandling');

		if ($outputtype == "json") {
			echo json_encode($spots); //TODO:make that a more specific array of data to return rather than resultset
		} else {
			header('Content-Type: text/xml; charset=UTF-8');
			ob_end_clean();
			echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?".">" . PHP_EOL;
			echo "<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\" xmlns:newznab=\"http://www.newznab.com/DTD/2010/feeds/attributes/\" encoding=\"UTF-8\">" . PHP_EOL;
			echo "<channel>" . PHP_EOL;
			echo "<atom:link href=\"" . $this->_settings->get('spotweburl') . "api\" rel=\"self\" type=\"application/rss+xml\" />" . PHP_EOL;
			echo "<title>Spotweb Index</title>" . PHP_EOL;
			echo "<description>SpotWeb Index API Results</description>" . PHP_EOL;
			echo "<link>" . $this->_settings->get('spotweburl') . "</link>" . PHP_EOL;
			echo "<language>en-gb</language>" . PHP_EOL;
			echo "<webMaster>spotweb@example.com (Spotweb Index)</webMaster>" . PHP_EOL;
			echo "<category></category>" . PHP_EOL;
			echo "<image>" . PHP_EOL;
			echo "\t<url>" . $this->_settings->get('spotweburl') . "images/spotnet.gif</url>" . PHP_EOL;
			echo "\t<title>Spotweb Index</title>" . PHP_EOL;
			echo "\t<link>" . $this->_settings->get('spotweburl') . "</link>" . PHP_EOL;
			echo "\t<description>SpotWeb Index API Results</description>" . PHP_EOL;
			echo "</image>" . PHP_EOL;
			echo "<newznab:response offset=\"" . $offset . "\" total=\"" . count($spots['list']) . "\" />" . PHP_EOL;

			foreach($spots['list'] as $spot) {
				$title = preg_replace(array('/</', '/>/', '/&/'), array('&#x3C;', '&#x3E;', '&#x26;'), $spot['title']);

				echo "<item>" . PHP_EOL;
				echo "\t<title>" . $spot['title'] . "</title>" . PHP_EOL;
				echo "\t<guid isPermaLink=\"true\">" . $this->_tplHelper->makeBaseUrl("full") . "?page=getspot&amp;messageid=" . urlencode($spot['messageid']) . "</guid>" . PHP_EOL;
				echo "\t<link>" . $this->_tplHelper->makeNzbUrl($spot) . "</link>" . PHP_EOL;
				echo "\t<pubDate>" . date('r', $spot['stamp']) . "</pubDate>" . PHP_EOL;
				echo "\t<category>" . SpotCategories::HeadCat2Desc($spot['category']) . " > " . SpotCategories::Cat2ShortDesc($spot['category'], $spot['subcat']) . "</category>" . PHP_EOL;

				switch ($nzbhandling['prepare_action']) {
					case 'zip':	echo "\t<enclosure url=\"" . $this->_tplHelper->makeNzbUrl($spot) . "\" length=\"" . $spot['filesize'] . "\" type=\"application/zip\" />" . PHP_EOL; break;
					default	  : echo "\t<enclosure url=\"" . $this->_tplHelper->makeNzbUrl($spot) . "\" length=\"" . $spot['filesize'] . "\" type=\"application/x-nzb\" />" . PHP_EOL; break;
				} # switch

				echo PHP_EOL;
				$nabCat = explode("|", $this->Cat2NewznabCat($spot['category'], $spot['subcat']));
				if ($nabCat[0] != "") {
					echo "\t<newznab:attr name=\"category\" value=\"" . $nabCat[0] . "\" />" . PHP_EOL;
					echo "\t<newznab:attr name=\"category\" value=\"" . $nabCat[1] . "\" />" . PHP_EOL;
				} # if

				echo "\t<newznab:attr name=\"size\" value=\"" . $spot['filesize'] . "\" />" . PHP_EOL;

				if ($this->_params['extended'] == "1") {
					echo "\t<newznab:attr name=\"poster\" value=\"" . $spot['poster'] . "@spot.net\" />" . PHP_EOL;
					echo "\t<newznab:attr name=\"comments\" value=\"" . $spot['commentcount'] . "\" />" . PHP_EOL;
				} # if

				echo "</item>" . PHP_EOL . PHP_EOL;
			} # foreach

			echo "</channel>" . PHP_EOL;
			echo "</rss>";
			
			$rssData = ob_get_contents();
			echo utf8_encode($rssData);
		}
	} # showResults

	function Cat2NewznabCat($hcat, $cat) {
		$newznabcat = $this->spotcat2nabcat();
		$catList = explode("|", $cat);
		$cat = $catList[0];

		if (empty($cat[0])) {
			return '';
		} # if

		$nr = substr($cat, 1);

		if (!isset($newznabcat[$hcat][$nr])) {
			return "-";
		} else {
			return $newznabcat[$hcat][$nr];
		} # if
	}

	function showApiError($errcode=900, $errtext="") {
		switch ($errcode) {
			case 100: $errtext = "Incorrect user credentials"; break;
			case 101: $errtext = "Account suspended"; break;
			case 102: $errtext = "Insufficient priviledges/not authorized"; break;
			case 103: $errtext = "Registration denied"; break;
			case 104: $errtext = "Registrations are closed"; break;
			case 105: $errtext = "Invalid registration (Email Address Taken)"; break;
			case 106: $errtext = "Invalid registration (Email Address Bad Format)"; break;
			case 107: $errtext = "Registration Failed (Data error)"; break;

			case 200: $errtext = "Missing parameter"; break;
			case 201: $errtext = "Incorrect parameter"; break;
			case 202: $errtext = "No such function"; break;
			case 203: $errtext = "Function not available"; break;

			case 300: $errtext = "No such item"; break;

			case 500: $errtext = "Request limit reached"; break;
			case 501: $errtext = "Download limit reached"; break;
			default: $errtext = "Unknown error"; break;
		} # switch

		header('Content-type: text/xml; charset=UTF-8');
		echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" . PHP_EOL;
		echo "<error code=\"$errcode\" description=\"$errtext\"/>" . PHP_EOL;
		die();
	} # showApiError

	function categories() {
		return array(
				array('name'		=> 'Console',
					  'cat'			=> '1000',
					  'subcat'		=> array('NDS'		=> '1010',
											 'PSP'		=> '1020',
											 'Wii'		=> '1030',
											 'Xbox'		=> '1040',
											 'Xbox 360'	=> '1050',
											 'PS3'		=> '1080')
				), array('name'		=> 'Movies',
						 'cat'		=> '2000',
						 'subcat'	=> array('SD'		=> '2030',
											 'HD'		=> '2040',
											 'Sport'	=> '2060')
				), array('name'		=> 'Audio',
						 'cat'		=> '3000',
						 'subcat'	=> array('MP3'		=> '3010',
											 'Video'	=> '3020',
											 'Lossless'	=> '3040')
				), array('name'		=> 'PC',
						 'cat'		=> '4000',
						 'subcat'	=> array('Mac'		=> '4030',
											 'Phone'	=> '4040',
											 'Games'	=> '4050')
				), array('name'		=> 'TV',
						 'cat'		=> '5000',
						 'subcat'	=> array('SD'		=> '5030',
											 'HD'		=> '5040',
											 'Sport'	=> '5060')
				), array('name'		=> 'XXX',
						 'cat'		=> '6000',
						 'subcat'	=> array('DVD'		=> '6010',
											 'WMV'		=> '6020',
											 'XviD'		=> '6030',
											 'x264'		=> '6040')
				), array('name'		=> 'Other',
						 'cat'		=> '7000',
						 'subcat'	=> array('Ebook'	=> '7020')
				)
		);
	} # categories

	function nabcat2spotcat($cat) {
		switch ($cat) {
			case 1000: return 'cat2_a3,cat2_a4,cat2_a5,cat2_a6,cat2_a7,cat2_a8,cat2_a9,cat2_a10,cat2_a11,cat2_a12';
			case 1010: return 'cat2_a10';
			case 1020: return 'cat2_a5';
			case 1030: return 'cat2_a11';
			case 1040: return 'cat2_a6';
			case 1050: return 'cat2_a7';
			case 1060: return 'cat2_a7';

			case 2000: return 'cat0_z0';
			case 2030: return 'cat0_a0,~cat0_z1,~cat0_z2,~cat0_z3';
			case 2040: return 'cat0_a4,cat0_a6,cat0_a7,cat0_a8,cat0_a9,~cat0_z1,~cat0_z2,~cat0_z3';
			case 2060: return 'cat0_d18';

			case 3000: return 'cat1_a';
			case 3010: return 'cat1_a0,cat1_a3,cat1_a5,cat1_a6';
			case 3020: return 'cat0_d13';
			case 3040: return 'cat1_a2,cat1_a4,cat1_a7,cat1_a8';

			case 4000: return 'cat3_a0';
			case 4030: return 'cat3_a1';
			case 4040: return 'cat3_a4,cat3_a5,cat3_a6,cat3_a7';
			case 4050: return 'cat2_a0,cat2_a1,cat2_a2';

			case 5000: return 'cat0_z1';
			case 5030: return 'cat0_z1,cat0_a0';
			case 5040: return 'cat0_z1,cat0_a4,cat0_a6,cat0_a7,cat0_a8,cat0_a9';
			case 5060: return 'cat0_z1,cat0_d18';

			case 6000: return 'cat0_z3';
			case 6010: return 'cat0_a3,cat0_a10,~cat0_z0,~cat0_z1,~cat0_z2';
			case 6020: return 'cat0_a1,cat0_a8,~cat0_z1,~cat0_z0,~cat0_z1,~cat0_z2';
			case 6030: return 'cat0_a0,~cat0_z0,~cat0_z1,~cat0_z2';
			case 6040: return 'cat0_a4,cat0_a6,cat0_a7,cat0_a8,cat0_a9,~cat0_z0,~cat0_z1,~cat0_z2';

			case 7020: return 'cat0_z2';
		}
	} # nabcat2spotcat

	function spotcat2nabcat() {
		return Array(0 =>
				Array(0 => "2000|2030",
					  1 => "2000|2030",
					  2 => "2000|2030",
					  3 => "2000|2030",
					  4 => "2000|2040",
					  5 => "7000|7020",
					  6 => "2000|2040",
					  7 => "2000|2040",
					  8 => "2000|2040",
					  9 => "2000|2040",
					  10 => "2000|2030"),
			  1 =>
				Array(0	=> "3000|3010",
					  1 => "3000|3010",
					  2 => "3000|3040",
					  3 => "3000|3010",
					  4 => "3000|3040",
					  5 => "3000|3040",
					  6 => "3000|3010",
					  7 => "3000|3040",
					  8 => "3000|3040"),
			  2 =>
				Array(0 => "4000|4050",
					  1 => "4000|4030",
					  2 => "TUX",
					  3 => "PS",
					  4 => "PS2",
					  5 => "1000|1020",
					  6 => "1000|1040",
					  7 => "1000|1050",
					  8 => "GBA",
					  9 => "GC",
					  10 => "1000|1010",
					  11 => "1000|1030",
					  12 => "1000|1080",
					  13 => "4000|4040",
					  14 => "4000|4040",
					  15 => "4000|4040",
					  16 => "3DS"),
			  3 =>
				Array(0 => "4000|4020",
					  1 => "4000|4030",
					  2 => "TUX",
					  3 => "OS/2",
					  4 => "4000|4040",
					  5 => "NAV",
					  6 => "4000|4040",
					  7 => "4000|4040")
			);
	} # spotcat2nabcat

} # class SpotPage_api