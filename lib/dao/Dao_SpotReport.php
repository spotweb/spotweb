<?php

interface Dao_SpotReport {

	function removeExtraReports($messageId);
	function matchReportMessageIds($hdrList);
	function addReportRefs($reportList);
	
} # Dao_SpotReport
