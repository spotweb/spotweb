<?php
class SpotPage_versioncheck extends SpotPage_Abs {

	function render() {
		# Check wheter user has permission to check the Spotweb version
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_spotweb_updates, '');

		# And instantiate the Spotweb version checker
		$versionCheck = new SpotWebVersionCheck();
		$itemList = $versionCheck->getItems();

		#- display stuff -#
		$this->template('versioncheck', array('items' => $itemList,
											  'uptodate' => $versionCheck->isLatestVersion($itemList[0])));
	} # render()

} # SpotPage_versioncheck