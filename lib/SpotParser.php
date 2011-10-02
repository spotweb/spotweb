<?php
class SpotParser {
	private $_spotSigning = null;
	
	function __construct() {
		$this->_spotSigning = new SpotSigning();
	} # ctor
	
	
	function parseFull($xmlStr) {
		# Gebruik een spot template zodat we altijd de velden hebben die we willen
		$tpl_spot = array('category' => '', 'website' => '', 'image' => '', 'sabnzbdurl' => '', 'messageid' => '', 'searchurl' => '', 'description' => '',
						  'sub' => '', 'filesize' => '', 'poster' => '', 'tag' => '', 'nzb' => '', 'title' => '', 
						  'filename' => '', 'newsgroup' => '', 'subcatlist' => array(), 'subcata' => '', 'subcatb' => '', 
						  'subcatc' => '', 'subcatd' => '', 'subcatz' => '');

		/* 
		 * Onderdruk errors bij corrupte messaegeid, bv: <evoCgYpLlLkWe97TQAmnV@spot.net>
		 */		
		$xml = @(new SimpleXMLElement($xmlStr));
		$xml = $xml->Posting;
		$tpl_spot['category'] = (string) $xml->Category;
		$tpl_spot['website'] = (string) $xml->Website;
		$tpl_spot['description'] = (string) utf8_encode($xml->Description);
		$tpl_spot['filesize'] = (string) $xml->Size;
		$tpl_spot['poster'] = (string) utf8_encode($xml->Poster);
		$tpl_spot['tag'] = (string) utf8_encode($xml->Tag);
		$tpl_spot['title'] = (string) utf8_encode($xml->Title);

		# FTD spots bevatten de filename
		if (!empty($xml->Filename)) {
			$tpl_spot['filename'] = (string) $xml->Filename;
		} # if

		# FTD spots bevatten de newsgroup
		if (!empty($xml->Newsgroup)) {
			$tpl_spot['newsgroup'] = (string) $xml->newsgroup;
		} # if

		# Images behandelen we op een speciale manier, in oude spots
		# was er gewoon een URL, in de nieuwe een hoogte/lengte/messageid
		if (empty($xml->Image->Segment)) {
			$tpl_spot['image'] = (string) $xml->Image;
 		} else {
			$tpl_spot['image'] = Array(
				'height' => (string) $xml->Image['Height'],
				'width' => (string) $xml->Image['Width']
			);
			
			foreach($xml->xpath('/Spotnet/Posting/Image/Segment') as $seg) {
				$tpl_spot['image']['segment'][] = (string) $seg;
			} # foreach
			
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
		# Bij FTD spots wordt er al een gesplitste array van subcategorieen aangeleverd
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
			if (preg_match('/(\d+)([aAbBcCdDzZ])(\d+)/', preg_quote($subcat), $tmpMatches)) {
				$subCatVal = strtolower($tmpMatches[2]) . ((int) $tmpMatches[3]);
				$tpl_spot['subcatlist'][] = $subCatVal;
				$tpl_spot['subcat' . $subCatVal[0]] .= $subCatVal . '|';
			} # if
		} # foreach
		
		# we zetten de Z3 category erbij op het moment dat een oude spot in de erotiek
		# category valt, dit maakt ons filter een stuk simpeler.
		if (empty($tpl_spot['subcatz'])) {
			$tpl_spot['subcatz'] = SpotCategories::createSubcatZ($tpl_spot['category'], $tpl_spot['subcata'] . $tpl_spot['subcatb'] . $tpl_spot['subcatd']);
		} # if
		
		# and return the parsed XML
		return $tpl_spot;
	} # parseFull()

