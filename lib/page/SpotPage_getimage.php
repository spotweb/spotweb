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
		$settings_nntp_hdr = $this->_settings->get('nntp_hdr');
		$settings_nntp_nzb = $this->_settings->get('nntp_nzb');

		# Haal de image op
		if ($this->_image == 'speeddial') {
			# init
			$spotImage = new SpotImage();
			
			$totalSpots = $this->_db->getSpotCount('');
			$newSpots = $this->_tplHelper->getNewCountForFilter('');
			$lastUpdate = $this->_tplHelper->formatDate($this->_db->getLastUpdate($settings_nntp_hdr['host']), 'lastupdate');
			$data = $spotImage->createSpeedDial($totalSpots, $newSpots, $lastUpdate);
		} else {
			# init
			$spotsOverview = new SpotsOverview($this->_db, $this->_settings);
			$hdr_spotnntp = new SpotNntp($settings_nntp_hdr);
			
			# Controleer de users' rechten
			$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_spotimage, '');

			/* Als de HDR en de NZB host hetzelfde zijn, zet geen tweede verbinding op */
			if ($settings_nntp_hdr['host'] == $settings_nntp_nzb['host']) {
				$nzb_spotnntp = $hdr_spotnntp;
			} else {
				$nzb_spotnntp = new SpotNntp($this->_settings->get('nntp_nzb'));
			} # else

			# Haal de volledige spotinhoud op
			$fullSpot = $this->_tplHelper->getFullSpot($this->_messageid, false);

			$data = $spotsOverview->getImage($fullSpot, $nzb_spotnntp);
		} # else

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
