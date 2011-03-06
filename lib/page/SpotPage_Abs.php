<?php
require_once "lib/SpotTemplateHelper.php";

abstract class SpotPage_Abs {
	protected $_db;
	protected $_settings;
	protected $_prefs;
	protected $_pageTitle;
	
	
	function __construct($db, $settings, $prefs) {
		$this->_db = $db;
		$this->_settings = $settings;
		$this->_prefs = $prefs;
	} # ctor
	
	/*
	 * Display de template
	 */
	function template($tpl, $params = array()) {
		extract($params, EXTR_REFS);
		$settings = $this->_settings;
		$pagetitle = 'SpotWeb - ' . $this->_pageTitle;
	
		if (file_exists($settings['tpl_path'] . '/CustomTplHelper.php')) {
			require_once $settings['tpl_path'] . '/CustomTplHelper.php';
			
			$tplHelper = new CustomTplHelper($this->_settings, $this->_prefs, $this->_db);
		} else {
			$tplHelper = new SpotTemplateHelper($this->_settings, $this->_prefs, $this->_db);
		} # else
		
		require_once($settings['tpl_path'] . $tpl . '.inc.php');
	} # template
	
	/*
	 * Daadwerkelijk renderen van de pagina -- implementatie specifiek
	 */
	abstract function render();
	
} # SpotPage_Abs 