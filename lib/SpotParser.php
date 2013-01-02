<?php
class SpotParser {
	private $_spotSigning = null;
	
	function __construct() {
		$this->_spotSigning = Services_Signing_Base::newServiceSigning();
	} # ctor
	

	/*
	 * Some Spotnet clients create invalid XML - see 
	 * messageid ZOB4WPyqQfcHqykUAES8q@spot.net for example, because
	 * it uses an unescaped & not in an CDATA block.
	 */
	private function correctElmContents($xmlStr, $elems) {
		$cdataStart = '<![CDATA[';
		$cdataEnd = ']]>';

		/*
		 * replace low-ascii characters, see messageid KNCuzvnxJJErJibUAAxQJ@spot.net
		 */
                $xmlStr = preg_replace('/[\x00-\x1F]/', '', $xmlStr);

		/* and loop through all elements and fix them up */
		foreach($elems as $elementName) {
			// find the element entries
			$startElem = stripos($xmlStr, '<' . $elementName . '>');
			$endElem = stripos($xmlStr, '</' . $elementName . '>');

			if (($startElem === false) || ($endElem === false)) {
				continue;
			}

			/*
			 * Make sure this elements content is not preceeded by the
			 * required CDATA header
			 */ 
			if (substr($xmlStr, $startElem + strlen($elementName) + 2, strlen($cdataStart)) !== $cdataStart) {
				$xmlStr = str_replace(
					Array('<' . $elementName . '>', '</' . $elementName . '>'),
					Array('<' . $elementName . '>' . $cdataStart, $cdataEnd . '</' . $elementName . '>'),
					$xmlStr);
			} // if
		} # foreach

		return $xmlStr;
	} # correctElmContents
	
