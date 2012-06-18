<?php

class Dao_Sqlite_UserFilterCount extends Dao_Base_UserFilterCount {

	/*
	 * Mark all filters as read
	 */
	function markFilterCountAsSeen($userId) {
		$filterList = $this->_conn->arrayQuery("SELECT currentspotcount, lastupdate, filterhash FROM filtercounts WHERE userid = -1", array());
		foreach($filterList as $filter) {
			$this->_conn->modify("UPDATE filtercounts
										SET lastvisitspotcount = %d,
											currentspotcount = %d,
											lastupdate = %d
										WHERE (filterhash = '%s') 
										  AND (userid = %d)",
							Array((int) $filter['currentspotcount'],
								  (int) $filter['currentspotcount'],
								  (int) $filter['lastupdate'],
								  $filter['filterhash'], 
								  (int) $userId));
		} # foreach
	} # markFilterCountAsSeen

} # Dao_Sqlite_UserFilterCount
