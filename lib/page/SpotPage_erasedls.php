<?php
class SpotPage_erasedls extends SpotPage_Abs {
	
	function render() {
		$this->_db->clearList("download", $this->_currentSession['user']['userid']);
		
		echo "<xml><return>ok</return></xml>";
	} # render()

} # SpotPage_erasedls
