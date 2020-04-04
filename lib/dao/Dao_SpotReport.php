<?php

interface Dao_SpotReport
{
    public function removeExtraReports($messageId);

    public function matchReportMessageIds($hdrList);

    public function addReportRefs($reportList);

    public function isReportPlaced($messageid, $userId);

    public function isReportMessageIdUnique($messageid);

    public function addPostedReport($userId, $report);
} // Dao_SpotReport
