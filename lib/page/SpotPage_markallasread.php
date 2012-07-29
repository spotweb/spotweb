<?php
class SpotPage_markallasread extends SpotPage_Abs {

	function render() {
		# Check the appropriate permissions
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_mark_spots_asread, '');
							  
		# instantiate an user system
		$spotUserSystem = new SpotUserSystem($this->_db, $this->_settings);
		
		# if this is allowed, mark all individual spots as read
		if ($this->_spotSec->allowed(SpotSecurity::spotsec_keep_own_seenlist, '')) {
			$spotUserSystem->markAllAsRead($this->_currentSession['user']['userid']);
		} # if

		# never cache this action
		$this->sendExpireHeaders(true);
		
		# reset the lastvisit and lastread timestamp
		$spotUserSystem->resetReadStamp($this->_currentSession['user']);

		echo "<xml><return>ok</return></xml>";
	} # render()

} # SpotPage_markallasread
