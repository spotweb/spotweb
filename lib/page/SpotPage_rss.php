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

		$spotsOverview = new SpotsOverview($this->_db, $this->_settings);
		$nzbhandling = $this->_currentSession['user']['prefs']['nzbhandling'];

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

		# Opbouwen XML
		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;

		$rss = $doc->createElement('rss');
		$rss->setAttribute('version', '2.0');
		$rss->setAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
		$doc->appendChild($rss);

		$atomSelfLink = $doc->createElementNS('http://www.w3.org/2005/Atom', 'atom10:link');
		$atomSelfLink->setAttribute('href', html_entity_decode($this->_tplHelper->makeSelfUrl("full")));
		$atomSelfLink->setAttribute('rel', 'self');
		$atomSelfLink->setAttribute('type', 'application/rss+xml');

		$channel = $doc->createElement('channel');
		$channel->appendChild($doc->createElement('generator', 'Spotweb v' . SPOTWEB_VERSION));
		$channel->appendChild($doc->createElement('language', 'nl'));
		$channel->appendChild($doc->createElement('title', 'Spotweb'));
		$channel->appendChild($doc->createElement('description', 'Spotweb RSS Feed'));
		$channel->appendChild($doc->createElement('link', $this->_tplHelper->makeBaseUrl("full")));
		$channel->appendChild($atomSelfLink);
		$channel->appendChild($doc->createElement('webMaster', $this->_currentSession['user']['mail'] . ' (' . $this->_currentSession['user']['firstname'] . ' ' . $this->_currentSession['user']['lastname'] . ')'));
		$channel->appendChild($doc->createElement('pubDate', date('r')));
		$rss->appendChild($channel);

		# Fullspots ophalen en aan XML toevoegen
		foreach($spotsTmp['list'] as $spotHeaders) {
			try {
				$spot = $this->_tplHelper->getFullSpot($spotHeaders['messageid'], false);
				# Normaal is fouten oplossen een beter idee, maar in dit geval is het een bug in de library (?)
				# Dit voorkomt Notice: Uninitialized string offset: 0 in lib/ubb/TagHandler.inc.php on line 140
				# wat een onbruikbare RSS oplevert
				$spot = @$this->_tplHelper->formatSpot($spot);

				$title = preg_replace(array('/</', '/>/', '/&/'), array('&#x3C;', '&#x3E;', '&#x26;'), $spot['title']);
				$poster = (empty($spot['userid'])) ? $spot['poster'] : $spot['poster'] . " (" . $spot['userid'] . ")";

				$guid = $doc->createElement('guid', $spot['messageid']);
				$guid->setAttribute('isPermaLink', 'false');

				$description = $doc->createElement('description');
				$descriptionCdata = $doc->createCDATASection($spot['description'] . '<br /><font color="#ca0000">Door: ' . $poster . '</font>');
				$description->appendChild($descriptionCdata);

				$item = $doc->createElement('item');
				$item->appendChild($doc->createElement('title', $title));
				$item->appendChild($guid);
				$item->appendChild($doc->createElement('link', $this->_tplHelper->makeBaseUrl("full") . '?page=getspot&amp;messageid=' . urlencode($spot['messageid']) . $this->_tplHelper->makeApiRequestString()));
				$item->appendChild($description);
				$item->appendChild($doc->createElement('author', $spot['messageid'] . ' (' . $poster . ')'));
				$item->appendChild($doc->createElement('pubDate', date('r', $spot['stamp'])));
				$item->appendChild($doc->createElement('category', SpotCategories::HeadCat2Desc($spot['category']) . ': ' . SpotCategories::Cat2ShortDesc($spot['category'], $spot['subcat'])));

				$enclosure = $doc->createElement('enclosure');
				$enclosure->setAttribute('url', html_entity_decode($this->_tplHelper->makeNzbUrl($spot)));
				$enclosure->setAttribute('length', $spot['filesize']);
				switch ($nzbhandling['prepare_action']) {
					case 'zip'	: $enclosure->setAttribute('type', 'application/zip'); break;
					default		: $enclosure->setAttribute('type', 'application/x-nzb');
				} # switch
				$item->appendChild($enclosure);

				$channel->appendChild($item);
			} # try
			catch(Exception $x) {
				// Article not found. ignore.
			} # catch
		} # foreach

		# XML output
		header('Content-Type: application/rss+xml; charset=UTF-8');
		echo $doc->saveXML();
	} # render()

} # class SpotPage_rss