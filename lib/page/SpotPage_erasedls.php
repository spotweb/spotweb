<?php
class SpotPage_erasedls extends SpotPage_Abs {

	function render() {
		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_keep_own_downloadlist, '');

		$this->_tplHelper->clearDownloadList();
		echo "<xml><return>ok</return></xml>";
	} # render()

} # SpotPage_erasedls