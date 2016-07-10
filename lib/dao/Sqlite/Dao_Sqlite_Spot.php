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

    function getQuerystr($extendedFieldList, $additionalTableList, $additionalJoinList, $ourUserId, $criteriaFilter,$sortList,$limit,$offset) {
        /*
         * Run the query with a limit always increased by one. this allows us to 
         * check whether any more results are available
         */
        $queryStr = "SELECT s.id AS id,
												s.messageid AS messageid,
												s.category AS category,
												s.poster AS poster,
												l.download as downloadstamp, 
												l.watch as watchstamp,
												l.seen AS seenstamp,
												s.subcata AS subcata,
												s.subcatb AS subcatb,
												s.subcatc AS subcatc,
												s.subcatd AS subcatd,
												s.subcatz AS subcatz,
												s.title AS title,
												s.tag AS tag,
												s.stamp AS stamp,
												s.moderated AS moderated,
												s.filesize AS filesize,
												s.spotrating AS rating,
												s.commentcount AS commentcount,
												s.reportcount AS reportcount,
												s.spotterid AS spotterid,
 												s.editstamp AS editstamp,
 												s.editor AS editor,
												f.verified AS verified,
												COALESCE(bl.idtype, wl.idtype, gwl.idtype) AS idtype
												" . $extendedFieldList . "
									 FROM spots AS s " . 
                                    $additionalTableList . 
                                    $additionalJoinList . 
                                  " LEFT JOIN spotstatelist AS l ON ((s.messageid = l.messageid) AND (l.ouruserid = " . $this->_conn->safe( (int) $ourUserId) . ")) 
									 LEFT JOIN spotsfull AS f ON (s.messageid = f.messageid)  
									 LEFT JOIN spotteridblacklist as bl ON ((bl.spotterid = s.spotterid) AND ((bl.ouruserid = " . $this->_conn->safe( (int) $ourUserId) . ") OR (bl.ouruserid = -1)) AND (bl.idtype = 1))
									 LEFT JOIN spotteridblacklist AS wl ON ((wl.spotterid = s.spotterid) AND ((wl.ouruserid = " . $this->_conn->safe( (int) $ourUserId) . ") AND (wl.idtype = 2)))
									 LEFT JOIN spotteridblacklist AS gwl ON ((gwl.spotterid = s.spotterid) AND ((gwl.ouruserid = -1) AND (gwl.idtype = 2))) \n " .
                                    $criteriaFilter . "
									 ORDER BY " . $sortList . 
                                  " LIMIT " . (int) ($limit + 1) ." OFFSET " . (int) $offset;
        return $queryStr ;
    }
	

} # Dao_Sqlite_Spot
