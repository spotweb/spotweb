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
		
		# Instantiate the user system as necessary for the management of user preferences
		$spotUserSystem = new SpotUserSystem($this->_daoFactory, $this->_settings);
		
		# set the page title
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

		# Are we trying to submit this form, or only rendering it?
		if ((!empty($formAction)) && (!$result->isError())) {
			switch($formAction) {
				case 'edit'	: {
					$svcActn_EditUserPrefs = new Service_Actions_EditUserPrefs();
					$result = $svcActn_EditUserPrefs->editUserPrefs($this->_editUserPrefsForm,
																	$spotUser);
					
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
