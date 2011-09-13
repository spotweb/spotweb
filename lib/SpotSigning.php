<?php
require_once "Crypt/RSA.php";

class SpotSigning {

	public function __construct() {
		if (!defined('CRYPT_RSA_MODE')) {
			if (extension_loaded("openssl")) {
				define('CRYPT_RSA_MODE', CRYPT_RSA_MODE_OPENSSL);
			} else {
				define('CRYPT_RSA_MODE', CRYPT_RSA_MODE_INTERNAL);
 			} # else
		} # if not defined
	} # ctor


	private function checkRsaSignature($toCheck, $signature, $rsaKey) {
		# de signature is base64 encoded, eerst decoden
		$signature = base64_decode($signature);

		# Controleer of we de native OpenSSL libraries moeten
		# gebruiken om RSA signatures te controleren
		if (CRYPT_RSA_MODE != CRYPT_RSA_MODE_OPENSSL) {
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
		} else {
			# Initialize the public key to verify with
			$pubKey['n'] = base64_decode($rsaKey['modulo']);
			$pubKey['e'] = base64_decode($rsaKey['exponent']);

			$nativeVerify = new SpotSeclibToOpenSsl();
			$tmpSave = $nativeVerify->verify($pubKey, $toCheck, $signature);
		} # else

		return $tmpSave;
	} # checkRsaSignature

	/*
	 * Creeert een private en public key paar
	 */
	public function createPrivateKey($sslCnfPath) {
		$rsa = new Crypt_RSA();
		$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
			
		# We hebben deze code geconfigureerd uit Crypt/RSA.php omdat
		# we anders de configuratie parameter niet mee kunnen geven aan
		# openssl_pkey_new()
		if (CRYPT_RSA_MODE != CRYPT_RSA_MODE_OPENSSL) {
			# We krijgen de keys base encoded terug		
			$keyPair = $rsa->createKey();
			return array('public' => $keyPair['publickey'],
						 'private' => $keyPair['privatekey']);
		} else {
            $opensslPrivKey = openssl_pkey_new(array('private_key_bits' => 1024, 'config' => $sslCnfPath));
            openssl_pkey_export($opensslPrivKey, $privateKey, null, array('config' => $sslCnfPath));
            $publicKey = openssl_pkey_get_details($opensslPrivKey);
            $publicKey = $publicKey['key'];
			openssl_free_key($opensslPrivKey);

			return array('public' => $publicKey,
						 'private' => $privateKey);
		} # else
	} # createPrivateKey 
	
	/*
	 * RSA signed een bericht, en geeft alle componenten terug
	 * die nodig zijn om dit te valideren, dus:
	 *
	 * - base64 encoded signature (signature)
	 * - Public key (publickey)
	 * - Het bericht dat gesigned is (message)
	 */
	public function signMessage($privatekey, $message) {
		/**
		 * Test code:
		 * 
		 * $rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
		 * extract($rsa->createKey());
		 * $spotSigning = new SpotSigning();
		 * $x = $spotSigning->signMessage($privatekey, 'testmessage');
		 * var_dump($x);
		 * var_dump($spotSigning->checkRsaSignature('testmessage', $x['signature'], $x['publickey']));
		 *
		 */
		 
		$rsa = new Crypt_RSA();
		$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
		$rsa->loadKey($privatekey);

		# extract de public key
		$signature = $rsa->sign($message);
		$publickey = $rsa->getPublicKey(CRYPT_RSA_PUBLIC_FORMAT_RAW);
		
		return array('signature' => base64_encode($signature),
					 'publickey' => array('modulo' => base64_encode($publickey['n']->toBytes()), 'exponent' => base64_encode($publickey['e']->toBytes())),
					 'message' => $message);
	} # signMessage
		

