<?php

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
	function generateSessionId() {
		$sessionId = '';
		
		for($i = 0; $i < 10; $i++) {
			$sessionId .= base_convert(mt_rand(), 10, 36);
		} # generateSessionId
		
		return $sessionId;
	} # generateSessionId
	
	/*
	 * Creeer een nieuwe session
	 */
	function createNewSession($userid) {
		# Creer een session record
		$session = array('sessionid' => $this->generateSessionId(),
						 'userid' => $userid,
						 'hitcount' => 0,
						 'lasthit' => 0);
		$this->_db->addSession($session);

		# Als de user ingelogged is, creeer een sessie
		$tmpUser = $this->getUser($userid);
		
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
			# userid 0 is altijd onze anonymous user
			$userSession = $this->createNewSession(0);
		} # if
		
		# update de sessie cookie zodat die niet spontaan gaat
		# expiren
		$this->updateCookie($userSession);
		
		return $userSession;
	} # useOrStartSession
	
	/*
	 * Probeert de user aan te loggen met de gegeven credentials,
	 * geeft user record terug of false als de user niet geauth kan
	 * worden
	 */
	function login($user, $password) {
		# Salt het password met het unieke salt in settings.php
		$password = sha1(strrev(substr($this->_settings->get('pass_salt'), 1, 3)) . $password . $this->_settings->get('pass_salt'));

		# authenticeer de user?
		$userId = $this->_db->authUser($user, $password);
		if ($userId !== false) {
			# Als de user ingelogged is, creeer een sessie
			return $this->createNewSession($userId);
		} else {
			return false;
		} # else
	} # login()

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
		return array('user' => $this->getUser($sessionValid['userid']),
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
							  
		return !in_array(strtolower($user), $invalidNames);
	} # validUsername
	
	/*
	 * Voegt een gebruiker toe aan de database 
	 */
	function addUser($user) {
		if (!$this->validUsername($user['username'])) {
			throw new Exception("Invalid username");
		} # if
		
		$tmpUser = $this->_db->addUser($user);
		$this->_db->setUserRsaKeys($user['userid'], $user['publickey'], $user['privatekey']);
	} # addUser()
	
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
		if (!$this->validUsername($username)) {
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
