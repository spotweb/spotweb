<?php

abstract class dbeng_abs {
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
	 * Voert de database specifieke "safe-parameter" functie uit.
	 */
	abstract function safe($s);	

	/*
	 * Geef het aantal affected rows terug
	 */
	abstract function rows();
	
	/* 
	 * Begins an transaction
	 */
	abstract function beginTransaction();
	
	/* 
	 * Commits an transaction
	 */
	abstract function commit();
	
	/* 
	 * Rolls back an transaction
	 */
	abstract function rollback();
	

	/*
	 * Prepared de query string door vsprintf() met safe() erover heen te gooien
	 */
	function prepareSql($s, $p) {
		#
		# Als er geen parameters zijn mee gegeven, dan voeren we vsprintf() ook niet
		# uit, dat zorgt er voor dat we bv. LIKE's kunnen uitvoeren (met %'s) zonder
		# dat vsprintf() die probeert te interpreteren.
		if (empty($p)) {
			return $s;
		} else {
			$p = array_map(array($this, 'safe'), $p);
			return vsprintf($s, $p);
		} # else
	} # prepareSql()

	/*
	 * Voer een query uit en geef het resultaat (resource of handle) terug
	 */
	function exec($s, $p = array()) {
		return $this->rawExec($this->prepareSql($s, $p));
	} # exec()

	/*
	 * INSERT or UPDATE statement, geef niets terug
	 */
	abstract function modify($s, $p = array());

	/*
	 * Construeert een stuk van een query om op text velden te matchen, geabstraheerd
	 * zodat we eventueel gebruik kunnen maken van FTS systemen in een db
	 */
	function createTextQuery($field, $value) {
		return array('filter' => " " . $field . " LIKE '%" . $this->safe($value) . "%'",
					 'sortable' => false);
	} # createTextQuery

}