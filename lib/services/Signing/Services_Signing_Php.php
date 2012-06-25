<?php
require_once "Crypt/RSA.php";

class Services_Signing_Php extends Services_Signing_Base {

	/* 
	 * Overwrite private constructor
	 */
	public function __construct() {

	} # ctor

	/*
	 * Actually checks the RSA signature
	 */
	protected function checkRsaSignature($toCheck, $signature, $rsaKey, $useCache) {
		# First decode the signature
		$signature = base64_decode($signature);

		# Initialize the public key to verify with
		$pubKey['n'] = new Math_BigInteger(base64_decode($rsaKey['modulo']), 256);
		$pubKey['e'] = new Math_BigInteger(base64_decode($rsaKey['exponent']), 256);
				
		# and verify the signature
		$rsa = new Crypt_RSA();
		$rsa->loadKey($pubKey, CRYPT_RSA_PUBLIC_FORMAT_RAW);
		$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);

		# Supress notice if the signature was invalid
		$saveErrorReporting = error_reporting(E_ERROR);
		$tmpSave = $rsa->verify($toCheck, $signature);
		error_reporting($saveErrorReporting);

		return $tmpSave;
	} # checkRsaSignature

	/*
	 * Creates a private and public keypair
	 */
	public function createPrivateKey($sslCnfPath) {
		$rsa = new Crypt_RSA();
		$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
			
		$keyPair = $rsa->createKey();
		return array('public' => $keyPair['publickey'],
					 'private' => $keyPair['privatekey']);
	} # createPrivateKey


} # Services_Signing_Php

