<?php

interface Dao_SpotReport {

	function removeExtraReports($messageId);
	function matchReportMessageIds($hdrList);
	function addReportRefs($reportList);
	function isReportPlaced($messageid, $userId);
	function isReportMessageIdUnique($messageid);
	function addPostedReport($userId, $report);


} # Dao_SpotReport
