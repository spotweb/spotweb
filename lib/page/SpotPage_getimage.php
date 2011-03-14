<?php
require_once "lib/page/SpotPage_Abs.php";

class SpotPage_proxy extends SpotPage_Abs {
	private $_url;
	
	function __construct($db, $settings, $prefs, $params) {
		parent::__construct($db, $settings, $prefs);
		$this->_messageid = $params['url'];
	} # ctor

	
	function render() {
		$resource = fopen($_GET['url'], 'r');
		foreach($http_response_header as $header => $value) {
			header($header . ': ' . $value);
		}
		fpassthru($resource);
	} # render
	
} # SpotPage_getnzb
