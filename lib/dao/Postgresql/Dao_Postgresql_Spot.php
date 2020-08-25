<?php

class Dao_Postgresql_Spot extends Dao_Base_Spot
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
            'INSERT INTO spotsfull(messageid, verified, usersignature, userkey, xmlsignature, fullxml)
								  	VALUES',
            [PDO::PARAM_STR, PDO::PARAM_INT, PDO::PARAM_STR, PDO::PARAM_STR, PDO::PARAM_STR, PDO::PARAM_STR],
            ['messageid', 'verified', 'user-signature', 'user-key', 'xml-signature', 'fullxml']
        );

        SpotTiming::stop(__CLASS__.'::'.__FUNCTION__, [$fullSpots]);
    }

    // addFullSpot

    /*
     * Remove older spots from the database
     */
    public function deleteSpotsRetention($retention)
    {
        SpotTiming::start(__CLASS__.'::'.__FUNCTION__);
        $retention = $retention * 24 * 60 * 60; // omzetten in seconden

        $this->_conn->modify(
            'DELETE FROM spots WHERE spots.stamp < :time',
            [
                ':time' => [time() - $retention, PDO::PARAM_INT],
            ]
        );
        $this->_conn->modify('DELETE FROM spotsfull WHERE NOT EXISTS
							(SELECT 1 FROM spots WHERE spots.messageid = spotsfull.messageid)');
        $this->_conn->modify('DELETE FROM commentsfull WHERE NOT EXISTS
							(SELECT 1 FROM commentsxover WHERE commentsxover.messageid = commentsfull.messageid)');
        $this->_conn->modify('DELETE FROM commentsxover WHERE NOT EXISTS
							(SELECT 1 FROM spots WHERE spots.messageid = commentsxover.nntpref)');
        $this->_conn->modify('DELETE FROM reportsxover WHERE NOT EXISTS
							(SELECT 1 FROM spots WHERE spots.messageid = reportsxover.nntpref)');
        $this->_conn->modify('DELETE FROM spotstatelist WHERE NOT EXISTS
							(SELECT 1 FROM spots WHERE spots.messageid = spotstatelist.messageid)');
        $this->_conn->modify('DELETE FROM reportsposted WHERE NOT EXISTS
							  (SELECT 1 FROM spots WHERE spots.messageid = reportsposted.inreplyto)');
        SpotTiming::stop(__CLASS__.'::'.__FUNCTION__, [$retention]);
    }

    // deleteSpotsRetention

    /*
     * Returns the amount of spots per hour
     */
    public function getSpotCountPerHour($limit)
    {
        if (empty($limit)) {
            return $this->_conn->arrayQuery('SELECT EXTRACT(HOUR FROM to_timestamp(stamp)) AS data,
                                                    COUNT(*) AS amount
                                               FROM spots
                                               GROUP BY data');
        } else {
            return $this->_conn->arrayQuery(
                'SELECT EXTRACT(HOUR FROM to_timestamp(stamp)) AS data,
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
            return $this->_conn->arrayQuery('SELECT EXTRACT(DOW FROM to_timestamp(stamp)) AS data,
                                                    COUNT(*) AS amount
                                               FROM spots
                                               GROUP BY data');
        } else {
            return $this->_conn->arrayQuery(
                'SELECT EXTRACT(DOW FROM to_timestamp(stamp)) AS data,
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

    // getSpotCountPerWeekday

    /*
     * Returns the amount of spots per month
     */
    public function getSpotCountPerMonth($limit)
    {
        if (empty($limit)) {
            return $this->_conn->arrayQuery('SELECT EXTRACT(MONTH FROM to_timestamp(stamp)) AS data,
                                                    COUNT(*) AS amount
                                               FROM spots
                                               GROUP BY data');
        } else {
            return $this->_conn->arrayQuery(
                'SELECT EXTRACT(MONTH FROM to_timestamp(stamp)) AS data,
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

    public function getQuerystr($extendedFieldList, $additionalTableList, $additionalJoinList, $ourUserId, $criteriaFilter, $sortList, $limit, $offset)
    {
        $sortList2 = str_replace('s.', 'x.', $sortList);
        $sortList2 = str_replace('spost.', 'x.', $sortList2);
        /*
         * Run the query with a limit always increased by one. this allows us to
         * check whether any more results are available
         */
        $queryStr = 'select 	x.*, 
	                        f.verified ,
						    COALESCE(x.idtype, wl.idtype, gwl.idtype) AS idtype
                            from 
                            (SELECT s.id AS id,
								    s.messageid AS messageid,
								    s.category AS category,
								    s.poster AS poster,
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
	                                l.download as downloadstamp, 
	                                l.watch as watchstamp,
	                                l.seen AS seenstamp,
                                    bl.idtype as idtype,
                                    s.reversestamp as reversestamp
								    '.$extendedFieldList." \n
								    FROM spots AS s ".
                                    $additionalTableList." \n".
                                    $additionalJoinList." \n".
                                   'LEFT JOIN spotteridblacklist as bl ON ((bl.spotterid = s.spotterid) AND ((bl.ouruserid = '.$this->_conn->safe((int) $ourUserId).') OR (bl.ouruserid = -1)) AND (bl.idtype = 1))
							        LEFT JOIN spotstatelist AS l on ((s.messageid = l.messageid) AND (l.ouruserid = '.$this->_conn->safe((int) $ourUserId).'))
                                    '.$criteriaFilter." \n
								    ORDER BY ".$sortList.' LIMIT '.(int) ($limit + 1).' OFFSET '.(int) $offset.'
                            ) as x
                            LEFT JOIN spotsfull AS f ON (f.messageid = x.messageid) 
 					        LEFT JOIN spotteridblacklist as wl on ((wl.spotterid = x.spotterid) AND ((wl.ouruserid = '.$this->_conn->safe((int) $ourUserId).') AND (wl.idtype = 2)))
							LEFT JOIN spotteridblacklist as gwl on ((gwl.spotterid = x.spotterid) AND ((gwl.ouruserid = -1) AND (gwl.idtype = 2))) 
                            ORDER BY '.$sortList2;

        return $queryStr;
    }
} // Dao_Postgresql_Spot