	function parseFull($xmlStr) {
		# Create a template array so we always have the full fields to prevent ugly notices
		$tpl_spot = array('category' => '', 'website' => '', 'image' => '', 'sabnzbdurl' => '', 'messageid' => '', 'searchurl' => '', 'description' => '',
						  'sub' => '', 'filesize' => '', 'poster' => '', 'tag' => '', 'nzb' => '', 'title' => '', 
						  'filename' => '', 'newsgroup' => '', 'subcatlist' => array(), 'subcata' => '', 'subcatb' => '', 
						  'subcatc' => '', 'subcatd' => '', 'subcatz' => '');

		/* 
		 * Some legacy potNet clients create incorrect/invalid multiple segments,
		 * we use this crude way to workaround this. GH issue #1608
		 */
		if (strpos($xmlStr, 'spot.net></Segment') !== false) {
                        $xmlStr = str_replace(
                                        Array('spot.net></Segment>', 'spot.ne</Segment>'),
                                        Array('spot.net</Segment>', 'spot.net</Segment>'),
					$xmlStr
                        );
		} // if 

		/* 
		 * Fix up some forgotten entity encoding / cdata sections in the XML
		 */
		$xmlStr = $this->correctElmContents($xmlStr, array('Title', 'Description', 'Image', 'Tag', 'Website'));
	
		/* 
		 * Supress errors for corrupt messageids, eg: <evoCgYpLlLkWe97TQAmnV@spot.net>
		 */		
		$xml = @(new SimpleXMLElement($xmlStr));
		$xml = $xml->Posting;
		$tpl_spot['category'] = (string) $xml->Category;
		$tpl_spot['website'] = (string) $xml->Website;
		$tpl_spot['description'] = (string) $xml->Description;
		$tpl_spot['filesize'] = (string) $xml->Size;
		$tpl_spot['poster'] = (string) utf8_encode($xml->Poster);
		$tpl_spot['tag'] = (string) utf8_encode($xml->Tag);
		$tpl_spot['title'] = (string) $xml->Title;

		# FTD spots have the filename
		if (!empty($xml->Filename)) {
			$tpl_spot['filename'] = (string) $xml->Filename;
		} # if

		# FTD spots have the newsgroup
		if (!empty($xml->Newsgroup)) {
			$tpl_spot['newsgroup'] = (string) $xml->newsgroup;
		} # if

		/*
		 * Images available can be in the XML in two different ways.
		 *
		 * Some older spots just have an URL we can use, newer spots
		 * have an height/width/messageid(s) pair we use to retrieve the image 
		 * from
		 */
		if (empty($xml->Image->Segment)) {
			$tpl_spot['image'] = (string) $xml->Image;
 		} else {
			$tpl_spot['image'] = Array(
				'height' => (string) $xml->Image['Height'],
				'width' => (string) $xml->Image['Width']
			);
			
			foreach($xml->xpath('/Spotnet/Posting/Image/Segment') as $seg) {
				# Make sure the messageid's are valid so we do not throw an NNTP error
				if (!$this->validMessageId((string) $seg)) {
					$tpl_spot['image']['segment'] = array();
					break;
				} else {
					$tpl_spot['image']['segment'][] = (string) $seg;
				} # if
			} # foreach			
		} # else

		# Just stitch together the NZB segments
		foreach($xml->xpath('/Spotnet/Posting/NZB/Segment') as $seg) {
			if (!$this->validMessageId((string) $seg)) {
				$tpl_spot['nzb'] = array();
				break;
			} else {
				$tpl_spot['nzb'][] = (string) $seg;
			} # else
		} # foreach
		
		# fix the category in the XML array but only for new spots
		if ((int) $xml->Key != 1) {
			$tpl_spot['category'] = ((int) $tpl_spot['category']) - 1;
		} # if

		/*
		 * For FTD spots an array of subcategories is created. This array is not 
		 * compatible with that of newer spots so we need two seperate codepaths
		 */
		$subcatList = array();

		/*
		 * We fix up the category list later in the system, so we just extract the
		 * list of subcategories
		 */
		if (!empty($xml->SubCat)) {
			foreach($xml->xpath('/Spotnet/Posting/Category/SubCat') as $sub) {
				$subcatList[] = (string) $sub;
			} # foreach
		} else {
			foreach($xml->xpath('/Spotnet/Posting/Category/Sub') as $sub) {
				$subcatList[] = (string) $sub;
			} # foreach
		} # if

		/*
		 * Mangle the several types of subcategory listing to make sure we only
		 * have to use one type in the rest of Spotwb
		 */
		foreach($subcatList as $subcat) {
			if (preg_match('/(\d+)([aAbBcCdDzZ])(\d+)/', preg_quote($subcat), $tmpMatches)) {
				$subCatVal = strtolower($tmpMatches[2]) . ((int) $tmpMatches[3]);
				$tpl_spot['subcatlist'][] = $subCatVal;
				$tpl_spot['subcat' . $subCatVal[0]] .= $subCatVal . '|';
			} # if
		} # foreach
		
		/*
		 * subcatz is a subcategory introduced in later Spotnet formats, we prefer to
		 * always have this subcategory so we just fake it if it's not listed.
		 */
		if (empty($tpl_spot['subcatz'])) {
			$tpl_spot['subcatz'] = SpotCategories::createSubcatZ($tpl_spot['category'], $tpl_spot['subcata'] . $tpl_spot['subcatb'] . $tpl_spot['subcatd']);
		} # if

		# map deprecated genre categories to their new genre category
		$tpl_spot['subcatd'] = SpotCategories::mapDepricatedGenreSubCategories($tpl_spot['category'], $tpl_spot['subcatd'], $tpl_spot['subcatz']);
		
		# and return the parsed XML
		return $tpl_spot;
	} # parseFull()

