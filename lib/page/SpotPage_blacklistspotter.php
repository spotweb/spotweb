<?php
class SpotPage_blacklistspotter extends SpotPage_Abs {
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
				
		# creeer een default blacklist
		$blackList = array('spotterid' => '',
						   'origin' => '');
		
		# blacklist is standaard niet geprobeerd
		$postResult = array();
		
		# zet de page title
		$this->_pageTitle = "report: blacklist spotter";

		/* 
		 * bring the forms' action into the local scope for 
		 * easier access
		 */
		$formAction = $this->_editFilterForm['action'];

		# Als de user niet ingelogged is, dan heeft dit geen zin
		if ($this->_currentSession['user']['userid'] == SPOTWEB_ANONYMOUS_USERID) {
			$postResult = array('result' => 'notloggedin');

			$formAction = '';
		} # if
		
		if (!empty($formAction)) {
			# zorg er voor dat alle variables ingevuld zijn
			$blackList = array_merge($blackList, $this->_blForm);

			# Instantieer het Spot user system
			$spotUserSystem = new SpotUserSystem($this->_db, $this->_settings);
			
			switch($formAction) {
				case 'addspotterid'		: {
					$spotUserSystem->addSpotterToBlacklist($this->_currentSession['user']['userid'], $blackList['spotterid'], $blackList['origin']);
					break;
				} # case addspotterid
				
				case 'removespotterid'	: {
					$spotUserSystem->removeSpotterFromBlacklist($this->_currentSession['user']['userid'], $blackList['spotterid']);
					break;
				} # case removespotterid
			} # switch
			
			$postResult = array('result' => 'success');
		} # if
		
		#- display stuff -#
		$this->template('blacklistspotter', array('blacklistspotter' => $blackList,
											 'formmessages' => $formMessages,
											 'postresult' => $postResult));
	} # render	
} # class SpotPage_blacklistspotter
