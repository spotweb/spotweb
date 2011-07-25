<?php
define('SPOTWEB_SETTINGS_VERSION', '0.04');
define('SPOTWEB_VERSION', '0.' . (SPOTDB_SCHEMA_VERSION * 100) . '.' . (SPOTWEB_SETTINGS_VERSION * 100) . '.' . (SPOTWEB_SECURITY_VERSION * 100));
/*
 * Classe om de server settings in op te slaan
 */
class SpotSettings {
	private static $_instance = null;
	
	private $_db;
	/* Gemergede array met alle settings */
	private static $_settings;
	/* Settings die uit PHP komen */
	private static $_phpSettings;
	/* Settings die uit de database komen */
	private static $_dbSettings;
	
	/* 
	 * Instantieert een nieuwe settings klasse
	 */
	public static function singleton(SpotDb $db, array $phpSettings) {
		if (self::$_instance === null) {
			self::$_instance = new SpotSettings($db);
			
			# maak de array met PHP settings beschikbaar in de klasse
			self::$_phpSettings = $phpSettings;
			
			# haal alle settings op, en prepareer die 
			self::$_dbSettings = $db->getAllSettings();

			# en merge de settings met degene die we door krijgen 
			self::$_settings = array_merge(self::$_phpSettings, self::$_dbSettings);
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
	 * Geeft terug of een bepaalde setting uit de database
	 * komt of uit de settings.php file. De settings-file
	 * heeft altijd prioriteit 
	 */
	function getOrigin($name) {
		if (isset(self::$_phpSettings[$name])) {
			return "php";
		} else {
			return "db";
		} # if
	} # getOrigin
	
	/*
	 * Set de waarde van de setting, maakt hem ook
	 * meteen persistent dus mee oppassen
	 */
	function set($name, $value) {
		# Als de setting uit PHP komt, dan mag die niet geupdate worden
		# hier omdat we dan niet meer weten wat er gebeurt.
		if (isset(self::$_phpSettings[$name])) {
			throw new InvalidSettingsUpdateException();
		} # if
		
		# Update onze eigen settings array zodat we meteen up-to-date zijn
		self::$_settings[$name] = $value;
		
		$this->_db->updateSetting($name, $value);
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
