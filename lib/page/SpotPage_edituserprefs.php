<?php

class SpotPage_edituserprefs extends SpotPage_Abs
{
    private $_editUserPrefsForm;
    private $_userIdToEdit;
    private $_dialogembedded;

    public function __construct(Dao_Factory $daoFactory, Services_Settings_Container $settings, array $currentSession, array $params)
    {
        parent::__construct($daoFactory, $settings, $currentSession);
        $this->_editUserPrefsForm = $params['edituserprefsform'];
        $this->_userIdToEdit = $params['userid'];
        $this->_dialogembedded = $params['dialogembedded'];
    }

    // ctor

    public function render()
    {
        // Make sure the result is set to 'not submitted' per default
        $result = new Dto_FormResult('notsubmitted');

        // Validate proper permissions
        if ($this->_userIdToEdit == $this->_currentSession['user']['userid']) {
            $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_edit_own_userprefs, '');
        } else {
            $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_edit_other_users, '');
        } // if

        // Instantiate the user system as necessary for the management of user preferences
        $svcUserRecord = new Services_User_Record($this->_daoFactory, $this->_settings);

        // set the page title
        $this->_pageTitle = 'spot: edit user preferences';

        // retrieve the to-edit user
        $spotUser = $svcUserRecord->getUser($this->_userIdToEdit);
        if ($spotUser === false) {
            $result->addError(sprintf(_('User %d can not be found'), $this->_userIdToEdit));
        } // if

        /*
         * bring the forms' action into the local scope for
         * easier access
         */
        $formAction = $this->_editUserPrefsForm['action'];

        /*
         * Check to see if a file was uploaded, if so, handle any associated errors
         */
        $avatarFileName = '';
        if ($formAction == 'edit') {
            $uploadHandler = new Services_Providers_FileUpload('edituserprefsform', 'avatar');

            if ($uploadHandler->isUploaded()) {
                if (!$uploadHandler->success()) {
                    $result->addError(_('Unable to update avatar').'('.$uploadHandler->errorText().')');
                } else {
                    $avatarFileName = $uploadHandler->getTempName();
                } // else
            } // if
        } // if

        // Are we trying to submit this form, or only rendering it?
        if ((!empty($formAction)) && (!$result->isError())) {
            switch ($formAction) {
                case 'edit':
                    $svcActn_EditUserPrefs = new Services_Actions_EditUserPrefs(
                        $this->_daoFactory,
                        $this->_settings,
                        $this->_spotSec
                    );
                    $result = $svcActn_EditUserPrefs->editUserPref(
                        $this->_editUserPrefsForm,
                        $this->_tplHelper->getTemplatePreferences(),
                        $spotUser,
                        $avatarFileName
                    );
                    break;
                 // case 'edit'

                case 'cancel':
                    $result->setResult('success');
                 // case 'cancel'
            } // switch
        } // if

        //- display stuff -#
        $this->template('edituserprefs', ['edituserprefsform' => $spotUser['prefs'],
            'spotuser'                                        => $spotUser,
            'dialogembedded'                                  => $this->_dialogembedded,
            'http_referer'                                    => $this->_editUserPrefsForm['http_referer'],
            'result'                                          => $result, ]);
    }

    // render
} // class SpotPage_edituserprefs
