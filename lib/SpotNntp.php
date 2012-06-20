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
		private $_currentgroup;

		private $_spotParser;
		
		function __construct($server) { 
			$error = '';
			
			$this->_connected = false;
			$this->_server = $server['host'];
			$this->_serverenc = $server['enc'];
			$this->_serverport = $server['port'];
			$this->_user = $server['user'];
			$this->_pass = $server['pass'];

			$this->_nntp = new Net_NNTP_Client();
			$this->_spotParser = new SpotParser();
		} # ctor

	
		/*
		 * Select a group as active group
		 */
		function selectGroup($group) {
			$this->connect();

			$this->_currentgroup = $group;
			return $this->_nntp->selectGroup($this->_currentgroup);
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
		 * Sends a no-operation to the usenet server to keep the
		 * connection alive
		 */
		function sendNoop() {
			if (!$this->_connected) {
				return ;
			} # if
			
			/* The NNTP protocol has no proper noop command, this will do fine */
			if (!empty($this->_currentgroup)) {
				$this->selectGroup($this->_currentgroup);		
			} # if
		} # sendnoop()

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
			
			# if an empty hostname is provided, abort
			if (empty($this->_server)) {
				throw new NntpException('Servername is empty', -1);
			}  # if 

			# if a portnumber is empty, abort
			if ((!is_numeric($this->_serverport)) || ($this->_serverport < 1)) {
				throw new NntpException('A server port has to be entered', -1);
			}  # if 

			# if the type of SSL is invalid, abort
			if (($this->_serverenc !== false) && (strtolower($this->_serverenc) !== 'ssl') && (strtolower($this->_serverenc) !== 'tls')) {
				throw new NntpException('Invalid encryption method specified', -1);
			}  # if 
			
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
				if ($ret === false) {
					throw new NntpException('Error while connecting to server (server did not respond)', -1);
				} # if
				
				if (!empty($tmpUser)) {
					$authed = $this->_nntp->authenticate($tmpUser, $tmpPass);
					
				} # if
			}catch(Exception $x){
				throw new NntpException($x->getMessage(), $x->getCode());
			}
		} # connect()
		
		/*
		 * Returns a full article divided between an
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
			# extract de velden we die we willen hebben
			foreach($headerList as $hdr) {
				$keys = explode(':', $hdr);

				switch($keys[0]) {
					case 'From'				: $tmpAr['fromhdr'] = utf8_encode(trim(substr($hdr, strlen('From: '), strpos($hdr, '<') - 1 - strlen('From: ')))); break;
					case 'Date'				: $tmpAr['stamp'] = strtotime(substr($hdr, strlen('Date: '))); break;
					case 'X-XML' 			: $tmpAr['fullxml'] .= substr($hdr, 7); break;
					case 'X-User-Signature'	: $tmpAr['user-signature'] = $this->_spotParser->unspecialString(substr($hdr, 18)); break;
					case 'X-XML-Signature'	: $tmpAr['xml-signature'] = $this->_spotParser->unspecialString(substr($hdr, 17)); break;
					case 'X-User-Avatar'	: $tmpAr['user-avatar'] .= substr($hdr, 15); break;
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
			$spotSigning = Services_Signing_Base::newServiceSigning();
			
			# We extracten elke comment en halen daar de datum en poster uit, inclusief de body
			# als comment text zelf.
			foreach($commentList as $comment) {
				try {
					$commentTpl = array('messageid' => '', 'fromhdr' => '', 'stamp' => 0, 'user-signature' => '', 
										'user-key' => '', 'spotterid' => '', 'verified' => false,
										'user-avatar' => '', 'fullxml' => '');
										
					$tmpAr = array_merge($commentTpl, $this->getArticle('<' . $comment['messageid'] . '>'));
					$tmpAr['messageid'] = $comment['messageid'];
					$tmpAr = array_merge($tmpAr, $this->parseHeader($tmpAr['header'], $tmpAr));

					# Valideer de signature van de XML, deze is gesigned door de user zelf
					$tmpAr['verified'] = $spotSigning->verifyComment($tmpAr);
					if ($tmpAr['verified']) {
						$tmpAr['spotterid'] = $spotSigning->calculateSpotterId($tmpAr['user-key']['modulo']);
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

		
		function getImage($segmentList) {
			$imageContent = '';

			/*
			 * Retrieve all image segments 
			 */
			foreach($segmentList['image']['segment'] as $seg) {
				$imgTmp = implode('', $this->getBody('<' . $seg . '>'));
				$imageContent .= $this->_spotParser->unspecialZipStr($imgTmp);
			} # foreach
			
			return $imageContent;
		} # getImage
		
		function getNzb($segList) {
			$nzb = '';
			
			foreach($segList as $seg) {
				$nzb .= implode('', $this->getBody('<' . $seg . '>'));
			} # foreach

			$nzb = gzinflate($this->_spotParser->unspecialZipStr($nzb));
			return $nzb;
		} # getNzb
		
		/*
		 * Post plain usenet message
		 */
		function postPlainMessage($newsgroup, $message, $additionalHeaders) {
			$header = 'Subject: ' . utf8_decode($message['title']) . "\r\n";
			$header .= 'Newsgroups: ' . $newsgroup . "\r\n";
			$header .= 'Message-ID: <' . $message['newmessageid'] . ">\r\n";
			$header .= "X-Newsreader: SpotWeb v" . SPOTWEB_VERSION . "\r\n";
			$header .= "X-No-Archive: yes\r\n";
			$header .= $additionalHeaders;

			return $this->post(array($header, $message['body']));
		} # postPlainMessage

		/*
		 * Post a signed usenet message, we allow for additional headers
		 * so this function can be used by anything
		 */
		function postSignedMessage($user, $serverPrivKey, $newsgroup, $message, $additionalHeaders) {
			# instantiate necessary objects
			$spotSigning = Services_Signing_Base::newServiceSigning();

			# also by the SpotWeb server 
			$server_signature = $spotSigning->signMessage($serverPrivKey, '<' . $message['newmessageid'] . '>');

			$addHeaders = '';
			
			# Only add the user-signature header if there is none set yet
			if (stripos($additionalHeaders, 'X-User-Signature: ') === false) {
				# sign the messageid
				$user_signature = $spotSigning->signMessage($user['privatekey'], '<' . $message['newmessageid'] . '>');
			
				$addHeaders .= 'X-User-Signature: ' . $this->_spotParser->specialString($user_signature['signature']) . "\r\n";
				$addHeaders .= 'X-User-Key: ' . $spotSigning->pubkeyToXml($user_signature['publickey']) . "\r\n";
			} # if
			
			$addHeaders .= 'X-Server-Signature: ' . $this->_spotParser->specialString($server_signature['signature']) . "\r\n";
			$addHeaders .= 'X-Server-Key: ' . $spotSigning->pubkeyToXml($server_signature['publickey']) . "\r\n";
			$addHeaders .= $additionalHeaders;

			return $this->postPlainMessage($newsgroup, $message, $addHeaders);
		} # postSignedMessage
		
		/*
		 * Post a binary usenet message
		 */
		function postBinaryMessage($user, $newsgroup, $body, $additionalHeaders) {
			$chunkLen = (1024 * 1024);
			$segmentList = array();
			$spotSigning = Services_Signing_Base::newServiceSigning();
			
			/*
			 * Now start posting chunks of the NZB files
			 */
			while(strlen($body) > 0) {
				$message = array();

				/*
				 * Cut of the first piece of the NZB file, and remove it
				 * from the source string
				 */
				$chunk = substr($body, 0, $chunkLen - 1);
				$body = substr($body, $chunkLen - 1);

				/* 
				 * Split the body in parts of 900 characters
				 */
				$message['body'] = chunk_split($this->_spotParser->specialZipstr($chunk), 900);

				/*
				 * Create an unique messageid and store it so we can return it
				 * for the actual Spot creation
				 */
				$message['newmessageid'] = $spotSigning->makeRandomStr(32) . '@spot.net';
				$message['title'] = md5($message['body']);

				$addHeaders = 'From: ' . $user['username'] . " <" . trim($user['username']) . '@spot.net>' . "\r\n";
				$addHeaders .= 'Content-Type: text/plain; charset=ISO-8859-1' . "\r\n";
				$addHeaders .= 'Content-Transfer-Encoding: 8bit' . "\r\n";
				$addHeaders .= $additionalHeaders;

				/* 
				 * Actually post the image
				 */
				$this->postPlainMessage( $newsgroup, $message, $addHeaders);

				$segmentList[] = $message['newmessageid'];
			} # if
			 
			return $segmentList;
		} # postBinaryMessage

		/*
		 * Post a comment to a spot
		 */
		function postComment($user, $serverPrivKey, $newsgroup, $comment) {
			/* 
			 * Create the comment specific headers
			 */
			$addHeaders = 'From: ' . $user['username'] . " <" . trim($user['username']) . '@spot.net>' . "\r\n";
			$addHeaders .= 'References: <' . $comment['inreplyto']. ">\r\n";
			$addHeaders .= 'X-User-Rating: ' . (int) $comment['rating'] . "\r\n";
			
			/*
			 * And add the X-User-Avatar header if user has an avatar specified
			 */
			if (!empty($user['avatar'])) {
				$tmpAvatar = explode("\r\n", chunk_split($user['avatar'], 900));
				
				foreach($tmpAvatar as $avatarChunk) {
					if (strlen(trim($avatarChunk)) > 0) {
						$addHeaders .= 'X-User-Avatar: ' . $avatarChunk . "\r\n";
					} # if
				} # foreach
			} # if

			return $this->postSignedMessage($user, $serverPrivKey, $newsgroup, $comment, $addHeaders);
		} # postComment
		
	
		/*
		 * Posts a spot file and its corresponding image and NZB file (actually done by
		 * helper functions)
		 */
		function postFullSpot($user, $serverPrivKey, $newsgroup, $spot) {
			# instantiate the necessary objects
			$spotSigning = Services_Signing_Base::newServiceSigning();

			/*
			 * Create the spotnet from header part accrdoing to the following structure:
			 *   From: [Nickname] <[PUBLICKEY-MODULO.USERSIGNATURE]@[CAT][KEY-ID][SUBCAT].[SIZE].[RANDOM].[DATE].[CUSTOM-ID].[CUSTOM-VALUE].[SIGNATURE]>
			 */
			$spotHeader = ($spot['category'] + 1) . $spot['key']; // Append the category and keyid
			
			# Process each subcategory and add them to the from header
			foreach($spot['subcatlist'] as $subcat) {
				$spotHeader .= $subcat[0] . str_pad(substr($subcat, 1), 2, '0', STR_PAD_LEFT);
			} # foreach
			
			$spotHeader .= '.' . $spot['filesize'];
			$spotHeader .= '.' . 10; // some kind of magic number?
			$spotHeader .= '.' . time();
			$spotHeader .= '.' . $spotSigning->makeRandomStr(4);
			$spotHeader .= '.' . $spotSigning->makeRandomStr(3);

			# If a tag is given, add it to the subject
			if (strlen(trim($spot['tag'])) > 0) {
				$spot['title'] = $spot['title'] . ' | ' . $spot['tag'];
			} # if
			
			# Create the user-signature
			$user_signature = $spotSigning->signMessage($user['privatekey'], '<' . $spot['newmessageid'] . '>');
			$header = 'X-User-Signature: ' . $this->_spotParser->specialString($user_signature['signature']) . "\r\n";
			$header .= 'X-User-Key: ' . $spotSigning->pubkeyToXml($user_signature['publickey']) . "\r\n";
				
			# sign the header by using the users' key
			$header_signature = $spotSigning->signMessage($user['privatekey'], $spot['title'] . $spotHeader . $spot['poster']);

			# sign the XML with the users' key
			$xml_signature = $spotSigning->signMessage($user['privatekey'], $spot['spotxml']);

			# Extract the users' publickey
			$userPubKey = $spotSigning->getPublicKey($user['privatekey']);
			
			# Create the From header
			$spotnetFrom = $user['username'] . ' <' . 
								$this->_spotParser->specialString($userPubKey['publickey']['modulo']) . 
								'.' . 
								$this->_spotParser->specialString($user_signature['signature']) . '@';
			$header = 'From: ' . $spotnetFrom . $spotHeader . '.' . $this->_spotParser->specialString($header_signature['signature']) . ">\r\n";
			
			# Add the Spotnet XML file, but split it in chunks of 900 characters
			$tmpXml = explode("\r\n", chunk_split($spot['spotxml'], 900));
			foreach($tmpXml as $xmlChunk) {
				if (strlen(trim($xmlChunk)) > 0) {
					$header .= 'X-XML: ' . $xmlChunk . "\r\n";
				} # if
			} # foreach
			$header .= 'X-XML-Signature: ' . $this->_spotParser->specialString($xml_signature['signature']) . "\r\n";

			# post the message
			return $this->postSignedMessage($user, $serverPrivKey, $newsgroup, $spot, $header);
		} # postFullSpot

		/*
		 * Retrieve the fullspot from the NNTP server
		 */
		function getFullSpot($msgId) {
			SpotTiming::start('SpotNntp::' . __FUNCTION__);

			# initialize some variables
			$spotSigning = Services_Signing_Base::newServiceSigning();
			
			$spot = array('fullxml' => '',
						  'user-signature' => '',
						  'user-key' => '',
						  'verified' => false,
						  'messageid' => $msgId,
						  'spotterid' => '',
						  'xml-signature' => '',
						  'moderated' => 0,
						  'user-avatar' => '');
			# Vraag de volledige article header van de spot op
			SpotTiming::start('SpotNntp::' . __FUNCTION__ . '->getHeader()');
			$header = $this->getHeader('<' . $msgId . '>');
			SpotTiming::stop('SpotNntp::' . __FUNCTION__ . '->getHeader()', array($header));

			# Parse de header
			SpotTiming::start('SpotNntp::' . __FUNCTION__ . '->parseHeader()');
			$spot = array_merge($spot, $this->parseHeader($header, $spot));
			SpotTiming::stop('SpotNntp::' . __FUNCTION__ . '->parseHeader()', array($spot));
			
			# Valideer de signature van de XML, deze is gesigned door de user zelf
			SpotTiming::start('SpotNntp::' . __FUNCTION__ . '->verifyFullSpot()');
			$spot['verified'] = $spotSigning->verifyFullSpot($spot);
			SpotTiming::stop('SpotNntp::' . __FUNCTION__ . '->verifyFullSpot()', array($spot));
			
			# als de spot verified is, toon dan de spotterid van deze user
			if ($spot['verified']) {
				$spot['spotterid'] = $spotSigning->calculateSpotterId($spot['user-key']['modulo']);
			} # if	
			
			# Parse nu de XML file, alles wat al gedefinieerd is eerder wordt niet overschreven
			SpotTiming::start('SpotNntp::' . __FUNCTION__ . '->parseFull()');
			$spot = array_merge($this->_spotParser->parseFull($spot['fullxml']), $spot);
			SpotTiming::stop('SpotNntp::' . __FUNCTION__ . '->parseFull()', array($spot));
			
			SpotTiming::stop('SpotNntp::' . __FUNCTION__, array($spot));
			
			return $spot;
		} # getFullSpot 
		
		function reportSpotAsSpam($user, $serverPrivKey, $newsgroup, $report) {
			/*
			 * Create the comment specific headers
			 */
			$addHeaders = 'From: ' . $user['username'] . " <" . trim($user['username']) . '@spot.net>' . "\r\n";
			$addHeaders .= 'References: <' . $report['inreplyto']. ">\r\n";

			return $this->postSignedMessage($user, $serverPrivKey, $newsgroup, $report, $addHeaders);
		} # reportSpotAsSpam
		
		/*
		 * validates wether can succesfully connect to the usenet
		 * server
		 */
		function validateServer() {
			/*
			 * We need to select a group, because authenticatin
			 * is not always entered but sometimes required
			 */
			$this->selectGroup('free.pt');
			
			$this->quit();
		} # validateServer
		
} # class SpotNntp
