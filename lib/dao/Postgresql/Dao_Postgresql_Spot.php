<?php

class Dao_Postgresql_Spot extends Dao_Base_Spot {

	/*
	 * Returns the amount of spots per hour
	 */
	function getSpotCountPerHour($limit) {
		$filter = ($limit) ? "WHERE stamp > " . strtotime("-1 " . $limit) : '';
		return $this->_conn->arrayQuery("SELECT EXTRACT(HOUR FROM to_timestamp(stamp)) AS data, count(*) AS amount FROM spots " . $filter . " GROUP BY data;");
	} # getSpotCountPerHour

	/*
	 * Returns the amount of spots per weekday
	 */
	function getSpotCountPerWeekday($limit) {
		$filter = ($limit) ? "WHERE stamp > " . strtotime("-1 " . $limit) : '';
		return $this->_conn->arrayQuery("SELECT EXTRACT(DOW FROM to_timestamp(stamp)) AS data, count(*) AS amount FROM spots " . $filter . " GROUP BY data;"); 
	} # getSpotCountPerWeekday

	/*
	 * Returns the amount of spots per month
	 */
	function getSpotCountPerMonth($limit) {
		$filter = ($limit) ? "WHERE stamp > " . strtotime("-1 " . $limit) : '';
		return $this->_conn->arrayQuery("SELECT EXTRACT(MONTH FROM to_timestamp(stamp)) AS data, count(*) AS amount FROM spots " . $filter . " GROUP BY data;");
	} # getSpotCountPerMonth
	
} # Dao_Postgresql_Spot
