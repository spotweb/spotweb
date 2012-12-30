<?php
class SpotPage_erasedls extends SpotPage_Abs {

	function render() {
		# Make sure the user has the appropriate permissions
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_keep_own_downloadlist, '');
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_keep_own_downloadlist, 'erasedls');

		# Instantiat the user system as necessary for the management of user preferences
		$svcUserRecord = new Services_User_record($this->_daoFactory, $this->_settings);
		$svcUserRecord->clearDownloadList($this->_currentSession['user']['userid']);
		
		$this->sendExpireHeaders(true);

		$result = new Dto_FormResult('success');
		$this->template('erasedls', array('result' => $result));
	} # render()

} # SpotPage_erasedls
