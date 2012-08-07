<?php

class Dao_Mysql_UserFilterCount extends Dao_Base_UserFilterCount {

	/*
	 * Resets the unread count for a specific user
	 */
	function resetFilterCountForUser($userId) {
		$this->_conn->modify("UPDATE filtercounts f, filtercounts t
									SET f.lastvisitspotcount = f.currentspotcount,
										f.currentspotcount = t.currentspotcount
									WHERE (f.filterhash = t.filterhash) 
									  AND (t.userid = -1) 
									  AND (f.userid = %d)",
						Array((int) $userId) );
	} # resetFilterCountForUser

	/*
	 * Updates the last filtercounts for sessions which are active at the moment
	 */
	function updateCurrentFilterCounts() {
		/*
		 * We do this in two parts because MySQL seems to fall over 
		 * when we use a subquery
		 */
		$sessionList = $this->_conn->arrayQuery("SELECT s.userid FROM sessions s 
														   INNER JOIN filtercounts f ON (f.userid = s.userid) 
												 WHERE lasthit > f.lastupdate 
												 GROUP BY s.userid",
												 array());
		
		/*
		 * Update the current filter counts if the session
		 * is still active
		 */
		if (!empty($sessionList)) {
			$this->_conn->modify("UPDATE filtercounts f, filtercounts t 
									SET f.currentspotcount = t.currentspotcount,
										f.lastupdate = t.lastupdate
									WHERE (f.filterhash = t.filterhash) 
									  AND (t.userid = -1)
									  AND (f.userid IN (" . $this->_conn->arrayValToIn($sessionList, 'userid') . "))");
		} # if

		/*
		 * Sometimes retrieve removes some sports, make sure
		 * we do not get confusing results
		 */
		$this->_conn->modify("UPDATE filtercounts f, filtercounts t
								SET f.lastvisitspotcount = t.currentspotcount
								WHERE (f.filterhash = t.filterhash) 
								  AND (f.lastvisitspotcount > t.currentspotcount)
								  AND (t.userid = -1)");
	} # updateCurrentFilterCounts

	/*
	 * Mark all filters as read
	 */
	function markFilterCountAsSeen($userId) {
		 $this->_conn->modify("UPDATE filtercounts f, filtercounts t
								SET f.lastvisitspotcount = t.currentspotcount,
									f.currentspotcount = t.currentspotcount,
									f.lastupdate = t.lastupdate
								WHERE (f.filterhash = t.filterhash) 
								  AND (t.userid = -1) 
								  AND (f.userid = %d)",
					Array( (int) $userId) );
	} # markFilterCountAsSeen

	
} # Dao_Mysql_UserFilterCount
