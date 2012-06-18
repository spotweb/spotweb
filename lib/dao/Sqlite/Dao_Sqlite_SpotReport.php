<?php

class Dao_Sqlite_SpotReport extends Dao_Base_SpotReport {

	/*
	 * Insert addReportRef, 
	 *   messageid is the actual report messageid
	 *   nntpref is the messageid of the spot
	 */
	function addReportRefs($reportList) {
		$this->beginTransaction();
		
		# Databases can have a maximum length of statements, so we 
		# split the amount of spots in chunks of 100
		$chunks = array_chunk($reportList, 1);

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

		$this->commitTransaction();
	} # addReportRefs


} # Dao_Base_SpotReport

