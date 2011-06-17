<?php
define('SPOTWEB_ANONYMOUS_USERID', 1);
define('SPOTWEB_ADMIN_USERID', 2);

class SpotUserSystem {
	private $_db;
	private $_settings;
	
	function __construct(SpotDb $db, SpotSettings $settings) {
		$this->_db = $db;
		$this->_settings = $settings;
	} # ctor

	/*
	 * Genereer een sessionid
	 */
	function generateUniqueId() {
		$sessionId = '';
		
		for($i = 0; $i < 10; $i++) {
			$sessionId .= base_convert(mt_rand(), 10, 36);
		} # for
		
		return $sessionId;
	} # generateUniqueId
	
	/*
	 * Creeer een nieuwe session
	 */
	private function createNewSession($userid) {
		# Als de user ingelogged is, creeer een sessie
		$tmpUser = $this->getUser($userid);
		
		# Als dit een anonieme user is, of als de user nog nooit
		# ingelogt heeft dan is het laatste bezoek altijd het 
		# moment van sessie creatie
		if (($userid == SPOTWEB_ANONYMOUS_USERID) || ($tmpUser['lastlogin'] == 0)) {
			$tmpUser['lastvisit'] = time();
		} else {
			$tmpUser['lastvisit'] = $tmpUser['lastlogin'];
		} # if

		# Creer een session record
		$session = array('sessionid' => $this->generateUniqueId(),
						 'userid' => $userid,
						 'hitcount' => 1,
						 'lasthit' => time());
		$this->_db->addSession($session);
		
		return array('user' => $tmpUser,
					 'session' => $session);
	} # createNewSession

	/* 
	 * Stuur een cookie met de sessionid mee
	 */
	function updateCookie($userSession) {
		SetCookie("spotsession",
				  $userSession['session']['sessionid'] . '.' . $userSession['user']['userid'],
				  time()+60*60*24*30,
				  '', # path: The default value is the current directory that the cookie is being set in.
				  $this->_settings->get('cookie_host'),
				  false,	# Indicates if the cookie should only be transmitted over a secure HTTPS connection from the client.
				  true);	# Only available to the HTTP protocol. This means that the cookie won't be accessible by scripting languages, such as JavaScript.
	} # updateCookie
	
	/*
	 * Verwijdert een sessie
	 */
	function removeSession($sessionId) {
		$this->_db->deleteSession($sessionId);
	} # removeSession
	
	/*
	 * Verwijdert alle users' sessies
	 */
	function removeAllUserSessions($userId) {
		$this->_db->deleteAllUserSessions($userId);
	} # removeAllUserSessions
	
	/*
	 * Kijk of de user een sessie heeft, als hij die heeft gebruik die dan,
	 * anders creeeren we een sessie voor de anonieme user
	 */
	function useOrStartSession() {
		$userSession = false;
		
		if (isset($_COOKIE['spotsession'])) {
			$userSession = $this->validSession($_COOKIE['spotsession']);
		} # if

		if ($userSession === false) {
			# als er nu nog geen sessie bestaat, creeer dan een nieuwe
			# anonieme sessie
			# userid 1 is altijd onze anonymous user
			$userSession = $this->createNewSession(SPOTWEB_ANONYMOUS_USERID);
		} # if
		
		# initialiseer het security systeem
		$spotSec = new SpotSecurity($this->_db, $this->_settings, $userSession['user']);
		$userSession['security'] = $spotSec;
		
		# update de sessie cookie zodat die niet spontaan gaat
		# expiren
		$this->updateCookie($userSession);
		
		return $userSession;
	} # useOrStartSession

	/*
	 * Password to hash
	 */
	function passToHash($password) {
		return sha1(strrev(substr($this->_settings->get('pass_salt'), 1, 3)) . $password . $this->_settings->get('pass_salt'));
	} # passToHash

