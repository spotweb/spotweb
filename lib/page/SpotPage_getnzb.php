<?php
class SpotPage_getnzb extends SpotPage_Abs {
	private $_messageid;
	private $_action;
	
	function __construct(Dao_Factory $daoFactory, SpotSettings $settings, $currentSession, $params) {
		parent::__construct($daoFactory, $settings, $currentSession);
		$this->_messageid = $params['messageid'];
		$this->_action = $params['action'];
	} # ctor

	
	function render() {
		# Check the users' basic rights
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_retrieve_nzb, '');
		
		/*
		 * If the user has configured download integration, make sure the user has
		 * permission for this specific download integration
		 */
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

		# We do not want NZB files to be cached on the client
		$this->sendExpireHeaders(true);

		try {
			$spotNzb = new SpotNzb($this->_daoFactory, $this->_settings);
			$spotNzb->handleNzbAction($this->_messageid, $this->_currentSession,
										$this->_action, $svcProvSpot, $svcProvNzb);
			
			if ($this->_action != 'display') {
				echo "<xml><result>OK</result><msg></msg></xml>";
			} # if
		}
		catch(Exception $x) {
			echo "<xml><result>ERROR</result><msg>" . $x->getMessage() . "</msg></xml>";
		} # catch
	} # render
	
} # SpotPage_getnzb
