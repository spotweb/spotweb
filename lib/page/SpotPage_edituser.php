<?php

class SpotPage_edituser extends SpotPage_Abs
{
    private $_editUserForm;
    private $_userIdToEdit;

    public function __construct(Dao_Factory $daoFactory, Services_Settings_Container $settings, array $currentSession, array $params)
    {
        parent::__construct($daoFactory, $settings, $currentSession);
        $this->_editUserForm = $params['edituserform'];
        $this->_userIdToEdit = $params['userid'];
    }

    // ctor

    public function render()
    {
        $result = new Dto_FormResult('notsubmitted');

        // check the users' permissions
        if ($this->_userIdToEdit == $this->_currentSession['user']['userid']) {
            $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_edit_own_user, '');
        } else {
            $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_edit_other_users, '');
        } // if

        // Instantiate the service userrecord object
        $svcUserRecord = new Services_User_Record($this->_daoFactory, $this->_settings);

        // and create a nice and shiny page title
        $this->_pageTitle = 'spot: edit user';

        // get the users' group membership
        $spotUser = $svcUserRecord->getUser($this->_userIdToEdit);
        $groupMembership = $svcUserRecord->getUserGroupMemberShip($this->_userIdToEdit);

        /*
         * bring the forms' action into the local scope for
         * easier access
         */
        $formAction = $this->_editUserForm['action'];

        // Only perform certain validations when the form is actually submitted
        if (!empty($formAction)) {
            switch ($formAction) {
                case 'delete':
                    $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_delete_user, '');

                    if ($this->_userIdToEdit == $this->_currentSession['user']['userid']) {
                        $result->addError('Cannot delete your own user');
                    } else {
                        $result = $svcUserRecord->removeUser($this->_userIdToEdit);
                    } // removeUser

                    break;
                 // case delete

                case 'edit':
                    // Mangle the grouplisting we get from the form to an usable format for the system
                    $groupList = [];
                    if (isset($this->_editUserForm['grouplist'])) {
                        foreach ($this->_editUserForm['grouplist'] as $val) {
                            if ($val != 'dummy') {
                                $groupList[] = ['groupid' => $val,
                                    'prio'                => count($groupList), ];
                            } // if
                        } // foreach
                    } // if

                    $this->_editUserForm['userid'] = $this->_userIdToEdit;
                    $result = $svcUserRecord->updateUserRecord(
                        $this->_editUserForm,
                        $groupList,
                        $this->_spotSec->allowed(SpotSecurity::spotsec_edit_groupmembership, '')
                    );
                    break;
                 // case 'edit'

                case 'removeallsessions':
                    $svcUserAuth = new Services_User_Authentication($this->_daoFactory, $this->_settings);
                    $result = $svcUserAuth->removeAllUserSessions($spotUser['userid']);

                    break;
                 // case 'removeallsessions'

                case 'resetuserapi':
                    $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_consume_api, '');

                    $result = $svcUserRecord->resetUserApi($spotUser);
                    break;
                 // case resetuserapi
            } // switch
        } // if

        //- display stuff -#
        $this->template('edituser', ['edituserform' => $spotUser,
            'result'                                => $result,
            'groupMembership'                       => $groupMembership, ]);
    }

    // render
} // class SpotPage_edituser
