<?php

class Dao_Base_SpotStateList implements Dao_SpotStateList {
	protected $_conn;

	/*
	 * constructs a new Dao_Base_SpotStateList object, 
	 * connection object is given
	 */
	public function __construct(dbeng_abs $conn) {
		$this->_conn = $conn;
	} # ctor

	/*
	 * Add a specific state to a specific spot
	 */
	private function addToSpotStateList($list, $messageId, $ourUserId) {
		SpotTiming::start(__CLASS__ . '::' . __FUNCTION__);

		$stamp = time();

		$this->_conn->exec("UPDATE spotstatelist SET " . $list . " = :stamp WHERE messageid = :messageid AND ouruserid = :ouruserid",
            array(
                ':stamp' => array($stamp, PDO::PARAM_INT),
                ':messageid' => array($messageId, PDO::PARAM_STR),
                ':ouruserid' => array($ourUserId, PDO::PARAM_INT)
            ));
		if ($this->_conn->rows() == 0) {
			$this->_conn->modify("INSERT INTO spotstatelist (messageid, ouruserid, " . $list . ") VALUES (:messageid, :ouruserid, :stamp)",
                array(
                    ':messageid' => array($messageId, PDO::PARAM_STR),
                    ':ouruserid' => array($ourUserId, PDO::PARAM_INT),
                    ':stamp' => array($stamp, PDO::PARAM_INT)
                ));
		} # if

		SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__, array($list, $messageId, $ourUserId, $stamp));
	} # addToSpotStateList
	
	/*
	 * Mark all as read can perform different functions, 
	 * depending on the state of the system. 
	 *
	 * If only 'seen' is kept, in the statelist, we just set
	 * seen to NULL to mark it as not explicitly seen.
	 *
	 * If either 'download' or 'watch' is also set, we update
	 * the seen timestamp, this allows us to show any new
	 * comments from the last time the spot was viewed
	 */
	function markAllAsRead($ourUserId) {
		SpotTiming::start(__CLASS__ . '::' . __FUNCTION__);
		$this->_conn->modify("UPDATE spotstatelist SET seen = NULL WHERE (ouruserid = :ouruserid) AND (download IS NULL) AND (watch IS NULL) ",
            array(
                ':ouruserid' => array($ourUserId, PDO::PARAM_INT)
            ));

		$this->_conn->modify("UPDATE spotstatelist SET seen = :stamp WHERE (ouruserid = :ouruserid) AND (download IS NOT NULL) OR (watch IS NOT NULL) ",
            array(
                ':stamp' => array(time(), PDO::PARAM_INT),
                ':ouruserid' => array($ourUserId, PDO::PARAM_INT)
            ));
		SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__, array($ourUserId));
	} # markAllAsRead

	/*
	 * Remove all downloads
	 */
	function clearDownloadList($ourUserId) {
		SpotTiming::start(__CLASS__ . '::' . __FUNCTION__);
		$this->_conn->modify("UPDATE spotstatelist SET download = NULL WHERE ouruserid = :ouruserid",
            array(
                ':ouruserid' => array($ourUserId, PDO::PARAM_INT)
            ));
		SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__, array($ourUserId));
	} # clearDownloadList

	/*
	 * Remove all entries from the spotstatelist where no actual
	 * information is stored anymore
	 */
	function cleanSpotStateList() {
		$this->_conn->rawExec("DELETE FROM spotstatelist WHERE download IS NULL AND watch IS NULL AND seen IS NULL");
	} # cleanSpotStateList

	/*
	 * Remove a Spot from the watchlist
	 */
	function removeFromWatchList($messageid, $ourUserId) {
		SpotTiming::start(__CLASS__ . '::' . __FUNCTION__);

		$this->_conn->modify("UPDATE spotstatelist SET watch = NULL WHERE messageid = :messageid AND ouruserid = :ouruserid LIMIT 1",
            array(
                ':messageid' => array($messageid, PDO::PARAM_STR),
                ':ouruserid' => array($ourUserId, PDO::PARAM_INT)
            ));

		SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__, array($messageid, $ourUserId));
	} # removeFromWatchList

	/*
	 * Add a spot to the watchlist
	 */
	function addToWatchList($messageid, $ourUserId) {
		$this->addToSpotStateList('watch', $messageid, $ourUserId);
	} # addToWatchList

	/*
	 * Add a spot to the seenlist
	 */
	function addToSeenList($messageid, $ourUserId) {
		$this->addToSpotStateList('seen', $messageid, $ourUserId);
	} # addToWatchList

	/*
	 * Add a spot to the download list
	 */
	function addToDownloadList($messageid, $ourUserId) {
		$this->addToSpotStateList('download', $messageid, $ourUserId);
	} # addToDownloadList
	
} # Dao_Base_SpotStateList
