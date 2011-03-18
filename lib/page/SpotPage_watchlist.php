<?php
require_once "lib/page/SpotPage_Abs.php";
require_once "lib/SpotCategories.php";

class SpotPage_watchlist extends SpotPage_Abs {
	private $_messageid;
	private $_action;
	
	function __construct($db, $settings, $prefs, $messageid, $action) {
		parent::__construct($db, $settings, $prefs);
		$this->_messageid = $messageid;
		
		if (array_search($action, array('add', 'remove', 'list')) === false) {
			$action = 'list';
		} # if
		$this->_action = $action;
	} # ctor


	function render() {
		# Haal de volledige watchlist op
		$watchList = $this->_db->getWatchList();

		# zet de page title
		$this->_pageTitle = "watchlist";
		
		# afhankelijk van wat re gekozen is, voer het uit
		switch($this->_action) {
			case 'add'		: $db->addToWatchList($this->_messageId, ''); break;
			case 'remove'	: $db->removeFromWatchlist($this->_messageId); break;
			default			: ;
		} # switch
		
		#- display stuff -#
		$this->template('header');
		$this->template('watchlist', array('watchlist' => $watchList, 'action' => $this->_action));
		$this->template('footer');
	} # render
	
} # class SpotPage_watchlist
