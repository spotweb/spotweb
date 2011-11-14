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

		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_spotimage, '');

		# Haal de image op
		if (isset($this->_image['type']) && $this->_image['type'] == 'speeddial') {
			/*
			 * Because the speeddial image shows stuff like last update and amount of new spots,
			 * we want to make sure this is not a totally closed system
			 */
			$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_spots_index, '');

			# init
			$spotImage = new SpotImage($this->_db, $this->_settings);

			$totalSpots = $this->_db->getSpotCount('');
			$newSpots = $this->_tplHelper->getNewCountForFilter('');
			$lastUpdate = $this->_tplHelper->formatDate($this->_db->getLastUpdate($settings_nntp_hdr['host']), 'lastupdate');
			$data = $spotImage->createSpeedDial($totalSpots, $newSpots, $lastUpdate);
		} elseif (isset($this->_image['type']) && $this->_image['type'] == 'statistics') {
			$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_statistics, '');

			# init
			$spotsOverview = new SpotsOverview($this->_db, $this->_settings);

			$graph = (isset($this->_image['graph'])) ? $this->_image['graph'] : false;
			$limit = (isset($this->_image['limit'])) ? $this->_image['limit'] : false;
			$data = $spotsOverview->getStatisticsImage($graph, $limit, $settings_nntp_hdr);
		} elseif (isset($this->_image['type']) && $this->_image['type'] == 'gravatar') {
			# init
			$spotsOverview = new SpotsOverview($this->_db, $this->_settings);

			$md5 = (isset($this->_image['md5'])) ? $this->_image['md5'] : false;
			$size = (isset($this->_image['size'])) ? $this->_image['size'] : 80;
			$default = (isset($this->_image['default'])) ? $this->_image['default'] : 'monsterid';
			$rating = (isset($this->_image['rating'])) ? $this->_image['rating'] : 'g';
			$data = $spotsOverview->getGravatarImage($md5, $size, $default, $rating);
		} else {
			# init
			$spotsOverview = new SpotsOverview($this->_db, $this->_settings);
			$hdr_spotnntp = new SpotNntp($settings_nntp_hdr);

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

		# Images mogen gecached worden op de client, behalve als is opgegeven dat het niet mag
		if (isset($data['expire'])) {
			$this->sendExpireHeaders(true);
		} else {
			$this->sendExpireHeaders(false);
		} # else

		header("Content-Type: " . image_type_to_mime_type($data['metadata']['imagetype']));
		header("Content-Length: " . strlen($data['content'])); 
		echo $data['content'];
	} # render

} # SpotPage_getimage
