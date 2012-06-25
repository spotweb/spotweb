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

		# Check users' permissions
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_spotimage, '');
		
		# Haal de image op
		if (isset($this->_image['type']) && $this->_image['type'] == 'speeddial') {
			/*
			 * Because the speeddial image shows stuff like last update and amount of new spots,
			 * we want to make sure this is not a totally closed system
			 */
			$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_spots_index, '');

			# init
			$spotImage = new SpotImage($this->_db);

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
			$data = $spotsOverview->getStatisticsImage($graph, $limit, $settings_nntp_hdr, $this->_currentSession['user']['prefs']['user_language']);
		} elseif (isset($this->_image['type']) && $this->_image['type'] == 'avatar') {
			# Check users' permissions
			$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_spotimage, 'avatar');
			
			# init
			$spotsOverview = new SpotsOverview($this->_db, $this->_settings);

			$imgDefaults = array('md5' => false,
								 'size' => 80,
								 'default' => 'identicon',
								 'rating' => 'g');
			$imgSettings = array_merge($imgDefaults, $this->_image);

			if ($imgSettings['size'] < 1 || $imgSettings['size'] > 512) {
				$imgSettings['size'] = $imgDefaults['size'];
			} # if

			if (!in_array($imgSettings['default'], array('identicon', 'mm', 'monsterid', 'retro', 'wavatar'))) {
				$imgSettings['default'] = $imgDefaults['default'];
			} # if

			if (!in_array($imgSettings['rating'], array('g', 'pg', 'r', 'x'))) {
				$imgSettings['rating'] = $imgDefaults['rating'];
			} # if

			$data = $spotsOverview->getAvatarImage($imgSettings['md5'], $imgSettings['size'], $imgSettings['default'], $imgSettings['rating']);
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

			/*
			 * Actually retrieve the image 
			 */
			$providerSpotImage = new Services_Providers_SpotImage(new Services_Providers_Http($this->_db->_cacheDao),
																  new Services_Nntp_SpotReading($nzb_spotnntp),
											  					  $this->_db->_cacheDao);
			$data = $providerSpotImage->fetchSpotImage($fullSpot);
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