	/*
	 * Probeert de user aan te loggen met de gegeven credentials,
	 * geeft user record terug of false als de user niet geauth kan
	 * worden
	 */
	function login($user, $password) {
		# Salt het password met het unieke salt in settings.php
		$password = $this->passToHash($password);

		# authenticeer de user?
		$userId = $this->_db->authUser($user, $password);
		if ($userId !== false) {
			# Als de user ingelogged is, creeer een sessie.
			# Volgorde is hier belangrijk omdat in de newsession
			# de lastvisit tijd op lastlogon gezet wordt moeten
			# we eerst de sessie creeeren.
			$userSession = $this->createNewSession($userId);
			$this->updateCookie($userSession);

			# nu gebruiken we het user record om de lastlogin te fixen
			$userSession['user']['lastlogin'] = time();
			$this->_db->setUser($userSession['user']);

			# initialiseer het security systeem
			$userSession['security'] = new SpotSecurity($this->_db, $this->_settings, $userSession['user']);

			return $userSession;
		} else {
			return false;
		} # else
	} # login

	function verifyApi($apikey) {
		# authenticeer de user?
		$userId = $this->_db->authUser(false, $apikey);
		if ($userId !== false && $userId > SPOTWEB_ADMIN_USERID && $apikey != '') {
			# Waar bij een normale login het aanmaken van
			# een sessie belangrijk is, doen we het hier
			# expliciet niet. Daarom halen we de gegevens
			# van de user direct op.
			$userRecord['user'] = $this->getUser($userId);

			# nu gebruiken we het user record om lastapiusage te fixen
			$userRecord['user']['lastapiusage'] = time();
			$this->_db->setUser($userRecord['user']);

			# initialiseer het security systeem
			$userRecord['security'] = new SpotSecurity($this->_db, $this->_settings, $userRecord['user']);

			return $userRecord;
		} else {
			return false;
		} # else
	} # verifyApi

	/*
	 * Reset the lastvisit timestamp
	 */
	function resetLastVisit($user) {
		$user['lastvisit'] = time();
		$this->_db->setUser($user);

		return $user;
	} # resetLastVisit

	/*
	 * Reset the seenstamp timestamp
	 */
	function resetReadStamp($user) {
		$user['lastread'] = $this->_db->getMaxMessageTime();
		$this->_db->setUser($user);

		return $user;
	} # resetReadStamp

	/*
	 * Controleert een session cookie, en als de sessie geldig
	 * is, geeft een user record terug
	 */
	function validSession($sessionCookie) {
		$sessionParts = explode(".", $sessionCookie);
		if (count($sessionParts) != 2) {
			return false;
		} # if

		# controleer of de sessie geldig is
		$sessionValid = $this->_db->getSession($sessionParts[0], $sessionParts[1]);
		if ($sessionValid === false) {
			return false;
		} # if
		
		# het is een geldige sessie, haal userrecord op
		$this->_db->hitSession($sessionParts[0]);
		$userRecord = $this->getUser($sessionValid['userid']);
		
		# als het user record niet gevonden kan worden, is het toch geen geldige
		# sessie
		if ($userRecord === false) {
			return false;
		} # if
		
		#
		# Controleer nu of we de lastvisit timestamp moeten updaten?
		# 
		# Als de lasthit (wordt geupdate bij elke hit) langer dan 15 minuten
		# geleden is, dan updaten we de lastvisit timestamp naar de lasthit time,
		# en resetten we daarmee effectief de lastvisit time.
		#
		if ($sessionValid['lasthit'] < (time() - 900)) {
			$userRecord['lastvisit'] = $sessionValid['lasthit'];
			$this->_db->setUser($userRecord);
		} # if
		
		return array('user' => $userRecord,
					 'session' => $sessionValid);
	} # validSession
	
	/*
	 * Geeft een boolean terug die aangeeft of een username geldig is of niet 
	 */
	function validUsername($user) {
		$invalidNames = array('god', 'mod', 'modje', 'spot', 'spotje', 'spotmod', 
							  'admin', 'drazix', 'moderator', 'superuser', 'supervisor', 
							  'spotnet', 'spotnetmod', 'administrator',  'spotweb',
							  'root', 'anonymous');

		$validUsername = !in_array(strtolower($user), $invalidNames);
		if ($validUsername) {
			$validUsername = strlen($user) >= 3;
		} # if
		
		return $validUsername;
	} # validUsername

