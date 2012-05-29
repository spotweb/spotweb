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
	 * Erase all fields from the user record which shouldn't
	 * be in the form anyways 
	 */
	function cleanseEditForm($editForm) {
		/* Make sure the preferences aren't set using this page as it might override security */
		$validFields = array('firstname', 'lastname', 'mail', 'newpassword1', 'newpassword2', 'grouplist', 'prefs');
		foreach($editForm as $key => $value) {
			if (in_array($key, $validFields) === false) {
				unset($editForm[$key]);
			} # if
		} # foreach
		
		return $editForm;
	} # cleanseEditForm

	function render() {
		$groupMembership = array();
		$formMessages = array('errors' => array(),
							  'info' => array());
							  
		# check the users' permissions
		if ($this->_userIdToEdit == $this->_currentSession['user']['userid']) {
			$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_edit_own_user, '');
		} else {
			$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_edit_other_users, '');
		} # if
		
		# per default the result is 'not tried'
		$editResult = array();

		# Instantiate the spotuser object
		$spotUserSystem = new SpotUserSystem($this->_db, $this->_settings);
		
		# and create a nic and shiny page title
		$this->_pageTitle = "spot: edit user";
		
		# retrieve the to-edit user
		$spotUser = $this->_db->getUser($this->_userIdToEdit);
		if ($spotUser === false) {
			$formMessages['errors'][] = sprintf(_('User %d can not be found'), $this->_userIdToEdit);
			$editResult = array('result' => 'failure');
		} # if
		
		# request the users' groupmembership
		if ($spotUser != false) {
			$groupMembership = $this->_db->getGroupList($spotUser['userid']);
		} # if

		/* 
		 * bring the forms' action into the local scope for 
		 * easier access
		 */
		$formAction = $this->_editUserForm['action'];

		# Only perform certain validations when the form is actually submitted
		if ((!empty($formAction)) && (empty($formMessages['errors']))) {
			# sta niet toe, dat de admin user gewist wordt
			if (($spotUser['userid'] <= SPOTWEB_ADMIN_USERID) && ($formAction == 'delete')) {
				$formMessages['errors'][] = _('Admin and Anonymous can not be deleted');
				$editResult = array('result' => 'failure');
			} # if
		} # if


		# Only perform certain validations when the form is actually submitted
		if ((!empty($formAction)) && (empty($formMessages['errors']))) {
			switch($formAction) {
				case 'delete' : {
					$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_delete_user, '');

					$spotUser = array_merge($spotUser, $this->_editUserForm);
					$spotUserSystem->removeUser($spotUser['userid']);
					$editResult = array('result' => 'success');

					break;
				} # case delete

				case 'edit'	: {
					# Remove any non-valid fields from the array
					$this->_editUserForm = $this->cleanseEditForm($this->_editUserForm);
					
					# validate the user fields
					$spotUser = array_merge($spotUser, $this->_editUserForm);
					$formMessages['errors'] = $spotUserSystem->validateUserRecord($spotUser, true);

					if (empty($formMessages['errors'])) {
						# actually update the user record
						$spotUserSystem->setUser($spotUser);

						/*
						 * Update the users' password, but only when
						 * a new password is given
						 */
						if (!empty($spotUser['newpassword1'])) {
							$spotUserSystem->setUserPassword($spotUser);
						} # if

						/*
						 * Did we get an groupmembership list? If so,
						 * try to update it as well
						 */						
						if (isset($this->_editUserForm['grouplist'])) {
							# retrieve the list of user groups
							$groupList = array();
							foreach($this->_editUserForm['grouplist'] as $val) {
								if ($val != 'dummy') {
									$groupList[] = array('groupid' => $val,
														'prio' => count($groupList));
								} # if
							} # for

							# make sure there is at least one group
							if (count($groupList) < 1) {
								$formMessages['errors'][] = _('A user must be member of at least one group');
								$editResult = array('result' => 'failure');
							} else {
								# Mangle the current group membership to a common format
								$currentGroupList = array();
								foreach($groupList as $value) {
									$currentGroupList[] = $value['groupid'];
								} # foreach

								# and mangle the new requested group membership
								$tobeGroupList = array();
								foreach($groupMembership as $value) {
									$tobeGroupList[] = $value['id'];
								} # foreach

								/*
								 * Try to compare the grouplist with the current
								 * grouplist. If the grouplist changes, the user 
								 * needs change group membership permissions
								 */
								sort($currentGroupList, SORT_NUMERIC);
								sort($tobeGroupList, SORT_NUMERIC);

								/* 
								 * If the groupmembership list changes, lets make sure
								 * the user has the specific permission
								 */
								$groupDiff = (count($currentGroupList) != count($tobeGroupList));
								for ($i = 0; $i < count($currentGroupList) && !$groupDiff; $i++) {
									$groupDiff = ($currentGroupList[$i] != $tobeGroupList[$i]);
								} # for

								if ($groupDiff) {
									if ($this->_spotSec->allowed(SpotSecurity::spotsec_edit_groupmembership, '')) {
										$spotUserSystem->setUserGroupList($spotUser, $groupList);
									} else {
										$formMessages['errors'][] = _('Changing group membership is not allowed');
										$editResult = array('result' => 'failure');										
									} # else
								} # if
							} # if
						} # if

						# report success
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

				case 'resetuserapi' : {
					$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_consume_api, '');

					$user = $spotUserSystem->resetUserApi($spotUser);
					$editResult = array('result' => 'success', 'newapikey' => $user['apikey']);
					break;
				} # case resetuserapi
			} # switch
		} # if

		#- display stuff -#
		$this->template('edituser', array('edituserform' => $spotUser,
										    'formmessages' => $formMessages,
											'editresult' => $editResult,
											'groupMembership' => $groupMembership));
	} # render
	
} # class SpotPage_edituser
