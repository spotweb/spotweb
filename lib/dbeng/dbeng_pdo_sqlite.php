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
/*
	function createTextQuery($field, $searchValue) {
		SpotTiming::start(__FUNCTION__);

		//
		// FIXME 
		// Sorteeren op rank, zie http://www.postgresql.org/docs/8.3/static/textsearch-controls.html
		//
		$queryPart = " to_tsvector('Dutch', " . $field . ") @@ '" . $this->safe(strtolower($searchValue)) . "' ";

		SpotTiming::stop(__FUNCTION__, array($field,$searchValue));
		
		return array('filter' => $queryPart,
					 'sortable' => false); 
	} # createTextQuery()
*/
} # class
