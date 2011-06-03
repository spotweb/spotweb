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
		
		$this->createServerKeys($this->_settings->get('openssl_cnf_path'));
		$this->createPasswordSalt();
		$this->setupNewsgroups();
		$this->createRsaKeys();
		$this->createXsrfSecret();
		$this->setIfNot('cookie_expires', 30);
		$this->setIfNot('sabnzbdurltpl', 'http://$SABNZBDHOST/sabnzbd/api?mode=$SABNZBDMODE&name=$NZBURL&nzbname=$SPOTTITLE&cat=$SABNZBDCAT&apikey=$APIKEY&output=text');
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
	 * Update de huidige versie van de settings
	 */
	function updateSettingsVersion() {
		$this->_settings->set('settingsversion', SPOTWEB_SETTINGS_VERSION);
	} # updateSettingsVersion
	
	/*
	 * Creeer de server private en public keys
	 */
	function createServerKeys($openSslCnfPath) {
		$spotSigning = new SpotSigning(true);
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
	} # setupNewsgroups()
	
} # SpotSettingsUpgrader
