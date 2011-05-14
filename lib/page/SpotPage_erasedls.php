<?php
class SpotPage_erasedls extends SpotPage_Abs {

	function render() {
		$this->_db->clearSpotStateList(SpotDb::spotstate_Down, $this->_currentSession['user']['userid']);

		echo "<xml><return>ok</return></xml>";
	} # render()

} # SpotPage_erasedls