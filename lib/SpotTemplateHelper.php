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
	
	function __construct($settings, $prefs, $db, $params) {
		$this->_settings = $settings;
		$this->_prefs = $prefs;
		$this->_db = $db;
		$this->_params = $params;
	} # ctor

	/*
	 * Geef het aantal spots terug 
	 */
	function getSpotCount() {
		return $this->_db->getSpotCount();
	} # getSpotCount

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
		if (!isset($spot['filename'])) {
			$tmp = str_replace('$SPOTFNAME', $spot['title'], $this->_settings['search_url']);
		} else {
			$tmp = str_replace('$SPOTFNAME', $spot['filename'], $this->_settings['search_url']);
		} # else 

		return $tmp;
	} # makeSearchUrl

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
			return '?page=getnzb&amp;action=' . $action . '&amp;messageid=' . $spot['messageid'];
		} # else
	} # makeSabnzbdUrl

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
		# We gebruiken een static list en een array search omdat dit waarschijnlijk
		# sneller is dan 100 tot 1000 queries per pagina in het overzichtsscherm.
		static $dlList = null;
		static $dlListCnt = 0;
		
		if ($dlList == null) {
			$dlList = $this->_db->getDownloads();
			$dlListCnt = count($dlList);
		} # if
		
		for($i = 0; $i < $dlListCnt; $i++) {
			if ($dlList[$i]['messageid'] == $spot['messageid']) {
				return true;
			} # if
		} # for
		
		return false;
	} # hasbeenDownloaded
	
	function getFilterParams($dontInclude = array()) {
		$getUrl = '';
		
		if (!is_array($dontInclude)) {
			$dontInclude = array($dontInclude);
		} # if
		
		foreach($this->_params['activefilter'] as $key => $val) {
			if (array_search($key, $dontInclude) === false) {
				$getUrl .= '&amp;search[' .  $key . ']=' . urlencode($val);
			}
		} # foreach
		
		return $getUrl;
	} # getFilterParams
	
	/*
	 * Omdat we geen zin hebben elke variabele te controleren of hij bestaat,
	 * vullen we een aantal defaults in.
	 */
	function formatSpot($spot, $comments) {
		// Category is altijd een integer bij ons
		$spot['category'] = (int) $spot['category'];
		
		// Geen website? Dan standaard naar de zoekmachine
		if (empty($spot['website'])) {
			$spot['website'] = $this->makeSearchUrl($spot);
		} # if
		
		// geef de category een fatsoenlijke naam
		$spot['catname'] = SpotCategories::HeadCat2Desc($spot['category']);
		$spot['formatname'] = SpotCategories::Cat2ShortDesc($spot['category'], $spot['subcata']);
		
		// fix the sabnzbdurl en searchurl
		$spot['sabnzbdurl'] = $this->makeSabnzbdUrl($spot);
		$spot['searchurl'] = $this->makeSearchUrl($spot);
		
		// properly escape sevreal urls
		if (!is_array($spot['image'])) {
			$spot['image'] = htmlentities($spot['image']);
		} else {
			$spot['image'] = '';
		} # else
		$spot['website'] = htmlentities($spot['website']);
		$spot['poster'] = htmlentities($spot['poster']);
		$spot['tag'] = htmlentities($spot['tag']);
		
		// description
		$spot['description'] = $this->formatDescription($spot['description']);
		
		// escape de HTML voor de comments
		$commentCount = count($comments);
		for($i = 0; $i < $commentCount; $i++ ){
			$comments[$i]['body'] = array_map('strip_tags', $comments[$i]['body']);
		} # for
		
		return array($spot, $comments);
	} # formatSpot
	
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
				default 			: return strftime($this->_settings['prefs']['date_formatting'], $stamp);
			} # switch
		} # else
	} # formatDate
	
} # class SpotTemplateHelper