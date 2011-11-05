<?php
class SpotPage_getimage extends SpotPage_Abs {
	private $_image;
	private $_messageid;

	const cache_image_prefix		= 'SpotImage::';

	function __construct(SpotDb $db, SpotSettings $settings, $currentSession, $params) {
		parent::__construct($db, $settings, $currentSession);
		$this->_messageid = $params['messageid'];
		$this->_image = $params['image'];
	} # ctor

	
	function render() {
		$hdr_spotnntp = new SpotNntp($this->_settings->get('nntp_hdr'));

		$spotsOverview = new SpotsOverview($this->_db, $this->_settings);

		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_spotimage, '');

		# Haal de volledige spotinhoud op
		$fullSpot = $this->_tplHelper->getFullSpot($this->_messageid, true);

		/* Als de HDR en de NZB host hetzelfde zijn, zet geen tweede verbinding op */
		$settings_nntp_hdr = $this->_settings->get('nntp_hdr');
		$settings_nntp_nzb = $this->_settings->get('nntp_nzb');
		if ($settings_nntp_hdr['host'] == $settings_nntp_nzb['host']) {
			$nzb_spotnntp = $hdr_spotnntp;
		} else {
			$nzb_spotnntp = new SpotNntp($this->_settings->get('nntp_nzb'));
		} # else

		# Images mogen gecached worden op de client
		$this->sendExpireHeaders(false);

		# Haal de image op
		list($header, $image) = $spotsOverview->getImage($fullSpot, $nzb_spotnntp);
		
		header($header);
		echo $image;
	} # render
	
} # SpotPage_getimage
