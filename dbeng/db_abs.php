<?php

abstract class db_abs {
	private $_error	= '';
	
	/*
	 * Connect/opent de database en creeert indien nodig de nodige tabellen.
	 *
	 * Geeft true terug als connectie gelukt is, anders false.
	 */
	abstract function connect();
	
	/*
	 * Voer query uit en vergeet de output (true indien geen error).
	 * SQL statements worden niet ge-escaped of iets dergelijks.
	 */
	abstract function rawExec($sql);
	
	/*
	 * Voer query uit met $params aan parameters. Alle parameters worden eerst
	 * door de safe() functie gehaald om SQL injectie te voorkomen.
	 *
	 * Geeft een enkele rij terug met resulaten (associative array), of 
	 * FALSE in geval van een error
	 */
	abstract function singleQuery($sql, $params = array());

	/*
	 * Voer query uit met $params aan parameters. Alle parameters worden eerst
	 * door de safe() functie gehaald om SQL injectie te voorkomen.
	 *
	 * Geeft een array terug met alle resulaten (associative array), of 
	 * FALSE in geval van een error
	 */
	abstract function arrayQuery($sql, $params = array());
	
	/*
	 * Draait de database specifieke "safe-parameter" functie uit.
	 */
	abstract function safe($s);	

	/*
	 * Set een bepaalde error string zodat, we storen deze hier in plaats 
	 * de database specifieke op te halen omdat we willen dat er eventueel nog
	 * extra informatie bijgezet kan worden.
	 */
	function setError($s) {
		$this->_error = $s;
	} # setError

	/*
	 * Geeft de error string terug. 
	 */
	function getError() {
		return $this->_error;
	} # getError
}