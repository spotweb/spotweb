<?php
require_once "lib/page/SpotPage_Abs.php";

class SpotPage_markallasread extends SpotPage_Abs {
	
	function render() {
		$_SESSION['last_visit'] = time();
		echo "<xml><return>ok</return></xml>";
	} # render()

} # SpotPage_markallasread
