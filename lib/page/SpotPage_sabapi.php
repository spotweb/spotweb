<?php
class SpotPage_sabapi extends SpotPage_Abs {

	function __construct($db, $settings) {
		$this->_dbsettings = $db;
		$this->_settings = $settings;
	} # __ctor

	function render() {
		$SpotUserSystem = new SpotUserSystem($this->_dbsettings, $this->_settings);

		parse_str($_SERVER['QUERY_STRING'], $request);
		$nzbhandling = $this->_settings->get('nzbhandling');
		$sabnzbd = $nzbhandling['sabnzbd'];

		if ($nzbhandling['action'] != 'push-sabnzbd') {
			die ('SABzndb is not configured on this node.');
		} elseif (!isset($request['apikey'])) {
			die ('API Key Required');
		} elseif ($SpotUserSystem->passToHash($sabnzbd['apikey']) != $request['apikey']) {
			die ('API Key Incorrect');
		} # else

		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 15 Apr 2006 12:26:00 GMT');

		if (stripos($_SERVER['QUERY_STRING'], 'output=xml')) {
			header('Content-Type:text/xml');
		} elseif (stripos($_SERVER['QUERY_STRING'], 'output=json')) {
			header('Content-type: application/json');
		} # else

		$apicall = str_replace('page=sabapi&', '', $_SERVER['QUERY_STRING']);
		$apicall = array();
		foreach($request as $key => $value) {
			if ($key != 'page' && $key != 'apikey')
			$apicall[] = $key . '=' . $value;
		}
		$request = implode('&', $apicall);
		
		$this->_url = parse_url($sabnzbd['url']);
		$this->_url = str_replace('$SABNZBDHOST', $sabnzbd['host'], $this->_url);

		$url = $this->_url['scheme'] . '://' . $this->_url['host'] . $this->_url['path'] . '?' . $request . '&apikey=' . $sabnzbd['apikey'];
		$output = @file_get_contents($url, 0);
		echo $output;
	} # render

} # class SpotPage_sabapi