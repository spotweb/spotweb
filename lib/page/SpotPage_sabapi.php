<?php
class SpotPage_sabapi extends SpotPage_Abs {

	function render() {
		$tplHelper = $this->getTplHelper(array());

		parse_str($_SERVER['QUERY_STRING'], $request);
		$nzbhandling = $this->_settings->get('nzbhandling');
		$sabnzbd = $nzbhandling['sabnzbd'];

		if ($nzbhandling['action'] != 'push-sabnzbd') {
			die ('SABzndb is not configured on this node.');
		} elseif (!isset($request['apikey'])) {
			die ('API Key Required');
		} elseif ($tplHelper->apiToHash($sabnzbd['apikey']) != $request['apikey']) {
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
			if ($key != 'page' && $key != 'apikey') {
				$apicall[] = $key . '=' . $value;
			} # if
		} # foreach
		$request = implode('&amp;', $apicall);
		
		$url = parse_url($sabnzbd['url']);
		$url['host'] = str_replace('$SABNZBDHOST', $sabnzbd['host'], $url['host']);

		$output = @file_get_contents($url['scheme'] . '://' . $url['host'] . $url['path'] . '?' . $request . '&apikey=' . $sabnzbd['apikey']);
		echo $output;
	} # render

} # class SpotPage_sabapi
