<?php
require_once "Crypt/RSA.php";

abstract class Services_Signing_Base {
	/* 
	 * We never want to create this directly
	 */	
	private function __construct() {
	} # ctor

	/*
	 * Create a factory method
	 */
	static public function newServiceSigning() {
		/*
		 * Automatically select OpenSSL if
		 * possible
		 */
		if (!defined('CRYPT_RSA_MODE')) {
			if (extension_loaded("openssl")) {
				define('CRYPT_RSA_MODE', CRYPT_RSA_MODE_OPENSSL);
			} else {
				define('CRYPT_RSA_MODE', CRYPT_RSA_MODE_INTERNAL);
 			} # else
		} # if not defined


		if (CRYPT_RSA_MODE == CRYPT_RSA_MODE_OPENSSL) {
			return new Services_Signing_Openssl(); 
		} else {
			return new Services_Signing_Php(); 
		} # else
	} # newServiceSigning

	/*
	 * Actually validates the RSA signature
	 */
	abstract protected function checkRsaSignature($toCheck, $signature, $rsaKey, $useCache);

	/*
	 * Creates a public and private key
	 */
	abstract public function createPrivateKey($sslCnfPath);
	
	/*
	 * RSA signs a message and returns all possible values needed
	 * to validate:
	 *
	 * - base64 encoded signature (signature)
	 * - Public key (publickey)
	 * - message which is signed (message)
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
		 * var_dump($spotSigning->checkRsaSignature('testmessage', $x['signature'], $x['publickey'], false));
		 *
		 */
		if (empty($privatekey)) {
			throw new InvalidPrivateKeyException();
		} # if
		 
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
	 * Returns a public key
	 */
	function getPublicKey($privateKey) {
		$rsa = new Crypt_RSA();
		$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
		$rsa->loadKey($privateKey);

		# extract the public key
		$publicKey = $rsa->getPublicKey(CRYPT_RSA_PUBLIC_FORMAT_RAW);

		return array('publickey' => array('modulo' => base64_encode($publicKey['n']->toBytes()), 'exponent' => base64_encode($publicKey['e']->toBytes())));
	} # getPublicKey

	/*
	 * Convets a usuable public key for us, to a public key
	 * usable for the SpotNet native client (.NET format)
	 */
	public function pubkeyToXml($pubkey) {
		return "<RSAKeyValue><Modulus>" . $pubkey['modulo'] . '</Modulus><Exponent>' . $pubkey['exponent'] . '</Exponent></RSAKeyValue>';
	} # pubkeyToXml 
		

	/*
	 * Helper function to verify a spot header
	 */	
	public function verifySpotHeader($spot, $signature, $rsaKeys) {
		# This is the string to verify
		$toCheck = $spot['title'] . substr($spot['header'], 0, strlen($spot['header']) - strlen($spot['headersign']) - 1) . $spot['poster'];

		# Check the RSA signature on the spot
		return $this->checkRsaSignature($toCheck, $signature, $rsaKeys[$spot['keyid']], true);
	} # verifySpotHeader()

	/*
	 * Helper function which verifies a fullspot
	 */
	public function verifyFullSpot($spot) {
		if ((empty($spot['user-signature'])) || (empty($spot['user-key']))) {
			return false;
		} # if
		
		$verified = $this->checkRsaSignature('<' . $spot['messageid'] . '>', $spot['user-signature'], $spot['user-key'], false);
		if ((!$verified) && (!empty($spot['xml-signature']))) {
			$verified = $this->checkRsaSignature($spot['xml-signature'], $spot['user-signature'], $spot['user-key'], false);
		} # if
		
		return $verified;
	} # verifyFullSpot()

	/*
	 * Helper function to verify a comment header
	 */	
	public function verifyComment($comment) {
		$verified = false;

		if ((!empty($comment['user-signature'])) && (!empty($comment['user-key']))) {
			$verified = $this->checkRsaSignature('<' . $comment['messageid'] .  '>', $comment['user-signature'], $comment['user-key'], false);
			if (!$verified) {
				$verified = $this->checkRsaSignature('<' . $comment['messageid'] .  '>' . 
																implode("\r\n", $comment['body']) . "\r\n" . 
																$comment['fromhdr'], 
													$comment['user-signature'], 
													$comment['user-key'],
													false);
			} # if
		} # if

		/*
		 * When a spot is valid with regards to an RSA signature, we can also check the users'
		 * hash, which also should validate. This hash is a so-called hashcash and is only
		 * meant to require CPU power on the posting clinet preventing floods.
		 *
		 * Currently, some buggy clients post invalid hash cashes but valid spots so we cannot
		 * use this yet.
		 */
		if ($verified) {
			# $userSignedHash = sha1('<' . $comment['messageid'] . '>', false);
			# $verified = (substr($userSignedHash, 0, 4) == '0000');
		} # if

		return $verified;
	} # verifyComment()
	
	/*
	 * Calculates an SHA1 hash of a message until the first bytes match 0000. Please use
	 * the JavaScript variant for this
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
			
			$hash = sha1($prefix . $uniquePart . $suffix, false);			
		} # while
		
		return $prefix . $uniquePart . $suffix;
	} # makeExpensiveHash

	/*
	 * Creates a random string of $len length with A-z0-9
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
	 * Calculates the user id using hte users' publickey
	 */		
	public function calculateSpotterId($userKey) {
		$userSignCrc = crc32(base64_decode($userKey));
		
		$userIdTmp = chr($userSignCrc & 0xFF) .
						chr(($userSignCrc >> 8) & 0xFF ).
						chr(($userSignCrc >> 16) & 0xFF) .
						chr(($userSignCrc >> 24) & 0xFF);
		
		return str_replace(array('/', '+', '='), '', base64_encode($userIdTmp));
	} # calculateSpotterId
	
} # Services_Signing_Base
