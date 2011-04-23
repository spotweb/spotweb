<?php
class SpotPage_getspot extends SpotPage_Abs {
	private $_messageid;
	
	function __construct($db, $settings, $currentSession, $messageid) {
		parent::__construct($db, $settings, $currentSession);
		$this->_messageid = $messageid;
	} # ctor


	function render() {
		$spotnntp = new SpotNntp($this->_settings->get('nntp_hdr'));
		
		# Haal de volledige spotinhoud op
		$spotsOverview = new SpotsOverview($this->_db, $this->_settings);
		$fullSpot = $spotsOverview->getFullSpot($this->_messageid, $this->_currentSession['user']['userid'], $spotnntp);

		# zet de page title
		$this->_pageTitle = "spot: " . $fullSpot['title'];
		
		# seen list
		if ($fullSpot['stamp'] > $_SESSION['last_visit'] && $fullSpot['seenstamp'] == NULL) {
			$spotsOverview->addToSeenList($this->_messageid, $this->_currentSession['user']['userid']);
		} # if
		
		#- display stuff -#
		$this->template('header');
		$this->template('spotinfo', array('spot' => $fullSpot));
		$this->template('footer');
	} # render
	
} # class SpotPage_getspot
