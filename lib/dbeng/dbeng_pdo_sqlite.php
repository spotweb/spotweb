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
	function createTextQuery($field, $searchValue) {
		SpotTiming::start(__FUNCTION__);

		//
		// FIXME 
		// 	Sorteren op rank
		//
		
		# omdat we de fieldname in tabel.fieldname krijgen, maar sqlite dat niet
		# snapt, halen we de tabelnaam weg
		$tmpField = implode('.', $field);
		$field = $tmpField[1];
		
		# en voeg de query daadwerkelijk uit
		$queryPart = " idx_fts_spots MATCH '" . $tmpField . ":" . $this->safe(strtolower($searchValue)) . "' AND (idx_fts_spots.rowid = s.rowid) ";

		SpotTiming::stop(__FUNCTION__, array($field,$searchValue));
		
		return array('filter' => $queryPart,
					 'additionalTables' => array('idx_fts_spots'),
					 'sortable' => false);
	} # createTextQuery()
} # class
