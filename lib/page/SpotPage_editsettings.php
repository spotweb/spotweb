<?php
class SpotPage_editsettings extends SpotPage_Abs {
	private $_editSettingsFrom;
	
	function __construct(SpotDb $db, SpotSettings $settings, $currentSession, $params) {
		parent::__construct($db, $settings, $currentSession);
		$this->_editSettingsForm = $params['editsettingsform'];
	} # ctor

	function render() {
		$formMessages = array('errors' => array(),
							  'info' => array());
							  
		# Validate proper permissions
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_edit_settings, '');

		# Make sure the editresult is set to 'not comited' per default
		$editResult = array();
		
		# zet de page title
		$this->_pageTitle = _('Settings');

		/* 
		 * bring the forms' action into the local scope for 
		 * easier access
		 */
		$formAction = $this->_editSettingsForm['action'];
		
		# Are we trying to submit this form, or only rendering it?
		if ((!empty($formAction)) && (empty($formMessages['errors']))) {
			switch($formAction) {
				case 'edit'	: {
					# Validate and apply all settings
					list($formMessages['errors'], $newSettings) = $this->_settings->validateSettings($this->_editSettingsForm);

					if (empty($formMessages['errors'])) {
						# and actually update the user in the database
						$this->_settings->setSettings($newSettings);

						# if we didnt get an exception, it automatically succeeded
						$editResult = array('result' => 'success');
					} else {
						$editResult = array('result' => 'failure');
					} # else
					
					break;
				} # case 'edit' 
				
				case 'cancel' : {
					$editResult = array('result' => 'success');
				} # case 'cancel'
			} # switch
		} # if

		#- display stuff -#
		$this->template('editsettings', array('editsettingsform' => $this->_settings,
											  'formmessages' => $formMessages,
											  'http_referer' => $this->_editSettingsForm['http_referer'],
											  'adminpanelresult' => $editResult));
	} # render
	
} # class SpotPage_edituserprefs
