<?php
class SpotNntp {
		private $_server;
		private $_user;
		private $_pass;
		private $_serverenc;
		private $_serverport;
		
		private $_error;
		private $_nntp;
		private $_connected;
		
		function __construct($server) { 
			$error = '';
			
			$this->_connected = false;
			$this->_server = $server['host'];
			$this->_serverenc = $server['enc'];
			$this->_serverport = $server['port'];
			$this->_user = $server['user'];
			$this->_pass = $server['pass'];

			$this->_nntp = new Net_NNTP_Client();
		} # ctor
		
		function selectGroup($group) {
			$this->connect();
			return $this->_nntp->selectGroup($group);
		} # selectGroup()
		
		function getOverview($first, $last) {
			$this->connect();
			$hdrList = $this->_nntp->getOverview($first . '-' . $last);
			
			return $hdrList;
		} # getOverview()

		function getMessageIdList($first, $last) {
			$this->connect();
			$hdrList = $this->_nntp->getHeaderField('Message-ID', ($first . '-' . $last));
			return $hdrList;
		} # getMessageIdList()
		
		function quit() {
			if (!$this->_connected) {
				return ;
			} # if
			
			try {
				$this->_nntp->quit();
			} 
			catch(Exception $x) {
				// dummy, we dont care about exceptions during quitting time
			} # catch
		} # quit()

		function post($article) {
			$this->connect();

			// We kunnen niet rechtstreeks post() aanroepen omdat die
			// de autoloader triggered
			$tmpError = $this->_nntp->cmdPost();
			if ($tmpError) {
				return $this->_nntp->cmdPost2($article);
			} else {
				return $tmpError;
			} # else
		} # post()
		
		function getHeader($msgid) {
			$this->connect();
			return $this->_nntp->getHeader($msgid);
		} # getHeader()

		function getBody($msgid) {
			$this->connect();
			return $this->_nntp->getBody($msgid);
		} # getBody	()
		
		function connect() {
			# dummy operation
			if ($this->_connected) {
				return ;
			} # if
			$this->_connected = true;

			/* 
			 * Erase username/password so it won't show up in any stacktrace
			 */
			$tmpUser = $this->_user;
			$tmpPass = $this->_pass;
			$this->_user = '*FILTERED*';
			$this->_pass = '*FILTERED*';
			
			try{
				$ret = $this->_nntp->connect($this->_server, $this->_serverenc, $this->_serverport, 10);
				if (!empty($tmpUser)) {
					$authed = $this->_nntp->authenticate($tmpUser, $tmpPass);
					
				} # if
			}catch(Exception $x){
				throw new NntpException($x->getMessage(), $x->getCode());
			}
		} # connect()
		
		function getArticle($msgId) {
			$this->connect();
	
			$result = array('header' => array(), 'body' => array());
			
			# Fetch het artikel
			$art = $this->_nntp->getArticle($msgId);
			
			# vervolgens splitsen we het op in een header array en een body array
			$i = 0;
			$lnCount = count($art);
			while( ($i < $lnCount) && ($art[$i] != '')) {
				$result['header'][] = $art[$i];
				$i++;
			} # while
			$i++;

			while($i < $lnCount) {
				$result['body'][] = $art[$i];
				$i++;
			} # while
			
			return $result;
		} # getArticle
		
		function cbCommentDateSort($a, $b) {
			if ($a['stamp'] == $b['stamp']) {
				return 0;
			} # if
			
			return ($a['stamp'] < $b['stamp']) ? -1 : 1;
		} # cbCommentDateSort
		
