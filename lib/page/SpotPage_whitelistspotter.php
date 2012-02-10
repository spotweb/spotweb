<?php
class SpotPage_whitelistspotter extends SpotPage_Abs {
	private $_blForm;
	
	function __construct(SpotDb $db, SpotSettings $settings, $currentSession, $params) {
		parent::__construct($db, $settings, $currentSession);
		$this->_blForm = $params['blform'];
	} # ctor

	function render() {
		$formMessages = array('errors' => array(),
							  'info' => array());
							  
		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_blacklist_spotter, '');
				
		# creeer een default whitelist
		$whiteList = array('spotterid' => '',
						   'origin' => '');
		
		# whitelist is standaard niet geprobeerd
		$postResult = array();
		
		# zet de page title
		$this->_pageTitle = "report: whitelist spotter";

		/* 
		 * bring the forms' action into the local scope for 
		 * easier access
		 */
		$formAction = $this->_blForm['action'];

		# Make sure the anonymous user and reserved usernames cannot post content
		$spotUserSystem = new SpotUserSystem($this->_db, $this->_settings);
		if (!$spotUserSystem->allowedToPost($this->_currentSession['user'])) {
			$postResult = array('result' => 'notloggedin');

			$formAction = '';
		} # if
		
		if (!empty($formAction)) {
			# zorg er voor dat alle variables ingevuld zijn
			$whiteList = array_merge($whiteList, $this->_blForm);

			switch($formAction) {
				case 'addspotterid'		: {
					$spotUserSystem->addSpotterToWhitelist($this->_currentSession['user']['userid'], $whiteList['spotterid'], $whiteList['origin']);
					break;
				} # case addspotterid
				
				case 'removespotterid'	: {
					$spotUserSystem->removeSpotterFromwhitelist($this->_currentSession['user']['userid'], $whiteList['spotterid']);
					break;
				} # case removespotterid
			} # switch
			
			$postResult = array('result' => 'success');
		} # if
		
		#- display stuff -#
		$this->template('whitelistspotter', array('whitelistspotter' => $whiteList,
											 'formmessages' => $formMessages,
											 'postresult' => $postResult));
	} # render	
} # class SpotPage_whitelistspotter
