<?php
if ($_SERVER['REQUEST_METHOD']=="GET" && $_GET['id']){
require_once "lib/SpotClassAutoload.php"; 
require_once "settings.php"; 
require_once "lib/SpotTiming.php"; 
require_once "lib/exceptions/ParseSpotXmlException.php"; 
require_once "lib/exceptions/NntpException.php";

try {
	$db = new SpotDb($settings['db']); 
	$db->connect(); 
} catch(Exception $x) {
	die("Unable to connect to database: " . $x->getMessage() . PHP_EOL); 
} # catch

deleteSpot($_GET['id']);

} #if
?>