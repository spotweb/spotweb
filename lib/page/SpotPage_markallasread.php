<?php
require_once "settings.php";
require_once "lib/SpotDb.php";
require_once "lib/page/SpotPage_Abs.php";

class SpotPage_markallasread extends SpotPage_Abs {
	
	function render() {
		try {
			$db = new SpotDb($this->_settings['db']);
			$db->connect();
		} 
		catch(Exception $x) {
			die("Unable to connect to database: " . $x->getMessage() . PHP_EOL);
		} # catch

		$_SESSION['last_visit'] = $db->getMaxMessageTime();
		echo "<xml><return>ok</return></xml>";
	} # render()

} # SpotPage_markallasread
