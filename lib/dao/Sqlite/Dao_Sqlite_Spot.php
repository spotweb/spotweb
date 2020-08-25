<?php

class Dao_Sqlite_Spot extends Dao_Base_Spot
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
     * Returns the spots in the database which match the
     * restrictions of $parsedSearch
     */
    public function getSpots($ourUserId, $pageNr, $limit, $parsedSearch)
    {
        $spotResults = parent::getSpots($ourUserId, $pageNr, $limit, $parsedSearch);

        /*
         * We force the category because sqlite can return an empty string
         * instead of an zero
         */
        $spotCnt = count($spotResults['list']);
        for ($i = 0; $i < $spotCnt; $i++) {
            $spotResults['list'][$i]['category'] = (int) $spotResults['list'][$i]['category'];
        } // foreach

        return $spotResults;
    }

    // getSpot

    /*
     * Returns the amount of spots per hour
     */
    public function getSpotCountPerHour($limit)
    {
        $filter = ($limit) ? 'WHERE stamp > '.strtotime('-1 '.$limit) : '';

        return $this->_conn->arrayQuery("SELECT strftime('%H', time(stamp, 'unixepoch')) AS data, count(*) AS amount FROM spots ".$filter.' GROUP BY data;');
    }

    // getSpotCountPerHour

    /*
     * Returns the amount of spots per weekday
     */
    public function getSpotCountPerWeekday($limit)
    {
        $filter = ($limit) ? 'WHERE stamp > '.strtotime('-1 '.$limit) : '';

        return $this->_conn->arrayQuery("SELECT strftime('%w', time(stamp, 'unixepoch')) AS data, count(*) AS amount FROM spots ".$filter.' GROUP BY data;');
    }

    // getSpotCountPerWeekday

    /*
     * Returns the amount of spots per month
     */
    public function getSpotCountPerMonth($limit)
    {
        $filter = ($limit) ? 'WHERE stamp > '.strtotime('-1 '.$limit) : '';

        return $this->_conn->arrayQuery("SELECT strftime('%m', time(stamp, 'unixepoch')) AS data, count(*) AS amount FROM spots ".$filter.' GROUP BY data;');
    }

    // getSpotCountPerMonth

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
                                  ' LEFT JOIN spotstatelist AS l ON ((s.messageid = l.messageid) AND (l.ouruserid = '.$this->_conn->safe((int) $ourUserId).')) 
									 LEFT JOIN spotsfull AS f ON (s.messageid = f.messageid)  
									 LEFT JOIN spotteridblacklist as bl ON ((bl.spotterid = s.spotterid) AND ((bl.ouruserid = '.$this->_conn->safe((int) $ourUserId).') OR (bl.ouruserid = -1)) AND (bl.idtype = 1))
									 LEFT JOIN spotteridblacklist AS wl ON ((wl.spotterid = s.spotterid) AND ((wl.ouruserid = '.$this->_conn->safe((int) $ourUserId).") AND (wl.idtype = 2)))
									 LEFT JOIN spotteridblacklist AS gwl ON ((gwl.spotterid = s.spotterid) AND ((gwl.ouruserid = -1) AND (gwl.idtype = 2))) \n ".
                                    $criteriaFilter.'
									 ORDER BY '.$sortList.
                                  ' LIMIT '.(int) ($limit + 1).' OFFSET '.(int) $offset;

        return $queryStr;
    }

    /*
     * Add a lis tof spots to the database
     */
    public function addSpots($spots, $fullSpots = [])
    {
        SpotTiming::start(__CLASS__.'::'.__FUNCTION__);
        foreach ($spots as &$spot) {
            /*
             * Manually check whether filesize is really a numeric value
             * because in some PHP vrsions an %d will overflow on >32bits (signed)
             * values causing a wrong result for files larger than 2GB
             */
            if (!is_numeric($spot['filesize'])) {
                $spot['filesize'] = 0;
            } // if

            /*
             * Cut off some strings to a maximum value as defined in the
             * database. We don't cut off the unique keys as we rather
             * have Spotweb error out than corrupt it
             *
             * We NEED to cast integers to actual integers to make sure our
             * batchInsert() call doesn't fail.
             */
            $spot['poster'] = substr($spot['poster'], 0, 127);
            $spot['title'] = substr($spot['title'], 0, 127);
            $spot['tag'] = substr($spot['tag'], 0, 127);
            $spot['subcata'] = substr($spot['subcata'], 0, 63);
            $spot['subcatb'] = substr($spot['subcatb'], 0, 63);
            $spot['subcatc'] = substr($spot['subcatc'], 0, 63);
            $spot['subcatd'] = substr($spot['subcatd'], 0, 63);
            $spot['spotterid'] = substr($spot['spotterid'], 0, 31);
            $spot['catgory'] = (int) $spot['category'];
            $spot['stamp'] = (int) $spot['stamp'];
            $spot['reversestamp'] = (int) ($spot['stamp'] * -1);

            /*
             * Make sure we only store valid utf-8
             */
            $spot['poster'] = mb_convert_encoding($spot['poster'], 'UTF-8', 'UTF-8');
            $spot['title'] = mb_convert_encoding($spot['title'], 'UTF-8', 'UTF-8');
            $spot['tag'] = mb_convert_encoding($spot['tag'], 'UTF-8', 'UTF-8');
        } // foreach
        unset($spot);

        $this->_conn->batchInsert(
            $spots,
            'INSERT INTO spots(messageid, poster, title, tag, category, subcata, 
														subcatb, subcatc, subcatd, subcatz, stamp, reversestamp, filesize, spotterid) 
									VALUES',
            [PDO::PARAM_STR, PDO::PARAM_STR, PDO::PARAM_STR, PDO::PARAM_STR, PDO::PARAM_INT, PDO::PARAM_STR,
                PDO::PARAM_STR, PDO::PARAM_STR, PDO::PARAM_STR, PDO::PARAM_STR, PDO::PARAM_INT, PDO::PARAM_INT,
                PDO::PARAM_STR, PDO::PARAM_STR, ],
            ['messageid', 'poster', 'title', 'tag', 'category', 'subcata', 'subcatb', 'subcatc',
                'subcatd', 'subcatz', 'stamp', 'reversestamp', 'filesize', 'spotterid', ]
        );

        if (!empty($fullSpots)) {
            $this->addFullSpots($fullSpots);
        } // if

        SpotTiming::stop(__CLASS__.'::'.__FUNCTION__, [$spots, $fullSpots]);
    }

    // addSpot()
} // Dao_Sqlite_Spot