		function getComments($commentList) {
			$comments = array();
			$spotSigning = new SpotSigning();
			$spotParser = new SpotParser();
			
			# We extracten elke comment en halen daar de datum en poster uit, inclusief de body
			# als comment text zelf.
			foreach($commentList as $comment) {
				try {
					$commentTpl = array('messageid' => '', 'fromhdr' => '', 'stamp' => 0, 'usersignature' => '', 
										'user-key' => '', 'userid' => '', 'verified' => false);
										
					$tmpAr = array_merge($commentTpl, $this->getArticle('<' . $comment['messageid'] . '>'));
					$tmpAr['messageid'] = $comment['messageid'];

					# extract de velden we die we willen hebben
					foreach($tmpAr['header'] as $hdr) {
						$keys = explode(':', $hdr);
						
						switch($keys[0]) {
							case 'From'				: $tmpAr['fromhdr'] = utf8_encode(trim(substr($hdr, strlen('From: '), strpos($hdr, '<') - 1 - strlen('From: ')))); break;
							case 'Date'				: $tmpAr['stamp'] = strtotime(substr($hdr, strlen('Date: '))); break;
							case 'X-User-Signature'	: $tmpAr['usersignature'] = $spotParser->unspecialString(substr($hdr, 18)); break;
							case 'X-User-Key'		: {
									$xml = simplexml_load_string(substr($hdr, 12)); 
									if ($xml !== false) {
										$tmpAr['user-key']['exponent'] = (string) $xml->Exponent;
										$tmpAr['user-key']['modulo'] = (string) $xml->Modulus;
									} # if
									break;
							} # x-user-key
						} # switch
					} # foreach

					# Valideer de signature van de XML, deze is gesigned door de user zelf
					$tmpAr['verified'] = $spotSigning->verifyComment($tmpAr);
					if ($tmpAr['verified']) {
						$tmpAr['userid'] = $spotSigning->calculateUserid($tmpAr['user-key']['modulo']);
					} # if

					# encode de body voor UTF8
					$tmpAr['body'] = array_map('utf8_encode', $tmpAr['body']);

					$comments[] = $tmpAr; 
				} 
				catch(Exception $x) {
					# Soms gaat het ophalen van een comment mis? Raar want deze komen van de XOVER
					# van de server zelf, dus tenzij ze gecancelled worden mag dit niet gebeuren.
					# iig, we negeren de error
					;
				} # catch
			} # foreach

			# sorteer de comments per datum
			usort($comments, array($this, 'cbCommentDateSort'));

			return $comments;
		} # getComments

		function postComment($user, $serverPrivKey, $newsgroup, $title, $comment) {
			# FIXME: Het aantal nullen (minimaal 4) instelbaar maken via settings.php

			# instantieer de benodigde objecten
			$spotSigning = new SpotSigning();
			$spotParser = new SpotParser();

			# sign het messageid
			$user_signature = $spotSigning->signMessage($user['privatekey'], '<' . $comment['newmessageid'] . '>');
			
			# ook door de php server 
			$server_signature = $spotSigning->signMessage($serverPrivKey, $comment['newmessageid']);

			$header = 'From: ' . $user['username'] . " <" . trim($user['username']) . '@spot.net>' . "\r\n";
			$header .= 'Subject: Re: ' . $title . "\r\n";
			$header .= 'Newsgroups: ' . $newsgroup . "\r\n";
			$header .= 'Message-ID: <' . $comment['newmessageid'] . ">\r\n";
			$header .= 'References: <' . $comment['inreplyto']. ">\r\n";
			$header .= 'X-User-Signature: ' . $spotParser->specialString($user_signature['signature']) . "\r\n";
			$header .= 'X-Server-Signature: ' . $spotParser->specialString($server_signature['signature']) . "\r\n";
			$header .= 'X-User-Key: ' . $spotSigning->pubkeyToXml($user_signature['publickey']) . "\r\n";
			$header .= 'X-Server-Key: ' . $spotSigning->pubkeyToXml($server_signature['publickey']) . "\r\n";
			$header .= 'X-User-Rating: ' . (int) $comment['rating'] . "\r\n";
			
			# $header .= 'X-User-Avatar: ' 
			# Message-ID van een avatar

			# $header .= 'X-User-Gravatar: ' 
			# Hashcode van een Gravatar

			$header .= "X-Newsreader: SpotWeb v" . SPOTWEB_VERSION . "\r\n";
			$header .= "X-No-Archive: yes\r\n";
			
			return $this->post(array($header, $comment['body']));
		} # postComment
		
		function getImage($segmentList) {
			$spotParser = new SpotParser();
			$imageContent = '';
			
			/*
			 * Retrieve all image segments 
			 */
			foreach($segmentList as $seg) {
				$imgTmp = implode('', $this->getBody('<' . $seg . '>'));
				$imageContent .= $spotParser->unspecialZipStr($imgTmp);
			} # foreach
			
			return $imageContent;
		} # getImage
		
		function getNzb($segList) {
			$nzb = '';
			
			foreach($segList as $seg) {
				$nzb .= implode('', $this->getBody('<' . $seg . '>'));
			} # foreach

			$spotParser = new SpotParser();
			return gzinflate( $spotParser->unspecialZipStr($nzb) );
		} # getNzb
		
