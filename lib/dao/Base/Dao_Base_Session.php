<?php

class Dao_Base_Session implements Dao_Session {
	protected $_conn;

	/*
	 * constructs a new Dao_Base_Comment object, 
	 * connection object is given
	 */
	public function __construct($conn) {
		$this->_conn = $conn;
	} # ctor


	/*
	 * Retrieves a session from the database
	 */
	function getSession($sessionid, $userid) {
		$tmp = $this->_conn->arrayQuery(
						"SELECT s.sessionid as sessionid,
								s.userid as userid,
								s.hitcount as hitcount,
								s.lasthit as lasthit,
								s.ipaddr as ipaddr,
								s.devicetype as devicetype
						FROM sessions AS s
						WHERE (sessionid = '%s') AND (userid = %d)",
				 Array($sessionid,
				       (int) $userid));
		if (!empty($tmp)) {
			return $tmp[0];
		} # if
		
		return false;
	} # getSession

	/*
	 * Creates a new session
	 */
	function addSession($session) {
		$this->_conn->modify(
				"INSERT INTO sessions(sessionid, userid, hitcount, lasthit, ipaddr, devicetype) 
					VALUES('%s', %d, %d, %d, '%s', '%s')",
				Array($session['sessionid'],
					  (int) $session['userid'],
					  (int) $session['hitcount'],
					  (int) $session['lasthit'],
					  $session['ipaddr'],
					  $session['devicetype']));
	} # addSession

	/*
	 * Removes a session from the database
	 */
	function deleteSession($sessionid) {
		$this->_conn->modify(
					"DELETE FROM sessions WHERE sessionid = '%s'",
					Array($sessionid));
	} # deleteSession

	/*
	 * Removes all sessions' for a user
	 */
	function deleteAllUserSessions($userid) {
		$this->_conn->modify(
					"DELETE FROM sessions WHERE userid = %d",
					Array( (int) $userid));
	} # deleteAllUserSessions
	
	/*
	 * Removes all expired sessions
	 */
	function deleteExpiredSessions($maxLifeTime) {
		$this->_conn->modify(
					"DELETE FROM sessions WHERE lasthit < %d",
					Array(time() - $maxLifeTime));
	} # deleteExpiredSessions

	/*
	 * Updates the last hit of a session
	 */
	function hitSession($sessionid) {
		$this->_conn->modify("UPDATE sessions
								SET hitcount = hitcount + 1,
									lasthit = %d
								WHERE sessionid = '%s'", 
							Array(time(), $sessionid));
	} # hitSession
	
} # Dao_Base_Session
