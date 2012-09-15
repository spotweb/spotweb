<?php
class SpotPage_markallasread extends SpotPage_Abs {

	function render() {
		$result = new Dto_FormResult('success');

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

		$this->render('markallasread', array('result' => $result));
	} # render()

} # SpotPage_markallasread
