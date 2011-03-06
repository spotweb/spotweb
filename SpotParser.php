<?php
require_once "Math/BigInteger.php";
require_once "Crypt/RSA.php";
require_once "settings.php";

class SpotParser {
	private $_xmlarray = array();
	private $_xmlelement = '';

	private function xmlfullStartElement($parser, $name, $attrs) 
	{
		$this->_xmlelement = strtolower($name);
	} # xmlfullStartElement
	
	private function xmlfullEndElement($parser, $name) 
	{
		$this->_xmlelement = '';
	} # xmlfullEndElement

	private function xmlfullCharacterHandler($parser, $data) {
		if ((isset($this->_xmlarray[$this->_xmlelement])) && (!empty($this->_xmlarray[$this->_xmlelement]))) {
			if (!is_array($this->_xmlarray[$this->_xmlelement])) {
				$this->_xmlarray[$this->_xmlelement] = array($this->_xmlarray[$this->_xmlelement], $data);
			} else {
				$this->_xmlarray[$this->_xmlelement][] = $data;
			} # else
		} else {
			$this->_xmlarray[$this->_xmlelement] = $data;
		} # else
	} # xmlfullCharacterHandler

	function parseFull($xml) {
		# Gebruik een spot template zodat we altijd de velden hebben die we willen
		$tpl_spot = array('category' => '', 'website' => '', 'image' => '', 'sabnzbdurl' => '', 'messageid' => '', 'searchurl' => '', 'description' => '',
						  'sub' => '', 'size' => '', 'poster' => '', 'tag' => '', 'segment' => '', 'title' => '', 'key-id' => '');
		$this->_xmlarray = $tpl_spot;
		
		$xml_parser = xml_parser_create();
		xml_set_element_handler($xml_parser, array($this, 'xmlfullStartElement'), array('SpotParser', 'xmlfullEndElement'));
		xml_set_character_data_handler($xml_parser, array($this, 'xmlfullCharacterHandler'));
		
		if (!xml_parse($xml_parser, $xml, true)) {
			$this->_xmlarray = false;
		} # if error parsing
		
		xml_parser_free($xml_parser);

		# fix the category in the XML array but only for new spots
		if ($this->_xmlarray['key-id'] != 1) {
			$this->_xmlarray['category'] = ((int) $this->_xmlarray['category']) - 1;
		} # if
		
		# We bieden de segment list altijd aan in een array, 
		# dus fix het als het nu geen array is
		if (!is_array($this->_xmlarray['segment'])) {
			$this->_xmlarray['segment'] = array($this->_xmlarray['segment']);
		} # if
		
		# and return the parsed XML
		return $this->_xmlarray;
	} # parseFull()

