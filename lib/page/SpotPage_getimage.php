<?php
class SpotPage_getimage extends SpotPage_Abs {
	private $_webCache = array();
	private $_image;
	private $_messageid;

	function __construct(SpotDb $db, SpotSettings $settings, $currentSession, $params) {
		parent::__construct($db, $settings, $currentSession);
		$this->_messageid = $params['messageid'];
		$this->_image = $params['image'];
		$this->_webCache = new SpotWebCache($this->_db);
	} # ctor

	
	function render() {
		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_spotimage, '');

		if (!$img = $this->_webCache->get_nntp_image($this->_messageid)) {
			$spotnntp_hdr = new SpotNntp($this->_settings->get('nntp_hdr'));

			# Haal de volledige spotinhoud op
			$fullSpot = $this->_tplHelper->getFullSpot($this->_messageid, true);

			# sluit de connectie voor de header
			$spotnntp_hdr->quit();
		} # if

		# Images mogen gecached worden op de client
		$this->sendExpireHeaders(false);

		if ($img) {
			$this->_webCache->save_nntp_image($this->_messageid, $img);

			header("Content-Type: image/jpeg");
			echo $img;
		} elseif (is_array($fullSpot['image'])) {
			$spotnntp_img = new SpotNntp($this->_settings->get('nntp_nzb'));

			# Haal de image op
			$image = $spotnntp_img->getImage($fullSpot['image']['segment']);

			# sluit de connectie voor de image
			$spotnntp_img->quit();

			# Sla de image op in de cache
			$this->_webCache->save_nntp_image($this->_messageid, $image);

			header("Content-Type: image/jpeg");
			echo $image;
		} else {
			list($http_headers, $image) = $this->_webCache->get_remote_content($fullSpot['image'], 24*60*60);
			
			foreach(explode("\r\n", $http_headers) as $hdr) {
				if (substr($hdr, 0, strlen('Content-Type: ')) == 'Content-Type: ') {
					header($hdr);
				} # if
			} # foreach
			
			echo $image;
		} # else
		
	} # render
	
} # SpotPage_getimage
