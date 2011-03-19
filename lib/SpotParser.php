<?php
require_once "Math/BigInteger.php";
require_once "Crypt/RSA.php";
require_once "settings.php";
require_once "lib/exceptions/ParseSpotXmlException.php";

class SpotParser {
	function parseFull($xmlStr) {
		# Gebruik een spot template zodat we altijd de velden hebben die we willen
		$tpl_spot = array('category' => '', 'website' => '', 'image' => '', 'sabnzbdurl' => '', 'messageid' => '', 'searchurl' => '', 'description' => '',
						  'sub' => '', 'size' => '', 'poster' => '', 'tag' => '', 'nzb' => '', 'title' => '', 'key-id' => '',
						  'subcatlist' => array(), 'subcata' => '', 'subcatb' => '', 'subcatc' => '', 'subcatd' => '', 'imageid' => '');

						  
		/* 
		 * Onderdruk errors bij corrupte messaegeid, bv: <evoCgYpLlLkWe97TQAmnV@spot.net>
		 */		
		$xml = @(new SimpleXMLElement($xmlStr));
		$xml = $xml->Posting;
		$tpl_spot['category'] = (string) $xml->Category;
		$tpl_spot['website'] = (string) $xml->Website;
		$tpl_spot['description'] = (string) $xml->Description;
		$tpl_spot['size'] = (string) $xml->Size;
		$tpl_spot['poster'] = (string) $xml->Poster;
		$tpl_spot['tag'] = (string) $xml->Tag;
		$tpl_spot['title'] = (string) $xml->Title;
		$tpl_spot['key-id'] = (string) $xml->{"Key-ID"};
		
		# Images behandelen we op een speciale manier, in de oude spots
		# was er gewoon een URL, in de nieuwe een hoogte/lengte/messageid
		if (empty($xml->Image->Segment)) {
			$tpl_spot['image'] = (string) $xml->Image;
 		} else {
			$tpl_spot['image'] = Array(
				'segment' => (string) $xml->Image->Segment,
				'height' => (string) $xml->Image['Height'],
				'width' => (string) $xml->Image['Width']
			);
		} # else
		
		# NZB segmenten plakken we gewoon aan elkaar
		foreach($xml->xpath('/Spotnet/Posting/NZB/Segment') as $seg) {
			$tpl_spot['nzb'][] = (string) $seg;
		} # foreach

		# fix the category in the XML array but only for new spots
		if ((int) $xml->Key != 1) {
			$tpl_spot['category'] = ((int) $tpl_spot['category']) - 1;
		} # if

		#
		# Bij oude-style (?) spots wordt er al een gesplitste array van subcategorieen aangeleverd
		# die uiteraard niet compatible is met de nieuwe style van subcategorieen
		#
		$subcatList = array();

		# Category subelementen plakken we gewoon aan elkaar, category zelf kennen we toe
		if (!empty($xml->SubCat)) {
			foreach($xml->xpath('/Spotnet/Posting/Category/SubCat') as $sub) {
				$subcatList[] = (string) $sub;
			} # foreach
		} else {
			foreach($xml->xpath('/Spotnet/Posting/Category/Sub') as $sub) {
				$subcatList[] = (string) $sub;
			} # foreach
		} # if

		# match hoofdcat/subcat-type/subcatvalue
		foreach($subcatList as $subcat) {
			if (preg_match('/(\d+)([aAbBcCdD])(\d+)/', preg_quote($subcat), $tmpMatches)) {
				$subCatVal = strtolower($tmpMatches[2]) . ((int) $tmpMatches[3]);
				$tpl_spot['subcatlist'][] = $subCatVal;
				$tpl_spot['subcat' . $subCatVal[0]] .= $subCatVal . '|';
			} # if
		} # foreach
		
		# and return the parsed XML
		return $tpl_spot;
	} # parseFull()

