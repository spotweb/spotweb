<?php

class Dao_Base_Spot implements Dao_Spot {
	protected $_conn;

	/*
	 * constructs a new Dao_Base_Spot object, 
	 * connection object is given
	 */
	public function __construct($conn) {
		$this->_conn = $conn;
	} # ctor

	/*
	 * Returns the spots in the database which match the 
	 * restrictions of $parsedSearch
	 */
	function getSpots($ourUserId, $pageNr, $limit, $parsedSearch) {
		SpotTiming::start(__FUNCTION__);
		$results = array();
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
		 * Run the query with a limit always increased by one. this allows us to 
		 * check whether any more results are available
		 */
 		$tmpResult = $this->_conn->arrayQuery("SELECT s.id AS id,
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
												f.verified AS verified,
												COALESCE(bl.idtype, wl.idtype, gwl.idtype) AS idtype
												" . $extendedFieldList . "
									 FROM spots AS s " . 
									 $additionalTableList . 
									 $additionalJoinList . 
								   " LEFT JOIN spotstatelist AS l on ((s.messageid = l.messageid) AND (l.ouruserid = " . $this->_conn->safe( (int) $ourUserId) . ")) 
									 LEFT JOIN spotsfull AS f ON (s.messageid = f.messageid) 
									 LEFT JOIN spotteridblacklist as bl ON ((bl.spotterid = s.spotterid) AND ((bl.ouruserid = " . $this->_conn->safe( (int) $ourUserId) . ") OR (bl.ouruserid = -1)) AND (bl.idtype = 1))
									 LEFT JOIN spotteridblacklist as wl on ((wl.spotterid = s.spotterid) AND ((wl.ouruserid = " . $this->_conn->safe( (int) $ourUserId) . ")) AND (wl.idtype = 2)) 
									 LEFT JOIN spotteridblacklist as gwl on ((gwl.spotterid = s.spotterid) AND ((gwl.ouruserid = -1)) AND (gwl.idtype = 2)) " .
									 $criteriaFilter . " 
									 ORDER BY " . $sortList . 
								   " LIMIT " . (int) ($limit + 1) ." OFFSET " . (int) $offset);

		/* 
		 * Did we get more results than originally asked? Remove the last element
		 * and set the flag we have gotten more results than originally asked for
		 */
		$hasMore = (count($tmpResult) > $limit);
		if ($hasMore) {
			# remove the last element
			array_pop($tmpResult);
		} # if

		SpotTiming::stop(__FUNCTION__, array($ourUserId, $pageNr, $limit, $criteriaFilter));
		return array('list' => $tmpResult, 'hasmore' => $hasMore);
	} # getSpots()

	/*
	 * Returns the header information of a spot
	 */
	function getSpotHeader($msgId) {
		SpotTiming::start(__FUNCTION__);
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
											  WHERE s.messageid = '%s'", Array($msgId));
		SpotTiming::stop(__FUNCTION__);

		if (empty($tmpArray)) {
			return ;
		} # if

		return $tmpArray[0];
	} # getSpotHeader 

	/*
	 * Retrieves one specific, full spot. When either the header or te full
	 * spot is not in the database, this function returns NULL
	 */
	function getFullSpot($messageId, $ourUserId) {
		SpotTiming::start('SpotDb::' . __FUNCTION__);
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
												LEFT JOIN spotstatelist AS l on ((s.messageid = l.messageid) AND (l.ouruserid = " . $this->_conn->safe( (int) $ourUserId) . "))
												LEFT JOIN spotteridblacklist as bl ON ((bl.spotterid = s.spotterid) AND ((bl.ouruserid = " . $this->_conn->safe( (int) $ourUserId) . ") OR (bl.ouruserid = -1)) AND (bl.idtype = 1))
												LEFT JOIN spotteridblacklist as wl on ((wl.spotterid = s.spotterid) AND ((wl.ouruserid = " . $this->_conn->safe( (int) $ourUserId) . ")) AND (wl.idtype = 2)) 
												LEFT JOIN spotteridblacklist as gwl on ((gwl.spotterid = s.spotterid) AND ((gwl.ouruserid = -1)) AND (gwl.idtype = 2))
												JOIN spotsfull AS f ON f.messageid = s.messageid
										  WHERE s.messageid = '%s'", Array($messageId));
		if (empty($tmpArray)) {
			return ;
		} # if
		$tmpArray = $tmpArray[0];

		# If spot is fully stored in db and is of the new type, we process it to
		# make it exactly the same as when retrieved using NNTP
		if (!empty($tmpArray['fullxml']) && (!empty($tmpArray['user-signature']))) {
			$tmpArray['user-key'] = unserialize(base64_decode($tmpArray['user-key']));
		} # if

		SpotTiming::stop('SpotDb::' . __FUNCTION__, array($messageId, $ourUserId));
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

		SpotTiming::start(__FUNCTION__);
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
		SpotTiming::stop(__FUNCTION__, array($spotMsgIdList));
	} # updateSpotRating

