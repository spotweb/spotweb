<?php
require_once "lib/page/SpotPage_Abs.php";

class SpotPage_getimage extends SpotPage_Abs {
	private $_messageid;
	private $_image;
	
	function __construct($db, $settings, $prefs, $params) {
		parent::__construct($db, $settings, $prefs);
		$this->_messageid = $params['messageid'];
		$this->_image = $params['image'];
	} # ctor

	
	function render() {
		$spotnntp_hdr = new SpotNntp($this->_settings['nntp_hdr']);

		# Haal de volledige spotinhoud op
		$spotsOverview = new SpotsOverview($this->_db, $this->_settings);
		$fullSpot = $spotsOverview->getFullSpot($this->_messageid, $spotnntp_hdr);
		
		# sluit de connectie voor de header, en open een nieuwe connectie voor de nzb
		$spotnntp_hdr->quit();
		$spotnntp_img = new SpotNntp($this->_settings['nntp_nzb']);
		
		#
		# is het een array met een segment nummer naar de image, of is het 
		# een string met de URL naar de image?
		#
		if (is_array($fullSpot['image'])) {
			Header("Content-Type: image/jpeg");
			echo $spotnntp_img->getImage($fullSpot['image']['segment']);
		} else {
			$x = file_get_contents($fullSpot['image']);
			
			foreach($http_response_header as $hdr) {
				if (substr($hdr, 0, strlen('Content-Type: ')) == 'Content-Type: ') {
					header($hdr);
				} # if
			} # foreach
			
			echo $x;
		} # else
		
	} # render
	
} # SpotPage_getimage
