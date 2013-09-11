<?php

class Dao_Base_Session implements Dao_Session {
	protected $_conn;

	/*
	 * constructs a new Dao_Base_Comment object, 
	 * connection object is given
	 */
	public function __construct(dbeng_abs $conn) {
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
						WHERE (sessionid = :sessionid) AND (userid = :userid)",
            array(
                ':sessionid' => array($sessionid, PDO::PARAM_STR),
                ':userid' => array($userid, PDO::PARAM_INT)
            ));
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
					VALUES(:sessionid, :userid, :hitcount, :lasthit, :ipaddr, :devicetype)",
            array(
                ':sessionid' => array($session['sessionid'], PDO::PARAM_STR),
                ':userid' => array($session['userid'], PDO::PARAM_INT),
                ':hitcount' => array($session['hitcount'], PDO::PARAM_INT),
                ':lasthit' => array($session['lasthit'], PDO::PARAM_INT),
                'ipaddr' => array($session['ipaddr'], PDO::PARAM_STR),
                'devicetype' => array($session['devicetype'], PDO::PARAM_STR)
            ));
	} # addSession

	/*
	 * Removes a session from the database
	 */
	function deleteSession($sessionid) {
		$this->_conn->modify(
					"DELETE FROM sessions WHERE sessionid = :sessionid",
            array(
                ':sessionid' => array($sessionid, PDO::PARAM_STR)
            ));
	} # deleteSession

	/*
	 * Removes all sessions' for a user
	 */
	function deleteAllUserSessions($userid) {
		$this->_conn->modify(
					"DELETE FROM sessions WHERE userid = :userid",
            array(
                ':userid' => array($userid, PDO::PARAM_INT)
            ));
	} # deleteAllUserSessions
	
	/*
	 * Removes all expired sessions
	 */
	function deleteExpiredSessions($maxLifeTime) {
		$this->_conn->modify(
					"DELETE FROM sessions WHERE lasthit < :lasthit",
            array(
                ':lashit', array(time() - $maxLifeTime, PDO::PARAM_INT)
            ));
	} # deleteExpiredSessions

	/*
	 * Updates the last hit of a session
	 */
	function hitSession($sessionid) {
		$this->_conn->modify("UPDATE sessions
								SET hitcount = hitcount + 1,
									lasthit = :lasthit
								WHERE sessionid = :sessionid",
            array(
                ':lasthit' => array(time(), PDO::PARAM_INT),
                ':sessionid' => array($sessionid, PDO::PARAM_STR)
            ));
	} # hitSession
	
} # Dao_Base_Session
