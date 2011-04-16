<?php
require_once "settings.php";
require_once "lib/SpotClassAutoload.php";

# Verzeker onszelf ervan dat we niet vanuit de webserver uitgevoerd worden
if (isset($_SERVER['SERVER_PROTOCOL'])) {
	die("Sorry, db-upgrade.php kan enkel vanuit de server zelf uitgevoerd worden, niet via de webbrowser!");
} # if

# Risky warning, might trip up some stuff
if (@!file_exists(getcwd() . '/' . basename($argv[0]))) {
	chdir(__DIR__);
} # if


try {
	echo "Updating schema..(" . $settings['db']['engine'] . ")" . PHP_EOL;
	
	$spotUpgrader = new SpotUpgrader($settings['db']);
	$spotUpgrader->database();
	echo "Schema update done" . PHP_EOL;
	echo "Updating settings" . PHP_EOL;
	$spotUpgrader->settings($settings);
	echo "Settings update done" . PHP_EOL;

} catch(Exception $x) {
	echo "Database schema of settings upgrade mislukt:" . PHP_EOL;
	echo "   " . $x->getMessage() . PHP_EOL;
	echo PHP_EOL . PHP_EOL;
	echo $x->getTraceAsString();
	die(1);
} # catch

