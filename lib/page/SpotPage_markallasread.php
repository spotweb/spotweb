<?php
class SpotPage_markallasread extends SpotPage_Abs {

	function render() {
		# en update het user record
		$spotUserSystem = new SpotUserSystem($this->_db, $this->_settings);
		$spotUserSystem->clearSeenList($this->_currentSession['user']);
		$spotUserSystem->resetLastVisit($this->_currentSession['user']);
		$spotUserSystem->resetReadStamp($this->_currentSession['user']);

		echo "<xml><return>ok</return></xml>";
	} # render()

} # SpotPage_markallasread
