<?php
class SpotPage_logout extends SpotPage_Abs {
	
	function render() {
		# Check users' permissions
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_perform_logout, '');
							  
		# Instanatiate the spotweb user system
		$spotUserSystem = new SpotUserSystem($this->_db, $this->_settings);
		
		# make sure the logout isn't cached
		$this->sendExpireHeaders(true);

		# send the appropriate content-type header
		$this->sendContentTypeHeader('xml');
		
		# and remove the users' session if the user isn't the anonymous one
		if ($this->_currentSession['user']['userid'] != $this->_settings->get('nonauthenticated_userid')) {
			$spotUserSystem->removeSession($this->_currentSession['session']['sessionid']);

			echo '<xml><result>OK</result></xml>';
		} else {
			echo '<xml><result>ERROR</result></xml>';
		} # else
	} # render
	
} # class SpotPage_logout
