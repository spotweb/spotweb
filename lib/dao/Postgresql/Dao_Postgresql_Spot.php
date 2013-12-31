<?php

class Dao_Postgresql_Spot extends Dao_Base_Spot {



    /*
     * Remove older spots from the database
     */
    function deleteSpotsRetention($retention) {
        SpotTiming::start(__CLASS__ . '::' . __FUNCTION__);
        $retention = $retention * 24 * 60 * 60; // omzetten in seconden

        $this->_conn->modify("DELETE FROM spots WHERE spots.stamp < :time",
            array(
                ':time' => array(time() - $retention, PDO::PARAM_INT)
            ));
        $this->_conn->modify("DELETE FROM spotsfull WHERE NOT EXISTS
							(SELECT 1 FROM spots WHERE spots.messageid = spotsfull.messageid)") ;
        $this->_conn->modify("DELETE FROM commentsfull WHERE NOT EXISTS
							(SELECT 1 FROM commentsxover WHERE commentsxover.messageid = commentsfull.messageid)");
        $this->_conn->modify("DELETE FROM commentsxover WHERE NOT EXISTS
							(SELECT 1 FROM spots WHERE spots.messageid = commentsxover.nntpref)") ;
        $this->_conn->modify("DELETE FROM reportsxover WHERE NOT EXISTS
							(SELECT 1 FROM spots WHERE spots.messageid = reportsxover.nntpref)") ;
        $this->_conn->modify("DELETE FROM spotstatelist WHERE NOT EXISTS
							(SELECT 1 FROM spots WHERE spots.messageid = spotstatelist.messageid)") ;
        $this->_conn->modify("DELETE FROM reportsposted WHERE NOT EXISTS
							  (SELECT 1 FROM spots WHERE spots.messageid = reportsposted.inreplyto)") ;
        SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__, array($retention));
    } # deleteSpotsRetention

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
