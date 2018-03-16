<?php

class Dao_Base_Spot implements Dao_Spot {
	protected $_conn;

	/*
	 * constructs a new Dao_Base_Spot object, 
	 * connection object is given
	 */
	public function __construct(dbeng_abs $conn) {
		$this->_conn = $conn;
	} # ctor

	/*
     * Returns the spots in the database which match the 
     * restrictions of $parsedSearch
     */
	function getSpots($ourUserId, $pageNr, $limit, $parsedSearch) {
		SpotTiming::start(__CLASS__ . '::' . __FUNCTION__);
		$offset = (int) $pageNr * (int) $limit;

		/*
         * there are the basic search criteria (category, title, etc) 
         * which are always available in the query
         */
		$criteriaFilter = " WHERE (bl.spotterid IS NULL) ";
		if (!empty($parsedSearch['filter'])) {
			$criteriaFilter .= ' AND ' . $parsedSearch['filter'];
		} # if 

		/*
         * but the queryparser is allowed to request any additional fields
         * to be queried upon, which we need to make available in the
         * query as well.
         */
		$extendedFieldList = '';
		foreach($parsedSearch['additionalFields'] as $additionalField) {
			$extendedFieldList = ', ' . $additionalField . $extendedFieldList;
		} # foreach

		/*
         * even additional tables might be requested, mostly used for FTS
         * with virtual tables
         */
		$additionalTableList = '';
		foreach($parsedSearch['additionalTables'] as $additionalTable) {
			$additionalTableList = ', ' . $additionalTable . $additionalTableList;
		} # foreach

		/*
         * add additional requested joins
         */
		$additionalJoinList = '';
		foreach($parsedSearch['additionalJoins'] as $additionalJoin) {
			$additionalJoinList = ' ' . $additionalJoin['jointype'] . ' JOIN ' . 
							$additionalJoin['tablename'] . ' AS ' . $additionalJoin['tablealias'] .
							' ON (' . $additionalJoin['joincondition'] . ') ';
		} # foreach

		/* 
         * we always sort, but sometimes on multiple fields.
         */		
		$sortFields = $parsedSearch['sortFields'];
		$sortList = array();
		foreach($sortFields as $sortValue) {
			if (!empty($sortValue)) {
				/*
                 * when asked to sort on the field 'stamp' descending, we secretly 
                 * sort ascsending on a field called 'reversestamp'. Older MySQL versions
                 * suck at sorting in reverse, and older NAS systems run ancient MySQl
                 * versions
                 */
				if ((strtolower($sortValue['field']) == 's.stamp') && strtolower($sortValue['direction']) == 'desc') {
					$sortValue['field'] = 's.reversestamp';
					$sortValue['direction'] = 'ASC';
				} # if
				
				$sortList[] = $sortValue['field'] . ' ' . $sortValue['direction'];
			} # if
		} # foreach
		$sortList = implode(', ', $sortList);
                
        /* 
         * The query is depending on the database implementation chosen
         */
        
        $queryStr = $this-> getQuerystr($extendedFieldList, $additionalTableList, $additionalJoinList, $ourUserId, $criteriaFilter,$sortList,$limit,$offset);
        $tmpResult = $this->_conn->arrayQuery($queryStr);

		/*
         * Did we get more results than originally asked? Remove the last element
         * and set the flag we have gotten more results than originally asked for
         */
		$hasMore = (count($tmpResult) > $limit);
		if ($hasMore) {
			# remove the last element
			array_pop($tmpResult);
		} # if

		SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__, array($ourUserId, $pageNr, $limit, $criteriaFilter));
		return array('list' => $tmpResult, 'hasmore' => $hasMore);
	} # getSpots()
    

	/*
	 * Returns the header information of a spot
	 */
	function getSpotHeader($msgId) {
		SpotTiming::start(__CLASS__ . '::' . __FUNCTION__);
		$tmpArray = $this->_conn->arrayQuery("SELECT s.id AS id,
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
												s.spotrating AS rating,
												s.commentcount AS commentcount,
												s.reportcount AS reportcount,
												s.moderated AS moderated
											  FROM spots AS s
											  WHERE s.messageid = :messageid",
            array(
                ':messageid' => array($msgId, PDO::PARAM_STR)
            ));
		SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__);

		if (empty($tmpArray)) {
			return null;
		} # if

		return $tmpArray[0];
	} # getSpotHeader 

	/*
	 * Retrieves one specific, full spot. When either the header or te full
	 * spot is not in the database, this function returns NULL
	 */
	function getFullSpot($messageId, $ourUserId) {
		SpotTiming::start(__CLASS__ . __FUNCTION__);
		$tmpArray = $this->_conn->arrayQuery("SELECT s.id AS id,
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
												s.spotrating AS rating,
												s.commentcount AS commentcount,
												s.reportcount AS reportcount,
												s.filesize AS filesize,
												s.spotterid AS spotterid,
												s.editstamp AS editstamp,
												s.editor AS editor,
												l.download AS downloadstamp,
												l.watch as watchstamp,
												l.seen AS seenstamp,
												f.verified AS verified,
												f.usersignature AS \"user-signature\",
												f.userkey AS \"user-key\",
												f.xmlsignature AS \"xml-signature\",
												f.fullxml AS fullxml,
												COALESCE(bl.idtype, wl.idtype, gwl.idtype) AS listidtype
												FROM spots AS s
												LEFT JOIN spotstatelist AS l on ((s.messageid = l.messageid) AND (l.ouruserid = :ouruserid1))
												LEFT JOIN spotteridblacklist as bl ON ((bl.spotterid = s.spotterid) AND ((bl.ouruserid = :ouruserid2) OR (bl.ouruserid = -1)) AND (bl.idtype = 1))
												LEFT JOIN spotteridblacklist as wl on ((wl.spotterid = s.spotterid) AND ((wl.ouruserid = :ouruserid3)) AND (wl.idtype = 2))
												LEFT JOIN spotteridblacklist as gwl on ((gwl.spotterid = s.spotterid) AND ((gwl.ouruserid = -1)) AND (gwl.idtype = 2))
												JOIN spotsfull AS f ON f.messageid = s.messageid
										  WHERE s.messageid = :messageid",
            array(
                ':ouruserid1' => array($ourUserId, PDO::PARAM_INT),
                ':ouruserid2' => array($ourUserId, PDO::PARAM_INT),
                ':ouruserid3' => array($ourUserId, PDO::PARAM_INT),
                ':messageid' => array($messageId, PDO::PARAM_STR)
            ));
		if (empty($tmpArray)) {
            SpotTiming::stop(__CLASS__ . __FUNCTION__, array($messageId, $ourUserId));

			return null;
		} # if
		$tmpArray = $tmpArray[0];

		# If spot is fully stored in db and is of the new type, we process it to
		# make it exactly the same as when retrieved using NNTP
		if (!empty($tmpArray['fullxml']) && (!empty($tmpArray['user-signature']))) {
			$tmpArray['user-key'] = unserialize(base64_decode($tmpArray['user-key']));
		} # if

		SpotTiming::stop(__CLASS__ . __FUNCTION__, array($messageId, $ourUserId));
		return $tmpArray;		
	} # getFullSpot()

	/*
	 * Updates the spot rating for a specific list of spots
	 */
	function updateSpotRating($spotMsgIdList) {
		# Empty list provided? Exit
		if (count($spotMsgIdList) == 0) {
			return;
		} # if

        SpotTiming::start(__CLASS__ . '::' . __FUNCTION__);

        # en update de spotrating
		$this->_conn->modify("UPDATE spots 
								SET spotrating = 
									(SELECT AVG(spotrating) as spotrating 
									 FROM commentsxover 
									 WHERE 
										spots.messageid = commentsxover.nntpref 
										AND spotrating BETWEEN 1 AND 10
									 GROUP BY nntpref)
							WHERE spots.messageid IN (" . $this->_conn->arrayKeyToIn($spotMsgIdList) . ")
						");
		SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__, array($spotMsgIdList));
	} # updateSpotRating

	/*
	 * Updates the commentcount for a specific list of spots
	 */
	function updateSpotCommentCount($spotMsgIdList) {
		# Empty list provided? Exit
		if (count($spotMsgIdList) == 0) {
			return;
		} # if

		SpotTiming::start(__CLASS__ . '::' . __FUNCTION__);
		$this->_conn->modify("UPDATE spots 
								SET commentcount = 
									(SELECT COUNT(1) as commentcount 
									 FROM commentsxover 
									 WHERE 
										spots.messageid = commentsxover.nntpref 
									 GROUP BY nntpref)
							WHERE spots.messageid IN (" . $this->_conn->arrayKeyToIn($spotMsgIdList) . ")
						");
		SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__, array($spotMsgIdList));
	} # updateSpotCommentCount

	/*
	 * Updates the reportcount for a specific list of spots
	 */
	function updateSpotReportCount($spotMsgIdList) {
		# Empty list provided? Exit
		if (count($spotMsgIdList) == 0) {
			return;
		} # if

		SpotTiming::start(__CLASS__ . '::' . __FUNCTION__);
		$this->_conn->modify("UPDATE spots 
								SET reportcount = 
									(SELECT COUNT(1) as reportcount 
									 FROM reportsxover
									 WHERE 
										spots.messageid = reportsxover.nntpref 
									 GROUP BY nntpref)
							WHERE spots.messageid IN (" . $this->_conn->arrayKeyToIn($spotMsgIdList) . ")
						");
		SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__, array($spotMsgIdList));
	} # updateSpotReportCount

    /* Get spotterId and stamp from the spots to be disposed to
     * enable checking of personal dispose messages
     */
        function getDisposedSpots($spotMsgIdList) {
		SpotTiming::start(__CLASS__ . '::' . __FUNCTION__);
        $tmparray = array();
        # Empty list provided? Exit
		if (count($spotMsgIdList) == 0) {
			return $tmparray;
		} # if

		# prepare a list of IN values
		$msgIdList = $this->_conn->arrayKeyToIn($spotMsgIdList);
        $msgIdList = "(" .$msgIdList .")";

        $tmpArray = $this->_conn->arrayQuery("SELECT s.messageid AS messageid, s.spotterid AS spotterid, s.stamp AS stamp
											  FROM spots AS s
											  WHERE s.messageid IN ".$msgIdList);
		SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__);
        return $tmpArray;
       }

    

	/*
	 * Remove a spot from the database
	 */
	function removeSpots($spotMsgIdList) {
		# Empty list provided? Exit
		if (count($spotMsgIdList) == 0) {
			return;
		} # if

		SpotTiming::start(__CLASS__ . '::' . __FUNCTION__);

		# prepare a list of IN values
		$msgIdList = $this->_conn->arrayKeyToIn($spotMsgIdList);

		$this->_conn->modify("DELETE FROM spots WHERE messageid IN (" . $msgIdList . ")");
		$this->_conn->modify("DELETE FROM spotsfull WHERE messageid  IN (" . $msgIdList . ")");
        // Comments are deleted in a seperate routine
        //$this->_conn->modify("DELETE FROM commentsfull WHERE messageid IN (SELECT messageid FROM commentsxover WHERE nntpref IN (" . $msgIdList . "))");
        //$this->_conn->modify("DELETE FROM commentsxover WHERE nntpref  IN (" . $msgIdList . ")");
		$this->_conn->modify("DELETE FROM spotstatelist WHERE messageid  IN (" . $msgIdList . ")");
		$this->_conn->modify("DELETE FROM reportsxover WHERE nntpref  IN (" . $msgIdList . ")");
		$this->_conn->modify("DELETE FROM reportsposted WHERE inreplyto  IN (" . $msgIdList . ")");
		SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__, array($spotMsgIdList));
	} # removeSpots

	/*
	 * Mark a spot in the database as moderated
	 */
	function markSpotsModerated($spotMsgIdList) {
		# Empty list provided? Exit
		if (count($spotMsgIdList) == 0) {
			return;
		} # if

		SpotTiming::start(__CLASS__ . '::' . __FUNCTION__);
		$this->_conn->modify("UPDATE spots SET moderated = :moderated WHERE messageid IN (" .
								$this->_conn->arrayKeyToIn($spotMsgIdList) . ")",
            array(
                ':moderated' => array(true, PDO::PARAM_BOOL)
            ));

		SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__, array($spotMsgIdList));
	} # markSpotsModerated

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
		$this->_conn->modify("DELETE FROM spotsfull WHERE spotsfull.messageid not in
							(SELECT messageid FROM spots)") ;
		$this->_conn->modify("DELETE FROM commentsfull WHERE messageid IN 
							(SELECT messageid FROM commentsxover WHERE commentsxover.nntpref not in 
							(SELECT messageid FROM spots))") ;
		$this->_conn->modify("DELETE FROM commentsxover WHERE commentsxover.nntpref not in 
							(SELECT messageid FROM spots)") ;
		$this->_conn->modify("DELETE FROM reportsxover WHERE reportsxover.nntpref not in 
							(SELECT messageid FROM spots)") ;
		$this->_conn->modify("DELETE FROM spotstatelist WHERE spotstatelist.messageid not in 
							(SELECT messageid FROM spots)") ;
		$this->_conn->modify("DELETE FROM reportsposted WHERE reportsposted.inreplyto not in 
							(SELECT messageid FROM spots)") ;
		SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__, array($retention));
	} # deleteSpotsRetention

	/*
	 * Add a lis tof spots to the database
	 */
	function addSpots($spots, $fullSpots = array()) {
		SpotTiming::start(__CLASS__ . '::' . __FUNCTION__);
		foreach($spots as &$spot) {
			/*
			 * Manually check whether filesize is really a numeric value
			 * because in some PHP vrsions an %d will overflow on >32bits (signed)
			 * values causing a wrong result for files larger than 2GB
			 */
			if (!is_numeric($spot['filesize'])) {
				$spot['filesize'] = 0;
			} # if
			
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

		} # foreach
        unset($spot);

		$this->_conn->batchInsert($spots,
								  "INSERT INTO spots(messageid, poster, title, tag, category, subcata, 
														subcatb, subcatc, subcatd, subcatz, stamp, reversestamp, filesize, spotterid) 
									VALUES",
                                  array(PDO::PARAM_STR, PDO::PARAM_STR, PDO::PARAM_STR, PDO::PARAM_STR, PDO::PARAM_INT, PDO::PARAM_STR,
                                        PDO::PARAM_STR, PDO::PARAM_STR, PDO::PARAM_STR, PDO::PARAM_STR, PDO::PARAM_INT, PDO::PARAM_INT,
                                        PDO::PARAM_INT, PDO::PARAM_STR),
								  array('messageid', 'poster', 'title', 'tag', 'category', 'subcata', 'subcatb', 'subcatc',
								  		'subcatd', 'subcatz', 'stamp', 'reversestamp', 'filesize', 'spotterid')
								  );

		if (!empty($fullSpots)) {
			$this->addFullSpots($fullSpots);
		} # if

		SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__, array($spots, $fullSpots));
	} # addSpot()

	/*
	 * Update the spots table with some information contained in the 
	 * fullspots information. 
	 *
	 * Some information in the fullspot is more reliable because 
	 * more fidelity encoding.
	 */
	function updateSpotInfoFromFull($fullSpot) {
		SpotTiming::start(__CLASS__ . '::' . __FUNCTION__);
		$this->_conn->modify("UPDATE spots SET title = :title, spotterid = :spotterid WHERE messageid = :messageid",
            array(
                ':title' => array($fullSpot['title'], PDO::PARAM_STR),
                ':spotterid' => array($fullSpot['spotterid'], PDO::PARAM_STR),
                ':messageid' => array($fullSpot['messageid'], PDO::PARAM_STR)
            ));

		SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__, array($fullSpot));
	} # updateSpotInfoFromFull

	/*
	 * adds a list of fullspots to the database. Don't use this without having an entry in the header
	 * table as it will remove the spot from the list
	 */
	function addFullSpots($fullSpots) {
		SpotTiming::start(__CLASS__ . '::' . __FUNCTION__);

		/* 
		 * Prepare the array for insertion
		 */
		foreach($fullSpots as &$fullSpot) {
			$fullSpot['verified'] = (int) $fullSpot['verified'];
			$fullSpot['user-key'] = base64_encode(serialize($fullSpot['user-key']));
		} # foreach

		$this->_conn->batchInsert($fullSpots,
								  "INSERT INTO spotsfull(messageid, verified, usersignature, userkey, xmlsignature, fullxml)
								  	VALUES",
                                  array(PDO::PARAM_STR, PDO::PARAM_INT, PDO::PARAM_STR, PDO::PARAM_STR, PDO::PARAM_STR, PDO::PARAM_STR),
								  array('messageid', 'verified', 'user-signature', 'user-key', 'xml-signature', 'fullxml')
								  );

		SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__, array($fullSpots));
	} # addFullSpot

	/*
	 * Update a spot in the spots and spotsfull tables after editing the spot
	 */
	function updateSpot($fullSpot, $editor) {
		SpotTiming::start(__CLASS__ . '::' . __FUNCTION__);

		/*
		 * Cut off some strings to a maximum value as defined in the
		 * database. 
		 */
		$fullSpot['title'] = substr($fullSpot['title'], 0, 127);
		$fullSpot['tag'] = substr($fullSpot['tag'], 0, 127);
		$fullSpot['subcata'] = substr($fullSpot['subcata'], 0, 63);
		$fullSpot['subcatb'] = substr($fullSpot['subcatb'], 0, 63);
		$fullSpot['subcatc'] = substr($fullSpot['subcatc'], 0, 63);
		$fullSpot['subcatd'] = substr($fullSpot['subcatd'], 0, 63);
		$fullSpot['subcatz'] = substr($fullSpot['subcatz'], 0, 63);
		$fullSpot['category'] = (int) $fullSpot['category'];

        /*
         * Make sure we only store valid utf-8
         */
        $fullSpot['title'] = mb_convert_encoding($fullSpot['title'], 'UTF-8', 'UTF-8');
        $fullSpot['tag'] = mb_convert_encoding($fullSpot['tag'], 'UTF-8', 'UTF-8');

		# update spots table
		$this->_conn->modify("UPDATE spots
                                SET title = :title,
                                    tag = :tag,
                                    subcata = :subcata,
				                    subcatb = :subcatb,
				                    subcatc = :subcatc,
				                    subcatd = :subcatd,
				                    subcatz = :subcatz,
				                    category = :category,
				                    editstamp = :editstamp,
				                    editor = :editor
				                WHERE messageid = :messageid",
            array(
                ':title' => array($fullSpot['title'], PDO::PARAM_STR),
                ':tag' => array($fullSpot['tag'], PDO::PARAM_STR),
                ':subcata' => array($fullSpot['subcata'], PDO::PARAM_STR),
                ':subcatb' => array($fullSpot['subcata'], PDO::PARAM_STR),
                ':subcatc' => array($fullSpot['subcatb'], PDO::PARAM_STR),
                ':subcatd' => array($fullSpot['subcatd'], PDO::PARAM_STR),
                ':subcatz' => array($fullSpot['subcatz'], PDO::PARAM_STR),
                ':category' => array($fullSpot['category'], PDO::PARAM_INT),
                ':editstamp' => array(time(), PDO::PARAM_INT),
                ':editor' => array($editor, PDO::PARAM_STR),
                ':messageid' => array($fullSpot['messageid'], PDO::PARAM_STR),
            ));

		# update spotsfull table
		$this->_conn->modify("UPDATE spotsfull
		                         SET fullxml = :fullxml
		                       WHERE messageid = :messageid",
            array(
                ':fullxml' => array($fullSpot['fullxml'], PDO::PARAM_STR),
                ':messageid' => array($fullSpot['messageid'], PDO::PARAM_STR)
            ));

		SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__, array($fullSpot));
	} # updateSpot
	
	/*
	 * Returns the oldest spot in the system
	 */
	function getOldestSpotTimestamp() {
		return $this->_conn->singleQuery("SELECT MIN(stamp) FROM spots;");
	} # getOldestSpotTimestamp

	
	/*
	 * Match set of spots
	 */
	function matchSpotMessageIds($hdrList) {
		$idList = array('spot' => array(), 'fullspot' => array());

		# Empty list, exit
		if (count($hdrList) == 0) {
			return $idList;
		} # if

		SpotTiming::start(__CLASS__ . '::' . __FUNCTION__);

		# Prepare a list of values
		$msgIdList = $this->_conn->arrayValToIn($hdrList, 'Message-ID');

		# Because MySQL doesn't know anything about full joins, we use this trick
		$rs = $this->_conn->arrayQuery("SELECT messageid AS spot, '' AS fullspot FROM spots WHERE messageid IN (" . $msgIdList . ")
											UNION
					 				    SELECT '' as spot, messageid AS fullspot FROM spotsfull WHERE messageid IN (" . $msgIdList . ")");

		# en lossen we het hier op
		foreach($rs as $msgids) {
			if (!empty($msgids['spot'])) {
				$idList['spot'][$msgids['spot']] = 1;
			} # if

			if (!empty($msgids['fullspot'])) {
				$idList['fullspot'][$msgids['fullspot']] = 1;
			} # if
		} # foreach
		SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__, array($hdrList, $idList));

		return $idList;
	} # matchMessageIds 

	/**
	 * Returns the amount of spots currently in the database
	 */
	function getSpotCount($sqlFilter) {
		SpotTiming::start(__CLASS__ . '::' . __FUNCTION__);
		if (empty($sqlFilter)) {
			$query = "SELECT COUNT(1) FROM spots AS s";
		} else {
			$query = "SELECT COUNT(1) FROM spots AS s
						LEFT JOIN spotsfull AS f ON s.messageid = f.messageid
						LEFT JOIN spotstatelist AS l ON s.messageid = l.messageid
						LEFT JOIN spotteridblacklist as bl ON ((bl.spotterid = s.spotterid) AND (bl.ouruserid = -1) AND (bl.idtype = 1))
						WHERE " . $sqlFilter . " AND (bl.spotterid IS NULL)";
		} # else
		$cnt = $this->_conn->singleQuery($query);
		SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__, array($sqlFilter));
		if ($cnt == null) {
			return 0;
		} else {
			return $cnt;
		} # if
	} # getSpotCount

	/*
	 * Returns the amount of spots per hour
	 */
	function getSpotCountPerHour($limit) {
		throw new NotImplementedException();
	} # getSpotCountPerHour

	/*
	 * Returns the amount of spots per weekday
	 */
	function getSpotCountPerWeekday($limit) {
		throw new NotImplementedException();
	} # getSpotCountPerWeekday

	/*
	 * Returns the amount of spots per month
	 */
	function getSpotCountPerMonth($limit) {
		throw new NotImplementedException();
	} # getSpotCountPerMonth

	function getQuerystr($extendedFieldList, $additionalTableList, $additionalJoinList, $ourUserId, $criteriaFilter,$sortList,$limit,$offset) {
		throw new NotImplementedException();
	} # getQuerystr
    

	/**
	 * Returns the amount of spots per category
     *
     * @param int|boolean $limit Amount of days to get the spotcount for, or false to get without any limits
     * @return array
	 */
	function getSpotCountPerCategory($limit) {
        if (!empty($limit)) {
            return $this->_conn->arrayQuery("SELECT category AS data, COUNT(category) AS amount FROM spots WHERE stamp > :stamp GROUP BY data",
                array(
                    ':stamp' => array(strtotime("-1 " . $limit), PDO::PARAM_INT)
                ));
        } else {
            return $this->_conn->arrayQuery("SELECT category AS data, COUNT(category) AS amount FROM spots GROUP BY data");
        } # else
	} # getSpotCountPerCategory

	/**
	 * Remove extra spots
     *
     * @param string $messageId All messages after the given messageid are to be removed
     * @return void
	 */
	function removeExtraSpots($messageId) {
		SpotTiming::start(__CLASS__ . '::' . __FUNCTION__);

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
            SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__, array($messageId, $spot));

			return ;
		} # if

		$this->_conn->modify("DELETE FROM spotsfull WHERE messageid IN (SELECT messageid FROM spots WHERE id > :id)",
            array(
                ':id' => array($spot['id'], PDO::PARAM_INT)
            ));
		$this->_conn->modify("DELETE FROM spots WHERE id > :id",
            array(
            ':id' => array($spot['id'], PDO::PARAM_INT)
        ));


        SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__, array($messageId, $spot));
	} # removeExtraSpots

	/**
	 * Add the posted spot to the database
     *
     * @param int $userId
     * @param array $spot
     * @param string $fullXml
     * @return void
	 */
	function addPostedSpot($userId, $spot, $fullXml) {
		SpotTiming::start(__CLASS__ . '::' . __FUNCTION__);

		$this->_conn->modify(
				"INSERT INTO spotsposted(ouruserid, messageid, stamp, title, tag, category, subcats, fullxml) 
					VALUES(:ouruserid, :newmessageid, :stamp, :title, :tag, :category, :subcats, :fullxml)",
            array(
                ':ouruserid' => array($userId, PDO::PARAM_INT),
                ':newmessageid' => array($spot['newmessageid'], PDO::PARAM_STR),
                ':stamp' => array(time(), PDO::PARAM_INT),
                ':title' => array($spot['title'], PDO::PARAM_STR),
                ':tag' => array($spot['tag'], PDO::PARAM_STR),
                ':category' => array($spot['category'], PDO::PARAM_INT),
                ':subcats' => array(implode(',', $spot['subcatlist']), PDO::PARAM_STR),
                ':fullxml' => array($fullXml, PDO::PARAM_STR)
            )
        );

		SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__, array($userId, $spot, $fullXml));
	} # addPostedSpot

	/**
	 * Removes items from te commentsfull table older than a specific amount of days
     *
     * @param int $expireDays Spots older than $expireDays are to be deleted
     * @return void
	 */
	function expireSpotsFull($expireDays) {
		SpotTiming::start(__CLASS__ . '::' . __FUNCTION__);

		$this->_conn->modify("DELETE FROM spotsfull WHERE messageid IN (SELECT messageid FROM spots WHERE stamp < :stamp)",
            array(
                ':stamp' => array(time() - ($expireDays*24*60*60), PDO::PARAM_INT)
            ));

		SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__, array($expireDays));
	} # expireSpotsFull

    /**
     * Makes sure a message has never been posted before or used before
     *
     * @param string $messageid Messageid to check if its unique
     * @return bool
     */
	function isNewSpotMessageIdUnique($messageid) {
		SpotTiming::start(__CLASS__ . '::' . __FUNCTION__);

		/* 
		 * We use a union between our own messageids and the messageids we already
		 * know to prevent a user from spamming the spotweb system by using existing
		 * but valid spots
		 */
		$tmpResult = $this->_conn->singleQuery("SELECT messageid FROM commentsposted WHERE messageid = :messageid1
												  UNION
											    SELECT messageid FROM spots WHERE messageid = :messageid2",
            array(
                ':messageid1' => array($messageid, PDO::PARAM_STR),
                ':messageid2' => array($messageid, PDO::PARAM_STR)
            ));
		SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__, array($messageid));
		
		return (empty($tmpResult));
	} # isNewSpotMessageIdUnique

	/**
	 * Returns the maximum timestamp of a spot in the database
     *
     * @return int
	 */
	function getMaxMessageTime() {
		SpotTiming::start(__CLASS__ . '::' . __FUNCTION__);

		$stamp = $this->_conn->singleQuery("SELECT MAX(stamp) AS stamp FROM spots");
		if ($stamp == null) {
			$stamp = time();
		} # if

		SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__, array($stamp));

		return $stamp;
	} # getMaxMessageTime()


    /**
     * Returns the highest messageid from server
     *
     * @param $headers string Which type of header to get the last messageids from
     * @throws Exception
     * @return array
     */
	function getMaxMessageId($headers) {
		SpotTiming::start(__CLASS__ . '::' . __FUNCTION__);

		if ($headers == 'headers') {
			$msgIds = $this->_conn->arrayQuery("SELECT messageid FROM spots ORDER BY id DESC LIMIT 5000");
		} elseif ($headers == 'comments') {
			$msgIds = $this->_conn->arrayQuery("SELECT messageid FROM commentsxover ORDER BY id DESC LIMIT 5000");
		} elseif ($headers == 'reports') {
			$msgIds = $this->_conn->arrayQuery("SELECT messageid FROM reportsxover ORDER BY id DESC LIMIT 5000");
		} else {
			throw new Exception("getLastMessageId() header-type value is unknown");
		} # else
		
		if ($msgIds == null) {
            SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__, array($headers));

			return array();
		} # if

		$tempMsgIdList = array();
		$msgIdCount = count($msgIds);
		for($i = 0; $i < $msgIdCount; $i++) {
			$tempMsgIdList['<' . $msgIds[$i]['messageid'] . '>'] = 1;
		} # for

		SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__, array($headers));

		return $tempMsgIdList;
	} # func. getLastMessageId


} # Dao_Base_Spot

