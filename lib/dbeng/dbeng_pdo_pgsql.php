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

		/* 
		 * arbitrarily chosen because some insert statements might
		 * be very large.
		 */
		$this->_batchInsertChunks = 250;
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

			// Disable standard conforming strings for now as it breaks our code.
			$this->rawExec('SET standard_conforming_strings=0');
		} # if
	} # connect()

	function safe($s) {
		$search=array("\\","\0","\n","\r","\x1a","'",'"');
		$replace=array("\\\\","\\0","\\n","\\r","\Z","\'",'"');
		return str_replace($search, $replace, $s);
	} # safe

} # class
