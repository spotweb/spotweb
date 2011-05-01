<?php

class NzbHandler_Nzbget extends NzbHandler_abs
{
	private $_host = null;
	private $_timeout = null;
	private $_url = null;
	private $_credentials = null;

	function __construct($settings)
	{
		$this->setName("NZBGet");
		$this->setNameShort("D/L");
		$this->setSettings($settings);
				
		$nzbhandling = $settings->get('nzbhandling');
		$nzbget = $nzbhandling['nzbget'];
		$this->_host = $nzbget['host'];
		$this->_timeout = $nzbget['timeout'];
		$this->_url = "http://" . $nzbget['host'] . ":" . $nzbget['port'] . "/jsonrpc";
		$this->_credentials = base64_encode($nzbget['username'] . ":" . $nzbget['password']);
	} # __construct

	public function processNzb($fullspot, $nzblist)
	{
		$filename = $fullspot['title'] . '.nzb';
		# nzbget does not support zip files, must merge
		$nzb = $this->mergeNzbList($nzblist); 
		$category = $this->convertCatToSabnzbdCat($fullspot, $this->getSettings());

		return $this->uploadNzb($filename, $category, false, $nzb);
	} # processNzb

	private function sendRequest($apiCall, $content)
	{
		# creeer de header
		$header = "Host: ". $this->_host . "\r\n".
			"Authorization: Basic " . $this->_credentials . "\r\n".
			"Content-type: application/json\r\n".
			"Content-Length: ".strlen($content) . "\r\n" .
			"\r\n";		
		
		$output = $this->sendHttpRequest('POST', $this->_url, $header, $content, $this->_timeout);

		if ($output === false)
		{
			error_log("ERROR: Could not decode json-data for NZBGet method '" . $apiCall ."'");
			throw new Exception("ERROR: Could not decode json-data for NZBGet method '" . $apiCall ."'");
		}

		$response = json_decode($output, true);
		if (is_array($response) && isset($response['error']) && isset($response['error']['code']))
		{
			error_log("NZBGet RPC: Method '" . $apiCall . "', " . $response['error']['message'] . " (" . $response['error']['code'] . ")");
			throw new Exception("NZBGet RPC: Method '" . $apiCall . "', " . $response['error']['message'] . " (" . $response['error']['code'] . ")");
		}
		else if (is_array($response) && isset($response['result']))
		{
			$response = $response['result'];
		}
		
		return $response;
	} # sendRequest

	/*
	 * NZBGet API method: Append
	 * Purpose: Add an NZB file to download queue 
	 */
	public function uploadNzb($filename, $category, $addToTop, $nzb)
	{
		$args = array($filename, $category, $addToTop, base64_encode($nzb));
		$reqarr = array('version' => '1.1', 'method' => 'append', 'params' => $args);
		$content = json_encode($reqarr);

		return $this->sendrequest('append', $content);
	} # nzbgetApi_append
	
}
