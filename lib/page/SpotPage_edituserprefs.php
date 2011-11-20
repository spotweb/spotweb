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
							  
		# Validate proper permissions
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_edit_own_userprefs, '');
		
		# Make sure the editresult is set to 'not comited' per default
		$editResult = array();

		# Instantiat the user system as necessary for the management of user preferences
		$spotUserSystem = new SpotUserSystem($this->_db, $this->_settings);
		
		# zet de page title
		$this->_pageTitle = "spot: edit user preferences";
		
		# Retrieve the user we want to edit, this is for now always the current user
		$spotUser = $this->_db->getUser($this->_currentSession['user']['userid']);
		if ($spotUser === false) {
			$formMessages['errors'][] = sprintf(_('User cannot be found'), $spotUser['username']);
			$editResult = array('result' => 'failure');
		} # if
		
		/*
		 * Determine what action the user choose (which button was pressed in the UI) and 
		 * set the formaction for this. We cannot use the value of the buttons because those
		 * must be able to be translated
		 */
		$formAction = '';
		if (isset($this->_editUserPrefsForm['submitedit'])) {
			$formAction = 'edit';
			unset($this->_editUserPrefsForm['submitedit']);
		} elseif (isset($this->_editUserPrefsForm['submitcancel'])) {
			$formAction = 'cancel';
			unset($this->_editUserPrefsForm['submitcancel']);
		} # if
		
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
					 * We ave a few dummy preferenes -- these are submitted like a checkbox for example
					 * but in reality do something completely difference.
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
								  'icon' => 'spotweb.png',
								  'tree' => '~cat0_z3'));
					} else {
						$spotUserSystem->removeIndexFilter($spotUser['userid']);
					} # if

					/*
					 * We do not want any user preferences to be submitted which aren't in the anonuser preferences,
					 * as this would alow garbage preferences or invalid settings for non-existing preferences.
					 *
					 * A simple recursive merge with the anonuer preferences is not possible because some browsers
					 * just don't submit the values of a checkbox when the checkbox is deselected, in that case the
					 * anonuser's settings would be set instead of the false setting as it should be.
					 */
					$spotUser['prefs'] = $spotUserSystem->cleanseUserPreferences($this->_editUserPrefsForm, $anonUser['prefs']);

					# Validate all preferences
					list($formMessages['errors'], $spotUser['prefs']) = $spotUserSystem->validateUserPreferences($spotUser['prefs'], $this->_currentSession['user']['prefs']);

					if (empty($formMessages['errors'])) {
					error_log(serialize($_FILES));
					
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
																$this->_currentSession['user']['userid'], 
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
					 * The safes option is to just do this wih each preferences submit
					 */
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
