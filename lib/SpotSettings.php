<?php
define('SPOTWEB_SETTINGS_VERSION', '0.03');
define('SPOTWEB_VERSION', '0.' . (SPOTDB_SCHEMA_VERSION * 100) . '.' . (SPOTWEB_SETTINGS_VERSION * 100) . '.' . (SPOTWEB_SECURITY_VERSION * 100));
/*
 * Classe om de server settings in op te slaan
 */
class SpotSettings {
	private static $_instance = null;
	
	private $_db;
	private static $_settings;
	
	/* 
	 * Instantieert een nieuwe settings klasse
	 */
	public static function singleton(SpotDb $db, array $settings) {
		if (self::$_instance === null) {
			self::$_instance = new SpotSettings($db);
			
			# haal alle settings op, en prepareer die 
			$dbSettings = $db->getAllSettings();
			$tmpSettings = array();
			foreach($dbSettings as $item) {
				if ($item['serialized']) {
					$item['value'] = unserialize($item['value']);
				} # if
				
				$tmpSettings[$item['name']] = $item['value'];
			} # foreach

			# en merge de settings met degene die we door krijgen 
			self::$_settings = array_merge($settings, $tmpSettings);
		} # if
		
		return self::$_instance;
	} # singleton

	/*
	 * Geeft de waarde van de setting terug
	 */
	function get($name) {
		return self::$_settings[$name];
	} # get

	/*
	 * Unset een bepaalde waarde
	 */
	function remove($name) {
		unset(self::$_settings[$name]);
		
		$this->_db->removeSetting($name);
	} # remove
	
	/*
	 * Set de waarde van de setting, maakt hem ook
	 * meteen persistent dus mee oppassen
	 */
	function set($name, $value) {
		# Update onze eigen settings array zodat we meteen up-to-date zijn
		self::$_settings[$name] = $value;
		
		# maar zet het eventueel serialized in de database als dat nodig is
		if ((is_array($value) || is_object($value))) {
			$value = serialize($value);
			$serialized = true;
		} else {
			$serialized = false;
		} # if
		
		$this->_db->updateSetting($name, $value, $serialized);
	} # set

	/* 
	 * Zijn onze settings versie nog wel geldig?
	 */
	function settingsValid() {
		# SPOTWEB_SETTINGS_VERSION is gedefinieerd bovenin dit bestand
		return ($this->get('settingsversion') == SPOTWEB_SETTINGS_VERSION);
	} # settingsValid

	
	/* 
	 * Bestaat de opgegeven setting ?
	 */
	function exists($name) {
		return isset(self::$_settings[$name]);
	} # isSet
	
	/*
	 * Private constructor, moet altijd via singleton gaan
	 */
	private function __construct($db) {
		$this->_db = $db;
	} # ctor
	
} # class SpotSettings