	function parseXover($subj, $from, $messageid, $rsakeys) {
		$_ID = 2;
		$_CAT = 0;
		$_STAMP = 3;

		// initialiseer wat variabelen
		$spot = array();
		
		// Eerst splitsen we de header string op in enkel de category info e.d.
		$tmpHdr = preg_split("(<|>)", $from);
		
		if (count($tmpHdr) < 2) {
			return null;
		} # if
		
		$tmpHdr = explode("@", $tmpHdr[1]);
		$spot['Header'] = $tmpHdr[1];
		$spot['Verified'] = false;
		$spot['MessageID'] = substr($messageid, 1, strlen($messageid) - 2);
		$fields = explode(".", $spot['Header']);

		if (count($fields) >= 6) {
			$spot['ID'] = $fields[$_ID];

			if ($spot['ID'] > 9) {
				$spot['Category'] = (substr($fields[$_CAT], 0, 1)) - 1.0;
				
				// extract de posters name
				$spot['Poster'] = explode("<", $from);
				$spot['Poster'] = Trim($spot['Poster'][0]);
				
				// key id
				$spot['KeyID'] = (int) substr($fields[$_CAT], 1, 1);
				
				// groupname
				$spot['GroupName'] = 'free.pt';
				
				if ($spot['KeyID'] >= 1) {
					$expression = '';
					$strInput = substr($fields[$_CAT], 2);
					$recentKey = $spot['KeyID'] <> 1;
					
					if ($recentKey) {	
						if ((strlen($strInput) == 0) || ((strlen($strInput) % 3) != 0)) {
							exit;
						} # if

						$subcatAr = $this->SplitBySizEx($strInput, 3);
						foreach($subcatAr as $str) {
							if (strlen($str) > 0) {
								$expression .= strtolower(substr($str, 0, 1)) . ((int) substr($str, 1)) . "|";
							} # if
						} # foeeach
						
						$spot['SubCat'] = (int) (substr($subcatAr[0], 1));
						
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
							$expression .= strtolower(substr($str, 0, 1)) . substr($str, 1) . "|";
						} # foreach
						
						$spot['SubCat'] = (int) (substr($list[0], 1));
					} # else if $recentKey 

					# Break up the subcategories per subcat-type
					if (strlen($expression) > 0) {
						$subcats = explode('|', $expression);
						$spot['SubCatA'] = '';
						$spot['SubCatB'] = '';
						$spot['SubCatC'] = '';
						$spot['SubCatD'] = '';
						
						foreach($subcats as $subcat) {
							if (array_search(strtolower(substr($subcat, 0, 1)), array('a','b','c','d')) !== false) {
								$spot['SubCat' . strtoupper(substr($subcat, 0, 1))] .= $subcat . '|';
							} # if
						} # foreach
					} # if
						
					if ((strpos($subj, "=?") !== false) && (strpos($subj, "?=") !== false)) {
						# Make sure its as simple as possible
						$subj = str_replace("?= =?", "?==?", $subj);
						$subj = str_replace("\r", "", trim($this->OldEncodingParse($subj)));
						$subj = str_replace("\n", "", $subj);
					} # if
					
					if ($recentKey) {
						if (strpos($subj, "|") !== false) {
							$tmp = explode("|", $subj);
							
							$spot['Title'] = trim($tmp[0]);
							$spot['Tag'] = trim($tmp[1]);
						} else {
							$spot['Title'] = trim($subj);
							$spot['Tag'] = '';
						} # else
					} else {
						$tmp = explode("|", $subj);
						if (count($tmp) <= 1) {
							$tmp = array($subj);
						} # if
						
						$spot['Tag'] = trim($tmp[count($tmp) - 1]);

						# remove the tags from the array
						array_pop($tmp);
						array_pop($tmp);
						
						$spot['Title'] = trim(implode('|', $tmp));
						
						if ((strpos($spot['Title'], chr(0xc2)) !== false) | (strpos($spot['Title'], chr(0xc3)) !== false)) {
							$spot['Title'] = trim($this->OldEncodingParse($spot['Title']));
						} # if
					} # if recentKey

					$spot['Stamp'] = $fields[$_STAMP];
					if (((strlen($spot['Title']) != 0) && (strlen($spot['Poster']) != 0)) && (($spot['ID'] >= 1000000) || $recentKey)) {

						# Vanaf spot-id 1385910 komen we KeyID's 2 tegen, dus vanaf daar gaan we alle niet-signed posts weigeren.
						$mustbeSigned = $recentKey | (!$recentKey & ($spot['ID'] > 1385910));

						# FIXME
						#
						# somehow there is a check that the key is only validated for spots with key id 2 ?
						# not sure about the code as it only seems to execute for more than 25000 spots or something?
						#
						$mustbeSigned = (($mustbeSigned) & ($spot['KeyID'] >= 2));
						
						# and verify the signature it
						if ($mustbeSigned) {
							$spot['HeaderSign'] = $fields[count($fields) - 1];
							
							if (strlen($spot['HeaderSign']) != 0) {
								$spot['WasSigned'] = true;

								# This is the string to verify
								$toCheck = $spot['Title'] . substr($spot['Header'], 0, strlen($spot['Header']) - strlen($spot['HeaderSign']) - 1) . $spot['Poster'];

								# the signature this header is signed with
								$signature = base64_decode($this->UnspecialString($spot['HeaderSign']));

								# Check the RSA signature on the spot
								$spot['Verified'] = $this->checkRsaSignature($toCheck, $signature, $rsakeys[$spot['KeyID']]);
							} # if
						} # if must be signed
						else {
							$spot['Verified'] = true;
							$spot['WasSigned'] = false;
						} # if doesnt need to be signed, pretend that it is
					} # if
				} # if
			} # if
			
		} # if 

		return $spot;
	} # parseXover
	
	private function FixPadding($strInput) {
		while ((strlen($strInput) % 4) != 0) {
			$strInput .= "=";
		} # while
	
		return $strInput;
	} # FixPadding

	/*private */function UnspecialString($strInput) {
		$strInput = $this->FixPadding($strInput);
		$strInput = str_replace("-s", "/", $strInput);
		$strInput = str_replace("-p", "+", $strInput);
		
		return $strInput;
	} # UnspecialString
	
	/*private */function UnspecialZipStr($strInput) {
		$strInput = str_replace("=C", "\n", $strInput);
		$strInput = str_replace("=B", "\r", $strInput);
		$strInput = str_replace("=A", "\0", $strInput);
		$strInput = str_replace("=D", "=", $strInput);
		
		return $strInput;
	} # UnspecialZipstr

	private function SplitBySizEx($strInput, $iSize) {
		$length = strlen($strInput);
		$index = 0;
		$tmp = array();

		for ($i = 0; ($i + $iSize) <= ($length + $iSize); $i += $iSize) {
			$tmp[$index] = substr($strInput, $i, $iSize);
			$index++;
		} # for
		
		return $tmp;
	} # SplitBySizEx

	
	function ParseEncodedWord($inputStr) {
		$str = "";
		$builder = "";
		
		if (substr($inputStr, 0, 1) !== '=') {
			return $inputStr;
		} # if
		
		if (substr($inputStr, strlen($inputStr) - 2) !== '?=') {
			return $inputStr;
		} # if
		
		$name = substr($inputStr, 2, strpos($inputStr, "?", 2) - 2);
		if (strtoupper($name) == "UTF8") {
			$name = "UTF-8";
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
								echo "Untested code path!";
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
	
	function OldEncodingParse($inputStr) {
		$builder = "";
		$builder2 = "";
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
						
						if ($ch2 != "=") {
							break;
						} # if
						
						$encodedWord = false;
						$builder .= $ch . $ch2;
						$builder2 .= $this->ParseEncodedWord($builder);
						$builder = "";
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
	} # OldEncodingParse

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
} # class Spot

