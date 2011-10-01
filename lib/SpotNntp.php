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
		
		/*
		 * Select a group as active group
		 */
		function selectGroup($group) {
			$this->connect();
			return $this->_nntp->selectGroup($group);
		} # selectGroup()
		
		/*
		 * Returns an overview (XOVER) from first id to lastid
		 */
		function getOverview($first, $last) {
			$this->connect();
			return $this->_nntp->getOverview($first . '-' . $last);
		} # getOverview()

		/*
		 * Get a list of messageid's within a range, same as XOVER
		 * but only for messageids
		 */
		function getMessageIdList($first, $last) {
			$this->connect();
			$hdrList = $this->_nntp->getHeaderField('Message-ID', ($first . '-' . $last));
			return $hdrList;
		} # getMessageIdList()
		
		/*
		 * Disconnect from the server if we are connected
		 */
		function quit() {
			if (!$this->_connected) {
				return ;
			} # if
			
			try {
				$this->_nntp->quit();
				$this->_connected = false;
			} 
			catch(Exception $x) {
				// dummy, we dont care about exceptions during quitting time
			} # catch
		} # quit()

		/*
		 * Post an article to the server, $article should be an 2-element 
		 * array with head and body as elements
		 */
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
		
		/*
		 * Returns the header of an messageid
		 */
		function getHeader($msgid) {
			$this->connect();
			return $this->_nntp->getHeader($msgid);
		} # getHeader()

		/*
		 * Returns the body of an messageid
		 */
		function getBody($msgid) {
			$this->connect();
			return $this->_nntp->getBody($msgid);
		} # getBody	()
		
		/*
		 * Connect to the newsserver and authenticate
		 * if necessary
		 */
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
		
		/*
		 * Returns a full article dividded between an
		 * header and body part
		 */
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

		/*
		 * Parse an header and extract specific fields
		 * from it
		 */
		function parseHeader($headerList, $tmpAr) {
			$spotParser = new SpotParser();
		
			# extract de velden we die we willen hebben
			foreach($headerList as $hdr) {
				$keys = explode(':', $hdr);

				switch($keys[0]) {
					case 'From'				: $tmpAr['fromhdr'] = utf8_encode(trim(substr($hdr, strlen('From: '), strpos($hdr, '<') - 1 - strlen('From: ')))); break;
					case 'Date'				: $tmpAr['stamp'] = strtotime(substr($hdr, strlen('Date: '))); break;
					case 'X-XML' 			: $tmpAr['fullxml'] .= substr($hdr, 7); break;
					case 'X-User-Signature'	: $tmpAr['user-signature'] = $spotParser->unspecialString(substr($hdr, 18)); break;
					case 'X-XML-Signature'	: $tmpAr['xml-signature'] = $spotParser->unspecialString(substr($hdr, 17)); break;
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
			
			return $tmpAr;
		} # parseHeader

		/*
		 * Callback function for sorting of comments on date
		 */
		function cbCommentDateSort($a, $b) {
			if ($a['stamp'] == $b['stamp']) {
				return 0;
			} # if
			
			return ($a['stamp'] < $b['stamp']) ? -1 : 1;
		} # cbCommentDateSort
		
		/*
		 * Returns a list of comments
		 */
		function getComments($commentList) {
			$comments = array();
			$spotSigning = new SpotSigning();
			
			# We extracten elke comment en halen daar de datum en poster uit, inclusief de body
			# als comment text zelf.
			foreach($commentList as $comment) {
				try {
					$commentTpl = array('messageid' => '', 'fromhdr' => '', 'stamp' => 0, 'user-signature' => '', 
										'user-key' => '', 'userid' => '', 'verified' => false);
										
					$tmpAr = array_merge($commentTpl, $this->getArticle('<' . $comment['messageid'] . '>'));
					$tmpAr['messageid'] = $comment['messageid'];
					$tmpAr = array_merge($tmpAr, $this->parseHeader($tmpAr['header'], $tmpAr));

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
			$server_signature = $spotSigning->signMessage($serverPrivKey, '<' . $comment['newmessageid'] . '>');

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
			$imgProcessed = chunk_split($spotParser->specialZipstr($imgContents), 900);
			
			/*
			 * Create an unique messageid and store it so we can return it
			 * for the actual Spot creation
			 */
			$messageId = $spotSigning->makeRandomStr(25) . '@spot.net';

			/* 
			 * Now we create the NNTP header 
			 */
			$header = 'From: ' . $user['username'] . " <" . trim($user['username']) . '@spot.net>' . "\r\n";
			$header .= 'Subject: ' . md5($imgContents) . "\r\n";
			$header .= 'Newsgroups: ' . $newsgroup . "\r\n";
			$header .= 'Message-ID: <' . $messageId .  ">\r\n";
			$header .= 'Content-Type: text/plain; charset=ISO-8859-1' . "\r\n";
			$header .= 'Content-Transfer-Encoding: 8bit' . "\r\n";
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
				$messageId = $spotSigning->makeRandomStr(25) . '@spot.net';
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
			
				$this->post(array($header, chunk_split($chunk, 900)));
			} # while
			
			return $segmentList;
		} # postNzbFile

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
			$spotXml = $spotParser->convertSpotToXml($spot, $imageInfo, $nzbSegmentList);
			
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

			# Create the messageid
			$spot['newmessageid'] = substr($spotSigning->makeExpensiveHash('<' . $spotSigning->makeRandomStr(15), '@spot.net>'), 1, -1);
			
			# sign the header with the servers' key
			$server_signature = $spotSigning->signMessage($serverPrivKey, $spot['title'] . $spotHeader . $spot['poster']);
			
			# sign the header by using the users' key
			$header_signature = $spotSigning->signMessage($user['privatekey'], $spot['title'] . $spotHeader . $spot['poster']);

			# sign the messageid by using the users' key
			$user_signature = $spotSigning->signMessage($user['privatekey'], '<' . $spot['newmessageid'] . '>');
			
			# sign the XML with the users' key
			$xml_signature = $spotSigning->signMessage($user['privatekey'], $spotXml);
			
			echo "Posted message with messageid: " . $spot['newmessageid'] . PHP_EOL;
			
			# and finally create the NNTP header
			$header = 'From: ' . $spotnetFrom . $spotHeader . '.' . $spotParser->specialString($header_signature['signature']) . ">\r\n";
			# FIXME: Als er geen tag is, ook geen opgeven
			$header .= 'Subject: ' . $spot['title'] . ' | ' . $spot['tag']. "\r\n";
			$header .= 'Newsgroups: ' . $newsgroup . "\r\n";
			# FIXME: Hashcash
			$header .= 'Message-ID: <' . $spot['newmessageid'] . ">\r\n";
			$header .= 'X-User-Signature: ' . $spotParser->specialString($user_signature['signature']) . "\r\n";
			$header .= 'X-User-Key: ' . $spotSigning->pubkeyToXml($user_signature['publickey']) . "\r\n";
			$header .= 'X-Server-Signature: ' . $spotParser->specialString($server_signature['signature']) . "\r\n";
			$header .= 'X-Server-Key: ' . $spotSigning->pubkeyToXml($server_signature['publickey']) . "\r\n";
			$header .= 'X-XML-Signature: ' . $spotParser->specialString($xml_signature['signature']) . "\r\n";
			$header .= "X-Newsreader: SpotWeb v" . SPOTWEB_VERSION . "\r\n";
			$header .= "X-No-Archive: yes\r\n";
			
			$tmpXml = explode("\r\n", chunk_split($spotXml, 900));
			foreach($tmpXml as $xmlChunk) {
				$header .= 'X-XML: ' . $xmlChunk . "\r\n";
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
			$spot = array_merge($spot, $this->parseHeader($header, $spot));
			
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
		
} # class SpotNntp
