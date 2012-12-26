<?php

class Services_Upgrade_Base {
	private $_daoFactory;
	private $_dbStruct;
	private $_phpSettings;
	
	function __construct(Dao_Factory $daoFactory, $phpSettings) {
		$this->_daoFactory = $daoFactory;
		$this->_dbStruct = SpotStruct_Abs::factory($phpSettings['db']['engine'], $daoFactory->getConnection());
		$this->_phpSettings = $phpSettings;
	} # ctor
	
	/*
	 * Upgrade de settings
	 */
	function settings() {
		# Create the settings object
		$settings = Services_Settings_Base::singleton($this->_daoFactory->getSettingDao(), 
													  $this->_daoFactory->getBlackWhiteListDao(),
											          $this->_phpSettings);
		$svcUpgradeSettings = new Services_Upgrade_Settings($this->_daoFactory, $settings);
		$svcUpgradeSettings->update();
	} # settings

	/*
	 * Upgrade de users
	 */
	function users() {
		# Create the settings object
		$settings = Services_Settings_Base::singleton($this->_daoFactory->getSettingDao(), 
												      $this->_daoFactory->getBlackWhiteListDao(),
													  $this->_phpSettings);
		$svcUpgradeUser = new Services_Upgrade_User($this->_daoFactory, $settings);
		$svcUpgradeUser->update();
	} # users
	 
	/*
	 * Creeert en upgrade de database
	 */
	function database() {
		$this->_dbStruct->updateSchema();
	 } # database

	/*
	 * Optimaliseert de database
	 */
	function analyze() {
		# Instantieeer een struct object
		$this->_dbStruct->analyze();
	 } # analyze

	/*
	 * Reset users' group membership
	 */
	function resetUserGroupMembership() {
		# Create the settings object
		$settings = Services_Settings_Base::singleton($this->_daoFactory->getSettingDao(), 
													  $this->_daoFactory->getBlackWhiteListDao(),
													  $this->_phpSettings);
		$svcUpgradeUser = new Services_Upgrade_User($this->_daoFactory, $settings);
		$svcUpgradeUser->resetUserGroupMembership($settings->get('systemtype'));
	} # resetUserGroupMembership

	/*
	 * Reset securitygroup settings to their default
	 */
	function resetSecurityGroups() {
		# Create the settings object
		$settings = Services_Settings_Base::singleton($this->_daoFactory->getSettingDao(),
												   	  $this->_daoFactory->getBlackWhiteListDao(), 
													  $this->_phpSettings);
		$svcUpgradeUser = new Services_Upgrade_User($this->_daoFactory, $settings);
		$svcUpgradeUser->updateSecurityGroups(true);
	} # resetSecurityGroups

	/*
	 * Reset users' filters settings to their default
	 */
	function resetFilters() {
		# Create the settings object
		$settings = Services_Settings_Base::singleton($this->_daoFactory->getSettingDao(), 
													  $this->_daoFactory->getBlackWhiteListDao(),
													  $this->_phpSettings);
		$svcUpgradeUser = new Services_Upgrade_User($this->_daoFactory, $settings);
		$svcUpgradeUser->updateUserFilters(true);
	} # resetFilters
	 
	/*
	 * Perform a mass change for users' preferences
	 */
	function massChangeUserPreferences($prefName, $prefValue) {
		# Create the settings object
		$settings = Services_Settings_Base::singleton($this->_daoFactory->getSettingDao(), 
													  $this->_daoFactory->getBlackWhiteListDao(),
													  $this->_phpSettings);
		$svcUpgradeUser = new Services_Upgrade_User($this->_daoFactory, $settings);
		$svcUpgradeUser->massChangeUserPreferences($prefName, $prefValue);
	} # massChangeUserPreferences

	/*
	 * Reset a systems' type to the given setting
	 */
	function resetSystemType($systemType) {
		# Create the settings object
		$settings = Services_Settings_Base::singleton($this->_daoFactory->getSettingDao(), 
													  $this->_daoFactory->getBlackWhiteListDao(),
													  $this->_phpSettings);
		$svcUpgradeUser = new Services_Upgrade_User($this->_daoFactory, $settings);
		$svcUpgradeSettings = new Services_Upgrade_Settings($this->_daoFactory, $settings);

		# change the systems' type
		$svcUpgradeSettings->setSystemType($systemType);
		
		# and reset all the users' group memberships for all users to match
		$svcUpgradeUser->resetUserGroupMembership($systemType);
	} # resetSystemType

	/*
	 * Reset a users' password
	 */
	function resetPassword($username) {
		# Create the settings object
		$settings = Services_Settings_Base::singleton($this->_daoFactory->getSettingDao(), 
													  $this->_daoFactory->getBlackWhiteListDao(),
													  $this->_phpSettings);
		$svcUpgradeUser = new Services_Upgrade_User($this->_daoFactory, $settings);

		# retrieve the userid
		$svcUpgradeUser->resetUserPassword($username, 'spotweb');
	} # resetPassword

} # Services_Upgrade_Base

