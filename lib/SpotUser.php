<?php

class SpotUser {

	/*
	 * Probeert de user aan te loggen met de gegeven credentials 
	 */
	function auth($user, $password) {
		return true;
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
	} # addUser()
	
	/*
	 * Geeft een user record terug
	 */
	function getUser($name) {
	} # getUser()
	
	/*
	 * Verwijdert een user record
	 */
	function removeUser($name) {
	} # removeUser()
	
} # class SpotUser
