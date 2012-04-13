<?php
error_reporting(2147483647);

try {
	/*
	 * If we are run from another directory, try to change the current
	 * working directory to a directory the script is in
	 */
	if (@!file_exists(getcwd() . '/' . basename($argv[0]))) {
		chdir(dirname(__FILE__));
	} # if

	require_once "lib/SpotClassAutoload.php";
	require_once "settings.php";

	/*
	 * Make sure we are not run from the server, an db upgrade can take too much time and
	 * will easily be aborted by either a database, apache or browser timeout
	 */
	SpotCommandline::initialize(array('reset-groupmembership', 'reset-securitygroups', 'reset-filters'), 
								array('reset-groupmembership' => false, 'reset-securitygroups' => false, 'reset-filters' => false));
	if (!SpotCommandline::isCommandline()) {
		die("upgrade-db.php can only be run from the console, it cannot be run from the web browser");
	} # if

	echo "Updating schema..(" . $settings['db']['engine'] . ")" . PHP_EOL;
	
	$spotUpgrader = new SpotUpgrader($settings['db']);
	$spotUpgrader->database();
	echo "Schema update done" . PHP_EOL;
	echo "Updating settings" . PHP_EOL;
	$spotUpgrader->settings($settings);
	echo "Settings update done" . PHP_EOL;
	$spotUpgrader->users($settings);
	echo "Updating users" . PHP_EOL;
	echo "Users' update done" . PHP_EOL;

	/* If the user asked to reset group membership, reset all group memberships */
	if (SpotCommandline::get('reset-securitygroups')) {
		echo "Resetting security groups to their default settings" . PHP_EOL;
		$spotUpgrader->resetSecurityGroups();
		echo "Reset security groups to their default settings done" . PHP_EOL;
	} # if


	/* 
	 * If the user asked to reset group membership, reset all group memberships.
	 */
	if (SpotCommandline::get('reset-groupmembership')) {
		echo "Resetting users' group membeship to the default" . PHP_EOL;
		$spotUpgrader->resetUserGroupMembership();
		echo "Reset of users' group membership done" . PHP_EOL;
	} # if

	/* 
	 * If the user asked to reset filters, do so
	 */
	if (SpotCommandline::get('reset-filters')) {
		echo "Resetting users' filters to the default" . PHP_EOL;
		$spotUpgrader->resetFilters();
		echo "Reset of users' filters done" . PHP_EOL;
	} # if

	echo "Performing basic analysis of database tables" . PHP_EOL;
	$spotUpgrader->analyze($settings);
	echo "Basic database optimalisation done" . PHP_EOL;
} 

catch(SpotwebCannotBeUpgradedToooldException $x) {
	die("Your current Spotweb installation is too old to be upgraded to this current version of Spotweb. " . PHP_EOL . 
		"Please download an earlier version of Spotweb (https://github.com/spotweb/spotweb/zipball/" . $x->getMessage() . "), " . PHP_EOL .
		"run upgrade-db.php using that version and then upgrade back to this version to run upgrade-db.php once more.");
} # SpotwebCannotBeUpgradedToooldException

catch(InvalidOwnSettingsSettingException $x) {
	echo "There is an error in your ownsettings.php" . PHP_EOL . PHP_EOL;
	echo $x->getMessage() . PHP_EOL;
} # InvalidOwnSettingsSettingException

catch(Exception $x) {
	echo PHP_EOL . PHP_EOL;
	echo 'SpotWeb crashed' . PHP_EOL . PHP_EOL;
	echo "Database schema of settings upgrade mislukt:" . PHP_EOL;
	echo "   " . $x->getMessage() . PHP_EOL;
	echo PHP_EOL . PHP_EOL;
	echo $x->getTraceAsString();
	die(1);
} # catch

