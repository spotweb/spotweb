<?php

abstract class dbeng_abs {
	protected $_batchInsertChunks = 500;

	/*
	 * Factory class which instantiates the specified DAO factory object
	 */
	public static function getDbFactory($engine) {
		/* 
		 * Erase username/password so it won't show up in any stacktrace,
		 * only erase them if they exist (eg: sqlite has no username and
		 * password)
		 */
		switch ($engine) {
			case 'mysql'		:
			case 'pdo_mysql'	: return new dbeng_pdo_mysql(); break; 
			case 'pdo_pgsql' 	: return new dbeng_pdo_pgsql(); break;
			case 'pdo_sqlite'	: return new dbeng_pdo_sqlite(); break;

			default				: throw new Exception("Unknown database engine (" . $engine . ") factory specified");
		} // switch
	} # getDbFactory()
	
	/*
	 * Connects to the database
	 */
	abstract function connect($host, $user, $pass, $db);
	
	/*
	 * Executes the query and discards any output. Returns true of no
	 * error was found. No handling of the SQL statement is done
	 */
	abstract function rawExec($sql);
	
	/*
	 * Executes the query with $params as parameters. All parameters are 
	 * parsed through the safe() function to prevent SQL injection.
	 *
	 * Returns a single associative array when query succeeds, returns 
	 * an exception when the query fails.
	 */
	abstract function singleQuery($sql, $params = array());

	/*
	 * Executes the query with $params as parameters. All parameters are 
	 * parsed through sthe safe() function to prevent SQL injection.
	 *
	 * Returns an array of associative arrays when query succeeds, returns 
	 * an exception when the query fails.
	 */
	abstract function arrayQuery($sql, $params = array());

	/*
	 * Database specific 'escape' or 'safe' function to escape strings
	 */
	abstract function safe($s);	
	
	/*
	 * Returns the amount of effected rows
	 */
	abstract function rows();
	
	/* 
	 * Begins an transaction
	 */
	abstract function beginTransaction();
	
	/* 
	 * Commits an transaction
	 */
	abstract function commit();
	
	/* 
	 * Rolls back an transaction
	 */
	abstract function rollback();
	
	/* 
	 * Returns the last insertid
	 */
	abstract function lastInsertId($tableName);

    /*
     * Transforms an array of values to an list usable by an
     * IN statement
     */
    abstract function batchInsert($ar, $sql, $typs, $fields);

	/*
	 * Executes the query and returns the (resource or handle)
	 */
	abstract function exec($s, $p = array());

	/*
	 * INSERT or UPDATE statement, doesn't return anything. Exception 
	 * thrown if a error occurs
	 */
	abstract function modify($s, $p = array());

	/*
	 * Transforms an array of keys to an list usable by an
	 * IN statement
	 */
	function arrayKeyToIn($ar) {
		$tmpList = '';

		foreach($ar as $k => $v) {
			$tmpList .= $this->safe((string) $k) . ",";
		} # foreach
		return substr($tmpList, 0, -1);
	} # arrayKeyToIn

    /*
     * Transforms an array of keys to an list usable by an
     * IN statement
     */
	function arrayKeyToInForComments($ar) {
		$tmpList = '';
        foreach($ar as $k => $v) {
            // Exclude messageid's from spots which are disposed by the owner, only process real disposes
            if ($v['spotterid'] == '') {
                $tmpList .= $this->safe($k) . ",";
            }
		} # foreach
		return substr($tmpList, 0, -1);
	} # arrayKeyToIn


	/*
	 * Transforms an array of values to an list usable by an
	 * IN statement
	 */
	function arrayValToIn($ar, $val) {
		$tmpList = '';

		foreach($ar as $v) {
			$tmpList .= $this->safe((string) $v[$val]) . ",";
		} # foreach
		return substr($tmpList, 0, -1);
	} # arrayValToIn

} # dbeng_abs