	/*
	 * Updates the commentcount for a specific list of spots
	 */
	function updateSpotCommentCount($spotMsgIdList) {
		# Empty list provided? Exit
		if (count($spotMsgIdList) == 0) {
			return;
		} # if

		SpotTiming::start(__FUNCTION__);
		$this->_conn->modify("UPDATE spots 
								SET commentcount = 
									(SELECT COUNT(1) as commentcount 
									 FROM commentsxover 
									 WHERE 
										spots.messageid = commentsxover.nntpref 
									 GROUP BY nntpref)
							WHERE spots.messageid IN (" . $this->_conn->arrayKeyToIn($spotMsgIdList) . ")
						");
		SpotTiming::stop(__FUNCTION__, array($spotMsgIdList));
	} # updateSpotCommentCount

	/*
	 * Updates the reportcount for a specific list of spots
	 */
	function updateSpotReportCount($spotMsgIdList) {
		# Empty list provided? Exit
		if (count($spotMsgIdList) == 0) {
			return;
		} # if

		SpotTiming::start(__FUNCTION__);
		$this->_conn->modify("UPDATE spots 
								SET reportcount = 
									(SELECT COUNT(1) as reportcount 
									 FROM reportsxover
									 WHERE 
										spots.messageid = reportsxover.nntpref 
									 GROUP BY nntpref)
							WHERE spots.messageid IN (" . $this->_conn->arrayKeyToIn($spotMsgIdList) . ")
						");
		SpotTiming::stop(__FUNCTION__, array($spotMsgIdList));
	} # updateSpotReportCount

	/*
	 * Remove a spot from the database
	 */
	function removeSpots($spotMsgIdList) {
		# Empty list provided? Exit
		if (count($spotMsgIdList) == 0) {
			return;
		} # if

		SpotTiming::start(__FUNCTION__);

		# prepare a list of IN values
		$msgIdList = $this->_conn->arrayKeyToIn($spotMsgIdList);

		$this->_conn->modify("DELETE FROM spots WHERE messageid IN (" . $msgIdList . ")");
		$this->_conn->modify("DELETE FROM spotsfull WHERE messageid  IN (" . $msgIdList . ")");
		$this->_conn->modify("DELETE FROM commentsfull WHERE messageid IN (SELECT messageid FROM commentsxover WHERE nntpref IN (" . $msgIdList . "))");
		$this->_conn->modify("DELETE FROM commentsxover WHERE nntpref  IN (" . $msgIdList . ")");
		$this->_conn->modify("DELETE FROM spotstatelist WHERE messageid  IN (" . $msgIdList . ")");
		$this->_conn->modify("DELETE FROM reportsxover WHERE nntpref  IN (" . $msgIdList . ")");
		$this->_conn->modify("DELETE FROM reportsposted WHERE inreplyto  IN (" . $msgIdList . ")");
		$this->_conn->modify("DELETE FROM cache WHERE resourceid  IN (" . $msgIdList . ")");
		SpotTiming::stop(__FUNCTION__, array($spotMsgIdList));
	} # removeSpots

	/*
	 * Mark a spot in the database as moderated
	 */
	function markSpotsModerated($spotMsgIdList) {
		# Empty list provided? Exit
		if (count($spotMsgIdList) == 0) {
			return;
		} # if

		SpotTiming::start(__FUNCTION__);
		$this->_conn->modify("UPDATE spots SET moderated = '%s' WHERE messageid IN (" . 
								$this->_conn->arrayKeyToIn($spotMsgIdList) . ")", Array($this->_conn->bool2dt(true)));
		SpotTiming::stop(__FUNCTION__, array($spotMsgIdList));
	} # markSpotsModerated

	/*
	 * Remove older spots from the database
	 */
	function deleteSpotsRetention($retention) {
		SpotTiming::start(__FUNCTION__);
		$retention = $retention * 24 * 60 * 60; // omzetten in seconden

		$this->_conn->modify("DELETE FROM spots WHERE spots.stamp < " . (time() - $retention) );
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
		$this->_conn->modify("DELETE FROM cache WHERE (cache.cachetype = %d OR cache.cachetype = %d) AND cache.resourceid not in 
							(SELECT messageid FROM spots)", Array(SpotCache::SpotImage, SpotCache::SpotNzb)) ;
		SpotTiming::stop(__FUNCTION__, array($retention));
	} # deleteSpotsRetention

	/*
	 * Add a lis tof spots to the database
	 */
	function addSpots($spots, $fullSpots = array()) {
		SpotTiming::start(__FUNCTION__);
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
			 * Cut off some strngs to a maximum value as defined in the
			 * database. We don't cut off the unique keys as we rather
			 * have Spotweb error out than corrupt it
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
		} # foreach

		$this->_conn->batchInsert($spots,
								  "INSERT INTO spots(messageid, poster, title, tag, category, subcata, 
														subcatb, subcatc, subcatd, subcatz, stamp, reversestamp, filesize, spotterid) 
									VALUES",
								  "('%s', '%s', '%s', '%s', %d, '%s', '%s', '%s', '%s', '%s', %d, %d, '%s', '%s')",
								  Array('messageid', 'poster', 'title', 'tag', 'category', 'subcata', 'subcatb', 'subcatc',
								  		'subcatd', 'subcatz', 'stamp', 'reversestamp', 'filesize', 'spotterid')
								  );

		if (!empty($fullSpots)) {
			$this->addFullSpots($fullSpots);
		} # if

		SpotTiming::stop(__FUNCTION__, array($spots, $fullSpots));
	} # addSpot()

	/*
	 * Update the spots table with some information contained in the 
	 * fullspots information. 
	 *
	 * Some information in the fullspot is more reliable because 
	 * more fidelity encoding.
	 */
	function updateSpotInfoFromFull($fullSpot) {
		SpotTiming::start(__FUNCTION__);
		$this->_conn->modify("UPDATE spots SET title = '%s', spotterid = '%s' WHERE messageid = '%s'",
							Array($fullSpot['title'], $fullSpot['spotterid'], $fullSpot['messageid']));
		SpotTiming::stop(__FUNCTION__, array($fullSpot));
	} # updateSpotInfoFromFull

	/*
	 * adds a list of fullspots to the database. Don't use this without having an entry in the header
	 * table as it will remove the spot from the list
	 */
	function addFullSpots($fullSpots) {
		SpotTiming::start(__FUNCTION__);

		/* 
		 * Prepare the array for insertion
		 */
		foreach($fullSpots as &$fullSpot) {
			$fullSpot['verified'] = $this->_conn->bool2dt($fullSpot['verified']);
			$fullSpot['user-key'] = base64_encode(serialize($fullSpot['user-key']));
		} # foreach

		$this->_conn->batchInsert($fullSpots,
								  "INSERT INTO spotsfull(messageid, verified, usersignature, userkey, xmlsignature, fullxml)
								  	VALUES",
								  "('%s', '%s', '%s', '%s', '%s', '%s')",
								  Array('messageid', 'verified', 'user-signature', 'user-key', 'xml-signature', 'fullxml')
								  );

		SpotTiming::stop(__FUNCTION__, array($fullSpots));
	} # addFullSpot

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

		SpotTiming::start(__FUNCTION__);

		# Prepare a list of values
		$msgIdList = $this->_conn->arrayValToInOffset($hdrList, 'Message-ID', 1, -1);

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
		SpotTiming::stop(__FUNCTION__, array($hdrList, $idList));

		return $idList;
	} # matchMessageIds 

