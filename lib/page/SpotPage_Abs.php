<?php
abstract class SpotPage_Abs {
	protected $_db;
	protected $_settings;
	protected $_pageTitle;
	protected $_currentSession;
	
	function __construct(SpotDb $db, SpotSettings $settings, $currentSession) {
		$this->_db = $db;
		$this->_settings = $settings;
		$this->_currentSession = $currentSession;
	} # ctor
	
	# Geef the tpl helper terug
	function getTplHelper($params) {
		if (file_exists($this->_settings->get('tpl_path') . '/CustomTplHelper.php')) {
			require_once $this->_settings->get('tpl_path') . '/CustomTplHelper.php';
			
			$tplHelper = new CustomTplHelper($this->_settings, $this->_currentSession, $this->_db, $params);
		} else {
			$tplHelper = new SpotTemplateHelper($this->_settings, $this->_currentSession, $this->_db, $params);
		} # else
		
		return $tplHelper;
	} # getTplHelper
		
	
	/*
	 * Display de template
	 */
	function template($tpl, $params = array()) {
		SpotTiming::start(__FUNCTION__ . ':' . $tpl);
		extract($params, EXTR_REFS);
		$settings = $this->_settings;
		$pagetitle = 'SpotWeb - ' . $this->_pageTitle;
		
		# We maken een aantal variabelen / objecten standaard beschikbaar in de template.
		$tplHelper = $this->getTplHelper($params);
		$currentSession = $this->_currentSession;

		# en we spelen de template af
		require_once($settings->get('tpl_path') . $tpl . '.inc.php');
		SpotTiming::stop(__FUNCTION__ . ':' . $tpl, array($params));
	} # template
	
	/*
	 * Daadwerkelijk renderen van de pagina -- implementatie specifiek
	 */
	abstract function render();
	
} # SpotPage_Abs 