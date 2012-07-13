<?php
class SpotNzb {
	private $_settings;
	private $_daoFactory;
	
	function __construct(Dao_Factory $daoFactory, SpotSettings $settings) {
		$this->_daoFactory = $daoFactory;
		$this->_settings = $settings;
	} # ctor

	/*
	 * Behandel de gekozen actie voor de NZB file
	 */
	function handleNzbAction($messageids, array $userSession, $action, Services_Providers_FullSpot $svcProvSpot, Services_Providers_Nzb $svcProvNzb) {
		if (!is_array($messageids)) {
			$messageids = array($messageids);
		} # if
		
		# Controleer de security
		$userSession['security']->fatalPermCheck(SpotSecurity::spotsec_retrieve_nzb, '');
		if ($action != 'display') {
			$userSession['security']->fatalPermCheck(SpotSecurity::spotsec_download_integration, $action);
		} # if

		/*
		 * Get all the full spots for all of the specified NZB files
		 */			
		$nzbList = array();
		foreach($messageids as $thisMsgId) {
			$fullSpot = $svcProvSpot->fetchFullSpot($thisMsgId, $userSession['user']['userid']);
			
			if (!empty($fullSpot['nzb'])) {
				$nzbList[] = array('spot' => $fullSpot, 
								   'nzb' => $svcProvNzb->fetchNzb($fullSpot));
			} # if
		} # foreach

		/*
		 * send nzblist to NzbHandler plugin
		 */
		$nzbHandlerFactory = new NzbHandler_Factory();
		$nzbHandler = $nzbHandlerFactory->build($this->_settings, $action, $userSession['user']['prefs']['nzbhandling']);

		$nzbHandler->processNzb($fullSpot, $nzbList);

		/*
		 * and mark the spot as downloaded
		 */
		if ($userSession['user']['prefs']['keep_downloadlist']) {
			if ($userSession['security']->allowed(SpotSecurity::spotsec_keep_own_downloadlist, '')) {
				foreach($messageids as $thisMsgId) {
					$this->_daoFactory->getSpotStateList()->addToDownloadList($thisMsgId, $userSession['user']['userid']);
				} # foreach
			} # if
		} # if

		# en verstuur een notificatie
		$spotsNotifications = new SpotNotifications($this->_daoFactory, $this->_settings, $userSession);
		$spotsNotifications->sendNzbHandled($action, $fullSpot);
	} # handleNzbAction
	
} # SpotNzb
