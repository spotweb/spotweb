<?php

class SpotUpgrader {
	private $_db;
	private $_dbEngine;
	
	function __construct($dbSettings, $phpSettings) {
		$this->_db = new SpotDb($dbSettings);
		$this->_db->connect();
		$this->_dbEngine = $dbSettings['engine'];
		$this->_phpSettings = $phpSettings;
	} # ctor
	
	/*
	 * Upgrade de settings
	 */
	function settings() {
		# Create the settings object
		$settings = SpotSettings::singleton($this->_db, $this->_phpSettings);
		$spotSettingsUpgrader = new SpotSettingsUpgrader($this->_db, $settings);
		$spotSettingsUpgrader->update();
	} # settings

	/*
	 * Upgrade de users
	 */
	function users() {
		# Create the settings object
		$settings = SpotSettings::singleton($this->_db, $this->_phpSettings);
		$spotUserUpgrader = new SpotUserUpgrader($this->_db, $settings);
		$spotUserUpgrader->update();
	} # users
	 
	/*
	 * Creeert en upgrade de database
	 */
	function database() {
		# Instantieeer een struct object
		switch($this->_dbEngine) {	
			case 'mysql'			:
			case 'pdo_mysql'		: $dbStruct = new SpotStruct_mysql($this->_db); break;

			case 'pdo_pgsql'		: $dbStruct = new SpotStruct_pgsql($this->_db); break;
			
			case 'pdo_sqlite'		: $dbStruct = new SpotStruct_sqlite($this->_db); break;
			
			default					: throw new Exception("Unknown database engine");
		} # switch
		
		$dbStruct->updateSchema();
	 } # database

	/*
	 * Optimaliseert de database
	 */
	function analyze() {
		# Instantieeer een struct object
		switch($this->_dbEngine) {	
			case 'mysql'			:
			case 'pdo_mysql'		: $dbStruct = new SpotStruct_mysql($this->_db); break;
			
			case 'pdo_pgsql'		: $dbStruct = new SpotStruct_pgsql($this->_db); break;
			
			case 'pdo_sqlite'		: $dbStruct = new SpotStruct_sqlite($this->_db); break;
			
			default					: throw new Exception("Unknown database engine");
		} # switch
		
		$dbStruct->analyze();
	 } # analyze

	/*
	 * Reset users' group membership
	 */
	function resetUserGroupMembership() {
		# Create the settings object
		$settings = SpotSettings::singleton($this->_db, $this->_phpSettings);
		$spotUserUpgrader = new SpotUserUpgrader($this->_db, $settings);
		$spotUserUpgrader->resetUserGroupMembership($settings->get('systemtype'));
	} # resetUserGroupMembership

	/*
	 * Reset securitygroup settings to their default
	 */
	function resetSecurityGroups() {
		# Create the settings object
		$settings = SpotSettings::singleton($this->_db, $this->_phpSettings);
		$spotUserUpgrader = new SpotUserUpgrader($this->_db, $settings);
		$spotUserUpgrader->updateSecurityGroups(true);
	} # resetSecurityGroups

	/*
	 * Reset users' filters settings to their default
	 */
	function resetFilters() {
		# Create the settings object
		$settings = SpotSettings::singleton($this->_db, $this->_phpSettings);
		$spotUserUpgrader = new SpotUserUpgrader($this->_db, $settings);
		$spotUserUpgrader->updateUserFilters(true);
	} # resetFilters
	 
	/*
	 * Perform a mass change for users' preferences
	 */
	function massChangeUserPreferences($prefName, $prefValue) {
		# Create the settings object
		$settings = SpotSettings::singleton($this->_db, $this->_phpSettings);
		$spotUserUpgrader = new SpotUserUpgrader($this->_db, $settings);
		$spotUserUpgrader->massChangeUserPreferences($prefName, $prefValue);
	} # massChangeUserPreferences

	/*
	 * Reset a systems' type to the given setting
	 */
	function resetSystemType($systemType) {
		# Create the settings object
		$settings = SpotSettings::singleton($this->_db, $this->_phpSettings);
		$spotUserUpgrader = new SpotUserUpgrader($this->_db, $settings);
		$spotSettingsUpgrader = new SpotSettingsUpgrader($this->_db, $settings);

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
		$settings = SpotSettings::singleton($this->_db, $this->_phpSettings);
		$spotUserUpgrader = new SpotUserUpgrader($this->_db, $settings);

		# retrieve the userid
		$spotUserUpgrader->resetUserPassword($username, 'spotweb');
	} # resetPassword

} # SpotUpgrader

