<?php
class dbeng_pdo_pgsql extends dbeng_pdo {
	protected $_conn;

    private $_rows = 0;

	function __construct() {
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

	function connect($host, $user, $pass, $db) {
		if (!$this->_conn instanceof PDO) {
			$db_conn = "host=" . $this->_db_host;
			
			try {
				$this->_conn = new PDO('pgsql:' . $db_conn . ';dbname=' . $db, $user, $pass);
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

} # class
