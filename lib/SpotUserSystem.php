<?php

class SpotUserSystem {
	private $_db;
	private $_settings;
	
	function __construct($db, $settings) {
		$this->_db = $db;
		$this->_settings = $settings;
	} # ctor

	/*
	 * Probeert de user aan te loggen met de gegeven credentials,
	 * geeft user record terug of false als de user niet geauth kan
	 * worden
	 */
	function auth($user, $password) {
		# Salt het password met het unieke salt in settings.php
		$password = sha1(substr($this->_settings->get('pass_salt'), 1, 3) . $password . $this->_settings->get('pass_salt'));

		# authenticeer de user?
		$userId = $this->_db->authUser($user, $password);
		if ($userId !== false) {
			return $this->getUser($userId);
		} else {
			return false;
		} # else
	} # auth()
	
	/*
	 * Geeft een boolean terug die aangeeft of een username geldig is of niet 
	 */
	function validUsername($user) {
		$invalidNames = array('god', 'mod', 'modje', 'spot', 'spotje', 'spotmod', 
							  'admin', 'drazix', 'moderator', 'superuser', 'supervisor', 
							  'spotnet', 'spotnetmod', 'administrator',  'spotweb',
							  'root');
							  
		return !in_array(strtolower($user), $invalidNames);
	} # validUsername
	
	/*
	 * Voegt een gebruiker toe aan de database 
	 */
	function addUser($name, $password, $mail) {
		if (!$this->validUsername($username)) {
			throw new Exception("Invalid username");
		} # if
		
		$this->_db->addUser(array());
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
	} # setUser()
	
	/*
	 * Verwijdert een user record
	 */
	function removeUser($userid) {
		$user = $this->getUser($userid);
		$user['disabled'] = true;
		$this->setUser($user);
	} # removeUser()
	
} # class SpotUserSystem
