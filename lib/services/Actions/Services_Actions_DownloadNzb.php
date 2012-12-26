<?php
class Services_Actions_DownloadNzb {
	private $_settings;
	private $_daoFactory;
	
	function __construct(Services_Settings_Base $settings, Dao_Factory $daoFactory) {
		$this->_settings = $settings;
		$this->_daoFactory = $daoFactory;
	} # ctor

	/*
	 * Check whether the appropriate permissions are there, and if so actually run the code
	 */
	function handleNzbAction($messageids, array $currEntsession, $action, Services_Providers_FullSpot $svcProvSpot, Services_Providers_Nzb $svcProvNzb) {
		if (!is_array($messageids)) {
			$messageids = array($messageids);
		} # if
		
		# Make sure the user has the appropriate permissions
		$currEntsession['security']->fatalPermCheck(SpotSecurity::spotsec_retrieve_nzb, '');
		if ($action != 'display') {
			$currEntsession['security']->fatalPermCheck(SpotSecurity::spotsec_download_integration, $action);
		} # if

		/*
		 * Get all the full spots for all of the specified NZB files
		 */			
		$nzbList = array();
		foreach($messageids as $thisMsgId) {
			$fullSpot = $svcProvSpot->fetchFullSpot($thisMsgId, $currEntsession['user']['userid']);
			
			if (!empty($fullSpot['nzb'])) {
				$nzbList[] = array('spot' => $fullSpot, 
								   'nzb' => $svcProvNzb->fetchNzb($fullSpot));
			} # if
		} # foreach

		/*
		 * send nzblist to NzbHandler plugin
		 */
		$nzbHandlerFactory = new NzbHandler_Factory();
		$nzbHandler = $nzbHandlerFactory->build($this->_settings, $action, $currEntsession['user']['prefs']['nzbhandling']);

		$nzbHandler->processNzb($fullSpot, $nzbList);

		/*
		 * and mark the spot as downloaded
		 */
		if ($currEntsession['user']['prefs']['keep_downloadlist']) {
			if ($currEntsession['security']->allowed(SpotSecurity::spotsec_keep_own_downloadlist, '')) {
				foreach($messageids as $thisMsgId) {
					$this->_daoFactory->getSpotStateList()->addToDownloadList($thisMsgId, $currEntsession['user']['userid']);
				} # foreach
			} # if
		} # if

		# and send notifications
		$spotsNotifications = new SpotNotifications($this->_daoFactory, $this->_settings, $currEntsession);
		$spotsNotifications->sendNzbHandled($action, $fullSpot);
	} # handleNzbAction
	
} # Services_Actions_DownloadNzb