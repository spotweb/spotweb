<?php

use phpseclib\Crypt\RSA;
use phpseclib\Math\BigInteger;

class Services_Signing_Php extends Services_Signing_Base
{
    /*
     * Override visibility of the constructor see GH issue #1554
     */
    public function __construct()
    {
    }

    // ctor

    /*
     * Actually checks the RSA signature
     */
    protected function checkRsaSignature($toCheck, $signature, $rsaKey, $useCache)
    {
        // First decode the signature
        $signature = base64_decode($signature);

        // Initialize the public key to verify with
        $pubKey['n'] = new BigInteger(base64_decode($rsaKey['modulo']), 256);
        $pubKey['e'] = new BigInteger(base64_decode($rsaKey['exponent']), 256);

        // and verify the signature
        $rsa = new RSA();
        $rsa->loadKey($pubKey, RSA::PUBLIC_FORMAT_RAW);
        $rsa->setSignatureMode(RSA::SIGNATURE_PKCS1);

        // Supress notice if the signature was invalid
        $saveErrorReporting = error_reporting(E_ERROR);
        $tmpSave = $rsa->verify($toCheck, $signature);
        error_reporting($saveErrorReporting);

        return $tmpSave;
    }

    // checkRsaSignature

    /*
     * Creates a private and public keypair
     */
    public function createPrivateKey($sslCnfPath)
    {
        $rsa = new RSA();
        $rsa->setSignatureMode(RSA::SIGNATURE_PKCS1);

        $keyPair = $rsa->createKey();

        return ['public' => $keyPair['publickey'],
            'private'    => $keyPair['privatekey'], ];
    }

    // createPrivateKey
} // Services_Signing_Php
