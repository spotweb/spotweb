<?php
class Sabnzbd_api {

	function __construct($db, $settings) {
		$this->_dbsettings = $db;
		$this->_settings = $settings;
	} # __ctor

	function render() {
		if ($this->_settings['action'] != "push-sabnzbd") {
			die ("SABzndb is not configured on this node.");
		} # if

		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 15 Apr 2006 12:26:00 GMT');

		if (stripos($_SERVER['QUERY_STRING'], 'output=xml')) {
			header("Content-Type:text/xml");
		} elseif (stripos($_SERVER['QUERY_STRING'], 'output=json')) {
			header('Content-type: application/json');
		} # else

		$request = str_replace("page=sabapi&", "", $_SERVER['QUERY_STRING']);
		$url = "http://" . $this->_settings['sabnzbd']['host'] . "/sabnzbd/api?apikey=" . $this->_settings['sabnzbd']['apikey'] . "&" . $request;
		$output = @file_get_contents($url, 0);
		echo $output;

	} # render

} # class Sabnzbd_api