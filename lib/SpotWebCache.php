<?php

class SpotWebCache {
	protected $_db;
	protected $_settings;
	protected $_currentSession;
	protected $_spotSec;
	
	function __construct(SpotDb $db) {
		$this->_db = $db;
	} # ctor

	function get_remote_content($url, $ttl = 900) {
		$url = urldecode($url);
		$url = str_replace(" ", "+", $url);
		$data = $this->_db->getWebCache($url);

		if ($data && time()-(int) $data['stamp'] < $ttl) {
			return array($data['headers'], $data['content']);
		} # if

		$ch = curl_init();
		curl_setopt ($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:7.0.1) Gecko/20100101 Firefox/7.0.1');
		curl_setopt ($ch, CURLOPT_URL, $url);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt ($ch, CURLOPT_FAILONERROR, 1);
		curl_setopt ($ch, CURLOPT_HEADER, 1); 
		if ($data) {
			curl_setopt($ch, CURLOPT_TIMECONDITION, CURL_TIMECOND_IFMODSINCE);
			curl_setopt($ch, CURLOPT_TIMEVALUE, (int) $data['stamp']);
		} # if

		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		$headers = substr($response, 0, $info['header_size']);
		$content = substr($response, -$info['download_content_length']);  
		
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($http_code == 200 || $http_code == 304) {
			if ($ttl > 0) {
				$this->_db->saveWebCache($url, trim($headers), $content);
			} # if
			return array($headers, $content);
		} else {
			if ($data) {
				return array($data['headers'], $data['content']);
			} else {
				return false;
			} # else
		} # else

	} # get_remote_content
	
} # class
