<?php
# a mess
require_once "lib/dbeng/db_pdo.php";
require_once "lib/dbstruct/SpotStruct_mysql.php";

class db_pdo_mysql extends db_pdo {
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
			if ($this->_db_host[0] === '/') {
				$this->_db_conn = "unix_socket=" . $this->_db_host;
			} else {
				$this->_db_conn = "host=" . $this->_db_host . ";port=3306";
			}

			try {
				$this->_conn = new PDO('mysql:' . $this->_db_conn . ';dbname=' . $this->_db_db, $this->_db_user, $this->_db_pass);
			} catch (PDOException $e) {
				print "Error!: " . $e->getMessage() . "<br/>";
				die();
			}

			$this->_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		
			# Create the database structure
			$dbStruct = new SpotStruct_mysql($this);
			$dbStruct->createDatabase();
        } # if
    } # connect()
		
	function safe($s) {
		return mysql_real_escape_string($s);
	} # safe

} # class
