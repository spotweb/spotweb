<?php

class Dao_Mysql_SpotStateList extends Dao_Base_SpotStateList {

	/*
	 * Add a specific state to a specific spot
	 */
	private function addToSpotStateList($list, $messageId, $ourUserId) {
		SpotTiming::start(__FUNCTION__);

		$stamp = time();
		$this->_conn->modify("INSERT INTO spotstatelist (messageid, ouruserid, " . $list . ") VALUES ('%s', %d, %d) ON DUPLICATE KEY UPDATE " . $list . " = %d",
								Array($messageId, (int) $ourUserId, $stamp, $stamp));

		SpotTiming::stop(__FUNCTION__, array($list, $messageId, $ourUserId, $stamp));
	} # addToSpotStateList

} # Dao_Mysql_SpotStateList
