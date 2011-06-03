<?php
class SpotPage_edituser extends SpotPage_Abs {
	private $_editUserForm;
	private $_userIdToEdit;
	
	function __construct(SpotDb $db, SpotSettings $settings, $currentSession, $params) {
		parent::__construct($db, $settings, $currentSession);
		$this->_editUserForm = $params['edituserform'];
		$this->_userIdToEdit = $params['userid'];
	} # ctor
	
	/* 
	 * Wis niet gewenste velden uit het formulier om te voorkomen dat
	 * er andere records geupdate kunnen worden
	 */
	function cleanseEditForm($editForm) {
		$validFields = array('firstname', 'lastname', 'mail', 'newpassword1', 'newpassword2');
		foreach($editForm as $key => $value) {
			if (in_array($key, $validFields) === false) {
				unset($editForm[$key]);
			} # if
		} # foreach
		
		return $editForm;
	} # cleanseEditForm

	function render() {
		$formMessages = array('errors' => array(),
							  'info' => array());
							  
		# Controleer de users' rechten
		if ($this->_userIdToEdit == $this->_currentSession['user']['userid']) {
			$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_edit_own_user, '');
		} else {
			$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_edit_other_users, '');
		} # if
		
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
		} elseif (isset($this->_editUserForm['removeallsessions'])) {
			$formAction = 'removeallsessions';
			unset($this->_editUserForm['removeallsessions']);
		} # else

		# Is dit een submit van een form, of nog maar de aanroep?
		if ((!empty($formAction)) && (empty($formMessages['errors']))) {
			# sta niet toe, dat de anonymous user gewijzigd wordt
			if ($spotUser['userid'] == SPOTWEB_ANONYMOUS_USERID) {
				$formMessages['errors'][] = array('edituser_cannoteditanonymous', array());
				$editResult = array('result' => 'failure');
			} # if

			# sta niet toe, dat de admin user gewist wordt
			if (($spotUser['userid'] <= SPOTWEB_ADMIN_USERID) && ($formAction == 'delete')) {
				$formMessages['errors'][] = array('edituser_cannotremovesystemuser', array());
				$editResult = array('result' => 'failure');
			} # if
		} # if


		# Is dit een submit van een form, of nog maar de aanroep?
		if ((!empty($formAction)) && (empty($formMessages['errors']))) {
			switch($formAction) {
				case 'delete' : {
					$spotUser = array_merge($spotUser, $this->_editUserForm);
					$spotUserSystem->removeUser($spotUser['userid']);
					$editResult = array('result' => 'success');

					break;
				} # case delete

				case 'edit'	: {
					# Verwijder eventueel niet geldige velden uit het formulier
					$this->_editUserForm = $this->cleanseEditForm($this->_editUserForm);
					
					# valideer de user
					$spotUser = array_merge($spotUser, $this->_editUserForm);
					$formMessages['errors'] = $spotUserSystem->validateUserRecord($spotUser, true);

					if (empty($formMessages['errors'])) {
						# bewerkt de user
						$spotUserSystem->setUser($spotUser);

						# als de gebruker een nieuw wachtwoord opgegeven heeft, update dan 
						# het wachtwoord ook
						if (!empty($spotUser['newpassword1'])) {
							$spotUserSystem->setUserPassword($spotUser);
						} # if

						# als het toevoegen van de user gelukt is, laat het weten
						$editResult = array('result' => 'success');
					} else {
						$editResult = array('result' => 'failure');
					} # else
					break;
				} # case 'edit' 
				
				case 'removeallsessions' : {
					$spotUserSystem->removeAllUserSessions($spotUser['userid']);
					$editResult = array('result' => 'success');

					break;
				} # case 'removeallsessions'

				case 'resetapi' : {
					$user = $spotUserSystem->resetUserApi($spotUser);
					$editResult = array('result' => 'success', 'newapikey' => $user['apikey']);

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
