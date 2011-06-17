<?php
class SpotPage_edituserprefs extends SpotPage_Abs {
	private $_editUserPrefsForm;
	
	function __construct(SpotDb $db, SpotSettings $settings, $currentSession, $params) {
		parent::__construct($db, $settings, $currentSession);
		$this->_editUserPrefsForm = $params['edituserprefsform'];
	} # ctor

	function render() {
		$formMessages = array('errors' => array(),
							  'info' => array());
							  
		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_edit_own_userprefs, '');
		
		# edituserprefs resultaat is standaard niet geprobeerd
		$editResult = array();

		# Instantieer het Spot user system
		$spotUserSystem = new SpotUserSystem($this->_db, $this->_settings);
		
		# zet de page title
		$this->_pageTitle = "spot: edit user preferences";
		
		# haal de te editten user op 
		$spotUser = $this->_db->getUser($this->_currentSession['user']['userid']);
		if ($spotUser === false) {
			$formMessages['errors'][] = array('edituser_usernotfound', array($spotUser['username']));
			$editResult = array('result' => 'failure');
		} # if
		
		# Bepaal welke actie er gekozen was (welke knop ingedrukt was)
		$formAction = '';
		if (isset($this->_editUserPrefsForm['submitedit'])) {
			$formAction = 'edit';
			unset($this->_editUserPrefsForm['submitedit']);
		} elseif (isset($this->_editUserPrefsForm['submitcancel'])) {
			$formAction = 'cancel';
			unset($this->_editUserPrefsForm['submitcancel']);
		} # if
		
		# We vragen de anonymous user account op, omdat die z'n preferences gebruikt worden
		# als basis.
		$anonUser = $this->_db->getUser(SPOTWEB_ANONYMOUS_USERID);
		
		# Is dit een submit van een form, of nog maar de aanroep?
		if ((!empty($formAction)) && (empty($formMessages['errors']))) {
			switch($formAction) {
				case 'edit'	: {
					# Er mogen geen user preferences doorgegeven worden, welke niet in de anonuser preferences staan,
					# een merge met de anonuser preferences kan niet, omdat dat niet opgegeven checkboxes (die komen gewoon
					# niet door), op true of false zou zetten naar gelang de default parameter en dus het formulier zou
					# negeren.
					$spotUser['prefs'] = $spotUserSystem->cleanseUserPreferences($this->_editUserPrefsForm, $anonUser['prefs']);

					# controleer en repareer alle preferences 
					list ($formMessages['errors'], $spotUser['prefs']) = $spotUserSystem->validateUserPreferences($spotUser['prefs']);

					if (empty($formMessages['errors'])) {
						# bewerkt de user
						$spotUserSystem->setUser($spotUser);

						# als het toevoegen van de user gelukt is, laat het weten
						$editResult = array('result' => 'success');
					} else {
						$editResult = array('result' => 'failure');
					} # else

					# Spotweb registreren bij de notificatie-providers. Dit moet mininmaal 1 keer, dus de veiligste optie is om dit
					# elke keer te doen als de voorkeuren worden opgeslagen
					$spotsNotifications = new SpotNotifications($this->_db, $this->_settings, $this->_currentSession);
					$spotsNotifications->register();
					
					break;
				} # case 'edit' 
				
				case 'cancel' : {
					$editResult = array('result' => 'success');
				} # case 'cancel'
			} # switch
		} # if

		#- display stuff -#
		$this->template('edituserprefs', array('edituserprefsform' => $spotUser['prefs'],
										    'formmessages' => $formMessages,
											'spotuser' => $spotUser,
											'http_referer' => $this->_editUserPrefsForm['http_referer'],
											'edituserprefsresult' => $editResult));
	} # render
	
} # class SpotPage_edituserprefs
