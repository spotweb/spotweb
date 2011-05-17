<?php
class SpotPage_edituser extends SpotPage_Abs {
	private $_editUserForm;
	private $_userIdToEdit;
	
	function __construct(SpotDb $db, SpotSettings $settings, $currentSession, $params) {
		parent::__construct($db, $settings, $currentSession);
		$this->_editUserForm = $params['edituserform'];
		$this->_userIdToEdit = $params['userid'];
	} # ctor

	function render() {
		$formMessages = array('errors' => array(),
							  'info' => array());
							  
		# edituser resultaat is standaard niet geprobeerd
		$editResult = array();

		# Instantieer het Spot user system
		$spotUserSystem = new SpotUserSystem($this->_db, $this->_settings);
		
		# zet de page title
		$this->_pageTitle = "spot: edit user";
		
		# haal de te editten user op 
		$spotUser = $this->_db->getUser($this->_userIdToEdit);
		if ($spotUser === false) {
			$formMessages['errors'][] = array('edituser_usernotfound', array($spotUser['username']));
			$editResult = array('result' => 'failure');
		} # if

		# Bepaal welke actie er gekozen was (welke knop ingedrukt was)
		$formAction = '';
		if (isset($this->_editUserForm['submitedit'])) {
			$formAction = 'edit';
			unset($this->_editUserForm['submitedit']);
		} elseif (isset($this->_editUserForm['submitdelete'])) {
			$formAction = 'delete';
			unset($this->_editUserForm['submitdelete']);
		} elseif (isset($this->_editUserForm['submitresetuserapi'])) {
			$formAction = 'resetapi';
			unset($this->_editUserForm['submitresetuserapi']);
		} # else

		# Is dit een submit van een form, of nog maar de aanroep?
		if ((!empty($formAction)) && (empty($formMessages['errors']))) {
			switch($formAction) {
				case 'delete' : {
					$spotUser = array_merge($spotUser, $this->_editUserForm);
					if ($spotUser['userid'] == SPOTWEB_ANONYMOUS_USERID) {
						$formMessages['errors'][] = array('edituser_cannoteditanonymous', array());
						$editResult = array('result' => 'failure');
					} else {
						$spotUserSystem->removeUser($spotUser['userid']);
						$editResult = array('result' => 'success');
					} # else

					break;
				} # case delete

				case 'edit'	: {
					# valideer de user
					$spotUser = array_merge($spotUser, $this->_editUserForm);
					$formMessages['errors'] = $spotUserSystem->validateUserRecord($spotUser);

					if (empty($formMessages['errors'])) {
						# voeg de user toe
						$spotUserSystem->setUser($spotUser);

						# als de gebruker een nieuw wachtwoord opgegeven heeft, update dan 
						# het wachtwoord ook
						if (!empty($spotUser)) {
							$spotUserSystem->setUserPassword($spotUser);
						} # if

						# als het toevoegen van de user gelukt is, laat het weten
						$editResult = array('result' => 'success');
					} else {
						$editResult = array('result' => 'failure');
					} # else
					break;
				} # case 'edit' 

				case 'resetapi' : {
					if ($spotUser['userid'] == SPOTWEB_ANONYMOUS_USERID) {
						$formMessages['errors'][] = array('edituser_cannoteditanonymous', array());
						$editResult = array('result' => 'failure');
					} else {
						$user = $spotUserSystem->resetUserApi($spotUser);
						$editResult = array('result' => 'success', 'newapikey' => $user['apikey']);
					} # else

					break;
				} # case resetapi
			} # switch
		} # if

		#- display stuff -#
		$this->template('edituser', array('edituserform' => $spotUser,
										    'formmessages' => $formMessages,
											'editresult' => $editResult));
	} # render
	
} # class SpotPage_edituser
