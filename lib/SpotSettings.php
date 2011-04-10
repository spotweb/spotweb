<?php

/*
 * Classe om de server settings in op te slaan
 */
class SpotSettings {
	private static $_instance = null;
	
	private $_db;
	private $_settings;
	
	/* 
	 * Instantieert een nieuwe settings klasse
	 */
	public static function singleton($db) {
		if (SpotSettings::$_instance === null) {
			SpotSettings::$_instance = new SpotSettings($db);
			SpotSettings::$_settings = $db->getAllSettings();
		} # if
		
		return SpotSettings::$_instance;
	} # singleton

	/*
	 * Geeft de waarde van de setting terug
	 */
	function get($name) {
		return $this->_settings[$name];
	} # get
	
	/*
	 * Set de waarde van de setting, maakt hem ook
	 * meteen persistent dus mee oppassen
	 */
	function set($name, $value) {
		$this->_settings[$name] = $value;
		$this->_db->updateSetting($name, $value);
	} # set
	
	/* 
	 * Bestaat de opgegeven setting ?
	 */
	function exists($name) {
		return isset($this->_settings[$name]);
	} # isSet
	
	/*
	 * Private constructor, moet altijd via singleton gaan
	 */
	private function __construct($db) {
		$this->_db = $db;
		
		$this->_settings = $db->getAllSettings();
		var_dump($this->_settings);
	} # ctor
	
} # class SpotSettings
