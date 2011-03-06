<?php
require_once "lib/SpotTemplateHelper.php";

abstract class SpotPage_Abs {
	protected $_db;
	protected $_settings;
	protected $_prefs;
	protected $_req;
	protected $_pageTitle;
	
	
	function __construct($db, $settings, $prefs, $req) {
		$this->_db = $db;
		$this->_settings = $settings;
		$this->_prefs = $prefs;
		$this->_req = $req;
	} # ctor
	
	/*
	 * Display de template
	 */
	function template($tpl, $params = array()) {
		extract($params, EXTR_REFS);
		$settings = $this->_settings;
		$pagetitle = 'SpotWeb - ' . $this->_pageTitle;
		
		$tplHelper = new SpotTemplateHelper($this->_settings, $this->_prefs);
		require_once($settings['tpl_path'] . $tpl . '.inc.php');
	} # template
	
	/*
	 * Daadwerkelijk renderen van de pagina -- implementatie specifiek
	 */
	abstract function render();
	
} # SpotPage_Abs 