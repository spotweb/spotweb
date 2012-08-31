<?php

abstract class dbfts_abs {
	protected $_db = null;
				
	/*
	 * constructor
	 */
	function __construct(dbeng_abs $dbCon) {
		$this->_db = $dbCon;		
	} // ctor

	/*
	 * Returns the correct FTS class for the given dbclass
	 */
	static function Factory(dbeng_abs $db) {
		if ($db instanceof dbeng_pdo_pgsql) {
			return new dbfts_pgsql($db);
		} elseif ($db instanceof dbeng_pdo_mysql) {
			return new dbfts_mysql($db);
		} elseif ($db instanceof dbeng_mysql) {
			return new dbfts_mysql($db);
		} elseif ($db instanceof dbeng_pdo_sqlite) {
			return new dbfts_sqlite($db);
		} else {
			throw new NotImplementedException("Unknown database engine for FTS ?");
		} # else
	} # factory

	/*
	 * Constructs a query part to match textfields. Abstracted so we can use
	 * a database specific FTS engine if one is provided by the DBMS
	 */
	function createTextQuery($searchFields, $additionalFields) {
		throw new NotImplementedException("createTextQuery() is running unoptimized while it shouldnt. Please report to the author");
		
		# Initialize some basic variables so our return statements are simple
		$filterValueSql = array();

		foreach($searchFields as $searchItem) {
			$searchValue = trim($searchItem['value']);
			$field = $searchItem['fieldname'];
			
			$filterValueSql[] = " (" . $searchItem['fieldname'] . " LIKE '%"  . $this->safe($searchValue) . "%') ";
		} # foreach

		return array('filterValueSql' => $filterValueSql,
					 'additionalTables' => array(),
					 'additionalFields' => $additionalFields,
					 'sortFields' => array());
	} # createTextQuery


} # dbfts_abs
