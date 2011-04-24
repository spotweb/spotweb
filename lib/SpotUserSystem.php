<?php
define('SPOTWEB_ANONYMOUS_USERID', 1);

class SpotUserSystem {
	private $_db;
	private $_settings;
	
	function __construct($db, $settings) {
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
	function createNewSession($userid) {
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
						 'hitcount' => 0,
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
				  time()+60*60*24*30); /*,
				  '',		# Path
				  ''.		# Domain
				  1);		# HTTP only, no javascript */
	} # updateCookie
	
	/*
	 * Verwijdert een sessie
	 */
	function removeSession($sessionId) {
		$this->_db->deleteSession($sessionId);
	} # removeSession
	
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
			# Als de user ingelogged is, creeer een sessie,
			# volgorde is hier belangrijk omdat in de newsession
			# de lastvisit tijd op lastlogon gezet wordt moeten
			# we eerst de sessie creeeren.
			$userSession = $this->createNewSession($userId);
			$this->updateCookie($userSession);
		
			# nu gebruiken we het user record om de lastlogin te fixen
			$userSession['user']['lastlogin'] = time();
			$this->_db->setUser($userSession['user']);
			
			return $userSession;
		} else {
			return false;
		} # else
	} # login()

	/*
	 * Reset the lastvisit timestamp
	 */
	function resetLastVisit($user) {
		$user['lastvisit'] = time();
		$this->_db->setUser($user);

		return $user;
	} # resetLastVisit

	/*
	 * Clear the seen list
	 */
	function clearSeenList($user) {
		$this->_db->clearSeenList($user);
	} # clearSeenList

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
		$user['passhash'] = $this->passToHash($user['password']);
		
		# en voeg het record daadwerkelijk toe
		$tmpUser = $this->_db->addUser($user);
		$this->_db->setUserRsaKeys($tmpUser['userid'], $user['publickey'], $user['privatekey']);
	} # addUser()
	
	/*
	 * Valideer het user record, kan gebruikt worden voor het toegevoegd word of
	 * geupdate wordt
	 */
	function validateUserRecord($user) {
		$errorList = array();
		
		# Controleer de username
		if (!$this->validUsername($user['username'])) {
			$errorList[] = 'invalidusername';
		} # if
		
		# Is er geen andere uset met dezelfde username?
		if ($this->_db->usernameExists($user['username'])) {
			$errorList[] = 'invalidusername';
		} # if

		# controleer de firstname
		if (strlen($user['firstname']) < 3) {
			$errorList[] = 'invalidfirstname';
		} # if
		
		# controleer de lastname
		if (strlen($user['lastname']) < 3) {
			$errorList[] = 'invalidlastname';
		} # if
		
		# controleer het mailaddress
		if (!filter_var($user['mail'], FILTER_VALIDATE_EMAIL)) {
			$errorList[] = 'invalidmail';
		} # if

		# Is er geen andere uset met dezelfde mailaddress?
		if ($this->_db->userEmailExists($user['mail'])) {
			$errorList[] = 'mailalreadyexist';
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
	 * Geeft een user record terug
	 */
	function getUser($userid) {
		$tmpUser = $this->_db->getUser($userid);
		$tmpUser['prefs'] = $this->_settings->get('prefs');
		
		return $tmpUser;
	} # getUser()
	
	/*
	 * Update een user record
	 */
	function setUser($user) {
		if (!$this->validUsername($user['username'])) {
			throw new Exception("Invalid username");
		} # if
		
		# We gaan er altijd van uit dat een password nooit gezet wordt
		# via deze functie dus dat stuk negeren we
		$this->_db->setUser($user);
	} # setUser()
	
	/*
	 * Verwijdert een user record
	 */
	function removeUser($userid) {
		$this->deleteUser($userid);
	} # removeUser()
	
} # class SpotUserSystem