	/*
	 * Voegt een gebruiker toe aan de database 
	 */
	function addUser($user) {
		if (!$this->validUsername($user['username'])) {
			throw new Exception("Invalid username");
		} # if

		# converteer het password naar een pass hash
		$user['passhash'] = $this->passToHash($user['newpassword1']);

		# Creëer een API key
		$user['apikey'] = md5($this->generateUniqueId());

		# en voeg het record daadwerkelijk toe
		$tmpUser = $this->_db->addUser($user);
		$this->_db->setUserRsaKeys($tmpUser['userid'], $user['publickey'], $user['privatekey']);
		
		# Geef de user default preferences en settingss
		$anonUser = $this->_db->getUser(SPOTWEB_ANONYMOUS_USERID);
		$tmpUser = array_merge($anonUser, $tmpUser);
		$this->_db->setUser($tmpUser);
		
		# en geef de gebruiker de nodige groepen
		$this->_db->setUserGroupList($tmpUser['userid'], $this->_settings->get('newuser_grouplist'));
	} # addUser()

	/*
	 * Update een gebruikers' group membership
	 */
	function setUserGroupList($user, $groupList) {
		$this->_db->setUserGroupList($user['userid'], $groupList);
	} # setUserGroupList
	 
	/*
	 * Update een gebruikers' password
	 */
	function setUserPassword($user) {
		# converteer het password naar een pass hash
		$user['passhash'] = $this->passToHash($user['newpassword1']);
		
		$this->_db->setUserPassword($user);
	} # setUserPassword

	/*
	 * Update een gebruikers' API key
	 */
	function resetUserApi($user) {
		$user['apikey'] = md5($this->generateUniqueId());
		
		$this->_db->setUser($user);
		return $user;
	} # setUserApi

	/* 
	 * Cleanup van user preferences
	 */
	function cleanseUserPreferences($prefs, $tpl) {
		# we willen nu zeker weten dat er in _editUserPrefsForm geen preferences gegeven zijn
		# welke we helemaal niet ondersteunen.
		foreach(array_diff_key($prefs, $tpl) as $keys => $values) {
			unset($prefs[$keys]);
		} # foreach
		
		return $prefs;
	} # cleanseUserPreferences
	
