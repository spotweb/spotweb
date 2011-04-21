<?php
class SpotParser {
	function parseFull($xmlStr) {
		# Gebruik een spot template zodat we altijd de velden hebben die we willen
		$tpl_spot = array('category' => '', 'website' => '', 'image' => '', 'sabnzbdurl' => '', 'messageid' => '', 'searchurl' => '', 'description' => '',
						  'sub' => '', 'filesize' => '', 'poster' => '', 'tag' => '', 'nzb' => '', 'title' => '', 
						  'filename' => '', 'newsgroup' => '', 'subcatlist' => array(), 'subcata' => '', 'subcatb' => '', 
						  'subcatc' => '', 'subcatd' => '', 'imageid' => '', 'subcatz' => '');

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

	function parseXover($subj, $from, $date, $messageid, $rsakeys) {
		$_CAT = 0;
		$_FSIZE = 1;

		// initialiseer wat variabelen
		$spot = array();

		// Eerst splitsen we de header string op in enkel de category info e.d.
		$fromInfoPos = strpos($from, '<');
		if ($fromInfoPos === false) {
			return false;
		} else {
			# Haal de postername en de <>'s weg
			$fromAddress = explode('@', substr($from, $fromInfoPos + 1, -1));
			if (count($fromAddress) < 2) {
				return false;
			} # if
			$spot['header'] = $fromAddress[1];
		} # if

		$spot['verified'] = false;
		$spot['filesize'] = 0;
		$spot['messageid'] = substr($messageid, 1, strlen($messageid) - 2);

		# als de spot in de toekomst ligt, dan corrigeren we dat naar nu
		if (time() < strtotime($date)) {
			$spot['stamp'] = time();
		} else {
			$spot['stamp'] = strtotime($date);
		} # if
		$fields = explode('.', $spot['header']);

		if (count($fields) >= 6) {
			$spot['filesize'] = $fields[$_FSIZE];
			$spot['category'] = (substr($fields[$_CAT], 0, 1)) - 1.0;

			// extract de posters name
			$spot['poster'] = substr($from, 0, $fromInfoPos -1);

			// key id
			$spot['keyid'] = (int) substr($fields[$_CAT], 1, 1);

			// groupname
			$spot['groupname'] = 'free.pt';
			if ($spot['keyid'] >= 0) {

				$expression = '';
				$strInput = substr($fields[$_CAT], 2);
				$recentKey = $spot['keyid'] <> 1;

				if ($recentKey) {	
					if ((strlen($strInput) == 0) || ((strlen($strInput) % 3) != 0)) {
						return;
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
					$spot['subcatz'] = '';

					foreach($subcats as $subcat) {
						if (in_array(strtolower(substr($subcat, 0, 1)), array('a','b','c','d','z')) !== false) {
							$spot['subcat' . strtolower(substr($subcat, 0, 1))] .= $subcat . '|';
						} # if
					} # foreach
					
					# We vullen hier de z categorieen alvast op in het geval er geen Z category gegeven is
					if (empty($spot['subcatz'])) {
						$spot['subcatz'] = SpotCategories::createSubcatz($spot['category'], $spot['subcata'] . $spot['subcatb'] . $spot['subcatd']);
					} # if

				} # if

				if ((strpos($subj, '=?') !== false) && (strpos($subj, '?=') !== false)) {
					# Make sure its as simple as possible
					$subj = str_replace('?= =?', '?==?', $subj);
					$subj = str_replace('\r', '', trim($this->oldEncodingParse($subj)));
					$subj = str_replace('\n', '', $subj);
				} # if

				if ($recentKey) {
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

				if (((strlen($spot['title']) != 0) && (strlen($spot['poster']) != 0))) {
	
					# Als er een recentkey is (key <> 1), OF de spot is na 2010 geplaatst, dan moet
					# de spot gesigned zijn.
					$mustbeSigned = $recentKey | ($spot['stamp'] > 1293870080);
					if ($mustbeSigned) {
						$spot['headersign'] = $fields[count($fields) - 1];

						if (strlen($spot['headersign']) != 0) {

							$spot['wassigned'] = true;

							# KeyID 7 betekent dat alleen een hashcash vereist is
							if ($spot['keyid'] == 7) {
								$userSignedHash = sha1('<' . $spot['messageid'] . '>', false);
								$spot['verified'] = (substr($userSignedHash, 0, 3) == '0000');
							} else {
								# the signature this header is signed with
								$signature = $this->unspecialString($spot['headersign']);

								$spotSigning = new SpotSigning();
								$spot['verified'] = $spotSigning->verifySpotHeader($spot, $signature, $rsakeys);
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

		# Nu zetten we de titel en dergelijke om naar utf8, we kunnen
		# dat niet eerder doen omdat anders de RSA signature niet meer
		# klopt.
		if (($spot !== false) && ($spot['verified'])) {
			$spot['title'] = utf8_encode($spot['title']);
			$spot['poster'] = utf8_encode($spot['poster']);
			$spot['tag'] = utf8_encode($spot['tag']);
		} # f

		return $spot;
	} # parseXover

	private function fixPadding($strInput) {
		while ((strlen($strInput) % 4) != 0) {
			$strInput .= '=';
		} # while

		return $strInput;
	} # fixPadding


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

	function specialString($strInput) {

		$strInput = str_replace('/', '-s', $strInput);
		$strInput = str_replace('+', '-p', $strInput);

		return $strInput;
	} # specialString

	/*private */function unspecialString($strInput) {
		$strInput = $this->fixPadding($strInput);
		$strInput = str_replace('-s', '/', $strInput);
		$strInput = str_replace('-p', '+', $strInput);

		return $strInput;
	} # unspecialString
} # class Spot