	/*
	 * Converteer een voor ons bruikbare publickey, naar een publickey
	 * formaat gebruikt door de SpotNet native client
	 */
	public function pubkeyToXml($pubkey) {
		return "<RSAKeyValue><Modulus>" . $pubkey['modulo'] . '</Modulus><Exponent>' . $pubkey['exponent'] . '</Exponent></RSAKeyValue>';
	} # pubkeyToXml 
		
	
	/*
	 * Helper functie om een spot header (resultaat uit een xover of getHeader()) te verifieeren
	 */
	public function verifySpotHeader($spot, $signature, $rsakeys) {
		# This is the string to verify
		$toCheck = $spot['title'] . substr($spot['header'], 0, strlen($spot['header']) - strlen($spot['headersign']) - 1) . $spot['poster'];
		
		# Check the RSA signature on the spot
		return $this->checkRsaSignature($toCheck, $signature, $rsakeys[$spot['keyid']]);
	} # verifySpotHeader()

	/*
	 * Helper functie om een fullspot te verifieeren
	 */
	public function verifyFullSpot($spot) {
		if ((empty($spot['user-signature'])) || (empty($spot['user-key']))) {
			return false;
		} # if
		
		$verified = $this->checkRsaSignature('<' . $spot['messageid'] . '>', $spot['user-signature'], $spot['user-key']);
		if (!$verified) {
			$verified = $this->checkRsaSignature($spot['xml-signature'], $spot['user-signature'], $spot['user-key']);
		} # if
		
		return $verified;
	} # verifyFullSpot()
	
	/*
	 * Helper functie om een comment header te verifieeren
	 */
	public function verifyComment($comment) {
		$verified = false;

		if ((!empty($comment['usersignature'])) && (!empty($comment['user-key']))) {
			$verified = $this->checkRsaSignature('<' . $comment['messageid'] .  '>', $comment['usersignature'], $comment['user-key']);
			if (!$verified) {
				$verified = $this->checkRsaSignature('<' . $comment['messageid'] .  '>' . 
																implode("\r\n", $comment['body']) . "\r\n" . 
																$comment['fromhdr'], 
													$comment['usersignature'], 
													$comment['user-key']);
			} # if
		} # if
		
		# als een spot qua RSA signature al klopt, kunnen we ook nog controleren op de users'
		# hash, deze zou eigenlijk ook moeten kloppen. 
		# Deze hash is puur gemaakt om rekenkracht te vereisen aan de kant van de poster om 
		# eventuele floods te voorkomen, de hash is dus ook op zich door iedereen te creeeren.
		#
		if ($verified) {
			# $userSignedHash = sha1('<' . $comment['messageid'] . '>', false);
			# $verified = (substr($userSignedHash, 0, 4) == '0000');
		} # if

		return $verified;
	} # verifyComment()
	
	/*
	 * Bereken een SHA1 hash van het bericht en doe dit net zo lang tot de eerste bytes
	 * bestaan uit 0000. 
	 *
	 * Normaal gebruik je hiervoor de JS variant.
	 */
	function makeExpensiveHash($prefix, $suffix) {
		$runCount = 0;
		
		$hash = $prefix . $suffix;

		while(substr($hash, 0, 4) !== '0000') {	
			if ($runCount > 400000) {
				throw new Exception("Unable to calculate SHA1 hash: " . $runCount);
			} # if
			$runCount++;
			
			$uniquePart = $this->makeRandomStr(15);
			
			$hash = sha1($prefix . '.' . $uniquePart . $suffix, false);			
		} # while
		
		return $prefix . '.' . $uniquePart . $suffix;
	} # makeExpensiveHash

	/*
	 * Creeert een random strng van A-Za-z,0-9 van $len length
	 */
	function makeRandomStr($len) {
		$possibleChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
		
		$unique = '';
		for($i = 0; $i < $len; $i++) {
			$unique .= $possibleChars[mt_rand(0, strlen($possibleChars) - 1)];
		} # for
		
		return $unique;
	} # makeRandomStr
				
		
	/*
	 * 'Bereken' de userid aan de hand van z'n publickey
	 */
	public function calculateUserid($userKey) {
		$userSignCrc = crc32(base64_decode($userKey));
		
		$userIdTmp = chr($userSignCrc & 0xFF) .
						chr(($userSignCrc >> 8) & 0xFF ).
						chr(($userSignCrc >> 16) & 0xFF) .
						chr(($userSignCrc >> 24) & 0xFF);
		
		return str_replace(array('/', '+', '='), '', base64_encode($userIdTmp));
	} # calculateUserId
	
} # class SpotSigning
