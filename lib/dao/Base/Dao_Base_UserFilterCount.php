<?php

class Dao_Base_UserFilterCount implements Dao_UserFilterCount {
	protected $_conn;

	/*
	 * constructs a new Dao_Base_UserFilterCount object, 
	 * connection object is given
	 */
	public function __construct($conn) {
		$this->_conn = $conn;
	} # ctor

	/*
	 * Add a filter count for a specific SHA1 hash
	 * of a filter for this specific user
	 */
	function setCachedFilterCount($userId, $filterHashes) {
		$maxSpotStamp = $this->_conn->singleQuery("SELECT MAX(stamp) AS stamp FROM spots");
		if ($maxSpotStamp == null) {
			$maxSpotStamp = time();
		} # if
		
		foreach($filterHashes as $filterHash => $filterCount) {
			/* Remove any existing cached filtercount for this user */		
			$this->_conn->modify("DELETE FROM filtercounts WHERE (userid = %d) AND (filterhash = '%s')",
									Array((int) $userId, $filterHash));
									
			/* and insert our new filtercount hash */
			$this->_conn->modify("INSERT INTO filtercounts(userid, filterhash, currentspotcount, lastvisitspotcount, lastupdate) 
											VALUES(%d, '%s', %d, %d, %d)",
									Array((int) $userId, $filterHash, $filterCount['currentspotcount'], $filterCount['lastvisitspotcount'], 
										  $maxSpotStamp ));
		} # foreach
	} # setCachedFilterCount

	/*
	 * Add a filter count for a specific SHA1 hash
	 * of a filter for this specific user
	 */
	function getNewCountForFilters($userId) {
		$filterHashes = array();
		$tmp = $this->_conn->arrayQuery("SELECT f.filterhash AS filterhash, 
												f.currentspotcount AS currentspotcount, 
												f.lastvisitspotcount AS lastvisitspotcount, 
												f.lastupdate AS lastupdate,
												t.currentspotcount - f.lastvisitspotcount AS newspotcount
										 FROM filtercounts f
										 INNER JOIN filtercounts t ON (t.filterhash = f.filterhash)
										 WHERE t.userid = -1 
										   AND f.userid = %d",
								Array((int) $userId) );
								
		foreach($tmp as $cachedItem) {
			$filterHashes[$cachedItem['filterhash']] = array('currentspotcount' => $cachedItem['currentspotcount'],
															 'lastvisitspotcount' => $cachedItem['lastvisitspotcount'],
															 'newspotcount' => $cachedItem['newspotcount'],
															 'lastupdate' => $cachedItem['lastupdate']);
		} # foreach
		
		return $filterHashes;
	} # getNewCountForFilters
	
	/*
	 * Makes sure all registered users have at least counts
	 * for all existing filters.
	 */
	function createFilterCountsForEveryone() {
		$userIdList = $this->_conn->arrayQuery('SELECT id FROM users WHERE id <> -1');
		
		foreach($userIdList as $user) {
			$userId = $user['id'];

			/* Get the list of filters */
			$filterList = $this->_conn->arrayQuery("SELECT id,
														  userid,
														  filtertype,
														  title,
														  icon,
														  torder,
														  tparent,
														  tree,
														  valuelist,
														  sorton,
														  sortorder,
														  enablenotify 
													FROM filters 
													WHERE userid = %d
													ORDER BY tparent, torder", Array($userId));

			/* We can assume userid -1 (baseline) has all the filters which exist */
			$cachedList = $this->getCachedFilterCount($userId);

			/* We add a dummy entry for 'all new spots' */
			$filterList[] = array('id' => 9999, 'userid' => $userId, 'filtertype' => 'dummyfilter', 
								'title' => 'NewSpots', 'icon' => '', 'torder' => 0, 'tparent' => 0,
								'tree' => '', 'valuelist' => 'New:0', 'sorton' => '', 'sortorder' => '',
								'enablenotify' => false);
			
			foreach($filterList as $filter) {
				$filterHash = sha1($filter['tree'] . '|' . urldecode($filter['valuelist']));
				
				# Do we have a cache entry already for this filter?
				if (!isset($cachedList[$filterHash])) {
					/*
					 * Create the cached count filter
					 */
					$filter['currentspotcount'] = 0;
					$filter['lastvisitspotcount'] = 0;
					
					$this->setCachedFilterCount($userId, array($filterHash => $filter));
				} # if
			} # foreach
		} # foreach
	} # createFilterCountsForEveryone
	
	/*
	 * Retrieves the filtercount for a specific userid
	 */
	function getCachedFilterCount($userId) {
		$filterHashes = array();
		$tmp = $this->_conn->arrayQuery("SELECT filterhash, currentspotcount, lastvisitspotcount, lastupdate FROM filtercounts WHERE userid = %d",
								Array( (int) $userId) );
		
		foreach($tmp as $cachedItem) {
			$filterHashes[$cachedItem['filterhash']] = array('currentspotcount' => $cachedItem['currentspotcount'],
															 'lastvisitspotcount' => $cachedItem['lastvisitspotcount'],
															 'lastupdate' => $cachedItem['lastupdate']);
		} # foreach

		return $filterHashes;
	} # getCachedFilterCount
	
	/*
	 * Resets the unread count for a specific user
	 */
	function resetFilterCountForUser($userId) {
		$filterList = $this->_conn->arrayQuery("SELECT currentspotcount, filterhash FROM filtercounts WHERE userid = -1", array());
		foreach($filterList as $filter) {
			$this->_conn->modify("UPDATE filtercounts
										SET lastvisitspotcount = currentspotcount,
											currentspotcount = %d
										WHERE (filterhash = '%s') 
										  AND (userid = %d)",
							Array((int) $filter['currentspotcount'], $filter['filterhash'], (int) $userId));
		} # foreach
	} # resetFilterCountForUser

	/*
	 * Updates the last filtercounts for sessions which are active at the moment
	 */
	function updateCurrentFilterCounts() {
		/*
		 * Update the current filter counts if the session
		 * is still active
		 */
		$filterList = $this->_conn->arrayQuery("SELECT currentspotcount, lastupdate, filterhash FROM filtercounts WHERE userid = -1", array());
		foreach($filterList as $filter) {
			$this->_conn->modify("UPDATE filtercounts
										SET currentspotcount = %d,
											lastupdate = %d
										WHERE (filterhash = '%s') 
										  AND (userid IN (SELECT userid FROM sessions WHERE lasthit > lastupdate GROUP BY userid ))",
							Array((int) $filter['currentspotcount'], (int) $filter['lastupdate'], $filter['filterhash']));
		} # foreach
	} # updateCurrentFilterCounts

	/*
	 * Mark all filters as read
	 */
	function markFilterCountAsSeen($userId) {
		throw new NotImplementedException();
	} # markFilterCountAsSeen

} # Dao_Base_UserFilterCount
