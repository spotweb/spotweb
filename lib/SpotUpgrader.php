<?php

class SpotUpgrader {
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
		$settings = SpotSettings::singleton($this->_daoFactory->getSettingDao(), $this->_phpSettings);
		$spotSettingsUpgrader = new SpotSettingsUpgrader($this->_daoFactory, $settings);
		$spotSettingsUpgrader->update();
	} # settings

	/*
	 * Upgrade de users
	 */
	function users() {
		# Create the settings object
		$settings = SpotSettings::singleton($this->_daoFactory->getSettingDao(), $this->_phpSettings);
		$spotUserUpgrader = new SpotUserUpgrader($this->_daoFactory, $settings);
		$spotUserUpgrader->update();
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
		$settings = SpotSettings::singleton($this->_daoFactory->getSettingDao(), $this->_phpSettings);
		$spotUserUpgrader = new SpotUserUpgrader($this->_daoFactory, $settings);
		$spotUserUpgrader->resetUserGroupMembership($settings->get('systemtype'));
	} # resetUserGroupMembership

	/*
	 * Reset securitygroup settings to their default
	 */
	function resetSecurityGroups() {
		# Create the settings object
		$settings = SpotSettings::singleton($this->_daoFactory->getSettingDao(), $this->_phpSettings);
		$spotUserUpgrader = new SpotUserUpgrader($this->_daoFactory, $settings);
		$spotUserUpgrader->updateSecurityGroups(true);
	} # resetSecurityGroups

	/*
	 * Reset securitygroup settings to their default
	 */
	function resetFilters() {
		# Create the settings object
		$settings = SpotSettings::singleton($this->_daoFactory->getSettingDao(), $this->_phpSettings);
		$spotUserUpgrader = new SpotUserUpgrader($this->_daoFactory, $settings);
		$spotUserUpgrader->updateUserFilters(true);
	} # resetFilters
	 
	/*
	 * Reset a systems' type to the given setting
	 */
	function resetSystemType($systemType) {
		# Create the settings object
		$settings = SpotSettings::singleton($this->_daoFactory->getSettingDao(), $this->_phpSettings);
		$spotUserUpgrader = new SpotUserUpgrader($this->_daoFactory, $settings);
		$spotSettingsUpgrader = new SpotSettingsUpgrader($this->_daoFactory, $settings);

		# change the systems' type
		$spotSettingsUpgrader->setSystemType($systemType);
		
		# and reset all the users' group memberships for all users to match
		$spotUserUpgrader->resetUserGroupMembership($systemType);
	} # resetSystemType

	/*
	 * Reset a users' password
	 */
	function resetPassword($username) {
		# Create the settings object
		$settings = SpotSettings::singleton($this->_daoFactory->getSettingDao(), $this->_phpSettings);
		$spotUserUpgrader = new SpotUserUpgrader($this->_daoFactory, $settings);

		# retrieve the userid
		$spotUserUpgrader->resetUserPassword($username, 'spotweb');
	} # resetPassword

} # SpotUpgrader

