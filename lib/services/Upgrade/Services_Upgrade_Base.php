<?php

class Services_Upgrade_Base
{
    private $_daoFactory;
    private $_dbStruct;

    public function __construct(Dao_Factory $daoFactory, Services_Settings_Container $settings, $dbEngine)
    {
        $this->_daoFactory = $daoFactory;
        $this->_dbStruct = SpotStruct_abs::factory($dbEngine, $daoFactory->getConnection());
        $this->_settings = $settings;
    }

    // ctor

    /*
     * Upgrade de settings
     */
    public function settings()
    {
        $svcUpgradeSettings = new Services_Upgrade_Settings($this->_daoFactory, $this->_settings);
        $svcUpgradeSettings->update();
    }

    // settings

    /*
     * Initialize the usenet state settings
     */
    public function usenetState()
    {
        /* Create the usenet settings */
        $usenetDao = $this->_daoFactory->getUsenetStateDao();
        $usenetDao->initialize();
    }

    // usenetState()

    /*
     * Upgrade de users
     */
    public function users()
    {
        $svcUpgradeUser = new Services_Upgrade_Users($this->_daoFactory, $this->_settings);
        $svcUpgradeUser->update();
    }

    // users

    /*
     * Creeert en upgrade de database
     */
    public function database()
    {
        $this->_dbStruct->updateSchema();
    }

    // database

    /*
     * Optimaliseert de database
     */
    public function analyze()
    {
        // Instantieeer een struct object
        $this->_dbStruct->analyze();
    }

    // analyze

    /*
    * Reset de database
    */
    public function resetdb()
    {
        // Instantieeer een struct object
        $this->_dbStruct->resetdb();
    }

    // analyze

    public function clearcache()
    {
        // Instantieeer een struct object
        $this->_dbStruct->clearcache();
    }

    // analyze

    /*
     * Reset users' group membership
     */
    public function resetUserGroupMembership()
    {
        $svcUpgradeUser = new Services_Upgrade_Users($this->_daoFactory, $this->_settings);
        $svcUpgradeUser->resetUserGroupMembership($this->_settings->get('systemtype'));
    }

    // resetUserGroupMembership

    /*
     * Reset securitygroup settings to their default
     */
    public function resetSecurityGroups()
    {
        $svcUpgradeUser = new Services_Upgrade_Users($this->_daoFactory, $this->_settings);
        $svcUpgradeUser->updateSecurityGroups(true);
    }

    // resetSecurityGroups

    /*
     * Reset users' filters settings to their default
     */
    public function resetFilters()
    {
        $svcUpgradeUser = new Services_Upgrade_Users($this->_daoFactory, $this->_settings);
        $svcUpgradeUser->updateUserFilters(true);
    }

    // resetFilters

    /*
     * Perform a mass change for users' preferences
     */
    public function massChangeUserPreferences($prefName, $prefValue)
    {
        $svcUpgradeUser = new Services_Upgrade_Users($this->_daoFactory, $this->_settings);
        $svcUpgradeUser->massChangeUserPreferences($prefName, $prefValue);
    }

    // massChangeUserPreferences

    /*
     * Reset a systems' type to the given setting
     */
    public function resetSystemType($systemType)
    {
        $svcUpgradeUser = new Services_Upgrade_Users($this->_daoFactory, $this->_settings);
        $svcUpgradeSettings = new Services_Upgrade_Settings($this->_daoFactory, $this->_settings);

        // change the systems' type
        $svcUpgradeSettings->setSystemType($systemType);

        // and reset all the users' group memberships for all users to match
        $svcUpgradeUser->resetUserGroupMembership($systemType);
    }

    // resetSystemType

    /*
     * Reset a users' password
     */
    public function resetPassword($username)
    {
        $svcUpgradeUser = new Services_Upgrade_Users($this->_daoFactory, $this->_settings);

        // retrieve the userid
        $svcUpgradeUser->resetUserPassword($username, 'spotweb');
    }

    // resetPassword
} // Services_Upgrade_Base
