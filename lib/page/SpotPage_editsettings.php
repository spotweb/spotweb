<?php

class SpotPage_editsettings extends SpotPage_Abs {
	private $_editSettingsForm;
	
	function __construct(Dao_Factory $daoFactory, Services_Settings_Container $settings, array $currentSession, array $params) {
		parent::__construct($daoFactory, $settings, $currentSession);
		$this->_editSettingsForm = $params['editsettingsform'];
	} # ctor

	function render() {
		# Validate proper permissions
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_edit_settings, '');

		# Make sure the editresult is set to 'not comited' per default
		$result = new Dto_FormResult('notsubmitted');
		
		# set the page title
		$this->_pageTitle = _('Settings');

		/* 
		 * bring the forms' action into the local scope for 
		 * easier access
		 */
		$formAction = $this->_editSettingsForm['action'];
		
		# Are we trying to submit this form, or only rendering it?
        if (!empty($formAction)) {
			switch($formAction) {
				case 'edit'	: {
					# Validate and apply all settings
                    $svcSettings = new Services_Settings_Base($this->_settings, $this->_daoFactory->getBlackWhiteListDao());
					$result = $svcSettings->validateSettings($this->_editSettingsForm);

					if ($result->isSuccess()) { 
						# and actually update the user in the database
						$newSettings = $result->getData('settings');
						$svcSettings->setSettings($newSettings);
					} # if
					
					break;
				} # case 'edit' 
				
				case 'cancel' : {
					$result = new Dto_FormResult('success');
				} # case 'cancel'
			} # switch
		} # if

		#- display stuff -#
		$this->template('editsettings', array('editsettingsform' => $this->_settings,
											  'result' => $result,
											  'http_referer' => $this->_editSettingsForm['http_referer']));
	} # render
	
} # class SpotPage_editsettings
