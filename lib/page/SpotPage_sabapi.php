<?php
class SpotPage_sabapi extends SpotPage_Abs {

	function render() {
		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_use_sabapi, '');
		
		parse_str($_SERVER['QUERY_STRING'], $request);
		$nzbhandling = $this->_currentSession['user']['prefs']['nzbhandling'];
		$sabnzbd = $nzbhandling['sabnzbd'];
	
		if ($nzbhandling['action'] != 'push-sabnzbd' && $nzbhandling['action'] != 'client-sabnzbd') {
			die ('SABzndb is not configured on this node.');
		} elseif (!isset($request['sabapikey'])) {
			die ('API Key Required');
		} elseif ($this->_tplHelper->apiToHash($sabnzbd['apikey']) != $request['sabapikey']) {
			die ('API Key Incorrect');
		} # else

		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 15 Apr 2006 12:26:00 GMT');

		if (stripos($_SERVER['QUERY_STRING'], 'output=xml')) {
			header('Content-Type:text/xml');
		} elseif (stripos($_SERVER['QUERY_STRING'], 'output=json')) {
			header('Content-type: application/json');
		} # else

		$apicall = array();
		foreach($request as $key => $value) {
			if ($key != 'page' && $key != 'sabapikey') {
				$apicall[] = $key . '=' . $value;
			} # if
		} # foreach
		$request = implode('&amp;', $apicall);
		
		$output = @file_get_contents($sabnzbd['url'] . 'sabnzbd/api?' . $request . '&apikey=' . $sabnzbd['apikey']);
		echo $output;
	} # render

} # class SpotPage_sabapi