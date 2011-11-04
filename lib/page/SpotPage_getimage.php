<?php
class SpotPage_getimage extends SpotPage_Abs {
	private $_messageid;

	function __construct(SpotDb $db, SpotSettings $settings, $currentSession, $params) {
		parent::__construct($db, $settings, $currentSession);
		$this->_messageid = $params['messageid'];
	} # ctor

	function render() {
		$hdr_spotnntp = new SpotNntp($this->_settings->get('nntp_hdr'));
		$spotsOverview = new SpotsOverview($this->_db, $this->_settings);

		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_spotimage, '');

		# Haal de volledige spotinhoud op
		$fullSpot = $this->_tplHelper->getFullSpot($this->_messageid, false);

		/* Als de HDR en de NZB host hetzelfde zijn, zet geen tweede verbinding op */
		$settings_nntp_hdr = $this->_settings->get('nntp_hdr');
		$settings_nntp_nzb = $this->_settings->get('nntp_nzb');
		if ($settings_nntp_hdr['host'] == $settings_nntp_nzb['host']) {
			$nzb_spotnntp = $hdr_spotnntp;
		} else {
			$nzb_spotnntp = new SpotNntp($this->_settings->get('nntp_nzb'));
		} # else

		# Haal de image op
		$data = $spotsOverview->getImage($fullSpot, $nzb_spotnntp);

		# Images mogen gecached worden op de client, behalve errors
		if (isset($data['isErrorImage'])) {
			$this->sendExpireHeaders(true);
		} else {
			$this->sendExpireHeaders(false);
		} # else

		header("Content-Type: " . image_type_to_mime_type($data['metadata']['imagetype']));
		header("Content-Length: " . strlen($data['content'])); 
		echo $data['content'];
	} # render

} # SpotPage_getimage
