<?php
/*
 * FIXME
 * XXX
 * TODO
 *
 * Nakijken
 */

class SpotPage_getnzbmobile extends SpotPage_Abs {
	private $_messageid;
	private $_action;
	
	function __construct(Dao_Factory $daoFactory, Services_Settings_Base $settings, $currentSession, $params) {
		parent::__construct($daoFactory, $settings, $currentSession);
		$this->_messageid = $params['messageid'];
		$this->_action = $params['action'];
	} # ctor

	
	function render() {
		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_retrieve_nzb, '');

		# als het niet display is, check of we ook download integratie rechten hebben
		if ($this->_action != 'display') {
			$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_download_integration, $this->_action);
		} # if

		/*
		 * Create the different NNTP components
		 */
		$svcBinSpotReading = new Services_Nntp_SpotReading(Services_Nntp_EnginePool::pool($this->_settings, 'bin'));
		$svcTextSpotReading = new Services_Nntp_SpotReading(Services_Nntp_EnginePool::pool($this->_settings, 'hdr'));
		$svcProvNzb = new Services_Providers_Nzb($this->_daoFactory->getCacheDao(), $svcBinSpotReading);
		$svcProvSpot = new Services_Providers_FullSpot($this->_daoFactory->getSpotDao(), $svcTextSpotReading);

		# NZB files mogen liever niet gecached worden op de client
		$this->sendExpireHeaders(true);

		try {
			$svcActnNzb = new Services_Actions_DownloadNzb($this->_settings, $this->_daoFactory);
			$svcActnNzb->handleNzbAction($this->_messageid, $this->_currentSession,
										$this->_action, $svcProvSpot, $svcProvNzb);
			
			if ($this->_action != 'display') {
				echo "<div data-role=page><div data-role=content><p>NZB saved.</p><a href='" .$this->_settings->get('spotweburl') ."' rel=external data-role='button'>OK</a></div></div>";			
			} # if
		}
		catch(Exception $x) {
			echo "<div data-role=page><div data-role=content><p>" . $x->getMessage() . "</p><a href='". $this->_settings->get('spotweburl') ."' rel=external data-role='button'>OK</a></div></div>";
		} # catch
	} # render
	
} # SpotPage_getnzb
