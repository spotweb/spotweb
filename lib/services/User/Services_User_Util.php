<?php

class Services_User_Util
{
    /*
     * Password to hash. Duplicated in SpotUserUpgrader
     * but we cannot rely on this class always being available
     * already
     */
    public static function passToHash($salt, $password)
    {
        return sha1(strrev(substr($salt, 1, 3)).$password.$salt);
    }

    // passToHash

    /*
     * Generates an unique id, mostly used for sessions
     */
    public static function generateUniqueId()
    {
        $sessionId = '';

        for ($i = 0; $i < 10; $i++) {
            $sessionId .= base_convert(mt_rand(), 10, 36);
        } // for

        return $sessionId;
    }

    // generateUniqueId
} // class Services_User_Util
