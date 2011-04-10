<?php
require_once "lib/SpotTemplateHelper.php";
require_once "lib/SpotsOverview.php";

abstract class SpotPage_Abs {
	protected $_db;
	protected $_settings;
	protected $_pageTitle;
	protected $_currentUser;
	
	function __construct($db, $settings, $currentUser) {
		$this->_db = $db;
		$this->_settings = $settings;
		$this->_currentUser = $currentUser;
	} # ctor
	
	# Geef the tpl helper terug
	function getTplHelper($params) {
		if (file_exists($this->_settings['tpl_path'] . '/CustomTplHelper.php')) {
			require_once $this->_settings['tpl_path'] . '/CustomTplHelper.php';
			
			$tplHelper = new CustomTplHelper($this->_settings, $this->_currentUser, $this->_db, $params);
		} else {
			$tplHelper = new SpotTemplateHelper($this->_settings, $this->_currentUser, $this->_db, $params);
		} # else
		
		return $tplHelper;
	} # getTplHelper
		
	
	/*
	 * Display de template
	 */
	function template($tpl, $params = array()) {
		extract($params, EXTR_REFS);
		$settings = $this->_settings;
		$pagetitle = 'SpotWeb - ' . $this->_pageTitle;
		
		# We maken een aantal variabelen / objecten standaard beschikbaar in de template.
		$tplHelper = $this->getTplHelper($params);

		# en we spelen de template af
		require_once($settings['tpl_path'] . $tpl . '.inc.php');
	} # template
	
	/*
	 * Daadwerkelijk renderen van de pagina -- implementatie specifiek
	 */
	abstract function render();
	
} # SpotPage_Abs 