<?php
require_once "lib/page/SpotPage_Abs.php";
require_once "lib/SpotCategories.php";

class SpotPage_getnzbmobile extends SpotPage_Abs {
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
				echo "<div data-role=page><div data-role=content><p>NZB saved.</p><a href='" .$this->_settings['nzbhandling']['sabnzbd']['spotweburl'] ."' rel=external data-role='button'>OK</a></div></div>";			
			} # if
		}
		catch(Exception $x) {
			echo "<div data-role=page><div data-role=content><p>" . $x->getMessage() . "</p><a href='". $this->_settings['nzbhandling']['sabnzbd']['spotweburl'] ."' rel=external data-role='button'>OK</a></div></div>";
		} # catch
	} # render
	
} # SpotPage_getnzb
