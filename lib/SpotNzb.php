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
	function handleNzbAction($messageids, $ourUserId, $action, $hdr_spotnntp, $nzb_spotnntp) {
		if (!is_array($messageids)) {
			$messageids = array($messageids);
		} # if
		
		# Haal de volledige spot op en gebruik de informatie daarin om de NZB file op te halen
		$spotsOverview = new SpotsOverview($this->_db, $this->_settings);
		
		$nzbList = array();
		foreach($messageids as $thisMsgId) {
			$fullSpot = $spotsOverview->getFullSpot($thisMsgId, $ourUserId, $hdr_spotnntp);
			
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
		if ($this->_settings->get('keep_downloadlist')) {
			foreach($messageids as $thisMsgId) {
				$this->_db->addToSpotStateList("download", $thisMsgId, $ourUserId);
			} # foreach
		} # if
	} # handleNzbAction
	
} # SpotNzb
