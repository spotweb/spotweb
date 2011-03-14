<?php
require_once "lib/page/SpotPage_Abs.php";
require_once "lib/SpotCategories.php";

class SpotPage_getnzb extends SpotPage_Abs {
	private $_messageid;
	private $_action;
	
	function __construct($db, $settings, $prefs, $params) {
		parent::__construct($db, $settings, $prefs);
		$this->_messageid = $params['messageid'];
		$this->_action = $params['action'];
	} # ctor

	
	function render() {
		$hdr_spotnntp = new SpotNntp($this->_settings['nntp_hdr']);

		/* Als de HDR en de NZB host hetzelfde zijn, zet geen tweede verbinding op */
		if ($this->_settings['nntp_hdr']['host'] == $this->_settings['nntp_nzb']['host']) {
			$nzb_spotnntp = $hdr_spotnntp;
		} else {
			$nzb_spotnntp = new SpotNntp($this->_settings['nntp_nzb']);
		} # else

		try {
			$spotNzb = new SpotNzb($this->_db, $this->_settings);
			$spotNzb->handleNzbAction($this->_messageid, $this->_action, $hdr_spotnntp, $nzb_spotnntp);
			
			if ($this->_action != 'display') {
				echo "<xml><result>OK</result><msg></msg></xml>";
			} # if
		}
		catch(Exception $x) {
			echo "<xml><result>ERROR</result><msg>" . $x->getMessage() . "</msg></xml>";
		} # catch
	} # render
	
} # SpotPage_getnzb
