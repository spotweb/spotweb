<?php
class dbeng_pdo_pgsql extends dbeng_pdo {
	private $_db_host;
	private $_db_user;
	private $_db_pass;
	private $_db_db;
	
	protected $_conn;

    private $_rows = 0;

	function __construct($host, $user, $pass, $db)
    {
		$this->_db_host = $host;
		$this->_db_user = $user;
		$this->_db_pass = $pass;
		$this->_db_db = $db;
	}

	/*
	 * Returns a database specific representation of a boolean value
	 */
	function bool2dt($b) {
		if ($b) {
			return 'true';
		} # if
		
		return 'false';
	} # bool2dt

	function connect() {
		if (!$this->_conn instanceof PDO) {
			$this->_db_conn = "host=" . $this->_db_host;
			
			try {
				$this->_conn = new PDO('pgsql:' . $this->_db_conn . ';dbname=' . $this->_db_db, $this->_db_user, $this->_db_pass);
			} catch (PDOException $e) {
				throw new DatabaseConnectionException($e->getMessage(), -1);
			} # catch

			$this->_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} # if
	} # connect()

	function safe($s) {
		$search=array("\\","\0","\n","\r","\x1a","'",'"');
		$replace=array("\\\\","\\0","\\n","\\r","\Z","\'",'\"');
		return str_replace($search, $replace, $s);
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
		$filterValueSql = array();
		$additionalFields = array();
		$sortFields = array();

		foreach($searchFields as $searchItem) {
			$searchValue = trim($searchItem['value']);
			$field = $searchItem['fieldname'];

			/*
			 * if we get multiple textsearches, we sort them per order
			 * in the system
			 */
			$tmpSortCounter = count($additionalFields);
			
			# Prepare the to_tsvector and to_tsquery strings
			$ts_vector = "to_tsvector('Dutch', " . $field . ")";
			$ts_query = "to_tsquery('" . $this->safe(strtolower($searchValue)) . "')";
			
			$filterValueSql[] = " " . $ts_vector . " @@ " . $ts_query;
			$additionalFields[] = " ts_rank(" . $ts_vector . ", " . $ts_query . ") AS searchrelevancy" . $tmpSortCounter;
			$sortFields[] = array('field' => 'searchrelevancy' . $tmpSortCounter,
								  'direction' => 'DESC',
								  'autoadded' => true,
								  'friendlyname' => null);
		} # foreach

		SpotTiming::stop(__FUNCTION__, array($filterValueSql,$additionalFields,$sortFields));
		
		return array('filterValueSql' => $filterValueSql,
					 'additionalTables' => array(),
					 'additionalFields' => $additionalFields,
					 'sortFields' => $sortFields);
	} # createTextQuery()

} # class
