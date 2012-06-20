<?php

class Dao_Base_SpotReport implements Dao_SpotReport {
	protected $_conn;

	/*
	 * constructs a new Dao_Base_Audit object, 
	 * connection object is given
	 */
	public function __construct($conn) {
		$this->_conn = $conn;
	} # ctor

	/*
	 * Remove extra comments
	 */
	function removeExtraReports($messageId) {
		# vraag eerst het id op
		$reportId = $this->_conn->singleQuery("SELECT id FROM reportsxover WHERE messageid = '%s'", Array($messageId));
		
		# als deze report leeg is, is er iets raars aan de hand
		if (empty($reportId)) {
			throw new Exception("Our highest report is not in the database!?");
		} # if

		# en wis nu alles wat 'jonger' is dan deze spot
		$this->_conn->modify("DELETE FROM reportsxover WHERE id > %d", Array($reportId));
	} # removeExtraReports
	

	/*
	 * Match set of reports
	 */
	function matchReportMessageIds($hdrList) {
		$idList = array();

		# geen message id's gegeven? vraag het niet eens aan de db
		if (count($hdrList) == 0) {
			return $idList;
		} # if

		# en vraag alle comments op die we kennen
		$rs = $this->_conn->arrayQuery("SELECT messageid FROM reportsxover WHERE messageid IN (" . $this->_conn->arrayValToInOffset($hdrList, 'Message-ID', 1, -1) . ")");

		# geef hier een array terug die kant en klaar is voor isset()
		foreach($rs as $msgids) {
			$idList[$msgids['messageid']] = 1;
		} # foreach
		
		return $idList;
	} # matchReportMessageIds

	/*
	 * Insert addReportRef, 
	 *   messageid is the actual report messageid
	 *   nntpref is the messageid of the spot
	 */
	function addReportRefs($reportList) {
		$this->_conn->batchInsert($reportList,
								  "INSERT INTO reportsxover(messageid, fromhdr, keyword, nntpref) VALUES",
								  "('%s', '%s', '%s', '%s')",
								  Array('messageid', 'fromhdr', 'keyword', 'nntpref')
								  );
	} # addReportRefs

	/*
	 * Check whether a user already ceated an report for a specific spot
	 */
	function isReportPlaced($messageid, $userId) {
		$tmpResult = $this->_conn->singleQuery("SELECT messageid FROM reportsposted WHERE inreplyto = '%s' AND ouruserid = %d", Array($messageid, $userId));
		
		return (!empty($tmpResult));
	} # isReportPlaced
	
	/* 
	 * Makes sure the messageid for this report hasn't been already used to post
	 */
	function isReportMessageIdUnique($messageid) {
		$tmpResult = $this->_conn->singleQuery("SELECT messageid FROM reportsposted WHERE messageid = '%s'",
						Array($messageid));
		
		return (empty($tmpResult));
	} # isReportMessageIdUnique

	
	/*
	 * Saves the posted report to our database
	 */
	function addPostedReport($userId, $report) {
		$this->_conn->modify(
				"INSERT INTO reportsposted(ouruserid, messageid, inreplyto, randompart, body, stamp)
					VALUES('%d', '%s', '%s', '%s', '%s', %d)", 
				Array((int) $userId,
					  $report['newmessageid'],
					  $report['inreplyto'],
					  $report['randomstr'],
					  $report['body'],
					  (int) time()));
	} # addPostedReport

} # Dao_Base_SpotReport
