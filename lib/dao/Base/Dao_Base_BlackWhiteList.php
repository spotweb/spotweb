<?php

class Dao_Base_BlackWhiteList implements Dao_BlackWhiteList
{
    protected $_conn;
    private $_util;

    /*
     * constructs a new Dao_Base_BlackWhiteList object,
     * connection object is given
     */
    public function __construct(dbeng_abs $conn)
    {
        $this->_conn = $conn;
        $this->_util = new Services_Format_Util();
    }

    // ctor

    /*
     * Removes an old black- and whitelist
     */
    public function removeOldList($listUrl, $idtype)
    {
        $this->_conn->modify(
            "DELETE FROM spotteridblacklist WHERE (ouruserid = -1) AND (origin = 'external') AND (idtype = :idtype)",
            [
                ':idtype' => [$idtype, PDO::PARAM_INT],
            ]
        );
    }

    // removeOldList

    /*
     * Updates the current black- and whitelist with new information for
     * external sources.
     */
    public function updateExternalList($newlist, $idtype)
    {
        $updatelist = [];
        $updskipped = 0;
        $countnewlistspotterid = 0;
        $countdellistspotterid = 0;

        if ($idtype == 'black') {
            $idtype = 1;
        } elseif ($idtype == 'white') {
            $idtype = 2;
        } else {
            throw new Exception('Invalid list type specified for updateExternalList: '.$idtype);
        } // else

        /* Retrieve the current list */
        $oldlist = $this->_conn->arrayQuery("SELECT spotterid,idtype
												FROM spotteridblacklist 
												WHERE ouruserid = -1 AND origin = 'external'");
        foreach ($oldlist as $obl) {
            $islisted = (($obl['idtype'] == $idtype) > 0);
            $updatelist[$obl['spotterid']] = 3 - $islisted;  	// Put "old" spotterids (current ones) on the to-delete list
        }
        /* verwerk de nieuwe lijst */
        foreach ($newlist as $nwl) {
            $nwl = trim($nwl);
            if ($idtype == 2) {
                $ex = explode(',', $nwl);
                if (isset($ex[1])) {
                    $nwl = $ex[1];
                } else {
                    $nwl = '';
                }
            }
            $nwl = $this->_util->calculateSpotterId($nwl);
            if ((strlen($nwl) >= 3) && (strlen($nwl) <= 6)) {	// Spotterids are between 2 and 7 characters long
                if (empty($updatelist[$nwl])) {
                    $updatelist[$nwl] = 1;						// We want to add this spotterid
                } elseif ($updatelist[$nwl] == 2) {
                    $updatelist[$nwl] = 5;						// SpotterID is on the list already, dont remove it
                } elseif ($updatelist[$nwl] == 3) {
                    if ($idtype == 1) {
                        $updatelist[$nwl] = 4;					// Spotterid is on another kind of list, change the idtype
                    } else {
                        $updskipped++;							// Spotter is already on the list, dont remove it
                        $updatelist[$nwl] = 5;
                    }
                } else {
                    $updskipped++;								// double spotterid in xxxxxlist.txt.
                }
            } else {
                $updskipped++;									// Spotterid did not pass the sanity check
            }
        }
        $updlist = array_keys($updatelist);
        $this->_conn->beginTransaction();
        foreach ($updlist as $updl) {
            if ($updatelist[$updl] == 1) {
                // Add new spotterid's to the list
                $countnewlistspotterid++;
                $this->_conn->modify(
                    "INSERT INTO spotteridblacklist (spotterid,ouruserid,idtype,origin) VALUES (:spotterid, '-1', :idtype,'external')",
                    [
                        ':spotterid' => [$updl, PDO::PARAM_STR],
                        ':idtype'    => [$idtype, PDO::PARAM_INT],
                    ]
                );
                $this->_conn->modify(
                    'UPDATE spotteridblacklist SET doubled = :doubled WHERE spotterid = :spotterid AND ouruserid != -1  AND idtype = :idtype',
                    [
                        ':doubled'   => [true, PDO::PARAM_BOOL],
                        ':spotterid' => [$updl, PDO::PARAM_STR],
                        ':idtype'    => [$idtype, PDO::PARAM_INT],
                    ]
                );
            } elseif ($updatelist[$updl] == 2) {
                // Remove spotters which aren't on the list
                $countdellistspotterid++;
                $this->_conn->modify(
                    "DELETE FROM spotteridblacklist WHERE (spotterid = :spotterid) AND (ouruserid = -1) AND (origin = 'external')",
                    [
                        ':spotterid' => [$updl, PDO::PARAM_STR],
                    ]
                );
                $this->_conn->modify(
                    'UPDATE spotteridblacklist SET doubled = :doubled WHERE spotterid = :spotterid AND ouruserid != -1  AND idtype = :idtype',
                    [
                        ':doubled'   => [true, PDO::PARAM_BOOL],
                        ':spotterid' => [$updl, PDO::PARAM_STR],
                        ':idtype'    => [$idtype, PDO::PARAM_INT],
                    ]
                );
            } elseif ($updatelist[$updl] == 4) {
                $countnewlistspotterid++;
                $this->_conn->modify(
                    "UPDATE spotteridblacklist SET idtype = 1 WHERE (spotterid = :spotterid) AND (ouruserid = -1) AND (origin = 'external')",
                    [
                        ':spotterid' => [$updl, PDO::PARAM_STR],
                    ]
                );
                $this->_conn->modify(
                    'UPDATE spotteridblacklist SET doubled = (idtype = 1) WHERE spotterid = :spotterid AND ouruserid != -1 ',
                    [
                        ':spotterid' => [$updl, PDO::PARAM_STR],
                        ':idtype'    => [$idtype, PDO::PARAM_INT],
                    ]
                );
            } // elseif
        } // foreach
        $this->_conn->commit();

        return ['added' => $countnewlistspotterid,
            'removed'   => $countdellistspotterid,
            'skipped'   => $updskipped,
            'total'     => count($newlist), ];
    }

    // updateExternallist

    /*
     * Adds a spotterid to the black- and whitelist
     */
    public function addSpotterToList($spotterId, $ourUserId, $origin, $idType)
    {
        $existInList = $this->_conn->singleQuery(
            'SELECT idtype FROM spotteridblacklist WHERE spotterid = :spotterid AND ouruserid = :ouruserid',
            [
                ':spotterid' => [$spotterId, PDO::PARAM_STR],
                ':ouruserid' => [$ourUserId, PDO::PARAM_INT],
            ]
        );

        if (empty($existInList)) {
            $this->_conn->modify(
                'INSERT INTO spotteridblacklist(spotterid, origin, ouruserid, idtype) VALUES (:spotterid, :origin, :ouruserid, :idtype)',
                [
                    ':spotterid' => [$spotterId, PDO::PARAM_STR],
                    ':origin'    => [$origin, PDO::PARAM_STR],
                    ':ouruserid' => [$ourUserId, PDO::PARAM_INT],
                    ':idtype'    => [$idType, PDO::PARAM_INT],
                ]
            );
        } else {
            $this->_conn->modify(
                'UPDATE spotteridblacklist SET idtype = :idtype, origin = :origin WHERE spotterid = :spotterid AND ouruserid = :ouruserid',
                [
                    ':idtype'    => [$idType, PDO::PARAM_INT],
                    ':origin'    => [$origin, PDO::PARAM_STR],
                    ':spotterid' => [$spotterId, PDO::PARAM_STR],
                    ':ouruserid' => [$ourUserId, PDO::PARAM_INT],
                ]
            );
        }
    }

    // addSpotterToList

    /*
     * Removes a specific spotter from the blacklist
     */
    public function removeSpotterFromList($spotterId, $ourUserId)
    {
        $this->_conn->modify(
            'DELETE FROM spotteridblacklist WHERE ouruserid = :ouruserid AND spotterid = :spotterid',
            [
                ':spotterid' => [$spotterId, PDO::PARAM_STR],
                ':ouruserid' => [$ourUserId, PDO::PARAM_INT],
            ]
        );
    }

    // removeSpotterFromList

    /*
     * Returns all spotterid's in the black- and whitelist specified
     * by this user (external items are not listed)
     */
    public function getSpotterList($ourUserId)
    {
        return $this->_conn->arrayQuery(
            'SELECT spotterid, origin, ouruserid, idtype FROM spotteridblacklist WHERE ouruserid = :ouruserid ORDER BY idtype',
            [
                ':ouruserid' => [$ourUserId, PDO::PARAM_INT],
            ]
        );
    }

    // getSpotterList

    /*
     * Returns one specific blacklisted record for a given spotterid
     */
    public function getBlacklistForSpotterId($userId, $spotterId)
    {
        $tmp = $this->_conn->arrayQuery(
            'SELECT spotterid, origin, ouruserid FROM spotteridblacklist WHERE spotterid = :spotterid and ouruserid = :ouruserid',
            [
                ':spotterid' => [$spotterId, PDO::PARAM_STR],
                ':ouruserid' => [$userId, PDO::PARAM_INT],
            ]
        );

        if (!empty($tmp)) {
            return $tmp[0];
        } else {
            return false;
        } // else
    }

    // getBlacklistForSpotterId
} // Dao_Base_BlackWhiteList
