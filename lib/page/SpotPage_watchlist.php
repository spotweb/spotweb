<?php
require_once "lib/page/SpotPage_Abs.php";
require_once "lib/SpotCategories.php";

class SpotPage_watchlist extends SpotPage_Abs {
	private $_messageid;
	private $_action;
	
	function __construct($db, $settings, $prefs, $messageid, $action) {
		parent::__construct($db, $settings, $prefs);
		$this->_messageid = trim($messageid);
		
		if (array_search($action, array('add', 'remove', 'list')) === false) {
			$action = 'list';
		} # if
		$this->_action = $action;
	} # ctor


	function render() {
		# zet de page title
		$this->_pageTitle = "watchlist";
		
		# we moeten een messageid opgeven
		if (empty($this->_messageid) && ($this->_action != 'list')) {
			throw new Exception("Must give messageid");
		} # if
		
		# afhankelijk van wat re gekozen is, voer het uit
		switch($this->_action) {
			case 'add'		: $this->_db->addToWatchList($this->_messageid, ''); break;
			case 'remove'	: $this->_db->removeFromWatchlist($this->_messageid); break;
			default			: ;
		} # switch
		
		# Haal de volledige watchlist op
		$watchList = $this->_db->getWatchList();

		#- display stuff -#
		$this->template('header');
		$this->template('watchlist', array('watchlist' => $watchList, 'action' => $this->_action));
		$this->template('footer');
	} # render
	
} # class SpotPage_watchlist
