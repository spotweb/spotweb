<?php
abstract class SpotPage_Abs {
	protected $_db;
	protected $_settings;
	protected $_pageTitle;
	protected $_currentSession;
	protected $_spotSec;
	protected $_tplHelper;
	
	function __construct(SpotDb $db, SpotSettings $settings, $currentSession) {
		$this->_db = $db;
		$this->_settings = $settings;
		$this->_currentSession = $currentSession;
		$this->_spotSec = $currentSession['security'];
		$this->_tplHelper = $this->getTplHelper(array());
	} # ctor
	
	# Geef the tpl helper terug
	function getTplHelper($params) {
		if (file_exists('templates/' . $this->_settings->get('tpl_name') . '/CustomTplHelper.php')) {
			require_once 'templates/' . $this->_settings->get('tpl_name') . '/CustomTplHelper.php';
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
		
		# update the template helper variables
		$this->_tplHelper->setParams($params);
		
		# We maken een aantal variabelen / objecten standaard beschikbaar in de template.
		$tplHelper = $this->_tplHelper;
		$currentSession = $this->_currentSession;
		$spotSec = $this->_currentSession['security'];

		# en we spelen de template af
		require_once('templates/' . $settings->get('tpl_name') . '/' . $tpl . '.inc.php');
		SpotTiming::stop(__FUNCTION__ . ':' . $tpl, array($params));
	} # template
	
	/*
	 * Daadwerkelijk renderen van de pagina -- implementatie specifiek
	 */
	abstract function render();
	
} # SpotPage_Abs 