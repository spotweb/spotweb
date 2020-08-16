<?php

class Dao_Mysql_Spot extends Dao_Base_Spot
{
    /*
     * adds a list of fullspots to the database. Don't use this without having an entry in the header
     * table as it will remove the spot from the list
     */
    public function addFullSpots($fullSpots)
    {
        SpotTiming::start(__CLASS__.'::'.__FUNCTION__);

        /*
         * Prepare the array for insertion
         */
        foreach ($fullSpots as &$fullSpot) {
            $fullSpot['verified'] = (int) $fullSpot['verified'];
            $fullSpot['user-key'] = base64_encode(serialize($fullSpot['user-key']));
        } // foreach

        $this->_conn->batchInsert(
            $fullSpots,
            'INSERT IGNORE INTO spotsfull(messageid, verified, usersignature, userkey, xmlsignature, fullxml)
								  	VALUES',
            [PDO::PARAM_STR, PDO::PARAM_INT, PDO::PARAM_STR, PDO::PARAM_STR, PDO::PARAM_STR, PDO::PARAM_STR],
            ['messageid', 'verified', 'user-signature', 'user-key', 'xml-signature', 'fullxml']
        );

        SpotTiming::stop(__CLASS__.'::'.__FUNCTION__, [$fullSpots]);
    }

    // addFullSpot

