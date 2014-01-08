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
                /*
                 * We specify the charset in the connection string to avoid a possible
                 * sqli by using charset encoding. Hoewever, early versions of PHP
                 * (<5.3.6) do not implement this properly, so we also set it using
                 * the init string.
                 *
                 * We need to set the collate string, to avoid MySQL from removing
                 * diacritics
                 */
				$this->_conn = new PDO('mysql:' . $db_conn . ';dbname=' . $db . ';charset=utf8',
                                        $user,
                                        $pass,
                                        array(PDO::MYSQL_ATTR_FOUND_ROWS => true,
                                              PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                                              PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8 COLLATE utf8_unicode_ci'
                                        ));
			} catch (PDOException $e) {
				throw new DatabaseConnectionException($e->getMessage(), -1);
			}
		} # if
	} # connect()

    /**
     * Helper function to either update a record, or
     * insert a new one, can be overriden by database
     * specific implementations (an UPSERT basically).
     *
     */
    public function upsert($tablename, array $parameters, array $idNames, $try = 0) {
        /*
         * Update failed to update any rows, lets insert the record
         */
        $sql = 'INSERT INTO ' . $tablename . '(';
        foreach($parameters as $k => $v) {
            $sql .= substr($k, 1) . ', ';
        } // foreach

        // remove the trailing comma
        $sql = substr($sql, 0, -2);

        $sql .= ') VALUES (';
        foreach($parameters as $k => $v) {
            $sql .= $k . ', ';
        } // foreach
        $sql = substr($sql, 0, -2) . ')';

        $sql .= ' ON DUPLICATE KEY UPDATE ';
        foreach($parameters as $k => $v) {
            $sql .= substr($k, 1) . ' = ' . $k . ', ';
        } // foreach

        // remove the trailing comma
        $sql = substr($sql, 0, -2);

        $this->modify($sql, $parameters);
    } // upsert


} # class
