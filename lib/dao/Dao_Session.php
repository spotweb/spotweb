<?php

interface Dao_Session {

	function getSession($sessionid, $userid);
	function addSession($session);
	function deleteSession($sessionid);
	function deleteAllUserSessions($userid);
	function deleteExpiredSessions($maxLifeTime);
	function hitSession($sessionid);
	
} # Dao_Session