    /*
     * Remove a spot from the database
     */
    public function removeSpots($spotMsgIdList)
    {
        if (count($spotMsgIdList) == 0) {
            return;
        } // if

        $this->_conn->modify('DELETE FROM spots, spotsfull, reportsxover, spotstatelist, reportsposted USING spots
							LEFT JOIN spotsfull ON spots.messageid=spotsfull.messageid
                            LEFT JOIN reportsxover ON spots.messageid=reportsxover.nntpref
							LEFT JOIN spotstatelist ON spots.messageid=spotstatelist.messageid
							LEFT JOIN reportsposted ON spots.messageid=reportsposted.inreplyto
							WHERE spots.messageid  IN ('.$this->_conn->arrayKeyToIn($spotMsgIdList).')');
    }

    // removeSpots

    /*
     * Remove older spots from the database
     */
    public function deleteSpotsRetention($retention)
    {
        $retention = $retention * 24 * 60 * 60; // omzetten in seconden

        $this->_conn->modify(
            'DELETE FROM spots, spotsfull, commentsxover, reportsxover, spotstatelist, reportsposted USING spots
                                LEFT JOIN spotsfull ON spots.messageid=spotsfull.messageid
                                LEFT JOIN commentsxover ON spots.messageid=commentsxover.nntpref
                                LEFT JOIN reportsxover ON spots.messageid=reportsxover.nntpref
                                LEFT JOIN spotstatelist ON spots.messageid=spotstatelist.messageid
                                LEFT JOIN reportsposted ON spots.messageid=reportsposted.inreplyto
                                WHERE spots.stamp < :stamp',
            [
                ':stamp' => [time() - $retention, PDO::PARAM_INT],
            ]
        );
    }

    // deleteSpotsRetention

    /*
     * Returns the amount of spots per hour
     */
    public function getSpotCountPerHour($limit)
    {
        if (empty($limit)) {
            return $this->_conn->arrayQuery('SELECT EXTRACT(HOUR FROM FROM_UNIXTIME(stamp)) AS data,
                                                    COUNT(*) AS amount
                                               FROM spots
                                               GROUP BY data');
        } else {
            return $this->_conn->arrayQuery(
                'SELECT EXTRACT(HOUR FROM FROM_UNIXTIME(stamp)) AS data,
                                                    COUNT(*) AS amount
                                               FROM spots
                                               WHERE stamp > :stamp
                                               GROUP BY data',
                [
                    ':stamp' => [strtotime('-1'.$limit), PDO::PARAM_INT],
                ]
            );
        } // else
    }

    // getSpotCountPerHour

    /*
     * Returns the amount of spots per weekday
     */
    public function getSpotCountPerWeekday($limit)
    {
        if (empty($limit)) {
            return $this->_conn->arrayQuery("SELECT FROM_UNIXTIME(stamp,'%w') AS data,
                                                    COUNT(*) AS amount
                                               FROM spots
                                               GROUP BY data");
        } else {
            return $this->_conn->arrayQuery(
                "SELECT FROM_UNIXTIME(stamp,'%w') AS data,
                                                    COUNT(*) AS amount
                                               FROM spots
                                               WHERE stamp > :stamp
                                               GROUP BY data",
                [
                    ':stamp' => [strtotime('-1'.$limit), PDO::PARAM_INT],
                ]
            );
        } // else
    }

    // getSpotCountPerWeekday

    /*
     * Returns the amount of spots per month
     */
    public function getSpotCountPerMonth($limit)
    {
        if (empty($limit)) {
            return $this->_conn->arrayQuery('SELECT EXTRACT(MONTH FROM FROM_UNIXTIME(stamp)) AS data,
                                                    COUNT(*) AS amount
                                               FROM spots
                                               GROUP BY data');
        } else {
            return $this->_conn->arrayQuery(
                'SELECT EXTRACT(MONTH FROM FROM_UNIXTIME(stamp)) AS data,
                                                    COUNT(*) AS amount
                                               FROM spots
                                               WHERE stamp > :stamp
                                               GROUP BY data',
                [
                    ':stamp' => [strtotime('-1'.$limit), PDO::PARAM_INT],
                ]
            );
        } // else
    }

    // getSpotCountPerMonth

    /*
     * Remove extra spots
     */
    public function removeExtraSpots($messageId)
    {
        // Retrieve the actual spot
        $spot = $this->getSpotHeader($messageId);

        /*
         * The spot might be empty because - for example, the spot
         * is moderated (and hence deleted), the highest spot retrieved
         * might be missing from the database because of the spam cleanup.
         *
         * Ignore this error
         */
        if (empty($spot)) {
            return;
        } // if

        $this->_conn->modify(
            'DELETE FROM spots, spotsfull USING spots
								LEFT JOIN spotsfull on spots.messageid=spotsfull.messageid
							  WHERE spots.id > :spotid',
            [
                ':spotid' => [$spot['id'], PDO::PARAM_INT],
            ]
        );
    }

    // removeExtraSpots

    public function getQuerystr($extendedFieldList, $additionalTableList, $additionalJoinList, $ourUserId, $criteriaFilter, $sortList, $limit, $offset)
    {
        /*
         * Run the query with a limit always increased by one. this allows us to
         * check whether any more results are available
         */
        $queryStr = 'SELECT s.id AS id,
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
												'.$extendedFieldList.'
									 FROM spots AS s '.
                                    $additionalTableList.
                                    $additionalJoinList.
                                  ' LEFT JOIN spotstatelist AS l FORCE INDEX FOR JOIN (idx_spotstatelist_1) ON ((s.messageid = l.messageid) AND (l.ouruserid = '.$this->_conn->safe((int) $ourUserId).')) 
									 LEFT JOIN spotsfull AS f FORCE INDEX FOR JOIN (idx_spotsfull_1) ON (s.messageid = f.messageid)  
									 LEFT JOIN spotteridblacklist as bl FORCE INDEX FOR JOIN (idx_spotteridblacklist_1) ON ((bl.spotterid = s.spotterid) AND ((bl.ouruserid = '.$this->_conn->safe((int) $ourUserId).') OR (bl.ouruserid = -1)) AND (bl.idtype = 1))
									 LEFT JOIN spotteridblacklist AS wl FORCE INDEX FOR JOIN (idx_spotteridblacklist_1) ON ((wl.spotterid = s.spotterid) AND ((wl.ouruserid = '.$this->_conn->safe((int) $ourUserId).") AND (wl.idtype = 2)))
									 LEFT JOIN spotteridblacklist AS gwl FORCE INDEX FOR JOIN (idx_spotteridblacklist_1) ON ((gwl.spotterid = s.spotterid) AND ((gwl.ouruserid = -1) AND (gwl.idtype = 2))) \n ".
                                    $criteriaFilter.'
									 ORDER BY '.$sortList.
                                  ' LIMIT '.(int) ($limit + 1).' OFFSET '.(int) $offset;

        return $queryStr;
    }
} // Dao_Mysql_Spot