	function parseXover($subj, $from, $date, $messageid, $rsaKeys) {
		// initialiseer wat variabelen
		$spot = array();


		/*
		 * De "From" header is als volgt opgebouwd:
		 *
		 *   From: [Nickname] <[RANDOM or PUBLICKEY]@[CAT][KEY-ID][SUBCAT].[SIZE].[RANDOM].[DATE].[CUSTOM-ID].[CUSTOM-VALUE].[SIGNATURE]>
		 *
		 * We willen nu alles extracten wat achter de '@' staat, maar omdat een nickname theoretisch ook een @ kan bevatten
		 * doen we eerst wat meer moeite 
		 */
		$fromInfoPos = strpos($from, '<');
		if ($fromInfoPos === false) {
			return false;
		} else {
			# Haal de postername en de <>'s weg
			$fromAddress = explode('@', substr($from, $fromInfoPos + 1, -1));
			if (count($fromAddress) < 2) {
				return false;
			} # if
			$spot['selfsignedpubkey'] = $this->unSpecialString($fromAddress[0]);
			$spot['header'] = $fromAddress[1];
		} # if

		/* 
		 * Initialiseer wat basis variabelen, doordat we verified op false zetten
		 * zal de spot altijd nog genegeerd worden ook al geven we nu de spot array
		 * terug 
		 */
		$spot['verified'] = false;
		$spot['filesize'] = 0;
		$spot['messageid'] = substr($messageid, 1, strlen($messageid) - 2);
		$spot['stamp'] = strtotime($date);

		/*
		 * Breek de .-delimited velden op in een array zodat we er makkelijker wat
		 * mee kunnen doen. We hebben tenminste 6 velden nodig, anders is de spot
		 * sowieso ongeldig. Meer velden kan (zie spec)
		 */		
		$fields = explode('.', $spot['header']);
		if (count($fields) < 6) {
			return false;
		} # if

		/*
		 * De velden die voor het oprapen liggen, halen we nu ook op
		 */
		$spot['poster'] = substr($from, 0, $fromInfoPos -1);
		$spot['category'] = (substr($fields[0], 0, 1)) - 1.0;
		$spot['keyid'] = (int) substr($fields[0], 1, 1);
		$spot['filesize'] = $fields[1];
		$spot['subcata'] = '';
		$spot['subcatb'] = '';
		$spot['subcatc'] = '';
		$spot['subcatd'] = '';
		$spot['subcatz'] = '';
		$isRecentKey = $spot['keyid'] <> 1;

		/* 
		 * Als er sowieso geen geldige keyid is, is de spot ook ongeldig
		 */
		if ($spot['keyid'] < 0) {
			return false;
		} # if

		/*
		 * De lijst met subcategorieen is opgebouwd afhankelijk van hoe recent de spot is.
		 *
		 * FTD spots zetten alle subcategorieen gewoon achter elkaar, dus bv: a9b4c0d5d15d11
		 * nieuwere spots reserveren steeds 3 karakters voor elke categorie, dus: a09b04c00d05d15d11.
		 *
		 * Omdat beide feitelijk dezelfde karakteristieken hebben, parseren we die op dezelfde
		 * manier. We voegen aan de $strCatLis een extra token toe zodat de laatste categorie ook geparsd
		 * kan worden. We voegen drie karakters toe zodat een eventuele sanitycheck (strlen() % 3 = 0) nog
		 * zou valideren.
		 */
		$strCatList = strtolower(substr($fields[0], 2)) . '!!!';
		$strCatListLen = strlen($strCatList);

		/*
		 * We initialiseren wat tijdelijke variables zodat we hier de sanity checking
		 * kunnen doen
		 */
		$validSubcats = array('a' => true, 'b' => true, 'c' => true, 'd' => true, 'z' => true);
		$tmpCatBuild = '';
		

		/* Probeer nu alle subcategorieen te extracten */
		for($i = 0; $i < $strCatListLen; $i++) {
			# Als het huidige karakter geen getal is, dan hebben we de volgende
			# categorie gevonden, voeg die toe aan de lijst met categorieen
			if ((!is_numeric($strCatList[$i])) && (!empty($tmpCatBuild))) {
				if (isset($validSubcats[$tmpCatBuild[0]])) {
					$spot['subcat' . $tmpCatBuild[0]] .= $tmpCatBuild[0] . (int) substr($tmpCatBuild, 1) . '|';
				} # if
				
				$tmpCatBuild = '';
			} # if

			$tmpCatBuild .= $strCatList[$i];
		} # for

		# We vullen hier de z categorieen alvast op in het geval er geen Z category gegeven is
		if (empty($spot['subcatz'])) {
			$spot['subcatz'] = SpotCategories::createSubcatz($spot['category'], $spot['subcata'] . $spot['subcatb'] . $spot['subcatd']);
		} # if

		if ((strpos($subj, '=?') !== false) && (strpos($subj, '?=') !== false)) {
			# Make sure its as simple as possible
			$subj = str_replace('?= =?', '?==?', $subj);
			$subj = str_replace('\r', '', trim($this->oldEncodingParse($subj)));
			$subj = str_replace('\n', '', $subj);
		} # if

		if ($isRecentKey) {
			$tmp = explode('|', $subj);

			$spot['title'] = trim($tmp[0]);
			if (count($tmp) > 1) {
				$spot['tag'] = trim($tmp[1]);
			} else {
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

		# Een title en poster zijn verplicht, anders kan de signature niet gechecked worden
		if (((strlen($spot['title']) == 0) || (strlen($spot['poster']) == 0))) {
			return $spot;
		} # if
		
		# Als er een recentkey is (key <> 1), OF de spot is na 2010 geplaatst, dan moet
		# de spot gesigned zijn.
		$mustbeSigned = $isRecentKey | ($spot['stamp'] > 1293870080);
		if ($mustbeSigned) {
			$spot['headersign'] = $fields[count($fields) - 1];

			if (strlen($spot['headersign']) != 0) {

				$spot['wassigned'] = true;

				# KeyID 7 betekent dat een hashcash vereist is
				if ($spot['keyid'] == 7) {
					$userSignedHash = sha1('<' . $spot['messageid'] . '>', false);
					$spot['verified'] = (substr($userSignedHash, 0, 3) == '0000');
					
					/*
					 * Create a fake RSA keyarray so we can validate it using our standard
					 * infrastructure
					 */
					 if ($spot['verified']) {
					 /* Not sure about this
						$userRsaKey = array(7 => array('modulo' => $spot['selfsignedpubkey'],
													   'exponent' => 'AQAB'));
						$spot['verified'] = $this->_spotSigning->verifySpotHeader($spot, $signature, $userRsaKey);
					*/
					} # if
				} else {
					# the signature this header is signed with
					$signature = $this->unspecialString($spot['headersign']);

					$spot['verified'] = $this->_spotSigning->verifySpotHeader($spot, $signature, $rsaKeys);
				} # else
			} # if
		} # if must be signed
		else {
			$spot['verified'] = true;
			$spot['wassigned'] = false;
		} # if doesnt need to be signed, pretend that it is

		# Nu zetten we de titel en dergelijke om naar utf8, we kunnen
		# dat niet eerder doen omdat anders de RSA signature niet meer
		# klopt.
		if (($spot !== false) && ($spot['verified'])) {
			$spot['title'] = utf8_encode($spot['title']);
			$spot['poster'] = utf8_encode($spot['poster']);
			$spot['tag'] = utf8_encode($spot['tag']);
			
			# als de spot in de toekomst ligt, dan corrigeren we dat naar nu
			if (time() < $spot['stamp']) {
				$spot['stamp'] = time();
			} # if
		} # if

		return $spot;
	} # parseXover

	/*private */function unspecialZipStr($strInput) {
		$strInput = str_replace('=C', "\n", $strInput);
		$strInput = str_replace('=B', "\r", $strInput);
		$strInput = str_replace('=A', "\0", $strInput);
		$strInput = str_replace('=D', '=', $strInput);

		return $strInput;
	} # unspecialZipstr

	/*private */function specialZipStr($strInput) {
		$strInput = str_replace("=", '=D', $strInput);
		$strInput = str_replace("\n", '=C', $strInput);
		$strInput = str_replace("\r", '=B', $strInput);
		$strInput = str_replace("\0", '=A', $strInput);

		return $strInput;
	} # specialZipstr
	
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
				while ($startIndex < strlen($inputStr)) {
					$ch2 = $inputStr[$startIndex];
					$chArray = null;

					switch($ch2) {
						case '=': {
							if ($startIndex >= (strlen($inputStr) - 2)) {
								$chArray = substr($inputStr, $startIndex + 1, 2);
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

	function specialString($strInput) {

		$strInput = str_replace('/', '-s', $strInput);
		$strInput = str_replace('+', '-p', $strInput);

		return $strInput;
	} # specialString

	/*private */function unspecialString($strInput) {
		/* Zorg dat de input string gepad wordt naar een multiple of 4 */
		$paddingLen = strlen($strInput) % 4;
		if ($paddingLen > 0) {
			$strInput .= str_repeat('=', (4 - $paddingLen));
		} # if
		
		return str_replace(array('-s', '-p'), array('/', '+'), $strInput);
	} # unspecialString
	
	/*
	 * Creates XML out of the Spot information array
	 */
	function convertSpotToXml($spot, $imageInfo, $nzbSegments) {
		# Opbouwen XML
		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = false;

		$mainElm = $doc->createElement('Spotnet');
		$postingElm = $doc->createElement('Posting');
		$postingElm->appendChild($doc->createElement('Key', $spot['key']));
		$postingElm->appendChild($doc->createElement('Created', time()));
		$postingElm->appendChild($doc->createElement('Poster', $spot['poster']));
		$postingElm->appendChild($doc->createElement('Size', $spot['filesize']));

		if (strlen($spot['tag']) > 0) {
			$postingElm->appendChild($doc->createElement('Tag', $spot['tag']));
		} # if

		/* 
		 * Title element is enclosed in CDATA
		 */
		$titleElm = $doc->createElement('Title');
		$titleElm->appendChild($doc->createCDATASection($spot['title']));
		$postingElm->appendChild($titleElm);
		
		/* 
		 * Description element is enclosed in CDATA
		 */
		$descrElm = $doc->createElement('Description');
		$descrElm->appendChild($doc->createCDATASection( str_replace( array("\r\n", "\r", "\n"), "[br]", $spot['body'])));
		$postingElm->appendChild($descrElm);

		/*
		 * Website element ins enclosed in cdata section
		 */
		$websiteElm = $doc->createElement('Website');
		$websiteElm->appendChild($doc->createCDATASection($spot['website']));
		$postingElm->appendChild($websiteElm);
		
		/*
		 * Category contains both an textelement as nested elements, so
		 * we do it somewhat different
		 *   <Category>01<Sub>01a09</Sub><Sub>01b04</Sub><Sub>01c00</Sub><Sub>01d11</Sub></Category>
		 */
		$categoryElm = $doc->createElement('Category');
		$categoryElm->appendChild($doc->createTextNode( str_pad($spot['category'] + 1, 2, '0', STR_PAD_LEFT) ));
		
		foreach($spot['subcatlist'] as $subcat) {
			if (!empty($subcat)) {
				$categoryElm->appendChild($doc->createElement('Sub', 
						str_pad($spot['category'] + 1, 2, '0', STR_PAD_LEFT) . 
						$subcat[0] . 
						str_pad(substr($subcat, 1), 2, '0', STR_PAD_LEFT)));
			} # if
		} # foreach
		$postingElm->appendChild($categoryElm);

		/*
		 * We only support embedding the image on usenet, so 
		 * we always use that
		 *
		 * 		<Image Width='1500' Height='1500'><Segment>4lnDJqptSMMifJpTgAc52@spot.net</Segment><Segment>mZgAC888A6EkfJpTgAJEX@spot.net</Segment></Image>
		 */
		$imgElm = $doc->createElement('Image');
		$imgElm->setAttribute('Width', $imageInfo['width']);
		$imgElm->setAttribute('Height', $imageInfo['height']);
		foreach($imageInfo['segments'] as $segment) {
			$imgElm->appendChild($doc->createElement('Segment', $segment));
		} # foreach
		$postingElm->appendChild($imgElm);
		
		/*
		 * Add the segments to the nzb file
		 */
		$nzbElm = $doc->createElement('NZB');
		foreach($nzbSegments as $segment) {
			$nzbElm->appendChild($doc->createElement('Segment', $segment));
		} # foreach
		$postingElm->appendChild($nzbElm);
		
		$mainElm->appendChild($postingElm);
		$doc->appendChild($mainElm);

		return $doc->saveXML($mainElm);
	} # spotToXml
	
} # class Spot
