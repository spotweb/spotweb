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

		# bereid de lijst voor met de queries in de where
		$msgIdList = '';
		foreach($hdrList as $hdr) {
			$msgIdList .= "'" . substr($this->_conn->safe($hdr['Message-ID']), 1, -1) . "', ";
		} # foreach
		$msgIdList = substr($msgIdList, 0, -2);

		# en vraag alle comments op die we kennen
		$rs = $this->_conn->arrayQuery("SELECT messageid FROM reportsxover WHERE messageid IN (" . $msgIdList . ")");

		# geef hier een array terug die kant en klaar is voor array_search
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
		$this->_conn->beginTransaction();
		
		# Databases can have a maximum length of statements, so we 
		# split the amount of spots in chunks of 100
		$chunks = array_chunk($reportList, 100);
		foreach($chunks as $reportList) {
			$insertArray = array();
			
			foreach($reportList as $report) {
				$insertArray[] = vsprintf("('%s', '%s', '%s', '%s')",
						Array($this->_conn->safe($report['messageid']),
							  $this->_conn->safe($report['fromhdr']),
							  $this->_conn->safe($report['keyword']),
							  $this->_conn->safe($report['nntpref'])));
			} # foreach

			# Actually insert the batch
			$this->_conn->modify("INSERT INTO reportsxover(messageid, fromhdr, keyword, nntpref)
									VALUES " . implode(',', $insertArray), array());
		} # foreach

		$this->_conn->commit();
	} # addReportRefs

} # Dao_Base_SpotReport