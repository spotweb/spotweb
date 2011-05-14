<?php
class SpotPage_resetuserapi extends SpotPage_Abs {

	function render() {
		$spotUserSystem = new SpotUserSystem($this->_db, $this->_settings);
		$user = $spotUserSystem->getUser($this->_currentSession['user']['userid']);
		$user = $spotUserSystem->resetUserApi($user);

		echo "<xml><return>ok</return><newkey>" . $user['apikey'] . "</newkey></xml>";
	} # render()

} # SpotPage_resetuserapi
