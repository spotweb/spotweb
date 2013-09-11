<?php

class Dao_Postgresql_Spot extends Dao_Base_Spot {

	/*
	 * Returns the amount of spots per hour
	 */
	function getSpotCountPerHour($limit) {
        if (empty($limit)) {
            return $this->_conn->arrayQuery("SELECT EXTRACT(HOUR FROM to_timestamp(stamp)) AS data,
                                                    COUNT(*) AS amount
                                               FROM spots
                                               GROUP BY data");
        } else {
            return $this->_conn->arrayQuery("SELECT EXTRACT(HOUR FROM to_timestamp(stamp)) AS data,
                                                    COUNT(*) AS amount
                                               FROM spots
                                               WHERE stamp > :stamp
                                               GROUP BY data",
                array(
                    ':stamp' => array(strtotime("-1" . $limit), PDO::PARAM_INT)
                ));
        } # else
	} # getSpotCountPerHour

	/*
	 * Returns the amount of spots per weekday
	 */
	function getSpotCountPerWeekday($limit) {
        if (empty($limit)) {
            return $this->_conn->arrayQuery("SELECT EXTRACT(DOW FROM to_timestamp(stamp)) AS data,
                                                    COUNT(*) AS amount
                                               FROM spots
                                               GROUP BY data");
        } else {
            return $this->_conn->arrayQuery("SELECT EXTRACT(DOW FROM to_timestamp(stamp)) AS data,
                                                    COUNT(*) AS amount
                                               FROM spots
                                               WHERE stamp > :stamp
                                               GROUP BY data",
                array(
                    ':stamp' => array(strtotime("-1" . $limit), PDO::PARAM_INT)
                ));
        } # else
 	} # getSpotCountPerWeekday

	/*
	 * Returns the amount of spots per month
	 */
	function getSpotCountPerMonth($limit) {
        if (empty($limit)) {
            return $this->_conn->arrayQuery("SELECT EXTRACT(MONTH FROM to_timestamp(stamp)) AS data,
                                                    COUNT(*) AS amount
                                               FROM spots
                                               GROUP BY data");
        } else {
            return $this->_conn->arrayQuery("SELECT EXTRACT(MONTH FROM to_timestamp(stamp)) AS data,
                                                    COUNT(*) AS amount
                                               FROM spots
                                               WHERE stamp > :stamp
                                               GROUP BY data",
                array(
                    ':stamp' => array(strtotime("-1" . $limit), PDO::PARAM_INT)
                ));
        } # else
	} # getSpotCountPerMonth
	
} # Dao_Postgresql_Spot
