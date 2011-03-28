<?php
# a mess
require_once "lib/dbeng/db_pdo.php";
require_once "lib/dbstruct/SpotStruct_sqlite.php";

class db_pdo_sqlite extends db_pdo {
	private $_db_path;
	protected $_conn;
	
	function __construct($path) {
		$this->_db_path = $path;
    } # ctor

	function connect() {
		if (!$this->_conn instanceof PDO) {
			$this->_conn = new PDO('sqlite:' . $this->_db_path);
			$this->_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		
			# Create the database structure
			$dbStruct = new SpotStruct_sqlite($this);
			$dbStruct->createDatabase();
        } # if		
	} # connect()
	
	function safe($s) {
		return sqlite_escape_string($s);
	} # safe

} # class