	/**
	 * Returns the amount of spots currently in the database
	 */
	function getSpotCount($sqlFilter) {
		SpotTiming::start(__FUNCTION__);
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
		SpotTiming::stop(__FUNCTION__, array($sqlFilter));
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

	/*
	 * Returns the amount of spots per category
	 */
	function getSpotCountPerCategory($limit) {
		$filter = ($limit) ? "WHERE stamp > " . strtotime("-1 " . $limit) : '';
		return $this->_conn->arrayQuery("SELECT category AS data, COUNT(category) AS amount FROM spots " . $filter . " GROUP BY data;");
	} # getSpotCountPerCategory

	/*
	 * Remove extra spots 
	 */
	function removeExtraSpots($messageId) {
		SpotTiming::start(__FUNCTION__);

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

		$this->_conn->modify("DELETE FROM spotsfull WHERE messageid IN (SELECT messageid FROM spots WHERE id > %d)", Array($spot['id']));
		$this->_conn->modify("DELETE FROM spots WHERE id > %d", Array($spot['id']));

		SpotTiming::stop(__FUNCTION__, array($messageid, $spot));
	} # removeExtraSpots

	/*
	 * Add the posted spot to the database
	 */
	function addPostedSpot($userId, $spot, $fullXml) {
		SpotTiming::start(__FUNCTION__);

		$this->_conn->modify(
				"INSERT INTO spotsposted(ouruserid, messageid, stamp, title, tag, category, subcats, fullxml) 
					VALUES(%d, '%s', %d, '%s', '%s', %d, '%s', '%s')", 
				Array((int) $userId,
					  $spot['newmessageid'],
					  (int) time(),
					  $spot['title'],
					  $spot['tag'],
					  (int) $spot['category'],
					  implode(',', $spot['subcatlist']),
					  $fullXml));

		SpotTiming::stop(__FUNCTION__, array($userId, $spot, $fullXml));
	} # addPostedSpot

	/*
	 * Removes items from te commentsfull table older than a specific amount of days
	 */
	function expireSpotsFull($expireDays) {
		SpotTiming::start(__FUNCTION__);

		$this->_conn->modify("DELETE FROM spotsfull WHERE messageid IN (SELECT messageid FROM spots WHERE stamp < %d)", Array((int) time() - ($expireDays*24*60*60)));

		SpotTiming::stop(__FUNCTION__, array($expireDays));
	} # expireSpotsFull

	/* 
	 * Makes sure a message has never been posted before or used before
	 */
	function isNewSpotMessageIdUnique($messageid) {
		SpotTiming::start(__FUNCTION__);

		/* 
		 * We use a union between our own messageids and the messageids we already
		 * know to prevent a user from spamming the spotweb system by using existing
		 * but valid spots
		 */
		$tmpResult = $this->_conn->singleQuery("SELECT messageid FROM commentsposted WHERE messageid = '%s'
												  UNION
											    SELECT messageid FROM spots WHERE messageid = '%s'",
						Array($messageid, $messageid));

		SpotTiming::stop(__FUNCTION__, array($messageid));
		
		return (empty($tmpResult));
	} # isNewSpotMessageIdUnique

	/*
	 * Returns the maximum timestamp of a spot in the database
	 */
	function getMaxMessageTime() {
		SpotTiming::start(__FUNCTION__);

		$stamp = $this->_conn->singleQuery("SELECT MAX(stamp) AS stamp FROM spots");
		if ($stamp == null) {
			$stamp = time();
		} # if

		SpotTiming::stop(__FUNCTION__, array($stamp));

		return $stamp;
	} # getMaxMessageTime()


	/* 
	 * Returns the highest messageid from server 
	 */
	function getMaxMessageId($headers) {
		SpotTiming::start(__FUNCTION__);

		if ($headers == 'headers') {
			$msgIds = $this->_conn->arrayQuery("SELECT messageid FROM spots ORDER BY id DESC LIMIT 5000");
		} elseif ($headers == 'comments') {
			$msgIds = $this->_conn->arrayQuery("SELECT messageid FROM commentsxover ORDER BY id DESC LIMIT 5000");
		} elseif ($headers == 'reports') {
			$msgIds = $this->_conn->arrayQuery("SELECT messageid FROM reportsxover ORDER BY id DESC LIMIT 5000");
		} else {
			throw new Exception("getMaxMessageId() header-type value is unknown");
		} # else
		
		if ($msgIds == null) {
			return array();
		} # if

		$tempMsgIdList = array();
		$msgIdCount = count($msgIds);
		for($i = 0; $i < $msgIdCount; $i++) {
			$tempMsgIdList['<' . $msgIds[$i]['messageid'] . '>'] = 1;
		} # for

		SpotTiming::stop(__FUNCTION__, array($headers));

		return $tempMsgIdList;
	} # func. getMaxMessageId


} # Dao_Base_Spot

