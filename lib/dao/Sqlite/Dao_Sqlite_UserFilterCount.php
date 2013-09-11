<?php

class Dao_Sqlite_UserFilterCount extends Dao_Base_UserFilterCount {

	/*
	 * Mark all filters as read
	 */
	function markFilterCountAsSeen($userId) {
		$filterList = $this->_conn->arrayQuery("SELECT currentspotcount, lastupdate, filterhash FROM filtercounts WHERE userid = -1");

		foreach($filterList as $filter) {
			$this->_conn->modify("UPDATE filtercounts
										SET lastvisitspotcount = :lastvisitspotcount,
											currentspotcount = :currentspotcount,
											lastupdate = :lastupdate
										WHERE (filterhash = :filterhash)
										  AND (userid = :userid)",
                array(
                    'lastvisitspotcount' => array($filter['currentspotcount'], PDO::PARAM_INT),
                    'currentspotcount' => array($filter['currentspotcount'], PDO::PARAM_INT),
                    'lastupdate' => array($filter['lastupdate'], PDO::PARAM_INT),
                    'filterhash' => array($filter['filterhash'], PDO::PARAM_STR),
                    'userid' => array($userId, PDO::PARAM_INT)
                ));
		} # foreach
	} # markFilterCountAsSeen

} # Dao_Sqlite_UserFilterCount