		/*
		 * Post an image (contents of the image file should be passed)
		 */
		function postImageFile($user, $newsgroup, $imgContents) {
			$spotParser = new SpotParser();
			$spotSigning = new SpotSigning();
			$imgProcessed = $spotParser->specialZipstr($imgContents);
			
			/*
			 * Create an unique messageid and store it so we can return it
			 * for the actual Spot creation
			 */
			$messageId = $spotSigning->makeRandomStr(15) . '@spot.net';

			/* 
			 * Now we create the NNTP header 
			 */
			$header = 'From: ' . $user['username'] . " <" . trim($user['username']) . '@spot.net>' . "\r\n";
			$header .= 'Subject: ' . md5($imgContents) . "\r\n";
			$header .= 'Newsgroups: ' . $newsgroup . "\r\n";
			$header .= 'Message-ID: <' . $messageId .  ">\r\n";
			$header .= "X-Newsreader: SpotWeb v" . SPOTWEB_VERSION . "\r\n";
			$header .= "X-No-Archive: yes\r\n";
		
			$this->post(array($header, $imgProcessed));		
			
			return array($messageId);
		} # postImageFile
		
		/*
		 * Post an NZB file. First we gzip the contents and then
		 * process special characters with it. After that is done, we
		 * split the file in 10K segments and actually post it to the 
		 * usenet server
		 *
		 * Returns a list of messageids
		 */
		function postNzbFile($user, $newsgroup, $nzbContents) {
			$chunkLen = 1024 * 10;
			$segmentList = array();
			
			$spotParser = new SpotParser();
			$spotSigning = new SpotSigning();
			$nzbZipped = $spotParser->specialZipstr(gzdeflate($nzbContents));
			
			/*
			 * Now start posting chunks of the NZB files
			 */
			while(strlen($nzbZipped) > 0) {
				/*
				 * Cut of the first piece of the NZB file, and remove it
				 * from the source string
				 */
				$chunk = substr($nzbZipped, 0, $chunkLen - 1);
				$nzbZipped = substr($nzbZipped, $chunkLen - 1);
				
				/*
				 * Create an unique segmentid and store it so we can return it
				 * for the actual Spot creation
				 */
				$messageId = $spotSigning->makeRandomStr(15) . '@spot.net';
				$segmentList[] = $messageId;

				/* 
				 * Now we create the NNTP header 
				 */
				$header = 'From: ' . $user['username'] . " <" . trim($user['username']) . '@spot.net>' . "\r\n";
				$header .= 'Subject: ' . md5($chunk) . "\r\n";
				$header .= 'Newsgroups: ' . $newsgroup . "\r\n";
				$header .= 'Message-ID: <' . $messageId .  ">\r\n";
				$header .= "X-Newsreader: SpotWeb v" . SPOTWEB_VERSION . "\r\n";
				$header .= "X-No-Archive: yes\r\n";
			
				$this->post(array($header, $chunk));
			} # while
			
			return $segmentList;
		} # postNzbFile

