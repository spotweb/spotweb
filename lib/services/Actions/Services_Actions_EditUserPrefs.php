<?php

class Services_Actions_EditUserPrefs
{
    private $_daoFactory;
    private $_settings;

    private $_svcUserFilter;
    private $_svcUserRecord;
    private $_svcUserAuth;
    private $_spotSec;

    public function __construct(Dao_Factory $daoFactory, Services_Settings_Container $settings, SpotSecurity $spotSec)
    {
        $this->_daoFactory = $daoFactory;
        $this->_settings = $settings;

        $this->_svcUserFilter = new Services_User_Filters($this->_daoFactory, $this->_settings);
        $this->_svcUserRecord = new Services_User_Record($this->_daoFactory, $this->_settings);
        $this->_svcUserAuth = new Services_User_Authentication($this->_daoFactory, $this->_settings);

        $this->_spotSec = $spotSec;
    }

    // ctor

    public function editUserPref(array $editUserPrefsForm, array $userPrefTemplate, array $spotUser, $avatarFileName)
    {
        /*
         * We want the anonymous' users account so we can use this users' preferences as a
         * template. This makes sure all properties are at least set.
         */
        $anonUser = $this->_svcUserRecord->getUser(SPOTWEB_ANONYMOUS_USERID);

        /*
         * We have a few dummy preferenes -- these are submitted like a checkbox for example
         * but in reality do something completely different.
         *
         * Because we use cleanseUserPreferences() those dummies will not end up in the database
         */
        if (isset($editUserPrefsForm['_dummy_prevent_porn'])) {
            $this->_svcUserFilter->setEroticIndexFilter($spotUser['userid']);
        } else {
            $this->_svcUserFilter->removeIndexFilter($spotUser['userid']);
        } // if

        // Save the current' user preferences because we need them before cleansing
        $savePrefs = $spotUser['prefs'];
        $spotUser['prefs'] = $this->_svcUserRecord->cleanseUserPreferences(
            $editUserPrefsForm,
            $anonUser['prefs'],
            $userPrefTemplate
        );

        // Validate all preferences
        $result = $this->_svcUserRecord->validateUserPreferences($spotUser['prefs'], $savePrefs);
        $spotUser['prefs'] = $result->getData('prefs');

        // Make sure user has permission to select this template
        if ($spotUser['prefs']['normal_template'] != $savePrefs['normal_template']) {
            $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_select_template, $spotUser['prefs']['normal_template']);
        } // if

        if ($spotUser['prefs']['mobile_template'] != $savePrefs['mobile_template']) {
            $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_select_template, $spotUser['prefs']['mobile_template']);
        } // if

        if ($spotUser['prefs']['tablet_template'] != $savePrefs['tablet_template']) {
            $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_select_template, $spotUser['prefs']['tablet_template']);
        } // if

        if ($result->isSuccess()) {

            // Test to see if a new avatar was provided
            if (!empty($avatarFileName)) {
                $avatarResult = $this->_svcUserRecord->changeAvatar(
                    $spotUser['userid'],
                    file_get_contents($avatarFileName)
                );

                /*
                 * Merge the result of the avatar update to our
                 * total result
                 */
                $result->mergeResult($avatarResult);
            } // if
        } // if

        if ($result->isSuccess()) {
            // and actually update the user in the database
            $this->_svcUserRecord->setUser($spotUser);
        } // if

        /*
         * We have the register Spotweb with the notification providers (growl, prowl, etc) atleast once.
         * The safes option is to just do this wih each preferences submit. But first we create a fake
         * session for this user.
         */
        $fakeSession = $this->_svcUserAuth->createNewSession($spotUser['userid']);
        $fakeSession['security'] = new SpotSecurity(
            $this->_daoFactory->getUserDao(),
            $this->_daoFactory->getAuditDao(),
            $this->_settings,
            $fakeSession['user'],
            ''
        );

        $spotsNotifications = new SpotNotifications($this->_daoFactory, $this->_settings, $fakeSession);
        $spotsNotifications->register();

        return $result;
    }

    // editUserPref
} // Services_Actions_EditUserPrefs