	function parseXover($subj, $from, $date, $messageid, $rsaKeys) {
		# Initialize an empty array, we create a basic template in a few
		$spot = array();

		/*
		 * The "From" header is created using the following system:
		 *
		 *   From: [Nickname] <[RANDOM or PUBLICKEY]@[CAT][KEY-ID][SUBCAT].[SIZE].[RANDOM].[DATE].[CUSTOM-ID].[CUSTOM-VALUE].[SIGNATURE]>
		 *		or
		 *   From: [Nickname] <[PUBLICKEY-MODULO.USERSIGNATURE]@[CAT][KEY-ID][SUBCAT].[SIZE].[RANDOM].[DATE].[CUSTOM-ID].[CUSTOM-VALUE].[SIGNATURE]>
		 *
		 *
		 * First we want to extract everything after the @ but because a nickname could contain an @, we have to mangle it a bit
		 */
		$fromInfoPos = strpos($from, '<');
		if ($fromInfoPos === false) {
			return false;
		} else {
			# Remove the posters' name and the <> characters
			$fromAddress = explode('@', substr($from, $fromInfoPos + 1, -1));
			if (count($fromAddress) < 2) {
				return false;
			} # if
			$spot['header'] = $fromAddress[1];

			/*
			 * It is possible the part before the @ contains both the 
			 * users' signature as the spots signature as signed by the user
			 */
			$headerSignatureTemp = explode('.', $fromAddress[0]);
			$spot['selfsignedpubkey'] = $this->unSpecialString($headerSignatureTemp[0]);
			if (isset($headerSignatureTemp[1])) {
				$spot['user-signature'] = $this->unSpecialString($headerSignatureTemp[1]);
			} # if
		} # if

		/* 
		 * Initialize some basic variables. We set 'verified' to false so we  can
		 * exit this function at any time and the gathered data for this spot up til
		 * then is stil ignored.
		 */
		$spot['verified'] = false;
		$spot['filesize'] = 0;
		$spot['messageid'] = substr($messageid, 1, strlen($messageid) - 2);
		$spot['stamp'] = strtotime($date);

		/*
		 * Split the .-delimited fields into an array so we can mangle it. We require
		 * atleast six fields, if any less we can safely assume the spot is invalid
		 */
		$fields = explode('.', $spot['header']);
		if (count($fields) < 6) {
			return false;
		} # if

		/*
		 * Extract the fixed fields from the header
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
		$spot['wassigned'] = false;
		$spot['spotterid'] = '';
		$isRecentKey = $spot['keyid'] <> 1;
		
		/* 
		 * If the keyid is invalid, abort trying to parse it
		 */
		if ($spot['keyid'] < 0) {
			return false;
		} # if

		/*
		 * Listings of subcategories is dependent on the age of the spot.
		 *
		 * FTD spots just list all subcategories like: a9b4c0d5d15d11
		 * Newer spots always use three characters for each subcategory like: a09b04c00d05d15d11.
		 *
		 * We really do not care for this, we just parse them using the same code as the
		 * first one.
		 *
		 * We pad $strCatList with an extra set of tokes so we always parse te last category,
		 * we make sure any sanitycheck is passed by adding 3 tokens.
		 */
		$strCatList = strtolower(substr($fields[0], 2)) . '!!!';
		$strCatListLen = strlen($strCatList);

		/*
		 * Initialize some basic variables to use for sanitychecking (eg: valid subcats)
		 */
		$validSubcats = array('a' => true, 'b' => true, 'c' => true, 'd' => true, 'z' => true);
		$tmpCatBuild = '';
		

		/* And just try to extract all given subcategories */
		for($i = 0; $i < $strCatListLen; $i++) {
			/*
			 * If the current character is not an number, we found the next
			 * subcategory. Add the current one to the list, and start
			 * parsing the new one
			 */
			if ((!is_numeric($strCatList[$i])) && (!empty($tmpCatBuild))) {
				if (isset($validSubcats[$tmpCatBuild[0]])) {
					$spot['subcat' . $tmpCatBuild[0]] .= $tmpCatBuild[0] . (int) substr($tmpCatBuild, 1) . '|';
				} # if
				
				$tmpCatBuild = '';
			} # if

			$tmpCatBuild .= $strCatList[$i];
		} # for

		/*
		 * subcatz is a subcategory introduced in later Spotnet formats, we prefer to
		 * always have this subcategory so we just fake it if it's not listed.
		 */
		if (empty($spot['subcatz'])) {
			$spot['subcatz'] = SpotCategories::createSubcatz($spot['category'], $spot['subcata'] . $spot['subcatb'] . $spot['subcatd']);
		} # if
		
		# map deprecated genre categories to their new genre category
		$spot['subcatd'] = SpotCategories::mapDepricatedGenreSubCategories($spot['category'], $spot['subcatd'], $spot['subcatz']);
		
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

		# Title and poster fields are mandatory, we require it to validate the signature
		if (((strlen($spot['title']) == 0) || (strlen($spot['poster']) == 0))) {
			return $spot;
		} # if

		/* 
		 * For any recentkey ( >1) or spots created after year-2010, we require the spot
		 * to be signed
		 */
		$mustbeSigned = $isRecentKey | ($spot['stamp'] > 1293870080);
		if ($mustbeSigned) {
			$spot['headersign'] = $fields[count($fields) - 1];
			$spot['wassigned'] = (strlen($spot['headersign']) != 0);
		} # if must be signed
		else {
			$spot['verified'] = true;
			$spot['wassigned'] = false;
		} # if doesnt need to be signed, pretend that it is

		/*
		 * Don't verify spots which are already verified
		 */
		if ($spot['wassigned']) {
			/*
			 * There are currently two known methods to which Spots are signed,
			 * each having different charachteristics, making it a bit difficult
			 * to work with this.
			 *
			 * The oldest method uses a secret private key and a signing server, we
			 * name this method SPOTSIGN_V1. The users' public key is only available
			 * in the XML header, not in the From header. This is the preferred method.
			 *
			 * The second method uses a so-called "self signed" spot (the spotter signs
			 * the spots, posts the public key in the header and a hashcash is used to
			 * prevent spamming). This method is called SPOTSIGN_V2.
			 *
			 */
			if ($spot['keyid'] == 7) {
				/*
				 * KeyID 7 has a special meaning, it defines a self-signed spot and
				 * requires a hashcash
				 */
				$signingMethod = 2;
			} else {
				$signingMethod = 1;
			} # else


			switch($signingMethod) {
				case 1 : {
					# the signature this header is signed with
					$signature = $this->unspecialString($spot['headersign']);
			
					/*
					 * Make sure the key specified is an actual known key 
					 */
					if (isset($rsaKeys[$spot['keyid']])) {
						$spot['verified'] = $this->_spotSigning->verifySpotHeader($spot, $signature, $rsaKeys);
					} # if

					break;
				} # SPOTSIGN_V1

				case 2 : {
					# the signature this header is signed with
					$signature = $this->unspecialString($spot['headersign']);

					$userSignedHash = sha1('<' . $spot['messageid'] . '>', false);
					$spot['verified'] = (substr($userSignedHash, 0, 3) == '0000');

					/*
					 * Create a fake RSA keyarray so we can validate it using our standard
					 * infrastructure
					 */
					 if ($spot['verified']) {
						$userRsaKey = array(7 => array('modulo' => $spot['selfsignedpubkey'],
													   'exponent' => 'AQAB'));
				
						/*
						 * We cannot use this as a full measure to check the spot's validness yet, 
						 * because at least one Spotnet client feeds us invalid data for now
						 */
						if ($this->_spotSigning->verifySpotHeader($spot, $signature, $userRsaKey)) {
							/* 
							 * The users' public key (modulo) is posted in the header, lets 
							 * try this.
							 */
							$spot['spotterid'] = $this->_spotSigning->calculateSpotterId($spot['selfsignedpubkey']);
						} # if
					} # if

					break;
				} # SPOTSIGN_V2
			} # switch

			/*
			 * Even more recent spots, contain the users' full publickey
			 * in the header. This allows us to uniquely identify and verify
			 * the poster of the spot.
			 *
			 * Try to extract this information.
			 */
			if (($spot['verified']) && (!empty($spot['user-signature'])) && (!empty($spot['selfsignedpubkey']))) {
				/*
				 * Extract the public key
				 */
				$spot['spotterid'] = $this->_spotSigning->calculateSpotterId($spot['selfsignedpubkey']);
				$spot['user-key'] = array('modulo' => $spot['selfsignedpubkey'],
										  'exponent' => 'AQAB');
				/* 
				 * The spot contains the signature in the header of the spot
				 */
				$spot['verified'] = $this->_spotSigning->verifyFullSpot($spot);
			} # if

		} # if was signed

		/*
		 * We convert the title and other fields to UTF8, we cannot
		 * do this any earlier because it would break the RSA signature
		 */
		if (($spot !== false) && ($spot['verified'])) {
			$spot['title'] = utf8_encode($spot['title']);
			$spot['poster'] = utf8_encode($spot['poster']);
			$spot['tag'] = utf8_encode($spot['tag']);
			
			# If a spot is in the future, fix it
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
		/* Pad the input string to a multiple of 4 */
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
		# XML
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
		$titleElm->appendChild($doc->createCDATASection(htmlentities($spot['title'], ENT_NOQUOTES, 'UTF-8')));
		$postingElm->appendChild($titleElm);
		
		/* 
		 * Description element is enclosed in CDATA
		 */
		$descrElm = $doc->createElement('Description');
		$descrElm->appendChild($doc->createCDATASection( htmlentities(str_replace( array("\r\n", "\r", "\n"), "[br]", $spot['body']), ENT_NOQUOTES, 'UTF-8')));
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
	
	private function validMessageId($messageId) {
		$invalidChars = '<>';
		
		$msgIdLen = strlen($messageId);		
		for ($i = 0; $i < $msgIdLen; $i++) {
			if (strpos($invalidChars, $messageId[$i]) !== false) {
				return false;
			} # if
		} # for
		
		return true;
	} # validMessageId
	
} # class Spot
