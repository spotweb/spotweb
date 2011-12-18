<?php
class SpotSettingsUpgrader {
	private $_db;
	private $_settings;

	function __construct(SpotDb $db, SpotSettings $settings) {
		$this->_db = $db;
		$this->_settings = $settings;
	} # ctor

	function update() {
		# Zorg dat de diverse versienummers altijd in de db staan zodat
		# we er mee kunnen vergelijken
		$this->setIfNot("settingsversion", "0.00");
		$this->setIfNot("securityversion", "0.00");

		if ($this->_settings->get('settingsversion') < 0.15) {
			$this->remove('system_languages');
		} # if
		
		$this->createServerKeys($this->_settings->get('openssl_cnf_path'));
		$this->createPasswordSalt();
		$this->setupNewsgroups();
		$this->createRsaKeys();
		$this->createXsrfSecret();
		$this->remove('sabnzbdurltpl');
		$this->remove('sabnzbdurl');
		$this->remove('recompress_nzb');
		$this->remove('available_languages');
		$this->remove('featureversion');
		$this->remove('max_newcount');
		$this->setIfNot('cookie_expires', 30);
		$this->setIfNot('sendwelcomemail', true);
		$this->setIfNot('twitter_consumer_key', 'LRJCpeHASigYtWEmxoNPA');
		$this->setIfNot('twitter_consumer_secret', 'QvwZglJNpzAnoVDt40uUyu5dRDlVFVs4ddxfEkYp7A'); // This secret can be shared
		$this->setIfNot('boxcar_api_key', 'pOQM9O2AnEWL0RjSoHln');
		$this->setIfNot('boxcar_api_secret', '7CwTFfX7KeAKfjM1DJjg5s9qcHm4cwmLkxQgW9fe'); // This secret can be shared
		$this->setIfNot('auditlevel', 0); // No auditing
		$this->setIfNot('system_languages', array('nl_NL' => 'Nederlands', 'en_US' => 'English'));
		$this->setIfNot('retention', 0);
		$this->setIfNot('deny_robots', true);
		$this->setIfNot('nntp_nzb', array('host' => '', 'user' => '', 'pass' => '', 'enc' => false, 'port' => 119, 'buggy' => false));
		$this->setIfNot('nntp_hdr', array('host' => '', 'user' => '', 'pass' => '', 'enc' => false, 'port' => 119, 'buggy' => false));
		$this->setIfNot('nntp_post', array('host' => '', 'user' => '', 'pass' => '', 'enc' => false, 'port' => 119, 'buggy' => false));
		$this->setIfNot('retrieve_newer_than', 0);
		$this->setIfNot('retrieve_full', false);
		$this->setIfNot('prefetch_image', false);
		$this->setIfNot('prefetch_nzb', false);
		$this->setIfNot('retrieve_comments', true);
		$this->setIfNot('retrieve_full_comments', false);
		$this->setIfNot('retrieve_reports', true);
		$this->setIfNot('retrieve_increment', 1000);
		$this->setIfNot('spot_moderation', 'act');
		$this->setIfNot('prepare_statistics', true);
		$this->setIfNot('external_blacklist', true);
		$this->setIfNot('blacklist_url', 'http://jij.haatmij.nl/spotnet/blacklist.txt');
		$this->setIfNot('enable_timing', false);
		$this->setIfNot('enable_stacktrace', true);
		$this->setIfNot('systemfrommail', 'spotweb@example.com');
		$this->updateSettingsVersion();
	} # update()
	
	/*
	 * Set een setting alleen als hij nog niet bestaat
	 */
	function setIfNot($name, $value) {
		if ($this->_settings->exists($name)) {
			return ;
		} # if
		
		$this->_settings->set($name,$value);
	} # setIfNot
	 
	/*
	 * Verwijder een setting
	 */
	function remove($name) {
		$this->_settings->remove($name);
	} # remove
	
	/*
	 * Update de huidige versie van de settings
	 */
	function updateSettingsVersion() {
		$this->_settings->set('settingsversion', SPOTWEB_SETTINGS_VERSION);
	} # updateSettingsVersion
	
	/*
	 * Creeer de server private en public keys
	 */
	function createServerKeys($openSslCnfPath) {
		$spotSigning = new SpotSigning();
		$x = $spotSigning->createPrivateKey($openSslCnfPath);
		
		$this->setIfNot('publickey', $x['public']);
		$this->setIfNot('privatekey', $x['private']);
	} # createServerKeys

	/*
	 * Creeer de RSA keys
	 */
	function createRsaKeys() {
		#
		# RSA keys
		# Worden gebruikt om te valideren of spots geldig zijn, hoef je normaal niet aan te komen
		#
		$rsaKeys = array();
		$rsaKeys[2] = array('modulo' => 'ys8WSlqonQMWT8ubG0tAA2Q07P36E+CJmb875wSR1XH7IFhEi0CCwlUzNqBFhC+P',
							'exponent' => 'AQAB');
		$rsaKeys[3] = array('modulo' => 'uiyChPV23eguLAJNttC/o0nAsxXgdjtvUvidV2JL+hjNzc4Tc/PPo2JdYvsqUsat',
							'exponent' => 'AQAB');
		$rsaKeys[4] = array('modulo' => '1k6RNDVD6yBYWR6kHmwzmSud7JkNV4SMigBrs+jFgOK5Ldzwl17mKXJhl+su/GR9',
							'exponent' => 'AQAB');
		
		$this->setIfNot('rsa_keys', $rsaKeys);
	} # createRsaKeys
	
	/*
	 * Create an xsrf secret
	 */
	function createXsrfSecret() {
		$userSystem = new SpotUserSystem($this->_db, $this->_settings);
		$secret = substr($userSystem->generateUniqueId(), 0, 8);
		
		$this->setIfNot('xsrfsecret', $secret);
	} # createXsrfSecret
	/*
	 * Creer de servers' password salt
	 */
	function createPasswordSalt() {
		$userSystem = new SpotUserSystem($this->_db, $this->_settings);
		$salt = $userSystem->generateUniqueId() . $userSystem->generateUniqueId();
		
		$this->setIfNot('pass_salt', $salt);
	} # createPasswordSalt

	/*
	 * Definieer de standaard SpotNet groepen
	 */
	function setupNewsgroups() {
		$this->setIfNot('hdr_group', 'free.pt');
		$this->setIfNot('nzb_group', 'alt.binaries.ftd');
		$this->setIfNot('comment_group', 'free.usenet');
		$this->setIfNot('report_group', 'free.willey');
	} # setupNewsgroups()
	
} # SpotSettingsUpgrader
