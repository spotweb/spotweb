<?php
class SpotPage_getimage extends SpotPage_Abs {
	private $_messageid;
	private $_image;

	function __construct(Dao_Factory $daoFactory, SpotSettings $settings, $currentSession, $params) {
		parent::__construct($daoFactory, $settings, $currentSession);

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
			$totalSpots = $this->_db->getSpotCount('');
			$newSpots = $this->_tplHelper->getNewCountForFilter('');
			$lastUpdate = $this->_tplHelper->formatDate($this->_db->getLastUpdate($settings_nntp_hdr['host']), 'lastupdate');

			$svc_ImageSpeedDial = new Services_Image_SpeedDial();
			$data = $svc_ImageSpeedDial->createSpeedDial($totalSpots, $newSpots, $lastUpdate);
		} elseif (isset($this->_image['type']) && $this->_image['type'] == 'statistics') {
			$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_statistics, '');

			$graph = (isset($this->_image['graph'])) ? $this->_image['graph'] : false;
			$limit = (isset($this->_image['limit'])) ? $this->_image['limit'] : false;

			# init
			$svcPrv_Stats = new Services_Providers_Statistics($this->_daoFactory->getSpotDao(),
															  $this->_daoFactory->getCacheDao(),
												 			  $this->_daoFactory->getNntpConfigDao()->getLastUpdate($settings_nntp_hdr['host']));
			$data = $svcPrv_Stats->renderStatImage($graph, $limit);


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
			$svc_nntphdr_engine = new Services_Nntp_Engine($settings_nntp_hdr);

			/* Als de HDR en de NZB host hetzelfde zijn, zet geen tweede verbinding op */
			if ($settings_nntp_hdr['host'] == $settings_nntp_nzb['host']) {
				$svc_nntpnzb_engine = $svc_nntphdr_engine;
			} else {
				$svc_nntpnzb_engine = new Services_Nntp_Engine($this->_settings->get('nntp_nzb'));
			} # else

			/*
			 * Retrieve the full spot, we need it to be able to retrieve the image
			 */
			$fullSpot = $this->_tplHelper->getFullSpot($this->_messageid, false);

			/*
			 * Actually retrieve the image 
			 */
			$providerSpotImage = new Services_Providers_SpotImage(new Services_Providers_Http($this->_db->_cacheDao),
																  new Services_Nntp_SpotReading($svc_nntpnzb_engine),
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
