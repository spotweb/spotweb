<?php
require_once "lib/SpotNzb.php";
require_once "lib/ubb/ubbparse.php";
require_once 'lib/ubb/taghandler.inc.php';

# Utility class voor template functies, kan eventueel 
# door custom templates extended worden
class SpotTemplateHelper {	
	protected $_settings;
	protected $_prefs;
	protected $_db;
	protected $_params;
	
	# We gebruiken een static watchlist en een array search omdat dit waarschijnlijk
	# sneller is dan 100 tot 1000 queries per pagina in het overzichtsscherm. We maken
	# deze op classe niveau beschikbaar zodat de isBeingWatched() en getWatchList()
	# dezelfde data gebruiken en maar 1 query nodig is
	protected static $wtList = null;

	function __construct($settings, $prefs, $db, $params) {
		$this->_settings = $settings;
		$this->_prefs = $prefs;
		$this->_db = $db;
		$this->_params = $params;
	} # ctor

	/*
	 * Geef het aantal spots terug
	 */
	function getSpotCount($sqlFilter) {
		return $this->_db->getSpotCount($sqlFilter);
	} # getSpotCount

	/*
	* Geef het aantal spots terug maar dan rekening houdende met het filter
	*/
	function getNewCountForFilter($filterStr) {
		if ($this->_settings['count_newspots'] == false) {
			return '';
		} # if

		$filterStr .= "&search[value][]=New:0";
		parse_str(html_entity_decode($filterStr), $query_params);
		$spotsOverview = new SpotsOverview($this->_db, $this->_settings);
		$sqlFilter = $spotsOverview->filterToQuery($query_params['search']);
		$newCount = $this->getSpotCount($sqlFilter);

		if ($newCount > 0) {
			return ' (' . $newCount . ')';
		} else {
			return '';
		}
	} # getFilteredSpotCount

	/*
	 * Geef het aantal spots terug 
	 */
	function getCommentCount($spot) {
		return $this->_db->getCommentCount($spot['messageid']);
	} # getCommentCount
	
	
	/*
	 * Creeert een URL naar de zoekmachine zoals gedefinieerd in de settings
	 */
	function makeSearchUrl($spot) {
		if (empty($spot['filename'])) {
			$tmp = str_replace('$SPOTFNAME', $spot['title'], $this->_settings['search_url']);
		} else {
			$tmp = str_replace('$SPOTFNAME', $spot['filename'], $this->_settings['search_url']);
		} # else 

		return $tmp;
	} # makeSearchUrl
	
	/*
	 * Geef het volledige path naar Spotweb terug
	 */
	function makeBaseUrl() {
		return $this->_settings['spotweburl'];
	} # makeBaseurl

	/*
	 * Creeert een linkje naar de sabnzbd API zoals gedefinieerd in de 
	 * settings
	 */
	function makeSabnzbdUrl($spot) {
		$action = $this->_settings['nzbhandling']['action'];
		# geef geen url terug als we disabled zijn
		if ($action == 'disable') {
			return '';
		} # if
		
		# als de gebruiker gevraagd heeft om niet clientside handling, geef ons zelf dan terug 
		# met de gekozen actie
		if ($action == 'client-sabnzbd') {
			$spotNzb = new SpotNzb($this->_db, $this->_settings);
			return $spotNzb->generateSabnzbdUrl($spot, $action);
		} else {
			return $this->makeBaseUrl() . '?page=getnzb&amp;action=' . $action . '&amp;messageid=' . $spot['messageid'];
		} # else
	} # makeSabnzbdUrl

	/*
	 * Creeert een linkje naar een specifieke spot
	 */
	function makeSpotUrl($spot) {
		return $this->makeBaseUrl() . "?page=getspot&amp;messageid=" . urlencode($spot['messageid']); 
	} # makeSpotUrl

	/*
	 * Creeert een linkje naar een specifieke nzb
	 */
	function makeNzbUrl($spot) {
		return $this->makeBaseUrl() . '?page=getnzb&amp;action=display&amp;messageid=' . urlencode($spot['messageid']);
	} # makeNzbUrl

	/*
	 * Geef het pad op naar de image
	 */
	function makeImageUrl($spot, $height, $width) {
		return $this->makeBaseUrl() . '?page=getimage&amp;messageid=' . urlencode($spot['messageid']) . '&amp;image[height]=' . $height . 'amp;image[width]=' . $width;
	} # makeImageUrl

