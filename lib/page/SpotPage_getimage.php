<?php

class SpotPage_getimage extends SpotPage_Abs {
	private $_messageid;
	private $_image;

	function __construct(Dao_Factory $daoFactory, Services_Settings_Base $settings, $currentSession, $params) {
		parent::__construct($daoFactory, $settings, $currentSession);

		$this->_messageid = $params['messageid'];
		$this->_image = $params['image'];
	} # ctor

	function render() {
		# Check users' permissions
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_spotimage, '');
		
		$settings_nntp_hdr = $this->_settings->get('nntp_hdr');
		$settings_nntp_nzb = $this->_settings->get('nntp_nzb');

		# Did the user request an SpeedDial image?
		if (isset($this->_image['type']) && $this->_image['type'] == 'speeddial') {
			$svcActn_SpeeDial = new Services_Action_SpeedDial($this->_daoFactory, $this->_spotSec, $this->_tplHelper);
			$data = $svcActn_SpeeDial->createSpeedDialImage();
			
		} elseif (isset($this->_image['type']) && $this->_image['type'] == 'statistics') {
			/* Check whether the user has view statistics permissions */
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

            $providerSpotImage = new Services_Providers_CommentImage(new Services_Providers_Http($this->_daoFactory->getCacheDao()));
			$data = $providerSpotImage ->fetchGravatarImage($this->_image);
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
            # and actually retrieve the spot
            $svcActn_GetSpot = new Services_Actions_GetSpot($this->_settings, $this->_daoFactory, $this->_spotSec);
            $fullSpot = $svcActn_GetSpot->getFullSpot($this->_currentSession, $this->_messageid, false);

			/*
			 * Actually retrieve the image 
			 */
			$providerSpotImage = new Services_Providers_SpotImage(new Services_Providers_Http($this->_daoFactory->getCacheDao()), 
																  new Services_Nntp_SpotReading($svc_nntpnzb_engine),
											  					  $this->_daoFactory->getCacheDao());
			$data = $providerSpotImage->fetchSpotImage($fullSpot);
		} # else

		# Images are allowed to be cached on the client unless the provider explicitly told us not to
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
