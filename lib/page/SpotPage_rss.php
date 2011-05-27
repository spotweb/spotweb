<?php
class SpotPage_rss extends SpotPage_Abs {
	private $_params;

	function __construct(SpotDb $db, SpotSettings $settings, $currentSession, $params) {
		parent::__construct($db, $settings, $currentSession);

		$this->_params = $params;
	}

	function render() {
		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_spotdetail, '');
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_spots_index, '');
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_rssfeed, '');
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_consume_api, '');

		$spotsOverview = new SpotsOverview($this->_db, $this->_settings);

		# Zet the query parameters om naar een lijst met filters, velden,
		# en sorteringen etc
		$parsedSearch = $spotsOverview->filterToQuery($this->_params['search'], $this->_currentSession);
		$this->_params['search'] = $parsedSearch['search'];

		# laad de spots
		$pageNr = $this->_params['page'];
		$spotsTmp = $spotsOverview->loadSpots($this->_currentSession['user']['userid'],
							$pageNr,
							$this->_currentSession['user']['prefs']['perpage'],
							$parsedSearch,
							array('field' => $this->_params['sortby'], 
								  'direction' => $this->_params['sortdir']));

		$fullSpots = array();
		$this->rss_header();

		foreach($spotsTmp['list'] as $spot) {
			try {
				$fullSpots[] = $this->_tplHelper->getFullSpot($spot['messageid'], false);
			} # try
			catch(Exception $x) {
				// Article not found. ignore.
			} # catch
		} # foreach

		$this->rss_data($fullSpots);
		$this->rss_footer();
	} # render()

	function rss_header() {
		header('Content-Type: application/rss+xml; charset=UTF-8');
		echo "<?xml version=\"1.0\" encoding=\"utf-8\"?" . ">" . PHP_EOL;
		echo "<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">" . PHP_EOL;
		echo "<atom10:link xmlns:atom10=\"http://www.w3.org/2005/Atom\" href=\"" . $this->_tplHelper->makeSelfUrl("full") . "\" rel=\"self\" type=\"application/rss+xml\" />" . PHP_EOL;
		if ($this->_settings->get('deny_robots')) { echo "<xhtml:meta xmlns:xhtml=\"http://www.w3.org/1999/xhtml\" name=\"robots\" content=\"noindex\" />" . PHP_EOL; }
		echo "<channel>" . PHP_EOL;
		echo "\t<generator>Spotweb</generator>" . PHP_EOL;
		echo "\t<language>nl</language>" . PHP_EOL;
		echo "\t<title>SpotWeb</title>" . PHP_EOL;
		echo "\t<description>SpotWeb RSS Feed</description>" . PHP_EOL;
		echo "\t<link>" . $this->_settings->get('spotweburl') . "</link>" . PHP_EOL;
		echo "\t<pubDate>" . date('r') . "</pubDate>" . PHP_EOL;
	}

	function rss_data($fullSpots) {
		$nzbhandling = $this->_settings->get('nzbhandling');

		foreach($fullSpots as $spot) {
			$title = preg_replace(array('/</', '/>/', '/&/'), array('&#x3C;', '&#x3E;', '&#x26;'), $spot['title']);

			$poster = $spot['poster'];
			if (!empty($spot['userid'])) {
				$poster .= " (" . $spot['userid'] . ")";
			}

			echo "\t\t<item>" . PHP_EOL;
			echo "\t\t\t<title>" . $title . "</title>" . PHP_EOL;
			echo "\t\t\t<link>" . $this->_tplHelper->makeBaseUrl("full") . "?page=getspot&amp;messageid=" . urlencode($spot['messageid']) . $this->_tplHelper->makeApiRequestString() . "</link>" . PHP_EOL;
			echo "\t\t\t<description><![CDATA[<p>" . $this->_tplHelper->formatContent($spot['description']) . "<br /><font color=\"#ca0000\">Door: " . $poster . "</font></p>]]></description>" . PHP_EOL;
			echo "\t\t\t<author>" . $spot['messageid'] . " (" . $poster . ")</author>" . PHP_EOL;
			echo "\t\t\t<pubDate>" . date('r', $spot['stamp']) . "</pubDate>" . PHP_EOL;
			echo "\t\t\t<category>" . SpotCategories::HeadCat2Desc($spot['category']) . ": " . SpotCategories::Cat2ShortDesc($spot['category'], $spot['subcat']) . "</category>" . PHP_EOL;
			echo "\t\t\t<guid isPermaLink=\"true\">" . $this->_tplHelper->makeBaseUrl("full") . "?page=getspot&amp;messageid=" . urlencode($spot['messageid']) . "</guid>" . PHP_EOL;

			if ($nzbhandling['prepare_action'] == "zip") {
				echo "\t\t\t<enclosure url=\"" . $this->_tplHelper->makeNzbUrl($spot) . "\" length=\"" . $spot['filesize'] . "\" type=\"application/zip\" />" . PHP_EOL;
			} else {
				echo "\t\t\t<enclosure url=\"" . $this->_tplHelper->makeNzbUrl($spot) . "\" length=\"" . $spot['filesize'] . "\" type=\"application/x-nzb\" />" . PHP_EOL;
			} # else
			echo "\t\t</item>" . PHP_EOL . PHP_EOL;
		}
	}

	function rss_footer() {
		echo "</channel>" . PHP_EOL;
		echo "</rss>";
	}

} # class SpotPage_rss