	/*
	 * Creert een sorteer url
	 */
	function makeSortUrl($page, $sortby, $sortdir) {
		return $this->makeBaseUrl() . '?page=' . $page . $this->getQueryParams(array('sortby', 'sortdir')) . '&amp;sortby=' . $sortby . '&amp;sortdir=' . $sortdir;
	} # makeSortUrl

	/*
	 * Creert een Poster url
	 */
	function makePosterUrl($spot) {
		return $this->makeSelfUrl() . '&amp;search[type]=Poster&amp;search[text]=' . urlencode($spot['poster']);
	} # makePosterUrl

	/*
	 * Creeert een linkje naar een zoekopdracht op userid
	 */
	function makeUserIdUrl($spot) {
		return $this->makeSelfUrl() . '&amp;search[type]=UserID&amp;search[text]=' . urlencode($spot['userid']);
	} # makeNzbUrl
	
	/*
	 * Creert een basis navigatie pagina
	 */
	function getPageUrl($page, $includeParams = false) {
		$url = $this->makeBaseUrl() . '?page=' . $page;
		if ($includeParams) {
			$url .= $this->getQueryParams();
		} # if
		
		return $url;
	} # getPageUrl
	
	/*
	 * Geeft het linkje terug naar ons zelf
	 */
	function makeSelfUrl() {
		return $this->makeBaseUrl() . '?' . $this->getQueryParams();
	} # makeSelfUrl
	
	# Function from http://www.php.net/manual/en/function.filesize.php#99333
	function format_size($size) {
		$sizes = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
		if ($size == 0) { 
			return('n/a'); 
		} else {
			return (round($size/pow(1024, ($i = floor(log($size, 1024)))), $i > 1 ? 2 : 0) . $sizes[$i]); 
		} # else
	} # format_size

	
	function formatDescription($tmp) {
		# initialize ubb parser
		$parser = new UbbParse($tmp);
		TagHandler::setDeniedTags( Array() );
        $tmp = $parser->parse();
		$tmp = $tmp[0];
	
		# en replace eventuele misvormde br tags
		$tmp = str_ireplace('&lt;br&gt;', '<br>', $tmp);
		$tmp = str_ireplace('&lt;br /&gt;', '<br>', $tmp);
		$tmp = str_ireplace('&amp;lt;br />', '<br>', $tmp);
		
		return $tmp;
	} # formatDescription
	
	function hasbeenDownloaded($spot) {
		if (!$this->_settings['keep_downloadlist']) {
			return false;
		} # if

		return ($spot['downloadstamp'] != NULL);
	} # hasbeenDownloaded

	function isBeingWatched($spot) {
		static $wtListCnt = 0;
		
		if (self::$wtList == null) {
			self::$wtList = $this->_db->getWatchList(array('field' => 'stamp', 'direction' => 'desc'));
			$wtListCnt = count(self::$wtList);
		} # if
		
		for($i = 0; $i < $wtListCnt; $i++) {
			if (self::$wtList[$i]['messageid'] == $spot['messageid']) {
				return true;
			} # if
		} # for
		
		return false;
	} # isBeingWatched
	
	function getQueryParams($dontInclude = array()) {
		$getUrl = '';
		
		if (!is_array($dontInclude)) {
			$dontInclude = array($dontInclude);
		} # if
	
		if (isset($this->_params['activefilter'])) {
			foreach($this->_params['activefilter'] as $key => $val) {
				if (array_search($key, $dontInclude) === false) {
					if (!is_array($val)) {
						$getUrl .= '&amp;search[' .  $key . ']=' . urlencode($val);
					} else {
						foreach($val as $valVal) {
							$getUrl .= '&amp;search[' .  $key . '][]=' . urlencode($valVal);
						} # foreach
					} # else
				}
			} # foreach
		} # if
		
		# zijn er sorteer opties meegestuurd?
		if (array_search('sortdir', $dontInclude) === false) {
			if (!empty($this->_params['sortdir'])) {
				$getUrl .= '&amp;sortdir=' . $this->_params['sortdir'];
			} # if
		} # if
		if (array_search('sortby', $dontInclude) === false) {
			if (!empty($this->_params['sortby'])) {
				$getUrl .= '&amp;sortby=' . $this->_params['sortby'];
			} # if
		} # if
		
		return $getUrl;
	} # getQueryParams

	/* 
	 * Safely escape de velden en vul wat velden in
	 */
	function formatSpotHeader($spot) {
		# fix the sabnzbdurl en searchurl
		$spot['sabnzbdurl'] = $this->makeSabnzbdUrl($spot);
		$spot['searchurl'] = $this->makeSearchUrl($spot);
		
		// title escapen
		$spot['title'] = htmlentities(strip_tags($spot['title']), ENT_QUOTES);
		$spot['poster'] = htmlentities(strip_tags($spot['poster']), ENT_QUOTES);

		return $spot;
	} # formatSpotHeader
	
