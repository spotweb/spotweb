<?php
class dbeng_pdo_sqlite extends dbeng_pdo {
	private $_db_path;
	protected $_conn;
	
	function __construct($path) {
		$this->_db_path = $path;
    } # ctor

	function connect() {
		if (!$this->_conn instanceof PDO) {
			$this->_conn = new PDO('sqlite:' . $this->_db_path);
			$this->_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } # if		
	} # connect()
	
	function safe($s) {
		return sqlite_escape_string($s);
	} # safe
	
	/*
	 * Construeert een stuk van een query om op text velden te matchen, geabstraheerd
	 * zodat we eventueel gebruik kunnen maken van FTS systemen in een db
	 */
	function createTextQuery($searchFields) {
		SpotTiming::start(__FUNCTION__);

		# Initialiseer een aantal arrays welke we terug moeten geven aan
		# aanroeper
		$filterValueSql = array('(idx_fts_spots.rowid = s.rowid)');
		$additionalTables = array('idx_fts_spots');
		$matchList = array();
		
		# sqlite kan maar 1 where clausule gebruiken voor alle textstrng
		# matches (anders krijg je een vage error), dus we plakken hier 
		# gewoon alle textmatches samen
		foreach($searchFields as $searchItem) {
			$searchValue = trim($searchItem['value']);
			# omdat we de fieldname in tabel.fieldname krijgen, maar sqlite dat niet
			# snapt, halen we de tabelnaam weg
			$tmpField = explode('.', $searchItem['fieldname']);
			$field = $tmpField[1];
			
			$matchList[] = $field . ':' . $this->safe($searchValue);
		} # foreach
		
		# en voeg nu 1 WHERE filter toe met alle condities hierin
		$filterValueSql[] = " (idx_fts_spots MATCH '" . implode(' ', $matchList) . "') ";
		
		SpotTiming::stop(__FUNCTION__, array($filterValueSql,$additionalTables));

		return array('filterValueSql' => $filterValueSql,
					 'additionalTables' => $additionalTables,
					 'additionalFields' => array(),
					 'sortFields' => array());
	} # createTextQuery()
} # class
