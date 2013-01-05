<?php
/*
 * Class to storage all settings in. Contains both 'ownsettings.php' settings as database settings
 */
class Services_Settings_Base {
	private static $_instance = null;
	private $_settingsDao;
	private $_blackWhiteListDao;

	/* Merged array with all settings (both db, and php) */
	private static $_settings;
	/* Settings which originated from PHP */
	private static $_phpSettings;
	/* Settings which originated from the database */
	private static $_dbSettings;

	/*
	 * Private constructor, class is singleton
	 */
	private function __construct(Dao_Setting $settingsDao, Dao_BlackWhiteList $blackWhiteListDao) {
		$this->_settingsDao = $settingsDao;
		$this->_blackWhiteListDao = $blackWhiteListDao;
	} # ctor
	
	/* 
	 * Services_Settings_Base is a singleton class, this function instantiates SpotSetttings
	 */
	public static function singleton(Dao_Setting $settingsDao, Dao_BlackWhiteList $blackWhiteListDao, array $phpSettings) {
		if (self::$_instance === null) {
			self::$_instance = new Services_Settings_Base($settingsDao, $blackWhiteListDao);
			
			# Make sure the PHP settings are stored in the class individually
			self::$_phpSettings = $phpSettings;
			
			# Retrieve all settings and prepare those
			self::$_dbSettings = $settingsDao->getAllSettings();

			# merge them
			self::$_settings = array_merge(self::$_dbSettings, self::$_phpSettings);

			/*
			 * When no specific NNTP header / comments server is entered, we override these with the NZB server
			 * header. This allows us to always assume those are entered by the user.
			 */
			if ((empty(self::$_settings['nntp_hdr']['host'])) && (!empty(self::$_settings['nntp_nzb']))) {
				self::$_settings['nntp_hdr'] = self::$_settings['nntp_nzb'];
			} # if

			# Same for the NNTP upload server
			if ((empty(self::$_settings['nntp_post']['host'])) && (!empty(self::$_settings['nntp_nzb']))) {
				self::$_settings['nntp_post'] = self::$_settings['nntp_nzb'];
			} # if
		} # if
		
		return self::$_instance;
	} # singleton

	/*
	 * Returns the value of a setting
	 */
	function get($name) {
		return self::$_settings[$name];
	} # get

	/*
	 * Removes a certain setting from the database. If it is a PHP setting,
	 * it will return the next time this class is instantiated.
	 */
	function remove($name) {
		unset(self::$_settings[$name]);
		
		$this->_settingsDao->removeSetting($name);
	} # remove
	
	/*
	 *
	 * Returns whether a specific setting originated from the
	 * database or the (own)settings.php file. If both contain
	 * the setting, the PHP takes precedence.
	 */
	function getOrigin($name) {
		if (isset(self::$_phpSettings[$name])) {
			return "php";
		} else {
			return "db";
		} # if
	} # getOrigin

	/*
	 * Updates a setting. It will throw an exception if the
	 * setting is set from within PHP to ensure we don't create
	 * an setting which seems to revert magically.
	 *
	 * Otherwise directly persists the setting, so be careful
	 */
	function set($name, $value) {
		/*
		 * If setting originates from PHP, throw an exception
		 */
		if (isset(self::$_phpSettings[$name])) {
			throw new InvalidSettingsUpdateException("InvalidSettingUpdate Exception for '" . $name . '"');
		} # if
		
		# Make sure we update our own settings system
		self::$_settings[$name] = $value;
		self::$_dbSettings[$name] = $value;
		
		$this->_settingsDao->updateSetting($name, $value);
	} # set
	
	/*
	 * Validate settings
	 */
	function validateSettings($settings) {
		$result = new Dto_FormResult();

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
			$result->addError(_('Invalid encryption setting'));
		} # if
		if (in_array($settings['spot_moderation'], $validModerationAction) === false) {
			$result->addError(_('Invalid spot moderation setting'));
		} # if
		if (in_array($settings['retentiontype'], $validRetentionTypes) === false) {
			$result->addError(_('Invalid spot retentiontype setting'));
		} # if

		# Verify settings
		$settings['cookie_expires'] = (int) $settings['cookie_expires'];
		if ($settings['cookie_expires'] < 0) {
			$result->addError(_('Invalid cookie_expires setting'));
		} # if

		$settings['retention'] = (int) $settings['retention'];
		if ($settings['retention'] < 0) {
			$result->addError(_('Invalid retention setting'));
		} # if

		if (($settings['retrieve_newer_than'] = strtotime($settings['retrieve_newer_than'])) === false || $settings['retrieve_newer_than'] > time()) {
			$result->addError(_('Invalid retrieve_newer_than setting'));
		} elseif ($settings['retrieve_newer_than'] < 1230789600) {
			/* We don't allow settings earlier than january 1st 2009 */
			$settings['retrieve_newer_than'] = 1230789600;
		} # elseif

		$settings['retrieve_increment'] = (int) $settings['retrieve_increment'];
		if ($settings['retrieve_increment'] < 1) {
			$result->addError(_('Invalid retrieve_increment setting'));
		} # if

		# check the mailaddress
		if (!filter_var($settings['systemfrommail'], FILTER_VALIDATE_EMAIL)) {
			$result->addError(_('Not a valid email address'));
		} # if

		# We don't want to save megabyts of CSS, so put a limit to the size
		if (strlen($settings['customcss'] > 1024 * 10)) { 
			$result->addError(_('Custom CSS is too large'));
		} # if		

		# Convert other settings (usually checkboxes) to be simply boolean settings
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

		/* 
		 * Remove dummy preferences 
		 */
		unset($settings['nntp_hdr']['use'], $settings['nntp_post']['use']);

		/*
		 * We want to pass the updated settings back to the caller because
		 * we fixed several stuff.
		 */
		$result->addData('settings', $settings);

		return $result;
	} # validateSettings

	function setSettings($settings) {
		# If we disable the external blacklist, clear all entries
		if ($settings['external_blacklist'] == false && $this->get('external_blacklist') == true) {
			$this->_blackWhiteListDao->removeOldList($this->get('blacklist_url'), 'black');
		} # if

		# If we disable the external whitelist, clear all entries
		if ($settings['external_whitelist'] == false && $this->get('external_whitelist') == true) {
			$this->_blackWhiteListDao->removeOldList($this->get('whitelist_url'), 'white');
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
	 * Is our database version still valid?
	 */
	function schemaValid() {
		# Is our database still up to date
		return ($this->get('schemaversion') == SPOTDB_SCHEMA_VERSION);
	} # schemaValid
	
	
	/* 
	 * Zijn onze settings versie nog wel geldig?
	 */
	function settingsValid() {
		# Is our settings list still valid?
		return ($this->get('settingsversion') == SPOTWEB_SETTINGS_VERSION);
	} # settingsValid

	/* 
	 * Does the setting actually exist?
	 */
	function exists($name) {
		return isset(self::$_settings[$name]);
	} # isSet

    /*
     * Returns a list of all settings in an array
     */
    function getAllSettings() {
        return self::$_settings;
    } # getAllSettings

} # class Services_Settings_Base
