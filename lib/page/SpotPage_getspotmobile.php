<?php
class SpotPage_getspotmobile extends SpotPage_Abs {
	private $_messageid;
	
	function __construct(SpotDb $db, SpotSettings $settings, $currentSession, $messageid) {
		parent::__construct($db, $settings, $currentSession);
		$this->_messageid = $messageid;
	} # ctor


	function render() {
		$spotnntp = new SpotNntp($this->_settings->get('nntp_hdr'));

		# Haal de volledige spotinhoud op
		$spotsOverview = new SpotsOverview($this->_db, $this->_settings);
		$fullSpot = $spotsOverview->getFullSpot($this->_messageid, $this->_currentSession['user']['userid'], $spotnntp);
		$comments = $spotsOverview->getSpotComments($this->_messageid, $spotnntp, 0, 0);
		
		# zet de page title
		$this->_pageTitle = "spot: " . $fullSpot['title'];
		
		#- display stuff -#

		$this->template('spotinfo', array('spot' => $fullSpot, 'comments' => $comments));
	} # render
	
} # class SpotPage_getspot
