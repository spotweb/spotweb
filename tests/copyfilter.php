<?php
error_reporting(E_ALL & ~8192 & ~E_USER_WARNING);       # 8192 == E_DEPRECATED maar PHP < 5.3 heeft die niet
require_once "lib/SpotClassAutoload.php";
#- main() -
        
require_once "settings.php";
        
# database object
$db = new SpotDb($settings['db']);
$db->connect();

$db->getDbHandle()->rawExec("DELETE FROM filters WHERE userid <> 1");
$userList = $db->getUserList("", 0, 9999999);

# loop through every user and fix it 
foreach($userList as $user) {
	if ($user['userid'] != 1) {
		echo "Copying filter from userid 1 to userid: " . $user['userid'] . PHP_EOL;
		$db->copyFilterList(1, $user['userid']);
	} # if
} # foreach

