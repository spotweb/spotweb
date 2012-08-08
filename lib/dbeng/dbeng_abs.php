<?php

abstract class dbeng_abs {
	protected $_batchInsertChunks = 500;
	private $_error	= '';
	
	/*
	 * Connects to the database
	 */
	abstract function connect();
	
	/*
	 * Executes the query and discards any output. Returns true of no
	 * error was found. No handling of the SQL statement is done
	 */
	abstract function rawExec($sql);
	
	/*
	 * Executes the query with $params as parameters. All parameters are 
	 * parsed through sthe safe() function to prevent SQL injection.
	 *
	 * Returns a single associative array when query succeeds, returns 
	 * an exception when the query fails.
	 */
	abstract function singleQuery($sql, $params = array());

	/*
	 * Executes the query with $params as parameters. All parameters are 
	 * parsed through sthe safe() function to prevent SQL injection.
	 *
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
	 * Returns a database specific representation of a boolean value
	 */
	abstract function bool2dt($b);

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
	 * Prepares the query string by running vsprintf() met safe() thrown around it
	 */
	function prepareSql($s, $p) {
		/*
		 * When no parameters are given, we don't run vsprintf(). This makes sure
		 * we can use arrayQuery() and singleQuery() with for example LIKE statements 
		 */
		if (empty($p)) {
			return $s;
		} else {
			$p = array_map(array($this, 'safe'), $p);
			return vsprintf($s, $p);
		} # else
	} # prepareSql()

	/*
	 * Executes the query and returns the (resource or handle)
	 */
	function exec($s, $p = array()) {
		return $this->rawExec($this->prepareSql($s, $p));
	} # exec()

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
			$tmpList .= "'" . $this->safe($k) . "', ";
		} # foreach
		return substr($tmpList, 0, -2);
	} # arrayKeyToIn

	/*
	 * Transforms an array of values to an list usable by an
	 * IN statement
	 */
	function arrayValToInOffset($ar, $val, $valOffset, $valEnd) {
		$tmpList = '';

		foreach($ar as $k => $v) {
			$tmpList .= "'" . $this->safe(substr($v[$val], $valOffset, $valEnd)) . "', ";
		} # foreach
		return substr($tmpList, 0, -2);
	} # arrayValToInOffset

	/*
	 * Transforms an array of values to an list usable by an
	 * IN statement
	 */
	function arrayValToIn($ar, $val) {
		$tmpList = '';

		foreach($ar as $k => $v) {
			$tmpList .= "'" . $this->safe($v[$val]) . "', ";
		} # foreach
		return substr($tmpList, 0, -2);
	} # arrayValToIn

	/*
	 * Transforms an array of values to an list usable by an
	 * IN statement
	 */
	function batchInsert($ar, $sql, $tpl, $fields) {
		$this->beginTransaction();
		
		/* 
		 * Databases usually have a maximum packet size length,
		 * so just sending down 100kbyte of text usually ends
		 * up in tears.
		 */
		$chunks = array_chunk($ar, $this->_batchInsertChunks);

		foreach($chunks as $items) {
			$insertArray = array();

			foreach($items as $item) {
				/*
				 * Add this items' fields to an array in 
				 * the correct order and nicely escaped
				 * from any injection
				 */
				$itemValues = array();
				foreach($fields as $idx => $field) {
					$itemValues[] = $this->safe($item[$field]);
				} # foreach

				$insertArray[] = vsprintf($tpl, $itemValues);
			} # foreach

			# Actually insert the batch
			if (!empty($insertArray)) {
				$this->modify($sql . implode(',', $insertArray), array());
			} # if

		} # foreach

		$this->commit();
	} # batchInsert

} # dbeng_abs
