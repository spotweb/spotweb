<?php

class SpotSigning {

	public function checkRsaSignature($toCheck, $signature, $rsaKey) {
		# de signature is base64 encoded, eerst decoden
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
	
	public function calculateUserid($userKey) {
		$userSignCrc = crc32(base64_decode($userKey));
		
		$userIdTmp = chr($userSignCrc & 0xFF) .
						chr(($userSignCrc >> 8) & 0xFF ).
						chr(($userSignCrc >> 16) & 0xFF) .
						chr(($userSignCrc >> 24) & 0xFF);
		
		return str_replace(array('/', '+', '='), '', base64_encode($userIdTmp));
	} # calculateUserId
	
} # class SpotSigning