	/*
	 * Valideer de user preferences
	 */
	function validateUserPreferences($prefs) {
		$errorList = array();
		
		# Definieer een aantal arrays met valid settings
		$validDateFormats = array('human', '%a, %d-%b-%Y (%H:%M)', '%d-%m-%Y (%H:%M)');
		$validTemplates = array('we1rdo');
		
		# Controleer de per page setting
		$prefs['perpage'] = (int) $prefs['perpage'];
		if (($prefs['perpage'] < 2) || ($prefs['perpage'] > 250)) {
			$errorList[] = array('validateuser_invalidpreference', array('perpage'));
		} # if
		
		# Controleer basis settings
		if (in_array($prefs['date_formatting'], $validDateFormats) === false) {
			$errorList[] = array('validateuser_invalidpreference', array('date_formatting')); 
		} # if
		
		if (in_array($prefs['template'], $validTemplates) === false) { 	
			$errorList[] = array('validateuser_invalidpreference', array('template'));
		} # if
		
		# als er een sabnzbd host opgegeven is, moet die geldig zijn
		if ( ($prefs['nzbhandling']['action'] == 'client-sabnzbd') || ($prefs['nzbhandling']['action'] == 'push-sabnzbd') ) {
			$tmpHost = parse_url($prefs['nzbhandling']['sabnzbd']['url']);
			
			if ( ($tmpHost === false) | (!isset($tmpHost['scheme'])) || (($tmpHost['scheme'] != 'http') && ($tmpHost['scheme'] != 'https')) ) {
				$errorList[] = array('validateuser_invalidpreference', array('sabnzbd url'));
			} # if
			
			# SABnzbd URL moet altijd eindigen met een slash
			if(substr($prefs['nzbhandling']['sabnzbd']['url'], -1) !== '/') {
				$prefs['nzbhandling']['sabnzbd']['url'] .= '/';
			} # if
		} # if

		# converteer overige settings naar boolean zodat we gewoon al weten wat er uitkomt
		$prefs['count_newspots'] = (isset($prefs['count_newspots'])) ? true : false;
		$prefs['keep_seenlist'] = (isset($prefs['keep_seenlist'])) ? true : false;
		$prefs['auto_markasread'] = (isset($prefs['auto_markasread'])) ? true : false;
		$prefs['keep_downloadlist'] = (isset($prefs['keep_downloadlist'])) ? true : false;
		$prefs['keep_watchlist'] = (isset($prefs['keep_watchlist'])) ? true : false;
		$prefs['show_filesize'] = (isset($prefs['show_filesize'])) ? true : false;
		$prefs['show_multinzb'] = (isset($prefs['show_multinzb'])) ? true : false;
		$prefs['notifications']['libnotify']['enabled'] = (isset($prefs['notifications']['libnotify']['enabled'])) ? true : false;
		$prefs['notifications']['growl']['enabled'] = (isset($prefs['notifications']['growl']['enabled'])) ? true : false;
		$prefs['notifications']['notifo']['enabled'] = (isset($prefs['notifications']['notifo']['enabled'])) ? true : false;
		$prefs['notifications']['prowl']['enabled'] = (isset($prefs['notifications']['prowl']['enabled'])) ? true : false;
		foreach (array('growl', 'libnotify', 'notifo', 'prowl') as $notifProvider) {
			$prefs['notifications'][$notifProvider]['events']['nzb_handled'] = (isset($prefs['notifications'][$notifProvider]['events']['nzb_handled'])) ? true : false;
			$prefs['notifications'][$notifProvider]['events']['retriever_finished'] = (isset($prefs['notifications'][$notifProvider]['events']['retriever_finished'])) ? true : false;
			$prefs['notifications'][$notifProvider]['events']['user_added'] = (isset($prefs['notifications'][$notifProvider]['events']['user_added'])) ? true : false;
		}
		
		# als men runcommand of save wil, moet er een local_dir opgegeven worden
		if (($prefs['nzbhandling']['action'] == 'save') || ($prefs['nzbhandling']['action'] == 'runcommand')) {
			if (empty($prefs['nzbhandling']['local_dir'])) {
				$errorList[] = array('validateuser_invalidpreference', array('local_dir'));
			} # if
		} # if

		# als men Growl wil gebruiken, moet er een host opgegeven worden
		if ($prefs['notifications']['growl']['enabled']) {
			if (empty($prefs['notifications']['growl']['host'])) {
				$errorList[] = array('validateuser_invalidpreference', array('growl host'));
			} # if
		} # if

		# als men Notifo wil gebruiken, moet er een username & apikey opgegeven worden
		if ($prefs['notifications']['notifo']['enabled']) {
			if (empty($prefs['notifications']['notifo']['username'])) {
				$errorList[] = array('validateuser_invalidpreference', array('notifo username'));
			} # if
			if (empty($prefs['notifications']['notifo']['api'])) {
				$errorList[] = array('validateuser_invalidpreference', array('notifo api'));
			} # if
		} # if

		# als men Prowl wil gebruiken, moet er een apikey opgegeven worden
		if ($prefs['notifications']['prowl']['enabled']) {
			if (empty($prefs['notifications']['prowl']['apikey'])) {
				$errorList[] = array('validateuser_invalidpreference', array('prowl apikey'));
			} # if
		} # if

		return array($errorList, $prefs);
	} # validateUserPreferences

	/*
	 * Valideer het user record, kan gebruikt worden voor het toegevoegd word of
	 * geupdate wordt
	 */
	function validateUserRecord($user, $isEdit) {
		$errorList = array();
		
		# Controleer de username
		if (!$isEdit) {
			if (!$this->validUsername($user['username'])) {
				$errorList[] = array('validateuser_invalidusername', array());
			} # if
		} # if
		
		# controleer de firstname
		if (strlen($user['firstname']) < 3) {
			$errorList[] = array('validateuser_invalidfirstname', array());
		} # if
		
		# controleer de lastname
		if (strlen($user['lastname']) < 3) {
			$errorList[] = array('validateuser_invalidlastname', array());
		} # if

		# controleer het password, als er een opgegeven is
		if (strlen($user['newpassword1'] > 0)) {
			if (strlen($user['newpassword1']) < 5){
				$errorList[] = array('validateuser_passwordtooshort', array());
			} # if 
		} # if

		# password1 en password2 moeten hetzelfde zijn (password en bevestig password)
		if ($user['newpassword1'] != $user['newpassword2']) {
			$errorList[] = array('validateuser_passworddontmatch', array());
		} # if

		# anonymous user editten mag niet
		if ($user['userid'] == SPOTWEB_ANONYMOUS_USERID) {
			$errorList[] = array('edituser_cannoteditanonymous', array());
		} # if
		
		# controleer het mailaddress
		if (!filter_var($user['mail'], FILTER_VALIDATE_EMAIL)) {
			$errorList[] = array('validateuser_invalidmail', array());
		} # if

		# Is er geen andere uset met dezelfde mailaddress?
		if ($this->_db->userEmailExists($user['mail']) !== $user['userid']) {
			$errorList[] = array('validateuser_mailalreadyexist', array());
		} # if
		
		return $errorList;
	} # validateUserRecord
	
