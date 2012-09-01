<?php

class Dao_Sqlite_Spot extends Dao_Base_Spot {

	/*
	 * Returns the spots in the database which match the 
	 * restrictions of $parsedSearch
	 */
	function getSpots($ourUserId, $pageNr, $limit, $parsedSearch) {
		$spotResults = parent::getSpots($ourUserId, $pageNr, $limit, $parsedSearch);

		/*
		 * We force the category because sqlite can return an empty string
		 * instead of an zero 
		 */
		$spotCnt = count($spotResults['list']);
		for ($i = 0; $i < $spotCnt; $i++) {
			$spotResults['list'][$i]['category'] = (int) $spotResults['list'][$i]['category'];
		} # foreach

		return $spotResults;
	} # getSpot


	/*
	 * Returns the amount of spots per hour
	 */
	function getSpotCountPerHour($limit) {
		$filter = ($limit) ? "WHERE stamp > " . strtotime("-1 " . $limit) : '';
		return $this->_conn->arrayQuery("SELECT strftime('%H', time(stamp, 'unixepoch')) AS data, count(*) AS amount FROM spots " . $filter . " GROUP BY data;");
	} # getSpotCountPerHour

	/*
	 * Returns the amount of spots per weekday
	 */
	function getSpotCountPerWeekday($limit) {
		$filter = ($limit) ? "WHERE stamp > " . strtotime("-1 " . $limit) : '';
		return $this->_conn->arrayQuery("SELECT strftime('%w', time(stamp, 'unixepoch')) AS data, count(*) AS amount FROM spots " . $filter . " GROUP BY data;");
	} # getSpotCountPerWeekday

	/*
	 * Returns the amount of spots per month
	 */
	function getSpotCountPerMonth($limit) {
		$filter = ($limit) ? "WHERE stamp > " . strtotime("-1 " . $limit) : '';
		return $this->_conn->arrayQuery("SELECT strftime('%m', time(stamp, 'unixepoch')) AS data, count(*) AS amount FROM spots " . $filter . " GROUP BY data;");
	} # getSpotCountPerMonth
	
} # Dao_Sqlite_Spot
