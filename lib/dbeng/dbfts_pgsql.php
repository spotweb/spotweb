<?php

class dbfts_pgsql extends dbfts_abs {
	/*
	 * Constructs a query part to match textfields. Abstracted so we can use
	 * a database specific FTS engine if one is provided by the DBMS
	 */
	function createTextQuery($searchFields, $additionalFields) {
		SpotTiming::start(__FUNCTION__);

		/*
		 * Initialize some basic values which are used as return values to
		 * make sure always return a valid set
		 */
		$filterValueSql = array();
		$sortFields = array();

		foreach($searchFields as $searchItem) {
			$searchValue = trim($searchItem['value']);
			$field = $searchItem['fieldname'];

			/*
			 * if we get multiple textsearches, we sort them per order
			 * in the system
			 */
			$tmpSortCounter = count($additionalFields);

			# Prepare the to_tsvector and to_tsquery strings
			$ts_vector = "to_tsvector('Dutch', " . $field . ")";
			$ts_query = "plainto_tsquery('Dutch', '" . $this->_db->safe(strtolower($searchValue)) . "')";
			
			$filterValueSql[] = " " . $ts_vector . " @@ " . $ts_query;
			$additionalFields[] = " ts_rank(" . $ts_vector . ", " . $ts_query . ") AS searchrelevancy" . $tmpSortCounter;
			$sortFields[] = array('field' => 'searchrelevancy' . $tmpSortCounter,
								  'direction' => 'DESC',
								  'autoadded' => true,
								  'friendlyname' => null);
		} # foreach

		SpotTiming::stop(__FUNCTION__, array($filterValueSql,$additionalFields,$sortFields));
		
		return array('filterValueSql' => $filterValueSql,
					 'additionalTables' => array(),
					 'additionalFields' => $additionalFields,
					 'sortFields' => $sortFields);
	} # createTextQuery()

} # dbfts_abs
