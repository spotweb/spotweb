<?php
require_once "lib/exceptions/InvalidLocalDirException.php";

# NZB Utility functies
class SpotNzb {
	private $_settings;
	private $_db;
	
	function __construct(SpotDb $db, SpotSettings $settings) {
		$this->_db = $db;
		$this->_settings = $settings;
	} # ctor

	/*
	 * Behandel de gekozen actie voor de NZB file
	 */
	function handleNzbAction($messageids, $userSession, $action, $hdr_spotnntp, $nzb_spotnntp) {
		if (!is_array($messageids)) {
			$messageids = array($messageids);
		} # if
		
		# Controleer de security
		$userSession['security']->fatalPermCheck(SpotSecurity::spotsec_retrieve_nzb, '');
		if ($action != 'display') {
			$userSession['security']->fatalPermCheck(SpotSecurity::spotsec_download_integration, $action);
		} # if
			
		# Haal de volledige spot op en gebruik de informatie daarin om de NZB file op te halen
		$spotsOverview = new SpotsOverview($this->_db, $this->_settings);
		
		$nzbList = array();
		foreach($messageids as $thisMsgId) {
			$fullSpot = $spotsOverview->getFullSpot($thisMsgId, $userSession['user']['userid'], $hdr_spotnntp);
			
			if (!empty($fullSpot['nzb'])) {
				$nzbList[] = array('spot' => $fullSpot, 
								   'nzb' => $spotsOverview->getNzb($fullSpot['nzb'], $nzb_spotnntp));
			} # if
		} # foreach

		# send nzblist to NzbHandler plugin
		$nzbHandlerFactory = new NzbHandler_Factory();
		$nzbHandler = $nzbHandlerFactory->build($this->_settings, $action);

		$nzbHandler->processNzb($fullSpot, $nzbList);

		# en voeg hem toe aan de lijst met downloads
		if ($userSession['user']['prefs']['keep_downloadlist']) {
			if ($userSession['security']->allowed(SpotSecurity::spotsec_keep_own_downloadlist, '')) {
				foreach($messageids as $thisMsgId) {
					$this->_db->addToSpotStateList(SpotDb::spotstate_Down, $thisMsgId, $userSession['user']['userid']);
				} # foreach
			} # if
		} # if
	} # handleNzbAction
	
} # SpotNzb
