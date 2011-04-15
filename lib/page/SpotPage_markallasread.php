<?php
class SpotPage_markallasread extends SpotPage_Abs {
	
	function render() {
		$_SESSION['last_visit'] = $this->_db->getMaxMessageTime();
		echo "<xml><return>ok</return></xml>";
	} # render()

} # SpotPage_markallasread
