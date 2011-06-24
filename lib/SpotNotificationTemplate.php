<?php

class SpotNotificationTemplate {
	protected $_db;
	protected $_settings;
	protected $_currentSession;
	protected $_spotSec;
	
	function __construct(SpotDb $db, SpotSettings $settings, $currentSession) {
		$this->_db = $db;
		$this->_settings = $settings;
		$this->_currentSession = $currentSession;
		$this->_spotSec = $currentSession['security'];
	} # ctor

	/*
	 * Vraagt de inhoud van de template op
	 */
	function template($tpl, $params = array()) {
		SpotTiming::start(__FUNCTION__ . ':notifications:' . $tpl);
		
		extract($params, EXTR_REFS);
		$settings = $this->_settings;
		
		# We maken een aantal variabelen / objecten standaard beschikbaar in de template.
		$currentSession = $this->_currentSession;
		$spotSec = $this->_currentSession['security'];

		# start output buffering
		ob_start(); 
		
		# en we spelen de template af
		require 'templates/' . $settings->get('tpl_name') . '/notifications/' . $tpl . '.inc.php';
		
		# nu vraag de inhoud van de output buffer op
		$notificationContent = ob_get_contents();
        ob_end_clean();	
		
		# de eerste regel is het onderwerp, de tweede regel is een spatie, 
		# en de rest is daadwerkelijke buffer
		$notificationArray = explode("\n", $notificationContent);
		
		SpotTiming::stop(__FUNCTION__ . ':notifications:' . $tpl, array($params));
		
		return array('title' => $notificationArray[0],
					 'body' => array_slice($notificationArray, 2));
	} # template
	
} # class
