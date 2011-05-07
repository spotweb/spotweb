<?php
class SpotPage_listusers extends SpotPage_Abs {
	
	function __construct(SpotDb $db, SpotSettings $settings, $currentSession) {
		parent::__construct($db, $settings, $currentSession);
	} # ctor

	function render() {
		# Instantieer het Spot user system
		$spotUserSystem = new SpotUserSystem($this->_db, $this->_settings);
		
		# zet de page title
		$this->_pageTitle = "spot: list users";
		
		# vraag de userlijst op
		$userList = $this->_db->listUsers('', 0, 9999);
		
		#- display stuff -#
		$this->template('header', array('activefilter' => $this->_params['search']));
		$this->template('listusers', array('userlist' => $userList['list']));
		$this->template('footer');
	} # render
	
} # class SpotPage_listusers
