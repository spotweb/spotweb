<?php
class SpotPage_markallasread extends SpotPage_Abs {

	function render() {
		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_mark_spots_asread, '');
							  
		# en update het user record
		$spotUserSystem = new SpotUserSystem($this->_db, $this->_settings);
		$this->_db->clearSpotStateList(SpotDb::spotstate_Seen, $this->_currentSession['user']['userid']);
		$spotUserSystem->resetLastVisit($this->_currentSession['user']);
		$spotUserSystem->resetReadStamp($this->_currentSession['user']);

		echo "<xml><return>ok</return></xml>";
	} # render()

} # SpotPage_markallasread
