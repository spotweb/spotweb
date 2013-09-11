<?php
class dbeng_pdo_mysql extends dbeng_pdo {
    /**
     * @var PDO
     */
    protected $_conn;

	function __construct() {
    	/* 
		 * arbitrarily chosen because some insert statements might
		 * be very large.
		 */
		$this->_batchInsertChunks = 100;
	}

	function connect($host, $user, $pass, $db) {
		if (!$this->_conn instanceof PDO) {
			if ($host[0] === '/') {
				$db_conn = "unix_socket=" . $host;
			} else {
				$db_conn = "host=" . $host . ";port=3306";
			}

			try {
				$this->_conn = new PDO('mysql:' . $db_conn . ';dbname=' . $db . ';charset=utf8',
                                        $user,
                                        $pass,
                                        array(PDO::MYSQL_ATTR_FOUND_ROWS => true));
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
	

} # class
