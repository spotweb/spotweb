<?php
define("SABNZBD_TIMEOUT",15);

class NzbHandler_Pushsabnzbd extends NzbHandler_abs
{
	private $_url = null;

	function __construct(SpotSettings $settings, array $nzbHandling)
	{
		parent::__construct($settings, 'SABnzbd', 'SAB', $nzbHandling);
		
		$sabnzbd = $nzbHandling['sabnzbd'];
		
		# prepare sabnzbd url
		$this->_url = $sabnzbd['url'] . 'sabnzbd/api?mode=addfile&apikey=' . $sabnzbd['apikey'] . '&output=text';
	} # __construct
	
	public function processNzb($fullspot, $nzblist)
	{
		$nzb = $this->prepareNzb($fullspot, $nzblist);
		$title = urlencode($this->cleanForFileSystem($fullspot['title']));
		$category = urlencode($this->convertCatToSabnzbdCat($fullspot));

		# yes, using a local variable instead of the member variable is intentional
		$url = $this->_url . '&nzbname=' . $title . '&cat=' . $category;

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
            "Content-Disposition: form-data; name=\"" . FORM_FIELD . "\"; filename=\"" . $nzb['filename'] . "\"\r\n" .
			"Content-Type: " . $nzb['mimetype'] . "\r\n\r\n" . 
			$nzb['nzb'] ."\r\n";
			
		# signal end of request (note the trailing "--")
		$content .= "--".MULTIPART_BOUNDARY."--\r\n";

		$output = $this->sendHttpRequest('POST', $url, $header, $content, SABNZBD_TIMEOUT);
		
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

} # class NzbHandler_Pushsabnzbd