	/*
	 * Omdat we geen zin hebben elke variabele te controleren of hij bestaat,
	 * vullen we een aantal defaults in.
	 */
	function formatSpot($spot, $comments) {
		# fix the sabnzbdurl en searchurl
		$spot['sabnzbdurl'] = $this->makeSabnzbdUrl($spot);
		$spot['searchurl'] = $this->makeSearchUrl($spot);
		
		// Category is altijd een integer bij ons
		$spot['category'] = (int) $spot['category'];
		
		// Geen website? Dan standaard naar de zoekmachine
		if (empty($spot['website'])) {
			$spot['website'] = $this->makeSearchUrl($spot);
		} # if
		
		// geef de category een fatsoenlijke naam
		$spot['catname'] = SpotCategories::HeadCat2Desc($spot['category']);
		$spot['formatname'] = SpotCategories::Cat2ShortDesc($spot['category'], $spot['subcata']);
		
		// properly escape sevreal urls
		if (!is_array($spot['image'])) {
			$spot['image'] = htmlentities($spot['image']);
		} else {
			$spot['image'] = '';
		} # else
		$spot['website'] = htmlentities($spot['website']);
		$spot['poster'] = htmlentities(strip_tags($spot['poster']), ENT_QUOTES);
		$spot['tag'] = htmlentities(strip_tags($spot['tag']));

		// title escapen
		$spot['title'] = htmlentities(strip_tags($spot['title']), ENT_QUOTES);
		
		// description
		$spot['description'] = $this->formatDescription($spot['description']);
		
		// escape de HTML voor de comments
		$commentCount = count($comments);
		for($i = 0; $i < $commentCount; $i++ ){
			$comments[$i]['body'] = array_map('strip_tags', $comments[$i]['body']);
		} # for
		
		return array($spot, $comments);
	} # formatSpot
	
	
	function newSinceLastVisit($spot) {
		return ($_SESSION['last_visit'] != false && $_SESSION['last_visit'] < $spot['stamp']); 
	} # newSinceLastVisit
	
	#
	# Copied from:
	# 	http://www.mdj.us/web-development/php-programming/another-variation-on-the-time-ago-php-function-use-mysqls-datetime-field-type/
	# DISPLAYS COMMENT POST TIME AS "1 year, 1 week ago" or "5 minutes, 7 seconds ago", etc...	
	function time_ago($date, $granularity=2) {
		$difference = time() - $date;
		$periods = array(0 => 315360000,
			1 => 31536000,
			2 => 2628000,
			3 => 604800, 
			4 => 86400,
			5 => 3600,
			6 => 60,
			7 => 1);
		$names_singular = array('eeuw', 'jaar', 'maand', 'week', 'dag', 'uur', 'minuut', 'seconde');
		$names_plural = array('eeuwen', 'jaar', 'maanden', 'weken', 'dagen', 'uur', 'minuten', 'seconden');
			
		$retval = '';
		foreach ($periods as $key => $value) {
			if ($difference >= $value) {
				$time = floor($difference/$value);
				$difference %= $value;
				$retval .= ($retval ? ' ' : '').$time.' ';
				
				if ($time > 1) {
					$retval .= $names_plural[$key];
				} else {
					$retval .= $names_singular[$key];
				} # if
				$retval .= ', ';
				$granularity--;
			}
			
			if ($granularity == '0') { break; }
		}
		return substr($retval, 0, -2);
	} # time_ago()


	function formatDate($stamp, $type) {
		if (!isset($this->_settings['prefs']['date_formatting'])) {
			$this->_settings['prefs']['date_formatting'] = "%a, %d-%b-%Y (%H:%M)";
		} # if
		
		if ($this->_settings['prefs']['date_formatting'] == 'human') {
			return $this->time_ago($stamp);
		} else {
			switch($type) {
				case 'comment'		:
				case 'spotlist'		: 
				case 'lastupdate'	: 
				case 'watchlist'	:
				default 			: return strftime($this->_settings['prefs']['date_formatting'], $stamp);
			} # switch
		} # else
	} # formatDate
	
	function isModerated($spot) {
		return ($spot['moderated'] != 0);
	} # isModerated

	function getWatchList() {
		if (self::$wtList == null) {
			self::$wtList = $this->_db->getWatchList(array('field' => 'stamp', 'direction' => 'desc'));
		} # if
		
		return self::$wtList;
	} # getWatchList
	
} # class SpotTemplateHelper
