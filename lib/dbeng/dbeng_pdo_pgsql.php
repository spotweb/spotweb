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

	function connect() {
		if (!$this->_conn instanceof PDO) {
			$this->_db_conn = "host=" . $this->_db_host;
			
			try {
				$this->_conn = new PDO('pgsql:' . $this->_db_conn . ';dbname=' . $this->_db_db, $this->_db_user, $this->_db_pass);
			} catch (PDOException $e) {
				print "Error!: " . $e->getMessage() . "<br/>";
				die();
			}

			$this->_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} # if
	} # connect()

	function safe($s) {
		$search=array("\\","\0","\n","\r","\x1a","'",'"');
		$replace=array("\\\\","\\0","\\n","\\r","\Z","\'",'\"');
		return str_replace($search, $replace, $s);
	} # safe

	/*
	 * Construeert een stuk van een query om op text velden te matchen, geabstraheerd
	 * zodat we eventueel gebruik kunnen maken van FTS systemen in een db
	 */
	function createTextQuery($searchFields) {
		SpotTiming::start(__FUNCTION__);

		# Initialiseer een aantal arrays welke we terug moeten geven aan
		# aanroeper
		$filterValueSql = array();
		$additionalFields = array();
		$sortFields = array();

		foreach($searchFields as $searchItem) {
			$searchValue = trim($searchItem['value']);
			$field = $searchItem['fieldname'];

			# We zouden in theorie meerdere van deze textsearches kunnen hebben, dan 
			# sorteren we ze in de volgorde waarop ze binnenkwamen 
			$tmpSortCounter = count($additionalFields);
			
			# prepareer de to_tsvector en de to_tsquery strings
			$ts_vector = "to_tsvector('Dutch', " . $field . ")";
			$ts_query = "to_tsquery('" . $this->safe(strtolower($searchValue)) . "')";
			
			$filterValueSql[] = " " . $ts_vector . " @@ " . $ts_query;
			$additionalFields[] = " ts_rank(" . $ts_vector . ", " . $ts_query . ") AS searchrelevancy" . $tmpSortCounter;
			$sortFields[] = array('field' => 'searchrelevancy' . $tmpSortCounter,
								  'direction' => 'DESC',
								  'autoadded' => true);
		} # foreach

		SpotTiming::stop(__FUNCTION__, array($filterValueSql,$additionalFields,$sortFields));
		
		return array('filterValueSql' => $filterValueSql,
					 'additionalTables' => array(),
					 'additionalFields' => $additionalFields,
					 'sortFields' => $sortFields);
	} # createTextQuery()

} # class
