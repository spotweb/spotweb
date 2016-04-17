<?php

class Dao_Mysql_Spot extends Dao_Base_Spot {

	/*
	 * Remove a spot from the database
	 */
	function removeSpots($spotMsgIdList) {
		if (count($spotMsgIdList) == 0) {
			return;
		} # if

		$this->_conn->modify("DELETE FROM spots, spotsfull, reportsxover, spotstatelist, reportsposted USING spots
							LEFT JOIN spotsfull ON spots.messageid=spotsfull.messageid
                            LEFT JOIN reportsxover ON spots.messageid=reportsxover.nntpref
							LEFT JOIN spotstatelist ON spots.messageid=spotstatelist.messageid
							LEFT JOIN reportsposted ON spots.messageid=reportsposted.inreplyto
							WHERE spots.messageid  IN (" . $this->_conn->arrayKeyToIn($spotMsgIdList, PDO::PARAM_STR) . ")");
	} # removeSpots

	/*
	 * Remove older spots from the database
	 */
	function deleteSpotsRetention($retention) {
		$retention = $retention * 24 * 60 * 60; // omzetten in seconden

		$this->_conn->modify("DELETE FROM spots, spotsfull, commentsxover, reportsxover, spotstatelist, reportsposted USING spots
                                LEFT JOIN spotsfull ON spots.messageid=spotsfull.messageid
                                LEFT JOIN commentsxover ON spots.messageid=commentsxover.nntpref
                                LEFT JOIN reportsxover ON spots.messageid=reportsxover.nntpref
                                LEFT JOIN spotstatelist ON spots.messageid=spotstatelist.messageid
                                LEFT JOIN reportsposted ON spots.messageid=reportsposted.inreplyto
                                WHERE spots.stamp < :stamp",
            array(
                ':stamp' => array(time() - $retention, PDO::PARAM_INT)
            ));
	} # deleteSpotsRetention

	/*
	 * Returns the amount of spots per hour
	 */
	function getSpotCountPerHour($limit) {
        if (empty($limit)) {
            return $this->_conn->arrayQuery("SELECT EXTRACT(HOUR FROM FROM_UNIXTIME(stamp)) AS data,
                                                    COUNT(*) AS amount
                                               FROM spots
                                               GROUP BY data");
        } else {
            return $this->_conn->arrayQuery("SELECT EXTRACT(HOUR FROM FROM_UNIXTIME(stamp)) AS data,
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
            return $this->_conn->arrayQuery("SELECT FROM_UNIXTIME(stamp,'%w') AS data,
                                                    COUNT(*) AS amount
                                               FROM spots
                                               GROUP BY data");
        } else {
            return $this->_conn->arrayQuery("SELECT FROM_UNIXTIME(stamp,'%w') AS data,
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
            return $this->_conn->arrayQuery("SELECT EXTRACT(MONTH FROM FROM_UNIXTIME(stamp)) AS data,
                                                    COUNT(*) AS amount
                                               FROM spots
                                               GROUP BY data");
        } else {
            return $this->_conn->arrayQuery("SELECT EXTRACT(MONTH FROM FROM_UNIXTIME(stamp)) AS data,
                                                    COUNT(*) AS amount
                                               FROM spots
                                               WHERE stamp > :stamp
                                               GROUP BY data",
                array(
                    ':stamp' => array(strtotime("-1" . $limit), PDO::PARAM_INT)
                ));
        } # else
	} # getSpotCountPerMonth

	/*
	 * Remove extra spots 
	 */
	function removeExtraSpots($messageId) {
		# Retrieve the actual spot
		$spot = $this->getSpotHeader($messageId);

		/*
		 * The spot might be empty because - for example, the spot
		 * is moderated (and hence deleted), the highest spot retrieved
		 * might be missing from the database because of the spam cleanup.
		 *
		 * Ignore this error
		 */
		if (empty($spot)) {
			return ;
		} # if

		$this->_conn->modify("DELETE FROM spots, spotsfull USING spots
								LEFT JOIN spotsfull on spots.messageid=spotsfull.messageid
							  WHERE spots.id > :spotid",
            array(
                ':spotid' => array($spot['id'], PDO::PARAM_INT)
            ));
	} # removeExtraSpots
	
} # Dao_Mysql_Spot
