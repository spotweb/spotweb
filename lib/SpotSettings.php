<?php
define('SPOTWEB_SETTINGS_VERSION', '0.25');
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
			self::$_settings = array_merge(self::$_dbSettings, self::$_phpSettings);

			# Override NNTP header/comments settings, als er geen aparte NNTP header/comments server is opgegeven, gebruik die van 
			# de NZB server
			if ((empty(self::$_settings['nntp_hdr']['host'])) && (!empty(self::$_settings['nntp_nzb']))) {
				self::$_settings['nntp_hdr'] = self::$_settings['nntp_nzb'];
			} # if

			# Hetzelfde voor de NNTP upload server
			if ((empty(self::$_settings['nntp_post']['host'])) && (!empty(self::$_settings['nntp_nzb']))) {
				self::$_settings['nntp_post'] = self::$_settings['nntp_nzb'];
			} # if
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
			throw new InvalidSettingsUpdateException("InvalidSettingUpdat Exception for '" . $name . '"');
		} # if
		
		# Update onze eigen settings array zodat we meteen up-to-date zijn
		self::$_settings[$name] = $value;
		
		$this->_db->updateSetting($name, $value);
	} # set
	
	/*
	 * Validate settings
	 */
	function validateSettings($settings) {
		$errorList = array();

		# Define arrays with valid settings
		$validNntpEnc = array(false, 'ssl', 'tls');
		$validModerationAction = array('disable', 'act', 'markspot');
		$validRetentionTypes = array('fullonly', 'everything');

		# Get the given value for NNTP encryption
		$settings['nntp_nzb']['enc'] = (isset($settings['nntp_nzb']['enc']['switch'])) ? $settings['nntp_nzb']['enc']['select'] : false;
		$settings['nntp_hdr']['enc'] = (isset($settings['nntp_hdr']['enc']['switch'])) ? $settings['nntp_hdr']['enc']['select'] : false;
		$settings['nntp_post']['enc'] = (isset($settings['nntp_post']['enc']['switch'])) ? $settings['nntp_post']['enc']['select'] : false;

		# Trim human-entered text fields
		$settings['nntp_nzb']['host'] = trim($settings['nntp_nzb']['host']);
		$settings['nntp_hdr']['host'] = trim($settings['nntp_hdr']['host']);
		$settings['nntp_post']['host'] = trim($settings['nntp_post']['host']);

		# Verify settings with the previous declared arrays
		if (in_array($settings['nntp_nzb']['enc'], $validNntpEnc) === false || in_array($settings['nntp_hdr']['enc'], $validNntpEnc) === false || in_array($settings['nntp_post']['enc'], $validNntpEnc) === false) {
			$errorList[] = _('Invalid encryption setting');
		} # if
		if (in_array($settings['spot_moderation'], $validModerationAction) === false) {
			$errorList[] = _('Invalid spot moderation setting');
		} # if
		if (in_array($settings['retentiontype'], $validRetentionTypes) === false) {
			$errorList[] = _('Invalid spot retentiontype setting');
		} # if

		# Verify settings
		$settings['cookie_expires'] = (int) $settings['cookie_expires'];
		if ($settings['cookie_expires'] < 0) {
			$errorList[] = _('Invalid cookie_expires setting');
		} # if

		$settings['retention'] = (int) $settings['retention'];
		if ($settings['retention'] < 0) {
			$errorList[] = _('Invalid retention setting');
		} # if

		if (($settings['retrieve_newer_than'] = strtotime($settings['retrieve_newer_than'])) === false || $settings['retrieve_newer_than'] > time()) {
			$errorList[] = _('Invalid retrieve_newer_than setting');
		} elseif ($settings['retrieve_newer_than'] < 1230789600) {
			$settings['retrieve_newer_than'] = 1230789600;
		} # elseif

		$settings['retrieve_increment'] = (int) $settings['retrieve_increment'];
		if ($settings['retrieve_increment'] < 1) {
			$errorList[] = _('Invalid retrieve_increment setting');
		} # if

		# check the mailaddress
		if (!filter_var($settings['systemfrommail'], FILTER_VALIDATE_EMAIL)) {
			$errorList[] = _('Not a valid email address');
		} # if

		# We don't want to save megabyts of CSS, so put a limit to the size
		if (strlen($settings['customcss'] > 1024 * 10)) { 
			$errorList[] = _('Custom CSS is too large');
		} # if		

		# converteer overige settings naar boolean zodat we gewoon al weten wat er uitkomt
		$settings['deny_robots'] = (isset($settings['deny_robots'])) ? true : false;
		$settings['sendwelcomemail'] = (isset($settings['sendwelcomemail'])) ? true : false;
		$settings['nntp_nzb']['buggy'] = (isset($settings['nntp_nzb']['buggy'])) ? true : false;
		$settings['nntp_hdr']['buggy'] = (isset($settings['nntp_hdr']['buggy'])) ? true : false;
		$settings['nntp_post']['buggy'] = (isset($settings['nntp_post']['buggy'])) ? true : false;
		$settings['retrieve_full'] = (isset($settings['retrieve_full'])) ? true : false;
		$settings['prefetch_image'] = (isset($settings['prefetch_image'])) ? true : false;
		$settings['prefetch_nzb'] = (isset($settings['prefetch_nzb'])) ? true : false;
		$settings['retrieve_comments'] = (isset($settings['retrieve_comments'])) ? true : false;
		$settings['retrieve_full_comments'] = (isset($settings['retrieve_full_comments'])) ? true : false;
		$settings['retrieve_reports'] = (isset($settings['retrieve_reports'])) ? true : false;
		$settings['enable_timing'] = (isset($settings['enable_timing'])) ? true : false;
		$settings['enable_stacktrace'] = (isset($settings['enable_stacktrace'])) ? true : false;
		$settings['prepare_statistics'] = (isset($settings['prepare_statistics'])) ? true : false;
		$settings['external_blacklist'] = (isset($settings['external_blacklist'])) ? true : false;
		$settings['external_whitelist'] = (isset($settings['external_whitelist'])) ? true : false;

		# Default server settings if they won't be used
		if (!isset($settings['nntp_hdr']['use'])) { 
			$settings['nntp_hdr'] = array('host' => '', 
										  'user' => '', 
										  'pass' => '', 
										  'enc' => false, 
										  'port' => 119, 
										  'buggy' => false); 
		} # if
										
		if (!isset($settings['nntp_post']['use'])) { 
			$settings['nntp_post'] = array('host' => '', 
										   'user' => '', 
										   'pass' => '', 
										   'enc' => false, 
										   'port' => 119, 
										   'buggy' => false); 
		} # if

		unset($settings['nntp_hdr']['use'], $settings['nntp_post']['use']);

		return array($errorList, $settings);
	} # validateSettings

	function setSettings($settings) {
		# If we disable the external blacklist, clear all entries
		if ($settings['external_blacklist'] == false && $this->get('external_blacklist') == true) {
			$this->_db->removeOldList($this->get('blacklist_url'), SpotDb::spotterlist_Black);
		} # if

		# If we disable the external whitelist, clear all entries
		if ($settings['external_whitelist'] == false && $this->get('external_whitelist') == true) {
			$this->_db->removeOldList($this->get('whitelist_url'), SpotDb::spotterlist_White);
		} # if

		# clear some stuff we don't need to store
		unset($settings['xsrfid'], $settings['http_referer'], $settings['buttonpressed'], $settings['action'], $settings['submitedit']);

		# Store settings
		foreach ($settings as $key => $value) {
			# and write these updated settings to the database
			$this->set($key, $value);
		} # foreach
	} # setSettings

	/* 
	 * Is onze database versie nog wel geldig?
	 */
	function schemaValid() {
		# SPOTDB_SCHEMA_VERSION is gedefinieerd bovenin SpotDb
		return ($this->get('schemaversion') == SPOTDB_SCHEMA_VERSION);
	} # schemaValid
	
	
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