	function parseXover($subj, $from, $messageid, $rsakeys) {
		$_ID = 2;
		$_CAT = 0;
		$_STAMP = 3;

		// initialiseer wat variabelen
		$spot = array();
		
		// Eerst splitsen we de header string op in enkel de category info e.d.
		$tmpHdr = preg_split('(<|>)', $from);
		
		if (count($tmpHdr) < 2) {
			return null;
		} # if
		
		$tmpHdr = explode('@', $tmpHdr[1]);
		if (count($tmpHdr) < 2) {
			return false;
		} # if 
		
		$spot['header'] = $tmpHdr[1];
		$spot['verified'] = false;
		$spot['messageid'] = substr($messageid, 1, strlen($messageid) - 2);
		$fields = explode('.', $spot['header']);

		if (count($fields) >= 6) {
			$spot['id'] = $fields[$_ID];

			if ($spot['id'] > 9) {
				$spot['category'] = (substr($fields[$_CAT], 0, 1)) - 1.0;
				
				// extract de posters name
				$spot['poster'] = explode('<', $from);
				$spot['poster'] = Trim($spot['poster'][0]);
				
				// key id
				$spot['keyid'] = (int) substr($fields[$_CAT], 1, 1);
				
				// groupname
				$spot['groupname'] = 'free.pt';
				
				if ($spot['keyid'] >= 1) {
					$expression = '';
					$strInput = substr($fields[$_CAT], 2);
					$recentKey = $spot['keyid'] <> 1;
					
					if ($recentKey) {	
						if ((strlen($strInput) == 0) || ((strlen($strInput) % 3) != 0)) {
							exit;
						} # if

						$subcatAr = $this->splitBySizEx($strInput, 3);
						foreach($subcatAr as $str) {
							if (strlen($str) > 0) {
								$expression .= strtolower(substr($str, 0, 1)) . ((int) substr($str, 1)) . '|';
							} # if
						} # foeeach
						
						$spot['subcat'] = (int) (substr($subcatAr[0], 1));
						
					} else {
						$list = array();
						for($i = 0; $i < strlen($strInput); $i++) {
							if (($strInput[$i] == 0) && (!is_numeric($strInput[$i])) && (strlen($expression) > 0)) {
								$list[] = $expression;
								$expression = '';
							} # if
							
							$expression .= $strInput[$i];
						} # for

						$list[] = $expression;
						$expression = '';
						foreach($list as $str) {
							$expression .= strtolower(substr($str, 0, 1)) . substr($str, 1) . '|';
						} # foreach
						
						$spot['subcat'] = (int) (substr($list[0], 1));
					} # else if $recentKey 

					# Break up the subcategories per subcat-type
					if (strlen($expression) > 0) {
						$subcats = explode('|', $expression);
						$spot['subcata'] = '';
						$spot['subcatb'] = '';
						$spot['subcatc'] = '';
						$spot['subcatd'] = '';
						
						foreach($subcats as $subcat) {
							if (array_search(strtolower(substr($subcat, 0, 1)), array('a','b','c','d')) !== false) {
								$spot['subcat' . strtolower(substr($subcat, 0, 1))] .= $subcat . '|';
							} # if
						} # foreach
					} # if
						
					if ((strpos($subj, '=?') !== false) && (strpos($subj, '?=') !== false)) {
						# Make sure its as simple as possible
						$subj = str_replace('?= =?', '?==?', $subj);
						$subj = str_replace('\r', '', trim($this->oldEncodingParse($subj)));
						$subj = str_replace('\n', '', $subj);
					} # if
					
					if ($recentKey) {
						if (strpos($subj, '|') !== false) {
							$tmp = explode('|', $subj);
							
							$spot['title'] = trim($tmp[0]);
							$spot['tag'] = trim($tmp[1]);
						} else {
							$spot['title'] = trim($subj);
							$spot['tag'] = '';
						} # else
					} else {
						$tmp = explode('|', $subj);
						if (count($tmp) <= 1) {
							$tmp = array($subj);
						} # if
						
						$spot['tag'] = trim($tmp[count($tmp) - 1]);

						# remove the tags from the array
						array_pop($tmp);
						array_pop($tmp);
						
						$spot['title'] = trim(implode('|', $tmp));
						
						if ((strpos($spot['title'], chr(0xc2)) !== false) | (strpos($spot['title'], chr(0xc3)) !== false)) {
							$spot['title'] = trim($this->oldEncodingParse($spot['title']));
						} # if
					} # if recentKey

					$spot['stamp'] = $fields[$_STAMP];
					if (((strlen($spot['title']) != 0) && (strlen($spot['poster']) != 0)) && (($spot['id'] >= 1000000) || $recentKey)) {

						# Vanaf spot-id 1385910 komen we KeyID's 2 tegen, dus vanaf daar gaan we alle niet-signed posts weigeren.
						$mustbeSigned = $recentKey | (!$recentKey & ($spot['id'] > 1385910));

						# FIXME
						#
						# somehow there is a check that the key is only validated for spots with key id 2 ?
						# not sure about the code as it only seems to execute for more than 25000 spots or something?
						#
						$mustbeSigned = (($mustbeSigned) & ($spot['keyid'] >= 2));
						
						# and verify the signature it
						if ($mustbeSigned) {
							$spot['headersign'] = $fields[count($fields) - 1];
							
							if (strlen($spot['headersign']) != 0) {
								$spot['wassigned'] = true;

								# KeyID 7 betekent dat het serverless signed is
								if ($spot['keyid'] == 7) {
									$userSignedHash = sha1('<' . $spot['messageid'] . '>', false);
									$spot['verified'] = (substr($userSignedHash, 0, 3) == '0000');
								} else {
									# the signature this header is signed with
									$signature = base64_decode($this->unspecialString($spot['headersign']));

									# This is the string to verify
									$toCheck = $spot['title'] . substr($spot['header'], 0, strlen($spot['header']) - strlen($spot['headersign']) - 1) . $spot['poster'];
									
									# Check the RSA signature on the spot
									$spot['verified'] = $this->checkRsaSignature($toCheck, $signature, $rsakeys[$spot['keyid']]);
								} # else
							} # if
						} # if must be signed
						else {
							$spot['verified'] = true;
							$spot['wassigned'] = false;
						} # if doesnt need to be signed, pretend that it is
					} # if
				} # if
			} # if
			
		} # if 

		return $spot;
	} # parseXover
	
