<?php
require_once "Math/BigInteger.php";
require_once "Crypt/RSA.php";

/*
 * Utility classe om RSA signatures te laten verifieeren door OpenSSL ipv
 * Crypt/RSA, dit is een keer of 100 sneller
 */
class SpotSeclibToOpenSsl {

	function verify($rsa, $toCheck, $signature) {
		$openSslPubKey = openssl_get_publickey($this->seclibToOpenSsl($rsa));
		$verified = openssl_verify($toCheck, $signature, $openSslPubKey);
		openssl_free_key($openSslPubKey);
		
		return $verified;
	} # verify

	function _getOidElementLength($component) {
		# Code copied from
		#   http://chaosinmotion.com/wiki/index.php?title=ASN.1_Library
        if ($component < 0) return 10;               // Full 64 bits takes 10*7 bits to encode
        $l = 1;
        for ($i = 1; $i < 9; ++$i) {
            $l <<= 7;
            if ($component < $l) break;
        }
        return $i;
	} 
	
	function _encodeObjectId($vals) {
		$return = array();
		
		$return[] = 40 * $vals[0] + $vals[1];
		for($i = 2; $i < count($vals); $i++) {
			# Code copied from
			#   http://chaosinmotion.com/wiki/index.php?title=ASN.1_Library
			$v = $vals[$i];
			$len = $this->_getOIDElementLength($v);
			
			for ($j = $len-1; $j > 0; --$j) {
				$m = 0x0080 | (0x007F & ($v >> ($j * 7)));
				$return[] = (int) $m;
			}
			$return[] = (int)(0x007F & $v);
		}

		return $return;
	} # _encodeObjectId


	function seclibToOpenSsl($rsa) {
		/* 
		 * Structuur van de OpenSSL publickey is als volgt:
		 *
		 * - Sequence
		 * +- Sequence
		 * ++- Object identifier die de RSA key weergeeft (1.2.840.113549.1.1.1)
		 * ++- NULL
		 * +- Bit String
		 * ++- Sequence
		 * +++- Integer
		 * +++- Integer
		 *
		 * Dit willen we nabootsen met deze encoding
		 */
		$publicExponent = $rsa->exponent->toBytes(true);
		$modulus = $rsa->modulus->toBytes(true);
		$components = array(
			'modulus' => pack('Ca*a*', CRYPT_RSA_ASN1_INTEGER, $rsa->_encodeLength(strlen($modulus)), $modulus),
			'publicExponent' => pack('Ca*a*', CRYPT_RSA_ASN1_INTEGER, $rsa->_encodeLength(strlen($publicExponent)), $publicExponent)
		);

		/* 
		 * First encoden we de keys in een bitstring 
		 */		 
		$encodedKeys = pack('Ca*a*a*',
					CRYPT_RSA_ASN1_SEQUENCE, 		 # Sequence
					$rsa->_encodeLength(strlen($components['modulus']) + strlen($components['publicExponent'])),
					$components['modulus'], 
					$components['publicExponent']
        );
		$encodedKeys = pack('Ca*Ca*',
					0x03, 		# 0x03 means BIT STRING
					$rsa->_encodeLength(strlen($encodedKeys) + 1), # add 1 voor de 0 unused bits
					0,
					$encodedKeys
		);
		
		/*
		 * Nu creeeren we de type header
		 */
		$rsaIdentifier = $this->_encodeObjectId(array(1,2,840,113549,1,1,1)); 	/* Magic value of RSA */
		$encryptionType = pack('Ca*',
				0x06,		# ASN.1 OBJECT IDENTIFIER
				$rsa->_encodeLength(count($rsaIdentifier))
		);
		for($i = 0; $i < count($rsaIdentifier); $i++) {	
			$encryptionType .= chr($rsaIdentifier[$i]);
		} # foreach
		
		# de encryption type header wordt geappend met een ASN.1 NULL
		$encryptionType .= pack('CC',
					0x05,			# ASN.1 NULL
					0
		);
		
		# en de encryptiontype pakken we in in een sequence
		$encryptionType = pack('Ca*a*',
					CRYPT_RSA_ASN1_SEQUENCE, 		 # Sequence
					$rsa->_encodeLength(strlen($encryptionType)),
					$encryptionType
		);
		
		# en ook dit alles pakken we in een sequence in
		$endResult = pack('Ca*a*',
					CRYPT_RSA_ASN1_SEQUENCE, 		 # Sequence
					$rsa->_encodeLength(strlen($encryptionType . $encodedKeys)),
					$encryptionType . $encodedKeys
		);
		return "-----BEGIN PUBLIC KEY-----\n" . 
				chunk_split(base64_encode($endResult), 64) .
				"-----END PUBLIC KEY-----\n";
	} # seclibToOpenSsl
	
} # SpotSeclibToOpenSsl
