<?php

class SpotPage_createuser extends SpotPage_Abs
{
    private $_createUserForm;

    public function __construct(Dao_Factory $daoFactory, Services_Settings_Container $settings, array $currentSession, array $params)
    {
        parent::__construct($daoFactory, $settings, $currentSession);
        $this->_createUserForm = $params['createuserform'];
    }

    // ctor

    public function render()
    {
        // Check permissions
        $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_create_new_user, '');

        $result = new Dto_FormResult('notsubmitted');

        /*
         * Create a default SpotUser so the form is always able to render
         * the values of the form
         */
        $spotUser = ['username' => '',
            'firstname'         => '',
            'lastname'          => '',
            'mail'              => '', ];

        // Set the page title to something useful
        $this->_pageTitle = 'spot: create user';

        // Are we actually submitting/creating this user?
        if ($this->_createUserForm['action'] == 'create') {
            // Instantiate the Spot usersystem
            $svcActnCreateUser = new Services_Actions_CreateUser($this->_settings, $this->_daoFactory);

            $spotUser = array_merge($spotUser, $this->_createUserForm);
            $result = $svcActnCreateUser->createNewUser($spotUser, $this->_currentSession);
        } // if

        //- display stuff -#
        $this->template('createuser', ['createuserform' => $spotUser,
            'result'                                    => $result, ]);
    }

    // render
} // class SpotPage_createuser
