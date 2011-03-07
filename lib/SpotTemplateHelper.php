<?php

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
		# alleen draaien als we gedefinieerd zijn
		if ((!isset($this->_settings['sabnzbd'])) | (!isset($this->_settings['sabnzbd']['apikey'])) | (!isset($this->_settings['sabnzbd']['categories']))) {
			return '';
		} # if
		
		# fix de category
		$spot['category'] = (int) $spot['category'];
		
		# vind een geschikte category
		$category = $this->_settings['sabnzbd']['categories'][$spot['category']]['default'];

		foreach($spot['subcatlist'] as $cat) {
			if (isset($this->_settings['sabnzbd']['categories'][$spot['category']][$cat])) {
				$category = $this->_settings['sabnzbd']['categories'][$spot['category']][$cat];
			} # if
		} # foreach
		
		# en creeer die sabnzbd url
		$tmp = $this->_settings['sabnzbd']['url'];
		$tmp = str_replace('$SABNZBDHOST', $this->_settings['sabnzbd']['host'], $tmp);
		$tmp = str_replace('$NZBURL', urlencode($this->_settings['sabnzbd']['spotweburl'] . '?page=getnzb&messageid='. $spot['messageid']), $tmp);
		$tmp = str_replace('$SPOTTITLE', urlencode($spot['title']), $tmp);
		$tmp = str_replace('$SANZBDCAT', $category, $tmp);
		$tmp = str_replace('$APIKEY', $this->_settings['sabnzbd']['apikey'], $tmp);

		return $tmp;
	} # sabnzbdurl

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
		$tmp = str_ireplace('[b]', '<b>', $tmp);
		$tmp = str_ireplace('[/b]', '</b>', $tmp);
		$tmp = str_ireplace('[i]', '<i>', $tmp);
		$tmp = str_ireplace('[/i]', '</i>', $tmp);
		$tmp = str_ireplace('[br]', "<br>", $tmp);
		$tmp = str_ireplace('[u]', '<u>', $tmp);
		$tmp = str_ireplace('[/u]', '</u>', $tmp);
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
	
	
} # class SpotTemplateHelper