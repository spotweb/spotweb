<?php

class Dao_Base_SpotReport implements Dao_SpotReport
{
    protected $_conn;

    /*
     * constructs a new Dao_Base_Audit object,
     * connection object is given
     */
    public function __construct(dbeng_abs $conn)
    {
        $this->_conn = $conn;
    }

    // ctor

    /*
     * Remove extra comments
     */
    public function removeExtraReports($messageId)
    {
        // vraag eerst het id op
        $reportId = $this->_conn->singleQuery(
            'SELECT id FROM reportsxover WHERE messageid = :messageid',
            [
                ':messageid' => [$messageId, PDO::PARAM_STR],
            ]
        );

        // als deze report leeg is, is er iets raars aan de hand
        if (empty($reportId)) {
            throw new Exception('Our highest report is not in the database!?');
        } // if

        // en wis nu alles wat 'jonger' is dan deze spot
        $this->_conn->modify(
            'DELETE FROM reportsxover WHERE id > :id',
            [
                ':id' => [$reportId, PDO::PARAM_INT],
            ]
        );
    }

    // removeExtraReports

    /*
     * Match set of reports
     */
    public function matchReportMessageIds($hdrList)
    {
        $idList = [];

        // geen message id's gegeven? vraag het niet eens aan de db
        if (count($hdrList) == 0) {
            return $idList;
        } // if

        // en vraag alle comments op die we kennen
        $rs = $this->_conn->arrayQuery('SELECT messageid FROM reportsxover WHERE messageid IN ('.$this->_conn->arrayValToIn($hdrList, 'Message-ID').')');

        // geef hier een array terug die kant en klaar is voor isset()
        foreach ($rs as $msgids) {
            $idList[$msgids['messageid']] = 1;
        } // foreach

        return $idList;
    }

    // matchReportMessageIds

    /*
     * Insert addReportRef,
     *   messageid is the actual report messageid
     *   nntpref is the messageid of the spot
     */
    public function addReportRefs($reportList)
    {
        $this->_conn->batchInsert(
            $reportList,
            'INSERT INTO reportsxover(messageid, fromhdr, keyword, nntpref) VALUES',
            [PDO::PARAM_STR, PDO::PARAM_STR, PDO::PARAM_STR, PDO::PARAM_STR],
            ['messageid', 'fromhdr', 'keyword', 'nntpref']
        );
    }

    // addReportRefs

    /*
     * Check whether a user already ceated an report for a specific spot
     */
    public function isReportPlaced($messageid, $userId)
    {
        $tmpResult = $this->_conn->singleQuery(
            'SELECT messageid FROM reportsposted WHERE inreplyto = :inreplyto AND ouruserid = :ouruserid',
            [
                'inreplyto' => [$messageid, PDO::PARAM_STR],
                'ouruserid' => [$userId, PDO::PARAM_INT],
            ]
        );

        return !empty($tmpResult);
    }

    // isReportPlaced

    /*
     * Makes sure the messageid for this report hasn't been already used to post
     */
    public function isReportMessageIdUnique($messageid)
    {
        $tmpResult = $this->_conn->singleQuery(
            'SELECT messageid FROM reportsposted WHERE messageid = :messageid',
            [
                'messageid' => [$messageid, PDO::PARAM_STR],
            ]
        );

        return empty($tmpResult);
    }

    // isReportMessageIdUnique

    /*
     * Saves the posted report to our database
     */
    public function addPostedReport($userId, $report)
    {
        $this->_conn->modify(
            'INSERT INTO reportsposted(ouruserid, messageid, inreplyto, randompart, body, stamp)
					VALUES(:ouruserid, :messageid, :inreplyto, :randompart, :body, :stamp)',
            [
                ':ouruserid' => [$userId, PDO::PARAM_INT],
                ':messageid' => [$report['newmessageid'], PDO::PARAM_STR],
                ':inreplyto' => [$report['inreplyto'], PDO::PARAM_STR],
                ':randomstr' => [$report['randompart'], PDO::PARAM_STR],
                ':body'      => [$report['body'], PDO::PARAM_STR],
                ':stamp'     => [time(), PDO::PARAM_INT],
            ]
        );
    }

    // addPostedReport
} // Dao_Base_SpotReport
