<?php

class Dao_Base_Session implements Dao_Session
{
    protected $_conn;

    /*
     * constructs a new Dao_Base_Comment object,
     * connection object is given
     */
    public function __construct(dbeng_abs $conn)
    {
        $this->_conn = $conn;
    }

    // ctor

    /*
     * Retrieves a session from the database
     */
    public function getSession($sessionid, $userid)
    {
        $tmp = $this->_conn->arrayQuery(
            'SELECT s.sessionid as sessionid,
								s.userid as userid,
								s.hitcount as hitcount,
								s.lasthit as lasthit,
								s.ipaddr as ipaddr,
								s.devicetype as devicetype
						FROM sessions AS s
						WHERE (sessionid = :sessionid) AND (userid = :userid)',
            [
                ':sessionid' => [$sessionid, PDO::PARAM_STR],
                ':userid'    => [$userid, PDO::PARAM_INT],
            ]
        );
        if (!empty($tmp)) {
            return $tmp[0];
        } // if

        return false;
    }

    // getSession

    /*
     * Creates a new session
     */
    public function addSession($session)
    {
        $this->_conn->modify(
            'INSERT INTO sessions(sessionid, userid, hitcount, lasthit, ipaddr, devicetype) 
					VALUES(:sessionid, :userid, :hitcount, :lasthit, :ipaddr, :devicetype)',
            [
                ':sessionid' => [$session['sessionid'], PDO::PARAM_STR],
                ':userid'    => [$session['userid'], PDO::PARAM_INT],
                ':hitcount'  => [$session['hitcount'], PDO::PARAM_INT],
                ':lasthit'   => [$session['lasthit'], PDO::PARAM_INT],
                'ipaddr'     => [$session['ipaddr'], PDO::PARAM_STR],
                'devicetype' => [$session['devicetype'], PDO::PARAM_STR],
            ]
        );
    }

    // addSession

    /*
     * Removes a session from the database
     */
    public function deleteSession($sessionid)
    {
        $this->_conn->modify(
            'DELETE FROM sessions WHERE sessionid = :sessionid',
            [
                ':sessionid' => [$sessionid, PDO::PARAM_STR],
            ]
        );
    }

    // deleteSession

    /*
     * Removes all sessions' for a user
     */
    public function deleteAllUserSessions($userid)
    {
        $this->_conn->modify(
            'DELETE FROM sessions WHERE userid = :userid',
            [
                ':userid' => [$userid, PDO::PARAM_INT],
            ]
        );
    }

    // deleteAllUserSessions

    /*
     * Removes all expired sessions
     */
    public function deleteExpiredSessions($maxLifeTime)
    {
        $this->_conn->modify(
            'DELETE FROM sessions WHERE lasthit < :lasthit',
            [
                ':lashit', [time() - $maxLifeTime, PDO::PARAM_INT],
            ]
        );
    }

    // deleteExpiredSessions

    /*
     * Updates the last hit of a session
     */
    public function hitSession($sessionid)
    {
        $this->_conn->modify(
            'UPDATE sessions
								SET hitcount = hitcount + 1,
									lasthit = :lasthit
								WHERE sessionid = :sessionid',
            [
                ':lasthit'   => [time(), PDO::PARAM_INT],
                ':sessionid' => [$sessionid, PDO::PARAM_STR],
            ]
        );
    }

    // hitSession
} // Dao_Base_Session
