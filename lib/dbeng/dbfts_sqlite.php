<?php

class dbfts_sqlite extends dbfts_abs {
	/*
	 * Constructs a query part to match textfields. Abstracted so we can use
	 * a database specific FTS engine if one is provided by the DBMS
	 */
	function createTextQuery($searchFields) {
		SpotTiming::start(__FUNCTION__);

		/*
		 * Initialize some basic values which are used as return values to
		 * make sure always return a valid set
		 */
		$filterValueSql = array('(idx_fts_spots.rowid = s.rowid)');
		$additionalTables = array('idx_fts_spots');
		$matchList = array();

		/*
		 * sqlite can only use one WHERE clause for all textstring matches,
		 * if you exceed this it throws an unrelated error and refuses the query
		 * so we have to collapse all textqueries into one query 
		 */
		foreach($searchFields as $searchItem) {
			$searchValue = trim($searchItem['value']);
			
			/*
			 * The caller usually provides an expiciet table.fieldname 
			 * for the select, but sqlite doesn't recgnize this in its
			 * MATCH statement so we remove it and hope there is no
			 * ambiguity
			 */
			$tmpField = explode('.', $searchItem['fieldname']);
			$field = $tmpField[1];
			
			$matchList[] = $field . ':' . $this->_db->safe($searchValue);
		} # foreach
		
		# add one WHERE MATCH conditions with all conditions
		$filterValueSql[] = " (idx_fts_spots MATCH '" . implode(' ', $matchList) . "') ";
		
		SpotTiming::stop(__FUNCTION__, array($filterValueSql,$additionalTables));

		return array('filterValueSql' => $filterValueSql,
					 'additionalTables' => $additionalTables,
					 'additionalFields' => array(),
					 'sortFields' => array());
	} # createTextQuery()

} # dbfts_sqlite
