<?php
class SpotPage_getimage extends SpotPage_Abs {
	private $_messageid;
	private $_image;
	
	function __construct(SpotDb $db, SpotSettings $settings, $currentSession, $params) {
		parent::__construct($db, $settings, $currentSession);
		$this->_messageid = $params['messageid'];
		$this->_image = $params['image'];
	} # ctor

	
	function render() {
		$spotnntp_hdr = new SpotNntp($this->_settings->get('nntp_hdr'));

		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_spotimage, '');
		
		# Haal de volledige spotinhoud op
		$fullSpot = $this->_tplHelper->getFullSpot($this->_messageid, true);
		
		# sluit de connectie voor de header, en open een nieuwe connectie voor de nzb
		$spotnntp_hdr->quit();
		$spotnntp_img = new SpotNntp($this->_settings->get('nntp_nzb'));

		# Images mogen gecached worden op de client
		$this->sendExpireHeaders(false);

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