	/*
	 * Stel de users' public en private keys in
	 */
	function setUserRsaKeys($user, $privateKey, $publicKey) {
		$this->_db->setUserRsaKeys($user['userid'], $privateKey, $publicKey);
	} # setUserRsaKeys
	
	/*
	 * Valideert een group record
	 */
	function validateSecGroup($group) {
		$errorList = array();

		# Verwijder overbodige spaties e.d.
		$group['name'] = trim($group['name']);
		
		# Controleer of er een usergroup opgegeven is en of de 
		# naam niet te kort is
		if (strlen($group['name']) < 3) {
			$errorList[] = array('validatesecgroup_invalidname', array('name'));
		} # if
		
		# Vraag nu alle security groepen om, om er zeker van te zijn
		# dat deze security groep nog niet voorkomt. Niet het meest efficient
		# maar het aantal verwachtte securitygroepen zal meevallen
		$secGroupList = $this->_db->getGroupList(null);
		foreach($secGroupList as $secGroup) {
			if ($secGroup['name'] == $group['name']) {
				if ($secGroup['id'] != $group['id']) {
					$errorList[] = array('validatesecgroup_duplicatename', array('name'));
				} # if
			} # if
		} # foreach
		
		return array($errorList, $group);
	} # validateSecGroup

	/*
	 * Verwijdert een permissie uit een security group
	 */
	function removePermFromSecGroup($groupId, $perm) {
		$this->_db->removePermFromSecGroup($groupId, $perm);
	} # removePermFromSecGroup
	
	/*
	 * Voegt een permissie aan een security group toe
	 */
	function addPermToSecGroup($groupId, $perm) {
		$errorList = array();
		
		// trim het objectid
		$perm['objectid'] = trim($perm['objectid']);
		
		// controleer dat deze specifieke permissie niet al in de security groep zit
		$groupPerms = $this->_db->getGroupPerms($groupId);
		foreach($groupPerms as $groupPerm) {
			if (($groupPerm['permissionid'] == $perm['permissionid']) && 
				($groupPerm['objectid'] == $perm['objectid'])) {
				
				# Dubbele permissie
				$errorList[] = array('validatesecgroup_duplicatepermission', array('name'));
			} # if
		} # foreach
	
		// voeg de permissie aan de groep
		if (empty($errorList)) {
			$this->_db->addPermToSecGroup($groupId, $perm);
		} # if
		
		return $errorList;
	} # addPermToSecGroup
	
	/*
	 * Update een group record
	 */
	function setSecGroup($group) {
		$this->_db->setSecurityGroup($group);
	} # setSecGroup

	/*
	 * Voegt een group record toe
	 */
	function addSecGroup($group) {
		$this->_db->addSecurityGroup($group);
	} # addSecGroup
	
	/*
	 * Geeft een group record terug
	 */
	function getSecGroup($groupId) {
		$tmpGroup = $this->_db->getSecurityGroup($groupId);
		if (!empty($tmpGroup)) {
			return $tmpGroup[0];
		} else {
			return false;
		} # else
	} # getSecGroup

	/*
	 * Verwijdert een group record
	 */
	function removeSecGroup($group) {
		$this->_db->removeSecurityGroup($group);
	} # removeSecGroup

	/*
	 * Geeft een user record terug
	 */
	function getUser($userid) {
		$tmpUser = $this->_db->getUser($userid);
		
		return $tmpUser;
	} # getUser()
	
	/*
	 * Update een user record
	 */
	function setUser($user) {
		# We gaan er altijd van uit dat een password nooit gezet wordt
		# via deze functie dus dat stuk negeren we
		$this->_db->setUser($user);
	} # setUser()
	
	/*
	 * Verwijdert een user record
	 */
	function removeUser($userid) {
		$this->_db->deleteUser($userid);
	} # removeUser()
	
} # class SpotUserSystem
