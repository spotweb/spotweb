<?php
/* Externe library */
require_once "lib/ubb/ubbparse.php";
require_once 'lib/ubb/taghandler.inc.php';

# Utility class voor template functies, kan eventueel 
# door custom templates extended worden
class SpotTemplateHelper {	
	protected $_settings;
	protected $_db;
	protected $_spotnzb;
	protected $_currentUser;
	protected $_params;
	
	static private $_commentCount = null;	
	
	function __construct($settings, $currentUser, $db, $params) {
		$this->_settings = $settings;
		$this->_currentUser = $currentUser;
		$this->_db = $db;
		$this->_params = $params;
		
		# We initialiseren hier een SpotNzb object omdat we die
		# voor het maken van de sabnzbd categorieen nodig hebben.
		# Door die hier aan te maken verplaatsen we een boel allocaties
		$this->_spotnzb = new SpotNzb($settings, $db);
	} # ctor

	/*
	 * Geef het aantal spots terug
	 */
	function getSpotCount($sqlFilter) {
		return $this->_db->getSpotCount($sqlFilter);
	} # getSpotCount

	/* 
	 * Geeft de waarde van een parameter terug
	 */
	function getParam($name) {
		if (isset($this->_params[$name])) {
			return $this->_params[$name];
		} else {
			return NULL;
		} # if
	} # getParam
	
	/*
 	 * Geef het aantal spots terug maar dan rekening houdende met het filter
 	 */
	function getFilteredSpotCount($filterStr) {
		parse_str(html_entity_decode($filterStr), $query_params);
		
		$spotsOverview = new SpotsOverview($this->_db, $this->_settings);
		$sqlFilter = $spotsOverview->filterToQuery($query_params['search']);
		
		return $this->getSpotCount($sqlFilter);
	} # getFilteredSpotCount

	/*
	 * Geef het aantal spots terug, maar enkel die new zijn
	 */
	function getNewCountForFilter($filterStr) {
		static $skipNewCount = false;
		
		if ((!$this->_settings->get('count_newspots')) || ($skipNewCount)) {
			return '';
		} # if
		
		$filterStr .= "&search[value][]=New:0";
		$newCount = $this->getFilteredSpotCount($filterStr);

		# lelijke hack om er voor te zorgen dat als er erg veel nieuwe spots 
		# zijn, SpotWeb niet ontzettend traag wordt. 
		if ($newCount > 5000) {
			$skipNewCount = true;
		} # if
		
		# en geef het aantal terug dat we willen hebben
		if ($newCount > 0) {
			return $newCount;
		} else {
			return '';
		} # else
	} # getNewCountForFilter

	/*
	 * Geef het aantal spots terug 
	 */
	function getCommentCount($spot) {
		# Lazily load commnet count
		if ((self::$_commentCount == null) && ($this->_settings->get('count_comments'))) {
			self::$_commentCount = $this->_db->getCommentCount($this->getParam('spots'));
		} # if

		# zitten er al comments in de database?
		if (!isset(self::$_commentCount[$spot['messageid']])) {
			return 0;
		} # if
		
		return self::$_commentCount[$spot['messageid']];
	} # getCommentCount
	
	/*
	 * Geeft een aantal comments terug
	 */
	function getSpotComments($msgId, $start, $length) {
		$spotnntp = new SpotNntp($this->_settings->get('nntp_hdr'));
		
		$spotsOverview = new SpotsOverview($this->_db, $this->_settings);
		return $spotsOverview->getSpotComments($msgId, $spotnntp, $start, $length);
	} # getSpotComments

	/*
	 * Geeft een full spot terug
	 */
	function getFullSpot($msgId) {
		$spotnntp = new SpotNntp($this->_settings->get('nntp_hdr'));
		
		$spotsOverview = new SpotsOverview($this->_db, $this->_settings);
		return $spotsOverview->getFullSpot($msgId, $this->_currentUser['userid'], $spotnntp);
	} # getFullSpot

	
	/*
	 * Creeert een URL naar de zoekmachine zoals gedefinieerd in de settings
	 */
	function makeSearchUrl($spot) {
		if (empty($spot['filename'])) {
			$tmp = str_replace('$SPOTFNAME', $spot['title'], $this->_settings->get('search_url'));
		} else {
			$tmp = str_replace('$SPOTFNAME', $spot['filename'], $this->_settings->get('search_url'));
		} # else 

		return $tmp;
	} # makeSearchUrl
	
	/*
	 * Geef het volledige path naar Spotweb terug
	 */
	function makeBaseUrl() {
		return $this->_settings->get('spotweburl');
	} # makeBaseurl

