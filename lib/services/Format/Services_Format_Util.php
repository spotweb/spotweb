<?php

class Services_Format_Util
{
    /*
     * Some binary postings are specially encoded where only
     * a very specific set of characters is escaped. Fix those.
     */
    public function unspecialZipStr($strInput)
    {
        return str_replace(
            ['=C', '=B', '=A', '=D'],
            ["\n", "\r", "\0", '='],
            $strInput
        );
    }

    // unspecialZipstr

    /*
     * Some binary postings are specially encoded where only
     * a very specific set of characters will be escaped.
     */
    public function specialZipStr($strInput)
    {
        return str_replace(
            ['=', "\n", "\r", "\0"],
            ['=D', '=C', '=B', '=A'],
            $strInput
        );
    }

    // specialZipstr

    /*
     * Yet another encoding specifically for base64-
     * encoded strings
     */
    public function spotPrepareBase64($strInput)
    {
        return str_replace(
            ['/', '+'],
            ['-s', '-p'],
            $strInput
        );
    }

    // spotUnprepareBase64

    /*
     * Decodes the earlier encoded base64 encoded string
     * to be fully usable
     */
    public function spotUnprepareBase64($strInput)
    {
        /* Pad the input string to a multiple of 4 */
        $paddingLen = strlen($strInput) % 4;
        if ($paddingLen > 0) {
            $strInput .= str_repeat('=', (4 - $paddingLen));
        } // if

        return str_replace(['-s', '-p'], ['/', '+'], $strInput);
    }

    // spotUnprepareBase64

    /*
     * Validates a messageid
     */
    public function validMessageId($messageId)
    {
        $invalidChars = '<>';

        $msgIdLen = strlen($messageId);
        for ($i = 0; $i < $msgIdLen; $i++) {
            if (strpos($invalidChars, $messageId[$i]) !== false) {
                return false;
            } // if
        } // for

        return true;
    }

    // validMessageId

    /*
     * Calculates the user id using hte users' publickey
     */
    public function calculateSpotterId($userKey)
    {
        $userSignCrc = crc32(base64_decode($userKey));

        $userIdTmp = chr($userSignCrc & 0xFF).
                        chr(($userSignCrc >> 8) & 0xFF).
                        chr(($userSignCrc >> 16) & 0xFF).
                        chr(($userSignCrc >> 24) & 0xFF);

        return str_replace(['/', '+', '='], '', base64_encode($userIdTmp));
    }

    // calculateSpotterId
} // Services_Format_Util
