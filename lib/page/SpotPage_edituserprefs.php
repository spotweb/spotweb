<?php
class SpotPage_edituserprefs extends SpotPage_Abs {
	private $_editUserPrefsForm;
	private $_userIdToEdit;
	private $_dialogembedded;
	
	function __construct(Dao_Factory $daoFactory, SpotSettings $settings, $currentSession, $params) {
		parent::__construct($daoFactory, $settings, $currentSession);
		$this->_editUserPrefsForm = $params['edituserprefsform'];
		$this->_userIdToEdit = $params['userid'];
		$this->_dialogembedded = $params['dialogembedded'];
	} # ctor

	function render() {
		# Make sure the editresult is set to 'not comited' per default
		$result = new Dto_FormResult('notsubmitted');
							  
		# Validate proper permissions
		if ($this->_userIdToEdit == $this->_currentSession['user']['userid']) {
			$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_edit_own_userprefs, '');
		} else {
			$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_edit_other_users, '');
		} # if
		
		# Instantiat the user system as necessary for the management of user preferences
		$spotUserSystem = new SpotUserSystem($this->_daoFactory, $this->_settings);
		
		# zet de page title
		$this->_pageTitle = "spot: edit user preferences";
		
		# retrieve the to-edit user
		$spotUser = $spotUserSystem->getUser($this->_userIdToEdit);
		if ($spotUser === false) {
			$result->addError(sprintf(_('User %d can not be found'), $this->_userIdToEdit));
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
		$anonUser = $spotUserSystem->getUser(SPOTWEB_ANONYMOUS_USERID);

		# Are we trying to submit this form, or only rendering it?
		if ((!empty($formAction)) && (!$result->isError())) {
			switch($formAction) {
				case 'edit'	: {
					/*
					 * We have a few dummy preferenes -- these are submitted like a checkbox for example
					 * but in reality do something completely different.
					 *
					 * Because we use cleanseUserPreferences() those dummies will not end up in the database
					 */
					if (isset($this->_editUserPrefsForm['_dummy_prevent_porn'])) {
						$spotUserSystem->setEroticIndexFilter($spotUser['userid']);
					} else {
						$spotUserSystem->removeIndexFilter($spotUser['userid']);
					} # if

					# Save the current' user preferences because we need them before cleansing 
					$savePrefs = $spotUser['prefs'];
					$spotUser['prefs'] = $spotUserSystem->cleanseUserPreferences($this->_editUserPrefsForm, 
												$anonUser['prefs'],
												$this->_tplHelper->getTemplatePreferences());

					# Validate all preferences
					$result = $spotUserSystem->validateUserPreferences($spotUser['prefs'], $savePrefs);
					$spotUser['prefs'] = $result->getData('prefs');

					# Make sure user has permission to select this template
					if ($spotUser['prefs']['normal_template'] != $savePrefs['normal_template']) {
						$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_select_template, $spotUser['prefs']['normal_template']);
					} # if

					if ($spotUser['prefs']['mobile_template'] != $savePrefs['mobile_template']) {
						$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_select_template, $spotUser['prefs']['mobile_template']);
					} # if

					if ($spotUser['prefs']['tablet_template'] != $savePrefs['tablet_template']) {
						$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_select_template, $spotUser['prefs']['tablet_template']);
					} # if

					if ($result->isSuccess()) {
						# Make sure an NZB file was provided
						if (isset($_FILES['edituserprefsform'])) {
							$uploadError = $_FILES['edituserprefsform']['error']['avatar'];
							
							/**
							 * Give a proper error if the file is too large, because changeAvatar() wont see
							 * these errors so they cannot provide the error
							 */
							if (($uploadError == UPLOAD_ERR_FORM_SIZE) || ($uploadError == UPLOAD_ERR_INI_SIZE)) {
								$result->addError(_("Uploaded file is too large"));
							}  # if 
							
							if ($uploadError == UPLOAD_ERR_OK) {
								$avatarResult  = $spotUserSystem->changeAvatar(
																$spotUser['userid'], 
																file_get_contents($_FILES['edituserprefsform']['tmp_name']['avatar']));

								/*
								 * Merge the result of the avatar update to our
								 * total result
								 */
								$result->mergeResult($avatarResult);
							} # if
						} # if
					} # if

					if ($result->isSuccess()) { 
						# and actually update the user in the database
						$spotUserSystem->setUser($spotUser);
					} # if

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
					$result->setResult('success');
				} # case 'cancel'
			} # switch
		} # if

		#- display stuff -#
		$this->template('edituserprefs', array('edituserprefsform' => $spotUser['prefs'],
											'spotuser' => $spotUser,
											'dialogembedded' => $this->_dialogembedded,
											'http_referer' => $this->_editUserPrefsForm['http_referer'],
											'result' => $result));
	} # render
	
} # class SpotPage_edituserprefs
