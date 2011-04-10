<?php
require_once "settings.php";
require_once "lib/SpotDb.php";
require_once "lib/page/SpotPage_Abs.php";

class SpotPage_markallasread extends SpotPage_Abs {
	
	function render() {
		$_SESSION['last_visit'] = $this->_db->getMaxMessageTime();
		echo "<xml><return>ok</return></xml>";
	} # render()

} # SpotPage_markallasread
