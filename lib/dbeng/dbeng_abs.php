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
	abstract function safe($s, $forceType = null);
	
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
	function arrayKeyToIn($ar, $forceType = null) {
		$tmpList = '';

		foreach($ar as $k => $v) {
			$tmpList .= $this->safe($k, $forceType) . ",";
		} # foreach
		return substr($tmpList, 0, -1);
	} # arrayKeyToIn

    /*
     * Transforms an array of values to an list usable by an
     * IN statement
     */
	function arrayValToIn($ar, $val, $forceType = null) {
		$tmpList = '';

		foreach($ar as $v) {
			if (is_array($v)) {
				$v = $v[$val];
			}
			$tmpList .= $this->safe($v, $forceType) . ",";
		} # foreach
		return substr($tmpList, 0, -1);
	} # arrayValToIn


    public function sqlUpdate($tableName, array $parameters, array $idNames) {
        $sql = 'UPDATE ' . $tableName . ' SET ';
        foreach($parameters as $k => $v) {
            // skip updating our ids as those never change anyway
            if (array_search(substr($k, 1), $idNames) === false) {
                $sql .= substr($k, 1) . ' = ' . $k . ', ';
            } // if
        } // foreach

        // remove the trailing comma
        $sql = substr($sql, 0, -2);
        $sql .= ' WHERE ';

        foreach($idNames as $idName) {
            $sql .= $idName . ' = :' . $idName . ' AND ';
        } // foreach
        $sql = substr($sql, 0, -5);

        /*
         * Now try to the update the row
         */
        $this->modify($sql, $parameters);
        return $this->rows();
    } // sqlUpdate()

    public function sqlInsert($tableName, array $parameters) {
        /*
         * Update failed to update any rows, lets insert the record
         */
        $sql = 'INSERT INTO ' . $tableName . '(';
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

        $this->modify($sql, $parameters);
        return $this->lastInsertId($tableName);
    } // sqlInsert()

    /**
     * Helper function to either update a record, or
     * insert a new one, can be overriden by database
     * specific implementations (an UPSERT basically).
     *
     * !! Is not concurrent-safe. !!
     */
    public function upsert($tableName, array $parameters, array $idNames, $try = 0) {
        /*
         * If the same parameters are updated as the where, an update can not
         * be done (those are always the same). We do require the record to be
         * inserted though.
         */
        $rowsUpdated = 0;
        if (count($parameters) == count($idNames)) {
            $sql = 'SELECT 1 FROM ' . $tableName . ' WHERE ';
            foreach($parameters as $k => $v) {
                $sql .= substr($k, 1) . ' = ' . $k . ' AND ';
            } // foreach
            $sql = substr($sql, 0, -5);

            $rowsUpdated = $this->singleQuery($sql, $parameters);
            if ($rowsUpdated === null) {
                $rowsUpdated = 0;
            } // if
        } else {
            $rowsUpdated = $this->sqlUpdate($tableName, $parameters, $idNames);

	    $t='';
	    foreach($parameters as $k=>$v) {
		$t.=','.$k;
	    }
	    //echo 'Rowsupdated: ' . $rowsUpdated . ' for ' . $tableName . ', params=(' . $t . '), idNames=(' . implode(',', $idNames) . ')'.PHP_EOL;
        } // else

        if ($rowsUpdated === 0) {
            try {
                $this->sqlInsert($tableName, $parameters);
            } catch(SqlErrorException $x) {
                /*
                 * Extremely ugly workaround for any concurrency issue...
                 */
                if ($try < 5) {
                    $this->upsert($tableName, $parameters, $idNames, ++$try);
                } else {
                    throw $x;
                } // else
            } // catch
        } // if
    } // upsert

    private function toCamelCase($s) {
        $tmpAr = array_map('ucfirst', explode('_', strtolower($s)));
        return implode('', $tmpAr);
    } // toCamelCase

    /**
     * Query to automatically fill in DTO's based on query results.
     * It prtty much sucks, and has a very strict and inflexible naming
     * convention and stuff, but it works. kinda.
     */
    public function sqlQuery($tableName, $objName, $idColName, $idValue, $additionalJoins = array()) {
        $additionalJoinList = '';
        foreach($additionalJoins as $additionalJoin) {
            $additionalJoinList = ' ' . $additionalJoin['jointype'] . ' JOIN ' .
                $additionalJoin['tablename'] . ' AS ' . $additionalJoin['tablealias'] .
                ' ON (' . $additionalJoin['joincondition'] . ') ';
        } # foreach
        $objArray = array();

        $sql = 'SELECT * FROM ' . $tableName . ' AS t1 ' . $additionalJoinList . ' WHERE ' . $idColName . ' = :' . $idColName;

        $resultList = $this->arrayQuery($sql,
                array(':'. $idColName => array($idValue, PDO::PARAM_INT))
        );

        foreach($resultList as $result) {
            $tmpObj = new $objName();

            foreach(array_keys($result) as $key) {
                $set = 'set' . $this->toCamelCase($key);
                $tmpObj->$set($result[$key]);
            } // foreach

            $objArray[] = $tmpObj;
        } // foreach

        return $objArray;
    } // sqlQuery

} # dbeng_abs
