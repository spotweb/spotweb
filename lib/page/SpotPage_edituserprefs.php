<?php
class SpotPage_edituserprefs extends SpotPage_Abs {
	private $_editUserPrefsForm;
	private $_userIdToEdit;
	private $_dialogembedded;
	
	function __construct(SpotDb $db, SpotSettings $settings, $currentSession, $params) {
		parent::__construct($db, $settings, $currentSession);
		$this->_editUserPrefsForm = $params['edituserprefsform'];
		$this->_userIdToEdit = $params['userid'];
		$this->_dialogembedded = $params['dialogembedded'];
	} # ctor

	function render() {
		$formMessages = array('errors' => array(),
							  'info' => array());
							  
		# Validate proper permissions
		if ($this->_userIdToEdit == $this->_currentSession['user']['userid']) {
			$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_edit_own_userprefs, '');
		} else {
			$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_edit_other_users, '');
		} # if
		
		# Make sure the editresult is set to 'not comitted' per default
		$editResult = array();

		# Instantiat the user system as necessary for the management of user preferences
		$spotUserSystem = new SpotUserSystem($this->_db, $this->_settings);
		
		# zet de page title
		$this->_pageTitle = "spot: edit user preferences";
		
		# retrieve the to-edit user
		$spotUser = $this->_db->getUser($this->_userIdToEdit);
		if ($spotUser === false) {
			$formMessages['errors'][] = sprintf(_('User %d can not be found'), $this->_userIdToEdit);
			$editResult = array('result' => 'failure');
		} # if
		
		/* 
		 * bring the forms' action into the local scope for 
		 * easier access
		 */
		$formAction = $this->_editUserPrefsForm['action'];

		/*
		 * We want the annymous' users account so we can use this users' preferences as a
		 * template. This makes sure all properties are atleast set.
		 */
		$anonUser = $this->_db->getUser(SPOTWEB_ANONYMOUS_USERID);

		# Are we trying to submit this form, or only rendering it?
		if ((!empty($formAction)) && (empty($formMessages['errors']))) {
			switch($formAction) {
				case 'edit'	: {
					/*
					 * We have a few dummy preferenes -- these are submitted like a checkbox for example
					 * but in reality do something completely different.
					 *
					 * Because we use cleanseUserPreferences() those dummies will not end up in the database
					 */
					if (isset($this->_editUserPrefsForm['_dummy_prevent_porn'])) {
						$spotUserSystem->setIndexFilter(
							$spotUser['userid'],
							array('valuelist' => array(),
								  'title' => 'Index filter',
								  'torder' => 999,
								  'tparent' => 0,
								  'children' => array(),
								  'filtertype' => 'index_filter',
								  'sorton' => '',
								  'sortorder' => '',
								  'enablenotifty' => false,
								  'icon' => 'spotweb.png',
								  'tree' => '~cat0_z3'));
					} else {
						$spotUserSystem->removeIndexFilter($spotUser['userid']);
					} # if

					# Save the current' user preferences because we need them before cleansing 
					$savePrefs = $spotUser['prefs'];
					$spotUser['prefs'] = $spotUserSystem->cleanseUserPreferences($this->_editUserPrefsForm, $anonUser['prefs']);

					# Validate all preferences
					list($formMessages['errors'], $spotUser['prefs']) = $spotUserSystem->validateUserPreferences($spotUser['prefs'], $savePrefs);

					if (empty($formMessages['errors'])) {
						# Make sure an NZB file was provided
						if (isset($_FILES['edituserprefsform'])) {
							$uploadError = $_FILES['edituserprefsform']['error']['avatar'];
							
							/**
							 * Give a proper error if the file is too large, because changeAvatar() wont see
							 * these errors so they cannot provide the error
							 */
							if (($uploadError == UPLOAD_ERR_FORM_SIZE) || ($uploadError == UPLOAD_ERR_INI_SIZE)) {
								$formMessages['errors'][] = _("Uploaded file is too large");
							}  # if 
							
							if ($uploadError == UPLOAD_ERR_OK) {
								$formMessages['errors'] = $spotUserSystem->changeAvatar(
																$spotUser['userid'], 
																file_get_contents($_FILES['edituserprefsform']['tmp_name']['avatar']));
							} # if
						} # if
					} # if

					if (empty($formMessages['errors'])) {
						# and actually update the user in the database
						$spotUserSystem->setUser($spotUser);

						# if we didnt get an exception, it automatically succeeded
						$editResult = array('result' => 'success');
					} else {
						$editResult = array('result' => 'failure');
					} # else

					/*
					 * We have the register Spotweb with the notification providers (growl, prowl, etc) atleast once. 
					 * The safes option is to just do this wih each preferences submit. But first we create a fake
					 * session for this user.
					 */
					$fakeSession = $spotUserSystem->createNewSession($spotUser['userid']);
					$fakeSession['security'] = new SpotSecurity($this->_db, $this->_settings, $fakeSession['user'], '');

					$spotsNotifications = new SpotNotifications($this->_db, $this->_settings, $fakeSession);
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
											'dialogembedded' => $this->_dialogembedded,
											'http_referer' => $this->_editUserPrefsForm['http_referer'],
											'edituserprefsresult' => $editResult));
	} # render
	
} # class SpotPage_edituserprefs
