<?php
require_once "Math/BigInteger.php";
require_once "Crypt/RSA.php";

/*
 * Utility classe om RSA signatures te laten verifieeren door OpenSSL ipv
 * Crypt/RSA, dit is een keer of 100 sneller
 */
class SpotSeclibToOpenSsl {

	function verify($pubKey, $toCheck, $signature) {
		$openSslPubKey = openssl_get_publickey($this->seclibToOpenSsl($pubKey));
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


	function seclibToOpenSsl($pubKey) {
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
		$publicExponent = $pubKey['e'];
		$modulus = $pubKey['n'];
		$components = array(
			'modulus' => pack('Ca*a*', CRYPT_RSA_ASN1_INTEGER, $this->_encodeLength(strlen($modulus)), $modulus),
			'publicExponent' => pack('Ca*a*', CRYPT_RSA_ASN1_INTEGER, $this->_encodeLength(strlen($publicExponent)), $publicExponent)
		);

		/* 
		 * First encoden we de keys in een bitstring 
		 */		 
		$encodedKeys = pack('Ca*a*a*',
					CRYPT_RSA_ASN1_SEQUENCE, 		 # Sequence
					$this->_encodeLength(strlen($components['modulus']) + strlen($components['publicExponent'])),
					$components['modulus'], 
					$components['publicExponent']
        );
		$encodedKeys = pack('Ca*Ca*',
					0x03, 		# 0x03 means BIT STRING
					$this->_encodeLength(strlen($encodedKeys) + 1), # add 1 voor de 0 unused bits
					0,
					$encodedKeys
		);
		
		/*
		 * Nu creeeren we de type header
		 */
		$rsaIdentifier = $this->_encodeObjectId(array(1,2,840,113549,1,1,1)); 	/* Magic value of RSA */
		$encryptionType = pack('Ca*',
				0x06,		# ASN.1 OBJECT IDENTIFIER
				$this->_encodeLength(count($rsaIdentifier))
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
					$this->_encodeLength(strlen($encryptionType)),
					$encryptionType
		);
		
		# en ook dit alles pakken we in een sequence in
		$endResult = pack('Ca*a*',
					CRYPT_RSA_ASN1_SEQUENCE, 		 # Sequence
					$this->_encodeLength(strlen($encryptionType . $encodedKeys)),
					$encryptionType . $encodedKeys
		);
		return "-----BEGIN PUBLIC KEY-----\n" . 
				chunk_split(base64_encode($endResult), 64) .
				"-----END PUBLIC KEY-----\n";
	} # seclibToOpenSsl

    /**
	 *
	 * From phpSeclib library
	 *
     * DER-encode the length
     *
     * DER supports lengths up to (2**8)**127, however, we'll only support lengths up to (2**8)**4.  See
     * {@link http://itu.int/ITU-T/studygroups/com17/languages/X.690-0207.pdf#p=13 X.690 § 8.1.3} for more information.
     *
     * @access private
     * @param Integer $length
     * @return String
     */
    function _encodeLength($length)
    {
        if ($length <= 0x7F) {
            return chr($length);
        }

        $temp = ltrim(pack('N', $length), chr(0));
        return pack('Ca*', 0x80 | strlen($temp), $temp);
    }
	
} # SpotSeclibToOpenSsl
