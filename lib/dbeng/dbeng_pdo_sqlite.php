<?php
class dbeng_pdo_sqlite extends dbeng_pdo {
	protected $_conn;
	
	function __construct() {
		/* 
		 * sqlite does not support batch inserts
		 */
		$this->_batchInsertChunks = 1;
    } # ctor

	function connect($host, $user, $pass, $db) {
		try {
			if (!$this->_conn instanceof PDO) {
				$this->_conn = new PDO('sqlite:' . $db);
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
	
} # class
