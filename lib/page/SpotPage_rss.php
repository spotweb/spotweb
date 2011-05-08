<?php
class SpotPage_rss extends SpotPage_Abs {
	private $_params;

	function __construct(SpotDb $db, SpotSettings $settings, $currentSession, $params) {
		parent::__construct($db, $settings, $currentSession);
		
		$this->_params = $params;
	}

	function render() {
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
		$spotnntp = new SpotNntp($this->_settings->get('nntp_hdr'));
		$this->rss_header();

		foreach($spotsTmp['list'] as $spot) {
			try {
				$fullSpots[] = $spotsOverview->getFullSpot($spot['messageid'], $this->_currentSession['user']['userid'], $spotnntp); 					
			}catch(Exception $x) {
				// Article not found. ignore.
			}

		}

		$this->rss_data($fullSpots);
		$this->rss_footer();
	} # render()
	
	function rss_header() {
		$tplHelper = new SpotTemplateHelper($this->_settings, $this->_currentSession, $this->_db, $this->_params);
		header('Content-Type: application/rss+xml; charset=UTF-8');
		
		echo "<?xml version=\"1.0\" encoding=\"utf-8\"?" . ">" . PHP_EOL;
		echo "<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">" . PHP_EOL;
		echo "<atom:link href=\"" . $tplHelper->makeSelfUrl("full") . "\" rel=\"self\" type=\"application/rss+xml\" />" . PHP_EOL;
		echo "<channel>" . PHP_EOL;
		echo "<generator>Spotweb</generator>" . PHP_EOL;
		echo "<language>nl</language>" . PHP_EOL;
		echo "<title>SpotWeb</title>" . PHP_EOL;
		echo "<description>SpotWeb RSS Feed</description>" . PHP_EOL;
		echo "<link>" . $this->_settings->get('spotweburl') . "</link>" . PHP_EOL;
		echo "<pubDate>" . date('r') . "</pubDate>" . PHP_EOL;
	}
	
	function rss_data($fullSpots) {
		$tplHelper = new SpotTemplateHelper($this->_settings, $this->_currentSession, $this->_db, $this->_params);
		$nzbhandling = $this->_settings->get('nzbhandling');

		foreach($fullSpots as $spot) {
			$title = preg_replace(array('/</', '/>/', '/&/'), array('&#x3C;', '&#x3E;', '&#x26;'), $spot['title']);

			echo "\t<item>" . PHP_EOL;
			echo "\t\t<title>" . $title . "</title>" . PHP_EOL;
			echo "\t\t<link>" . $tplHelper->makeBaseUrl("full") . "?page=getspot&amp;messageid=" . urlencode($spot['messageid']) . $tplHelper->makeApiRequestString() . "</link>" . PHP_EOL;
			echo "\t\t<description><![CDATA[<p>" . $tplHelper->formatContent($spot['description']) . "<br /><font color=\"#ca0000\">Door: " . $spot['poster'] . " (" . $spot['userid'] . ")</font></p>]]></description>" . PHP_EOL;
			echo "\t\t<author>" . $spot['messageid'] . " (" . $spot['poster']; if (!empty($spot['userid'])) { echo " (" . $spot['userid'] . ")"; } echo ")</author>" . PHP_EOL;
			echo "\t\t<pubDate>" . date('r', $spot['stamp']) . "</pubDate>" . PHP_EOL;
			echo "\t\t<category>" . SpotCategories::HeadCat2Desc($spot['category']) . ": " . SpotCategories::Cat2ShortDesc($spot['category'],$spot['subcat']) . "</category>" . PHP_EOL;
			echo "\t\t<guid isPermaLink=\"true\">" . $tplHelper->makeBaseUrl("full") . "?page=getspot&amp;messageid=" . urlencode($spot['messageid']) . "</guid>" . PHP_EOL;
			
			if ($nzbhandling['prepare_action'] == "zip") {
				echo "\t\t<enclosure url=\"" . $tplHelper->makeNzbUrl($spot) . "\" length=\"" . $spot['filesize'] . "\" type=\"application/zip\" />" . PHP_EOL;
			} else {
				echo "\t\t<enclosure url=\"" . $tplHelper->makeNzbUrl($spot) . "\" length=\"" . $spot['filesize'] . "\" type=\"application/x-nzb\" />" . PHP_EOL;
			} # else
			echo "\t</item>" . PHP_EOL . PHP_EOL;
		}
	}
	
	function rss_footer() {
		echo "</channel>" . PHP_EOL;
		echo "</rss>" . PHP_EOL;
	}
	
} # class SpotPage_rss
