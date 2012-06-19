<?php

class Dao_Mysql_Spot extends Dao_Base_Spot {

	/*
	 * Remove a spot from the database
	 */
	function removeSpots($spotMsgIdList) {
		if (count($spotMsgIdList) == 0) {
			return;
		} # if

		$this->_conn->modify("DELETE FROM spots, spotsfull, commentsxover, reportsxover, spotstatelist, reportsposted, cache USING spots
							LEFT JOIN spotsfull ON spots.messageid=spotsfull.messageid
							LEFT JOIN commentsxover ON spots.messageid=commentsxover.nntpref
							LEFT JOIN reportsxover ON spots.messageid=reportsxover.nntpref
							LEFT JOIN spotstatelist ON spots.messageid=spotstatelist.messageid
							LEFT JOIN reportsposted ON spots.messageid=reportsposted.inreplyto
							LEFT JOIN cache ON spots.messageid=cache.resourceid
							WHERE spots.messageid  IN (" . $this->_conn->arrayKeyToIn($spotMsgIdList) . ")");
	} # removeSpots

	/*
	 * Remove older spots from the database
	 */
	function deleteSpotsRetention($retention) {
		$retention = $retention * 24 * 60 * 60; // omzetten in seconden

		$this->_conn->modify("DELETE FROM spots, spotsfull, commentsxover, reportsxover, spotstatelist, reportsposted, cache USING spots
			LEFT JOIN spotsfull ON spots.messageid=spotsfull.messageid
			LEFT JOIN commentsxover ON spots.messageid=commentsxover.nntpref
			LEFT JOIN reportsxover ON spots.messageid=reportsxover.nntpref
			LEFT JOIN spotstatelist ON spots.messageid=spotstatelist.messageid
			LEFT JOIN reportsposted ON spots.messageid=reportsposted.inreplyto
			LEFT JOIN cache ON spots.messageid=cache.resourceid
			WHERE spots.stamp < " . (time() - $retention) );
	} # deleteSpotsRetention

	/*
	 * Returns the amount of spots per hour
	 */
	function getSpotCountPerHour($limit) {
		$filter = ($limit) ? "WHERE stamp > " . strtotime("-1 " . $limit) : '';
		return $this->_conn->arrayQuery("SELECT EXTRACT(HOUR FROM FROM_UNIXTIME(stamp)) AS data, count(*) AS amount FROM spots " . $filter . " GROUP BY data;");
	} # getSpotCountPerHour

	/*
	 * Returns the amount of spots per weekday
	 */
	function getSpotCountPerWeekday($limit) {
		$filter = ($limit) ? "WHERE stamp > " . strtotime("-1 " . $limit) : '';
		return $this->_conn->arrayQuery("SELECT FROM_UNIXTIME(stamp,'%w') AS data, count(*) AS amount FROM spots " . $filter . " GROUP BY data;");
	} # getSpotCountPerWeekday

	/*
	 * Returns the amount of spots per month
	 */
	function getSpotCountPerMonth($limit) {
		$filter = ($limit) ? "WHERE stamp > " . strtotime("-1 " . $limit) : '';
		return $this->_conn->arrayQuery("SELECT EXTRACT(MONTH FROM FROM_UNIXTIME(stamp)) AS data, count(*) AS amount FROM spots " . $filter . " GROUP BY data;");
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
							  WHERE spots.id > %d", array($spot['id']));
	} # removeExtraSpots
	
} # Dao_Mysql_Spot
