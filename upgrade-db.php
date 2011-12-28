<?php
error_reporting(E_ALL & ~8192 & ~E_USER_WARNING);	# 8192 == E_DEPRECATED maar PHP < 5.3 heeft die niet

try {
	/*
	 * If we are run from another directory, try to change the current
	 * working directory to a directory the script is in
	 */
	if (@!file_exists(getcwd() . '/' . basename($argv[0]))) {
		chdir(__DIR__);
	} # if

	require_once "lib/SpotClassAutoload.php";
	require_once "settings.php";

	/*
	 * Make sure we are not run from the server, an db upgrade can take too much time and
	 * will easily be aborted by either a database, apache or browser timeout
	 */
	SpotCommandline::initialize(array('reset-groupmembership'), array('reset-groupmembership' => false));
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

	/* Perform some custom work at the end */
	if (SpotCommandline::get('reset-groupmembership')) {
		echo "Resetting users' group membeship to the default" . PHP_EOL;
		$spotUpgrader->resetUserGroupMembership();
		echo "Reset of users' group membership done" . PHP_EOL;
	} # if

	echo "Performing basic analysis of database tables" . PHP_EOL;
	$spotUpgrader->analyze($settings);
	echo "Basic database optimalisation done" . PHP_EOL;
} 

catch(SpotwebCannotBeUpgradedToooldException $x) {
	die("Your current Spotweb installation is tooo old to be upgraded to this current version of Spotweb. " . PHP_EOL . 
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

