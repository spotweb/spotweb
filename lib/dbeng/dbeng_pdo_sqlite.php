<?php
class dbeng_pdo_sqlite extends dbeng_pdo {
	private $_db_path;
	protected $_conn;
	
	function __construct($path) {
		$this->_db_path = $path;
    } # ctor

	function connect() {
		try {
			if (!$this->_conn instanceof PDO) {
				$this->_conn = new PDO('sqlite:' . $this->_db_path);
				$this->_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			} # if		
		} catch(PDOException $e) {
				throw new DatabaseConnectionException($e->getMessage(), -1);
		} # catch
	} # connect()
	
	function safe($s) {
		return SQLite3::escapeString($s);
		// sqlite module is deprecated in more recnt PHP versions, hence wont work
		// 	return sqlite_escape_string($s);
	} # safe
	
	/*
	 * Constructs a query part to match textfields. Abstracted so we can use
	 * a database specific FTS engine if one is provided by the DBMS
	 */
	function createTextQuery($searchFields) {
		SpotTiming::start(__FUNCTION__);

		/*
		 * Initialize some basic values which are used as return values to
		 * make sure always return a valid set
		 */
		$filterValueSql = array('(idx_fts_spots.rowid = s.rowid)');
		$additionalTables = array('idx_fts_spots');
		$matchList = array();

		/*
		 * sqlite can only use one WHERE clause for all textstring matches,
		 * if you exceed this it throws an unrelated error and refuses the query
		 * so we have to collapse all textqueries into one query 
		 */
		foreach($searchFields as $searchItem) {
			$searchValue = trim($searchItem['value']);
			
			/*
			 * The caller usually provides an expiciet table.fieldname 
			 * for the select, but sqlite doesn't recgnize this in its
			 * MATCH statement so we remove it and hope there is no
			 * ambiguity
			 */
			$tmpField = explode('.', $searchItem['fieldname']);
			$field = $tmpField[1];
			
			$matchList[] = $field . ':' . $this->safe($searchValue);
		} # foreach
		
		# add one WHERE MATCH conditions with all conditions
		$filterValueSql[] = " (idx_fts_spots MATCH '" . implode(' ', $matchList) . "') ";
		
		SpotTiming::stop(__FUNCTION__, array($filterValueSql,$additionalTables));

		return array('filterValueSql' => $filterValueSql,
					 'additionalTables' => $additionalTables,
					 'additionalFields' => array(),
					 'sortFields' => array());
	} # createTextQuery()
} # class
