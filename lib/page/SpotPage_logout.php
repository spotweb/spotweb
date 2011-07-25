<?php
class SpotPage_logout extends SpotPage_Abs {
	
	function render() {
		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_perform_logout, '');
							  
		# Instantieer het Spot user system
		$spotUserSystem = new SpotUserSystem($this->_db, $this->_settings);
		
		# logout mag niet gecached worden
		$this->sendExpireHeaders(true);
		
		# als het geen anonymous user is
		if ($this->_currentSession['user']['userid'] != 1) {
			$spotUserSystem->removeSession($this->_currentSession['session']['sessionid']);
			
			echo '<xml><result>OK</result></xml>';
		} else {
			echo '<xml><result>ERROR</result></xml>';
		} # else
	} # render
	
} # class SpotPage_logout
