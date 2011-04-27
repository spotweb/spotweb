<?php
class SpotPage_sabapi extends SpotPage_Abs {

	function __construct($db, $settings, $currentSession) {
		$this->_dbsettings = $db;
		$this->_settings = $settings;
		$this->_currentSession = $currentSession;
	} # __ctor

	function render() {
		$SpotTemplateHelper = new SpotTemplateHelper($this->_settings, $this->_currentSession, $this->_dbsettings, '');

		parse_str($_SERVER['QUERY_STRING'], $this->_request);
		$this->_nzbhandling = $this->_settings->get('nzbhandling');
		$this->_sabnzbd = $this->_nzbhandling['sabnzbd'];

		if ($this->_nzbhandling['action'] != 'push-sabnzbd') {
			die ('SABzndb is not configured on this node.');
		} elseif (!isset($this->_request['apikey'])) {
			die ('API Key Required');
		} elseif ($SpotTemplateHelper->apiToHash($this->_sabnzbd['apikey']) != $this->_request['apikey']) {
			die ('API Key Incorrect');
		} # else

		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 15 Apr 2006 12:26:00 GMT');

		if (stripos($_SERVER['QUERY_STRING'], 'output=xml')) {
			header('Content-Type:text/xml');
		} elseif (stripos($_SERVER['QUERY_STRING'], 'output=json')) {
			header('Content-type: application/json');
		} # else

		$this->_apicall = array();
		foreach($this->_request as $key => $value) {
			if ($key != 'page' && $key != 'apikey')
			$this->_apicall[] = $key . '=' . $value;
		}
		$this->_request = implode('&amp;', $this->_apicall);
		
		$this->_url = parse_url($this->_sabnzbd['url']);
		$this->_url['host'] = str_replace('$SABNZBDHOST', $this->_sabnzbd['host'], $this->_url['host']);

		$output = @file_get_contents($this->_url['scheme'] . '://' . $this->_url['host'] . $this->_url['path'] . '?' . $this->_request . '&apikey=' . $this->_sabnzbd['apikey']);
		echo $output;
	} # render

} # class SpotPage_sabapi