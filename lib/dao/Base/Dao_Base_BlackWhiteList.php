<?php

class Dao_Base_BlackWhiteList implements Dao_BlackWhiteList {
	protected $_conn;

	/*
	 * constructs a new Dao_Base_BlackWhiteList object, 
	 * connection object is given
	 */
	public function __construct($conn) {
		$this->_conn = $conn;
	} # ctor

	/*
	 * Removes an old black- and whitelist
	 */
	function removeOldList($listUrl, $idtype) {
		$this->_conn->modify("DELETE FROM spotteridblacklist WHERE (ouruserid = -1) AND (origin = 'external') AND (idtype = %d)",Array((int) $idtype));
		$this->_conn->modify("DELETE FROM cache WHERE (resourceid = '%s') AND (cachetype = '%s')", Array(md5($listUrl), SpotCache::Web));
	} # removeOldList
	
	/*
	 * Updates the current black- and whitelist with new information for
	 * external sources. 
	 */
	function updateExternalList($newlist,$idtype) {
		$updatelist = array();
		$updskipped = 0;
		$countnewlistspotterid = 0;
		$countdellistspotterid = 0;

		/* Haal de oude lijst op*/
		$oldlist = $this->_conn->arrayQuery("SELECT spotterid,idtype
												FROM spotteridblacklist 
												WHERE ouruserid = -1 AND origin = 'external'");
		foreach ($oldlist as $obl) {
			$islisted = (($obl['idtype'] == $idtype) > 0);
			$updatelist[$obl['spotterid']] = 3 - $islisted;  # 'oude' spotterid eerst op verwijderen zetten.
		}
		/* verwerk de nieuwe lijst */
		foreach ($newlist as $nwl) {
			$nwl = trim($nwl);									# Enters en eventuele spaties wegfilteren
			if ((strlen($nwl) >= 3) && (strlen($nwl) <= 6)) {	# de lengte van een spotterid is tussen 3 en 6 karakters groot (tot op heden)
				if (empty($updatelist[$nwl])) {
					$updatelist[$nwl] = 1;						# nieuwe spoterids toevoegen 
				} elseif ($updatelist[$nwl] == 2) {
					$updatelist[$nwl] = 5;						# spotterid staat al op dezelfde lijst, niet verwijderen.
				} elseif ($updatelist[$nwl] == 3) {
					if ($idtype == 1) {
						$updatelist[$nwl] = 4;					# spotterid staat op een andere lijst, idtype veranderen.
					} else {
						$updskipped++;							# spotterid staat al op de blacklist, niet veranderen.
						$updatelist[$nwl] = 5;
					}
				} else {
					$updskipped++;								# dubbel spotterid in xxxxxlist.txt.
				}
			} else {
				$updskipped++;									# er is iets mis met het spotterid (bijvoorbeeld een lege regel in xxxxxlist.txt)
			}
		}
		$updlist = array_keys($updatelist);
		foreach ($updlist as $updl) {
			if ($updatelist[$updl] == 1) {
				# voeg nieuwe spotterid's toe aan de lijst
				$countnewlistspotterid++;
				$this->_conn->modify("INSERT INTO spotteridblacklist (spotterid,ouruserid,idtype,origin) VALUES ('%s','-1',%d,'external')", Array($updl, (int) $idtype));
				$this->_conn->modify("UPDATE spotteridblacklist SET doubled = '%s' WHERE spotterid = '%s'AND ouruserid != -1  AND idtype = %d ", Array($this->_conn->bool2dt(true), $updl, (int) $idtype));
			} elseif ($updatelist[$updl] == 2) {
				# verwijder spotterid's die niet meer op de lijst staan
				$countdellistspotterid++;
				$this->_conn->modify("DELETE FROM spotteridblacklist WHERE (spotterid = '%s') AND (ouruserid = -1) AND (origin = 'external')", Array($updl));
				$this->_conn->modify("UPDATE spotteridblacklist SET doubled = '%s' WHERE spotterid = '%s' AND ouruserid != -1 AND idtype = %d ", Array($this->_conn->bool2dt(true), $updl, (int) $idtype));
			} elseif ($updatelist[$updl] == 4) {
				$countnewlistspotterid++;
				$this->_conn->modify("UPDATE spotteridblacklist SET idtype = 1 WHERE (spotterid = '%s') AND (ouruserid = -1) AND (origin = 'external')", Array($updl));
				$this->_conn->modify("UPDATE spotteridblacklist SET doubled = (idtype = 1) WHERE spotterid = '%s' AND ouruserid != -1", Array($updl));
			}

		}
		return array('added' => $countnewlistspotterid,'removed' => $countdellistspotterid,'skipped' => $updskipped);
	} # updateExternallist

	/*
	 * Adds a spotterid to the black- and whitelist
	 */
	function addSpotterToList($spotterId, $ourUserId, $origin, $idType) {
		$existInList = $this->_conn->singleQuery("SELECT idtype FROM spotteridblacklist WHERE spotterid = '%s' AND ouruserid = %d", Array($spotterId, (int) $ourUserId));
		if (empty($existInList)) {
			$this->_conn->modify("INSERT INTO spotteridblacklist(spotterid, origin, ouruserid, idtype) VALUES ('%s', '%s', %d, %d)",
						Array($spotterId, $origin, (int) $ourUserId, (int) $idType));
		} else {
			$this->_conn->modify("UPDATE spotteridblacklist SET idtype = %d, origin = '%s' WHERE spotterid = '%s' AND ouruserid = %d", Array( (int) $idType, $origin, $spotterId, (int) $ourUserId));
		}
	} # addSpotterToList

	/*
	 * Removes a specific spotter from the blacklist
	 */
	function removeSpotterFromList($spotterId, $ourUserId) {
		$this->_conn->modify("DELETE FROM spotteridblacklist WHERE ouruserid = %d AND spotterid = '%s'",
					Array((int) $ourUserId, $spotterId));
	} # removeSpotterFromList
	
	/*
	 * Returns all spotterid's in the black- and whitelist specified
	 * by this user (external items are not listed)
	 */
	function getSpotterList($ourUserId) {
		return $this->_conn->arrayQuery("SELECT spotterid, origin, ouruserid, idtype FROM spotteridblacklist WHERE ouruserid = %d ORDER BY idtype",
					Array((int) $ourUserId));
	} # getSpotterList

	/*
	 * Returns one specific blacklisted record for a given spotterid
	 */
	function getBlacklistForSpotterId($userId, $spotterId) {
		$tmp = $this->_conn->arrayQuery("SELECT spotterid, origin, ouruserid FROM spotteridblacklist WHERE spotterid = '%s' and ouruserid = %d",
					Array($spotterId, $userId));
					
		if (!empty($tmp)) {
			return $tmp[0];
		} else {
			return false;
		} # else
	} # getBlacklistForSpotterId

} # Dao_Base_BlackWhiteList
