<?php
require_once "settings.php";

try {
	$db = new SpotDb($settings['db']);
	$db->connect();
} 
catch(Exception $x) {
	die("Unable to connect to database: " . $x->getMessage() . PHP_EOL);
} # catch

if(empty($_SESSION['last_visit'])) {
	if(!isset($_COOKIE['last_visit'])) {
		$_SESSION['last_visit'] = $db->getMaxMessageTime();
	} else {
		$_SESSION['last_visit'] = $_COOKIE['last_visit'];
	} # else
} # if 
	
// set cookie
setcookie('last_visit', $db->getMaxMessageTime(), time()+(86400*$settings['cookie_expires']), '/', $settings['cookie_host']);
