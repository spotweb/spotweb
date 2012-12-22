<?php

class Dao_Postgresql_UserFilterCount extends Dao_Base_UserFilterCount {


	/*
	 * Mark all filters as read
	 */
	function markFilterCountAsSeen($userId) {
		$this->_conn->modify("UPDATE filtercounts AS f
									SET lastvisitspotcount = o.currentspotcount,
										currentspotcount = o.currentspotcount,
										lastupdate = o.lastupdate
									FROM filtercounts AS o
									WHERE (f.filterhash = o.filterhash) 
									  AND (f.userid = %d) AND (o.userid = %d)",
						Array((int) $userId, (int) $userId));
	} # markFilterCountAsSeen

} # Dao_Postgresql_UserFilterCount
