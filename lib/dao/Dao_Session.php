<?php

interface Dao_Session
{
    public function getSession($sessionid, $userid);

    public function addSession($session);

    public function deleteSession($sessionid);

    public function deleteAllUserSessions($userid);

    public function deleteExpiredSessions($maxLifeTime);

    public function hitSession($sessionid);
} // Dao_Session
