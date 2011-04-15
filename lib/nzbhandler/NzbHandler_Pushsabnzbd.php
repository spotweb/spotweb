<?php
define("SABNZBD_TIMEOUT",15);

class NzbHandler_Pushsabnzbd extends NzbHandler_abs
{
	private $_name = "SabNZBd";
	private $_nameShort = "SAB";

	private $_url = null;

	function __construct($settings)
	{
		$nzbhandling = $settings->get('nzbhandling');
		$sabnzbd = $nzbhandling['sabnzbd'];
		
		# prepare sabnzbd url
		# substitute variables that are not download specific
		$this->_url = $sabnzbd['url'];		
		$this->_url = str_replace('$SABNZBDHOST', $sabnzbd['host'], $this->_url);
		$this->_url = str_replace('$APIKEY', $sabnzbd['apikey'], $this->_url);
		$this->_url = str_replace('$SABNZBDMODE', 'addfile', $this->_url);
		$this->_url = str_replace('$NZBURL', '', $this->_url); # not used for push-sabnzbd

	} # __construct
	
	public function processNzb($fullspot, $filename, $category, $nzb, $mimetype)
	{
		$title = urlencode($this->cleanForFileSystem($fullspot['title']));
		$category = urlencode($category);
		
		# yes, using a local variable instead of the member variable is intentional		
		$url = str_replace('$SPOTTITLE', $title, $this->_url);
		$url = str_replace('$SANZBDCAT', $category, $url);

		@define('MULTIPART_BOUNDARY', '--------------------------'.microtime(true));
		# equivalent to <input type="file" name="nzbfile"/>
		@define('FORM_FIELD', 'nzbfile'); 

		# dit is gecopieerd van:
		#	http://stackoverflow.com/questions/4003989/upload-a-file-using-file-get-contents

		# creeer de header
		$header = 'Content-Type: multipart/form-data; boundary='.MULTIPART_BOUNDARY;

		# bouw nu de content
		$content = "--" . MULTIPART_BOUNDARY . "\r\n";
		$content .= 
            "Content-Disposition: form-data; name=\"" . FORM_FIELD . "\"; filename=\"" . $filename . "\"\r\n" .
			"Content-Type: " . $mimetype . "\r\n\r\n" . 
			$nzb ."\r\n";
			
		# signal end of request (note the trailing "--")
		$content .= "--".MULTIPART_BOUNDARY."--\r\n";

		# create an stream context to be able to pass certain parameters
		$ctx = stream_context_create(array('http' => 
					array('timeout' => SABNZBD_TIMEOUT,
						  'method' => 'POST',
						  'header' => $header,
						  'content' => $content)));

		$output = @file_get_contents($url, 0, $ctx);
		if ($output	=== false)
		{
			error_log("Unable to open sabnzbd url: " . $url);
			throw new Exception("Unable to open sabnzbd url: " . $url);
		} # if
		
		if (strtolower(trim($output)) != 'ok')
		{
			error_log("sabnzbd returned: " . $output);
			throw new Exception("sabnzbd returned: " . $output);
		} # if
	} # processNzb
	
}
