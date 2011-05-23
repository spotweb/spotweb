<?php
class SpotPage_getspot extends SpotPage_Abs {
	private $_messageid;

	function __construct(SpotDb $db, SpotSettings $settings, $currentSession, $messageid) {
		parent::__construct($db, $settings, $currentSession);
		$this->_messageid = $messageid;
	} # ctor

	function render() {
		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_spotdetail, '');

		# Haal de volledige spotinhoud op
		$fullSpot = $this->_tplHelper->getFullSpot($this->_messageid);

		# zet de page title
		$this->_pageTitle = "spot: " . $fullSpot['title'];

		#- display stuff -#
		$this->template('spotinfo', array('spot' => $fullSpot));
	} # render

} # class SpotPage_getspot