	private function fixPadding($strInput) {
		while ((strlen($strInput) % 4) != 0) {
			$strInput .= '=';
		} # while
	
		return $strInput;
	} # fixPadding

	/*private */function unspecialString($strInput) {
		$strInput = $this->fixPadding($strInput);
		$strInput = str_replace('-s', '/', $strInput);
		$strInput = str_replace('-p', '+', $strInput);
		
		return $strInput;
	} # unspecialString
	
	/*private */function unspecialZipStr($strInput) {
		$strInput = str_replace('=C', "\n", $strInput);
		$strInput = str_replace('=B', "\r", $strInput);
		$strInput = str_replace('=A', "\0", $strInput);
		$strInput = str_replace('=D', '=', $strInput);
	
		return $strInput;
	} # unspecialZipstr

	private function splitBySizEx($strInput, $iSize) {
		$length = strlen($strInput);
		$index = 0;
		$tmp = array();

		for ($i = 0; ($i + $iSize) <= ($length + $iSize); $i += $iSize) {
			$tmp[$index] = substr($strInput, $i, $iSize);
			$index++;
		} # for
		
		return $tmp;
	} # splitBySizEx

	
	function parseEncodedWord($inputStr) {
		$str = '';
		$builder = '';
		
		if (substr($inputStr, 0, 1) !== '=') {
			return $inputStr;
		} # if
		
		if (substr($inputStr, strlen($inputStr) - 2) !== '?=') {
			return $inputStr;
		} # if
		
		$name = substr($inputStr, 2, strpos($inputStr, '?', 2) - 2);
		if (strtoupper($name) == 'UTF8') {
			$name = 'UTF-8';
		} # if
		
		$c = $inputStr[strlen($name) + 3];
		$startIndex = strlen($name) + 5;
		
		switch(strtolower($c)) {
			case 'q' :
			{
				while ($startIndex < strlen($input)) {
					$ch2 = $strInput[$startIndex];
					$chArray = null;
					
					switch($ch2) {
						case '=': {
							if ($startIndex >= (strlen($input) - 2)) {
								$chArray = substr($strInput, $startIndex + 1, 2);
							} # if
							
							if ($chArray == null) {
								echo 'Untested code path!';
								$builder .= $chArray . chr(10);
								$startIndex += 3;
							} # if 
							
							continue;
						} # case '=' 
						
						case '?': {
							if ($strInput[$startIndex + 1] == '=') {
								$startIndex += 2;
							} # if
							
							continue;
						} # case '?' 
					} # switch
					
					$builder .= $ch2;
					$startIndex++;
				} # while
				break;
			} # case 'q'
			
			case 'b' :
			{
				$builder .= base64_decode(substr($inputStr, $startIndex, ((strlen($inputStr) - $startIndex) - 2)));
				break;
			} # case 'b'
		} # switch
		
		return $builder;
	} # parseEncodedWord
	
	function oldEncodingParse($inputStr) {
		$builder = '';
		$builder2 = '';
		$encodedWord = false;
		$num = 0;
		
		while ($num < strlen($inputStr)) {
			$bliep = false;
			$ch = $inputStr[$num];

			switch($ch) {
				case '=' : 
				{
						if (($num != (strlen($inputStr) - 1)) && ($inputStr[$num + 1] == '?')) {
							$encodedWord = true;
						} # if
						break;
				} # case '='
						
				case '?' :
				{
						$ch2 = ' ';
						
						if ($num != (strlen($inputStr) - 1)) {
							$ch2 = $inputStr[$num+1];
						} # if
						
						if ($ch2 != '=') {
							break;
						} # if
						
						$encodedWord = false;
						$builder .= $ch . $ch2;
						$builder2 .= $this->parseEncodedWord($builder);
						$builder = '';
						$num += 2;
						$bliep = true;
						continue;						
				} # case '?' 
			} # switch
			
			if (!$bliep) {
				if ($encodedWord) {
					$builder .= $ch;
					$num++;
				} else {	
					$builder2 .= $ch;
					$num++;
				} # else
			} # if
						
		} # while
		
		return $builder2;
	} # oldEncodingParse

	public function checkRsaSignature($toCheck, $signature, $rsaKey) {
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
	
} # class Spot

