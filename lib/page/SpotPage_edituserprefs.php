<?php
class SpotPage_edituserprefs extends SpotPage_Abs {
	private $_editUserPrefsForm;
	private $_userIdToEdit;
	
	function __construct(SpotDb $db, SpotSettings $settings, $currentSession, $params) {
		parent::__construct($db, $settings, $currentSession);
		$this->_editUserPrefsForm = $params['edituserprefsform'];
		$this->_userIdToEdit = $params['userid'];
	} # ctor

	function render() {
		$formMessages = array('errors' => array(),
							  'info' => array());
							  
		# edituserprefs resultaat is standaard niet geprobeerd
		$editResult = array();

		# Instantieer het Spot user system
		$spotUserSystem = new SpotUserSystem($this->_db, $this->_settings);
		
		# zet de page title
		$this->_pageTitle = "spot: edit user preferences";
		
		# haal de te editten user op 
		$spotUser = $this->_db->getUser($this->_userIdToEdit);
		if ($spotUser === false) {
			$formMessages['errors'][] = array('edituser_usernotfound', array($spotUser['username']));
			$editResult = array('result' => 'failure');
		} # if

		# Bepaal welke actie er gekozen was (welke knop ingedrukt was)
		$formAction = '';
		if (isset($this->_editUserPrefsForm['submitedit'])) {
			$formAction = 'edit';
			unset($this->_editUserPrefsForm['submitedit']);
		} # if

		# Is dit een submit van een form, of nog maar de aanroep?
		if ((!empty($formAction)) && (empty($formMessages['errors']))) {
			switch($formAction) {
				case 'edit'	: {
					# We vragen de anonymous user account op, omdat die z'n preferences gebruikt worden
					# als basis.
					$anonUser = $this->_db->getUser(SPOTWEB_ANONYMOUS_USERID);

					# user preferences mergen met anonymous account
					$spotUser['prefs'] = array_merge($anonUser['prefs'], $this->_editUserPrefsForm);
					$spotUser['prefs'] = $spotUserSystem->cleanseUserPreferences($spotUser['prefs'], $anonUser['prefs']);
					
					# controleer en repareer alle preferences 
					$formMessages['errors'] = $spotUserSystem->validateUserPreferences($spotUser['prefs']);

					if (empty($formMessages['errors'])) {
						# bewerkt de user
						$spotUserSystem->setUser($spotUser);

						# als het toevoegen van de user gelukt is, laat het weten
						$editResult = array('result' => 'success');
					} else {
						$editResult = array('result' => 'failure');
					} # else
					break;
				} # case 'edit' 
			} # switch
		} # if

		#- display stuff -#
		$this->template('edituserprefs', array('edituserprefsform' => $spotUser,
										    'formmessages' => $formMessages,
											'edituserprefsresult' => $editResult));
	} # render
	
} # class SpotPage_edituserprefs
