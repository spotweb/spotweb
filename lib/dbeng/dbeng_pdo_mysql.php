<?php
class dbeng_pdo_mysql extends dbeng_pdo {
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
		$this->_batchInsertChunks = 100;
	}

	function connect() {
		if (!$this->_conn instanceof PDO) {
			if ($this->_db_host[0] === '/') {
				$this->_db_conn = "unix_socket=" . $this->_db_host;
			} else {
				$this->_db_conn = "host=" . $this->_db_host . ";port=3306";
			}

			try {
				$this->_conn = new PDO('mysql:' . $this->_db_conn . ';dbname=' . $this->_db_db, $this->_db_user, $this->_db_pass, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
			} catch (PDOException $e) {
				throw new DatabaseConnectionException($e->getMessage(), -1);
			}

			$this->_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} # if
	} # connect()

	/*
	 * Returns a database specific representation of a boolean value
	 */
	function bool2dt($b) {
		if ($b) {
			return '1';
		} # if
		
		return '0';
	} # bool2dt
	
	function safe($s) {
		$search=array("\\","\0","\n","\r","\x1a","'",'"');
		$replace=array("\\\\","\\0","\\n","\\r","\Z","\'",'\"');
		return str_replace($search, $replace, $s);
	} # safe


} # class