		/*
		 * Creates XML out of the Spot information array
		 */
		function convertSpotToXml($spot, $imageInfo, $nzbSegments) {
			# Opbouwen XML
			$doc = new DOMDocument('1.0', 'utf-8');
			$doc->formatOutput = false;

			$mainElm = $doc->createElement('SpotNet');
			$postingElm = $doc->createElement('Posting');
			$postingElm->appendChild($doc->createElement('Category', $spot['category'] + 1));

			$websiteElm = $doc->createElement('Website');
			$websiteElm->appendChild($doc->createCDATASection($spot['website']));
			$postingElm->appendChild($websiteElm);
			
			/* 
			 * Description element is enclosed in CDATA
			 */
			$descrElm = $doc->createElement('Description');
			$descrElm->appendChild($doc->createCDATASection( str_replace( array("\r\n", "\r", "\n"), "[br]", $spot['body'])));
			$postingElm->appendChild($descrElm);
			
			$postingElm->appendChild($doc->createElement('Size', $spot['filesize']));
			$postingElm->appendChild($doc->createElement('Poster', $spot['poster']));
			$postingElm->appendChild($doc->createElement('Tag', $spot['tag']));

			/* 
			 * Title element is enclosed in CDATA
			 */
			$titleElm = $doc->createElement('Title');
			$titleElm->appendChild($doc->createCDATASection($spot['title']));
			$postingElm->appendChild($titleElm);
			
			$postingElm->appendChild($doc->createElement('Created', time()));

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
		
		/*
		 * Posts a spot file and its corresponding image and NZB file (actually done by
		 * helper functions)
		 */
		function postFullSpot($user, $serverPrivKey, $newsgroup, $spot, $nzbFilename, $imageFilename) {
			# instantieer de benodigde objecten
			$spotSigning = new SpotSigning();
			$spotParser = new SpotParser();

			/* 
			 * Create one list of all subcategories
			 */
			$spot['subcatlist'] = array_filter(explode('|', $spot['subcata'] . $spot['subcatb'] . $spot['subcatc'] . $spot['subcatd'] . $spot['subcatz']));

			/*
			 * Retrieve the image information and post the image to 
			 * the appropriate newsgroup so we have the messageid list of 
			 * images
			 */
			$imgSegmentList = $this->postImageFile($user, $newsgroup, file_get_contents($imageFilename));
			$tmpGdImageSize = getimagesize($imageFilename);
			$imageInfo = array('width' => $tmpGdImageSize[0],
							   'height' => $tmpGdImageSize[1],
							   'segments' => $imgSegmentList);
				
			/*
			 * Post the NZB file to the appropriate newsgroups
			 */
			 $nzbSegmentList = $this->postNzbFile($user, $newsgroup, file_get_contents($nzbFilename));
			
			# convert the current Spotnet info, to an XML structure
			$spotXml = $this->convertSpotToXml($spot, $imageInfo, $nzbSegmentList);
			
			/*
			 * Create the spotnet from header part accrdoing to the following structure:
			 *   From: [Nickname] <[RANDOM]@[CAT][KEY-ID][SUBCAT].[SIZE].[RANDOM].[DATE].[CUSTOM-ID].[CUSTOM-VALUE].[SIGNATURE]>
			 */
			$spotnetFrom = $user['username'] . ' <' . $spotSigning->makeRandomStr(8) . '@';
			$spotHeader = ($spot['category'] + 1) . $spot['key']; // Append the category and keyid
			
			/*
			 * Process each subcategory and add them to the from header
			 */
			foreach($spot['subcatlist'] as $subcat) {
				$spotHeader .= $subcat[0] . str_pad(substr($subcat, 1), 2, '0', STR_PAD_LEFT);
			} # foreach
			
			$spotHeader .= '.' . $spot['filesize'];
			$spotHeader .= '.' . 10; // some kind of magic number?
			$spotHeader .= '.' . time();
			$spotHeader .= '.' . $spotSigning->makeRandomStr(4);
			$spotHeader .= '.' . $spotSigning->makeRandomStr(3);

			# also by the server
			$server_signature = $spotSigning->signMessage($serverPrivKey, $spot['title'] . $spotHeader . $spot['poster']);
			
			# sign the header by using the users' key
			$user_signature = $spotSigning->signMessage($user['privatekey'], $spot['title'] . $spotHeader . $spot['poster']);
			
			# Create the messageid
			$spot['newmessageid'] = substr($spotSigning->makeExpensiveHash('<' . $spotSigning->makeRandomStr(15), '@spot.net>'), 1, -1);
			
			echo "Posted message with messageid: " . $spot['newmessageid'] . PHP_EOL;
			
			# and finally create the NNTP header
			$header = 'From: ' . $spotnetFrom . $spotHeader . '.' . $spotParser->specialString($user_signature['signature']) . ">\r\n";
			# FIXME: Als er geen tag is, ook geen opgeven
			$header .= 'Subject: ' . $spot['title'] . ' | ' . $spot['tag']. "\r\n";
			$header .= 'Newsgroups: ' . $newsgroup . "\r\n";
			# FIXME: Hashcash
			$header .= 'Message-ID: <' . $spot['newmessageid'] . ">\r\n";
			$header .= 'X-User-Signature: ' . $spotParser->specialString($user_signature['signature']) . "\r\n";
			$header .= 'X-Server-Signature: ' . $spotParser->specialString($server_signature['signature']) . "\r\n";
			$header .= 'X-User-Key: ' . $spotSigning->pubkeyToXml($user_signature['publickey']) . "\r\n";
			$header .= 'X-Server-Key: ' . $spotSigning->pubkeyToXml($server_signature['publickey']) . "\r\n";
			$header .= "X-Newsreader: SpotWeb v" . SPOTWEB_VERSION . "\r\n";
			$header .= "X-No-Archive: yes\r\n";
			
			$tmpXml = $spotXml;
			while (strlen($tmpXml) > 0) {
				$header .= 'X-XML: ' . substr($tmpXml, 0, 256) . "\r\n";
				
				if (strlen($tmpXml) >= 256) {
					$tmpXml = substr($tmpXml, 256);
				} else {
					$tmpXml = '';
				} # else
			} # while
			
			var_dump($header);
			
			return $this->post(array($header, $spot['body']));
		} # postFullSpot
		
		function getFullSpot($msgId) {
			# initialize some variables
			$spotSigning = new SpotSigning();
			$spotParser = new SpotParser();
			
			$spot = array('fullxml' => '',
						  'user-signature' => '',
						  'user-key' => '',
						  'verified' => false,
						  'messageid' => $msgId,
						  'userid' => '',
						  'xml-signature' => '',
						  'moderated' => 0);
			# Vraag de volledige article header van de spot op
			$header = $this->getHeader('<' . $msgId . '>');

			# Parse de header			  
			foreach($header as $str) {
				$keys = explode(':', $str);
				
				switch($keys[0]) {
					case 'X-XML' 			: $spot['fullxml'] .= substr($str, 7); break;
					case 'X-User-Signature'	: $spot['user-signature'] = $spotParser->unspecialString(substr($str, 18)); break;
					case 'X-XML-Signature'	: $spot['xml-signature'] = substr($str, 17); break;
					case 'X-User-Key'		: {
							$xml = simplexml_load_string(substr($str, 12)); 
							if ($xml !== false) {
								$spot['user-key']['exponent'] = (string) $xml->Exponent;
								$spot['user-key']['modulo'] = (string) $xml->Modulus;
							} # if
							break;
					} # x-user-key
				} # switch
			} # foreach
			
			# Valideer de signature van de XML, deze is gesigned door de user zelf
			$spot['verified'] = $spotSigning->verifyFullSpot($spot);

			# als de spot verified is, toon dan de userid van deze user
			if ($spot['verified']) {
				$spot['userid'] = $spotSigning->calculateUserid($spot['user-key']['modulo']);
			} # if	
			
			# Parse nu de XML file, alles wat al gedefinieerd is eerder wordt niet overschreven
			$spot = array_merge($spotParser->parseFull($spot['fullxml']), $spot);
			
			return $spot;
		} # getFullSpot 
		
		function reportSpotAsSpam($user, $serverPrivKey, $title, $report) {			
			# instantieer de benodigde objecten
			$spotSigning = new SpotSigning();
			$spotParser = new SpotParser();
			
			# sign het messageid
			$user_signature = $spotSigning->signMessage($user['privatekey'], '<' . $report['newmessageid'] . '>');
			
			# ook door de php server 
			$server_signature = $spotSigning->signMessage($serverPrivKey, '<' . $report['newmessageid'] . '>');
			
			$header = 'From: ' . $user['username'] . " <" . trim($user['username']) . '@spot.net>' . "\r\n";
			$header .= 'Subject: REPORT <' . $report['inreplyto'] . '> ' . $title . "\r\n";
			$header .= 'Newsgroups: free.willey' . "\r\n";
			$header .= 'Message-ID: <' . $report['newmessageid'] . ">\r\n";
			$header .= 'References: <' . $report['inreplyto'] . ">\r\n";
			$header .= 'X-User-Signature: ' . $spotParser->specialString($user_signature['signature']) . "\r\n";
			$header .= 'X-Server-Signature: ' . $spotParser->specialString($server_signature['signature']) . "\r\n";
			$header .= 'X-User-Key: ' . $spotSigning->pubkeyToXml($user_signature['publickey']) . "\r\n";
			$header .= 'X-Server-Key: ' . $spotSigning->pubkeyToXml($server_signature['publickey']) . "\r\n";
			
			$header .= "X-Newsreader: SpotWeb v" . SPOTWEB_VERSION . "\r\n";
			$header .= "X-No-Archive: yes\r\n";
			
			return $this->post(array($header, $report['body']));
			
		} #reportSpotAsSpam
		
} # class SpotNntp
