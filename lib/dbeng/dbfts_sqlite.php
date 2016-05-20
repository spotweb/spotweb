<?php

class dbfts_sqlite extends dbfts_abs {
	/*
	 * Constructs a query part to match textfields. Abstracted so we can use
	 * a database specific FTS engine if one is provided by the DBMS
	 */
	function createTextQuery($searchFields, $additionalFields) {
		SpotTiming::start(__CLASS__ . '::' . __FUNCTION__);
        //var_dump($searchFields);
        /*
		 * Initialize some basic values which are used as return values to
		 * make sure always return a valid set
		 */
        $fieldIndex = array('title' => 1, 'poster' => 2, 'tag' => 3);
        
        // Determine additional table to use
        $tmpField = explode('.', $searchFields[0]['fieldname']);
        $columnField = strtolower($tmpField[1]);

        $idxnum = $fieldIndex[$columnField];
        if (empty($idxnum)) {
            throw new NotImplementedException("Undefined SQLite FTS column: " . $columnField);
        }
                
        $additionalTables = array(' idx_fts_spots AS idx_fts_spots_' . $idxnum);
        $filterValueSql = array();      
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
			$matchList[] = $searchValue;
		} # foreach
		
		# add one WHERE MATCH conditions with all conditions

        $filterValueSql[] =  '(idx_fts_spots_'. $idxnum .'.docid = s.rowid) AND ' . " (idx_fts_spots_" . $idxnum . ".". $columnField . " MATCH '" . implode(' ', $matchList) . "') ";
		
		SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__, array($filterValueSql,$additionalTables));

		return array('filterValueSql' => $filterValueSql,
					 'additionalTables' => $additionalTables,
					 'additionalFields' => $additionalFields,
					 'sortFields' => array());
	} # createTextQuery()

} # dbfts_sqlite
