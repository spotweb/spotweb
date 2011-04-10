<?php
require_once "lib/page/SpotPage_Abs.php";
require_once "lib/SpotCategories.php";

class SpotPage_getspot extends SpotPage_Abs {
	private $_messageid;
	
	function __construct($db, $settings, $currentUser, $messageid) {
		parent::__construct($db, $settings, $currentUser);
		$this->_messageid = $messageid;
	} # ctor


	function render() {
		$spotnntp = new SpotNntp($this->_settings->get('nntp_hdr'), $this->_settings->get('use_openssl'));
		
		# Haal de volledige spotinhoud op
		$spotsOverview = new SpotsOverview($this->_db, $this->_settings);
		$fullSpot = $spotsOverview->getFullSpot($this->_messageid, $this->_currentUser['userid'], $spotnntp);

		# zet de page title
		$this->_pageTitle = "spot: " . $fullSpot['title'];
		
		#- display stuff -#
		$this->template('header');
		$this->template('spotinfo', array('spot' => $fullSpot));
		$this->template('footer');
	} # render
	
} # class SpotPage_getspot
