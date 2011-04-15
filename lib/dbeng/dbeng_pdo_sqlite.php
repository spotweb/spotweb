<?php
class dbeng_pdo_sqlite extends dbeng_pdo {
	private $_db_path;
	protected $_conn;
	
	function __construct($path) {
		$this->_db_path = $path;
    } # ctor

	function connect() {
		if (!$this->_conn instanceof PDO) {
			$this->_conn = new PDO('sqlite:' . $this->_db_path);
			$this->_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } # if		
	} # connect()
	
	function safe($s) {
		return sqlite_escape_string($s);
	} # safe
	
} # class