	/*
	 * Creeert een linkje naar de sabnzbd API zoals gedefinieerd in de 
	 * settings
	 */
	function makeSabnzbdUrl($spot) {
		$nzbHandling = $this->_settings->get('nzbhandling');
		$action = $nzbHandling['action'];
		# geef geen url terug als we disabled zijn
		if ($action == 'disable') {
			return '';
		} # if
		
		# als de gebruiker gevraagd heeft om niet clientside handling, geef ons zelf dan terug 
		# met de gekozen actie
		if ($action == 'client-sabnzbd') {
			return $this->_spotnzb->generateSabnzbdUrl($spot, $action);
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
		return $this->makeBaseUrl() . '?page=getimage&amp;messageid=' . urlencode($spot['messageid']) . '&amp;image[height]=' . $height . '&amp;image[width]=' . $width;
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
		return $this->makeBaseUrl() . '?search[tree]=&amp;search[type]=UserID&amp;search[text]=' . urlencode($spot['userid']);
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
		return $this->makeBaseUrl() . '?' . htmlentities((isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : ""));
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

	
	function formatContent($tmp) {
		# initialize ubb parser
		$parser = new UbbParse($tmp);
		TagHandler::setDeniedTags( Array() );
		TagHandler::setadditionalinfo('img', 'allowedimgs', $this->getSmileyList() );
        $tmp = $parser->parse();
		$tmp = $tmp[0];
	
		# en replace eventuele misvormde br tags
		$tmp = str_ireplace('&lt;br&gt;', '<br>', $tmp);
		$tmp = str_ireplace('&lt;br /&gt;', '<br>', $tmp);
		$tmp = str_ireplace('&amp;lt;br />', '<br>', $tmp);
		
		return $tmp;
	} # formatContent
	
	function hasbeenDownloaded($spot) {
		if (!$this->_settings->get('keep_downloadlist')) {
			return false;
		} # if

		return ($spot['downloadstamp'] != NULL);
	} # hasbeenDownloaded

	function isBeingWatched($spot) {
		if (!$this->_settings->get('keep_watchlist')) {
			return false;
		} # if
		
		return ($spot['w_dateadded'] != NULL);
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
						if (!empty($val)) {
							$getUrl .= '&amp;search[' .  $key . ']=' . urlencode($val);
						} # if
					} else {
						foreach($val as $valVal) {
							if (!empty($valVal)) {
								$getUrl .= '&amp;search[' .  $key . '][]=' . urlencode($valVal);
							} # if
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
		# fix the sabnzbdurl, searchurl, sporturl
		$spot['sabnzbdurl'] = $this->makeSabnzbdUrl($spot);
		$spot['searchurl'] = $this->makeSearchUrl($spot);
		$spot['spoturl'] = $this->makeSpotUrl($spot);
		$spot['posterurl'] = $this->makePosterUrl($spot);
		
		// title escapen
		$spot['title'] = htmlentities(strip_tags($spot['title']), ENT_QUOTES);
		$spot['poster'] = htmlentities(strip_tags($spot['poster']), ENT_QUOTES);
		
		// we zetten de short description van de category bij
		$spot['catshortdesc'] = SpotCategories::Cat2ShortDesc($spot['category'], $spot['subcata']);
		$spot['catdesc'] = SpotCategories::Cat2Desc($spot['category'], $spot['subcat' . SpotCategories::SubcatNumberFromHeadcat($spot['category'])]);
		$spot['subcatfilter'] = SpotCategories::SubcatToFilter($spot['category'], $spot['subcata']);

		
		// hoeveel comments zitten er bij deze spot ongeveer?
		$spot['commentcount'] = $this->getCommentCount($spot);
		
		// is deze spot al eens gedownload?
		$spot['hasbeendownloaded'] = $this->hasBeenDownloaded($spot);
		
		// zit deze spot in de watchlist?
		$spot['isbeingwatched'] = $this->isBeingWatched($spot);

		return $spot;
	} # formatSpotHeader

	/*
	 * Formatteert (maakt op) een lijst van comments
	 */
	function formatComments($comments) {
		// escape de HTML voor de comments
		$commentCount = count($comments);
		for($i = 0; $i < $commentCount; $i++ ){
			$comments[$i]['body'] = array_map('strip_tags', $comments[$i]['body']);
			
			# we joinen eerst de contents zodat we het kunnen parsen als 1 string
			# en tags over meerdere lijnen toch nog ewrkt. We voegen een extra \n toe
			# om zeker te zijn dat we altijd een array terugkrijgen
			$tmpBody = implode("\n", $comments[$i]['body']);
			$tmpBody = $this->formatContent($tmpBody);
			$comments[$i]['body'] = explode("\n", $tmpBody);
		} # for
		
		return $comments;
	} # formatComments
	
	/*
	 * Omdat we geen zin hebben elke variabele te controleren of hij bestaat,
	 * vullen we een aantal defaults in.
	 */
	function formatSpot($spot) {
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
		$spot['description'] = $this->formatContent($spot['description']);
				
		return $spot;
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
		if ($this->_currentUser['prefs']['date_formatting'] == 'human') {
			return $this->time_ago($stamp);
		} else {
			switch($type) {
				case 'comment'		:
				case 'spotlist'		: 
				case 'lastupdate'	: 
				default 			: return strftime($this->_currentUser['prefs']['date_formatting'], $stamp);
			} # switch
		} # else
	} # formatDate
	
	function isModerated($spot) {
		return ($spot['moderated'] != 0);
	} # isModerated

	/*
	 * Geeft een lijst van mogelijke smilies terug
	 */
	function getSmileyList() {
		return array();
	} # getSmileyList
	
	# Functie voor in combinatie met SpotPage_statics.php -
	# deze functie hoort een lijst van onze static files terug te geven die door de SpotPage_statics
	# dan geserved wordt als nooit meer veranderend. 
	function getStaticFiles($type) {
		return array();
	} # getStaticFiles

	# Functie voor in combinatie met SpotPage_statics.php -
	# deze functie kijkt wat de laatste timetsamp is van de file en kan gebruikt worden in de templates.
	# Omdat stat() behoorlijk traag is, is het voor betere performance aan te raden handmatig je versie nummer
	# op te hogen in je template en deze functie niet te gebruiken
	function getStaticModTime($type) {
		$fileTime = 0;
		$fileList = $this->getStaticFiles($type);
		
		foreach($fileList as $file) {
			$thisftime = filemtime($file);
			
			if ($thisftime > $fileTime) {
				$fileTime = $thisftime;
			} # if
		} # foreach
		
		return $fileTime;
	} # getStaticFiles
	
} # class SpotTemplateHelper
