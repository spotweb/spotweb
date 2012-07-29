<?php
class SpotPage_erasedls extends SpotPage_Abs {

	function render() {
		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_keep_own_downloadlist, '');
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_keep_own_downloadlist, 'erasedls');

		# Instantiat the user system as necessary for the management of user preferences
		$spotUserSystem = new SpotUserSystem($this->_db, $this->_settings);
		$spotUserSystem->clearDownloadList($this->_currentSession['user']['userid']);
		
		$this->sendExpireHeaders(true);
		echo "<xml><return>ok</return></xml>";
	} # render()

} # SpotPage_erasedls