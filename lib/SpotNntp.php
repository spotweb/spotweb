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
	private $_spotParseUtil;
	private $_nntpEngine;
	private $_nntpReading;

	/*
	 * constructor
	 */
	function __construct($server) { 
		$error = '';
		
		$this->_connected = false;
		$this->_server = $server['host'];
		$this->_serverenc = $server['enc'];
		$this->_serverport = $server['port'];
		$this->_user = $server['user'];
		$this->_pass = $server['pass'];

		$this->_nntpEngine = new Services_Nntp_Engine($server);
		$this->_nntpReading = new Services_Nntp_SpotReading($this->_nntpEngine);
		$this->_spotParser = new Services_Format_Parsing();
		$this->_spotParseUtil = new Services_Format_Util();
	} # ctor


	function selectGroup($group) {
		return $this->_nntpEngine->selectGroup($group);
	} # selectGroup()
	
	function getOverview($first, $last) {
		return $this->_nntpEngine->getOverview($first, $last);
	} # getOverview()

	function getMessageIdList($first, $last) {
		return $this->_nntpEngine->getMessageIdList($first, $last);
	} # getMessageIdList()
	
	function quit() {
		return $this->_nntpEngine->quit();
	} # quit()

	function sendNoop() {
		return $this->_nntpEngine->sendNoop();
	} # sendnoop()

	function post($article) {
		return $this->_nntpEngine->post($article);
	} # post()
	
	function getHeader($msgid) {
		return $this->_nntpEngine->getHeader($msgid);
	} # getHeader()

	function getBody($msgid) {
		return $this->_nntpEngine->getBody($msgid);
	} # getBody	()
	
	function connect() {
		return $this->_nntpEngine->connect();
	} # connect()
	
	function getArticle($msgId) {
		return $this->_nntpEngine->getArticle($msgId);
	} # getArticle

	function getComments($commentList) {
		return $this->_nntpReading->readComments($commentList);
	} # getComments

	public function getFullSpot($msgId) {
		return $this->_nntpReading->readFullSpot($msgId);
	} # getFullSpot 

	function getImage($image) {
		$segmentList = array();
		foreach($image['image']['segment'] as $seg) {
			$segmentList[] = $seg;
		} # foreach

		return $this->_nntpReading->readBinary($segmentList, false);
	} # getImage
	
	function getNzb($segList) {
		return $this->_nntpReading->readBinary($segList, true);
	} # getNzb
	
	/*
	 * Post plain usenet message
	 */
	private function postPlainMessage($newsgroup, $message, $additionalHeaders) {
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
	private function postSignedMessage($user, $serverPrivKey, $newsgroup, $message, $additionalHeaders) {
		# instantiate necessary objects
		$spotSigning = Services_Signing_Base::newServiceSigning();

		# also by the SpotWeb server 
		$server_signature = $spotSigning->signMessage($serverPrivKey, '<' . $message['newmessageid'] . '>');

		$addHeaders = '';
		
		# Only add the user-signature header if there is none set yet
		if (stripos($additionalHeaders, 'X-User-Signature: ') === false) {
			# sign the messageid
			$user_signature = $spotSigning->signMessage($user['privatekey'], '<' . $message['newmessageid'] . '>');
		
			$addHeaders .= 'X-User-Signature: ' . $this->_spotParseUtil->spotPrepareBase64($user_signature['signature']) . "\r\n";
			$addHeaders .= 'X-User-Key: ' . $spotSigning->pubkeyToXml($user_signature['publickey']) . "\r\n";
		} # if
		
		$addHeaders .= 'X-Server-Signature: ' . $this->_spotParseUtil->spotPrepareBase64($server_signature['signature']) . "\r\n";
		$addHeaders .= 'X-Server-Key: ' . $spotSigning->pubkeyToXml($server_signature['publickey']) . "\r\n";
		$addHeaders .= $additionalHeaders;

		return $this->postPlainMessage($newsgroup, $message, $addHeaders);
	} # postSignedMessage
	
	/*
	 * Post a binary usenet message
	 */
	public function postBinaryMessage($user, $newsgroup, $body, $additionalHeaders) {
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
			$message['body'] = chunk_split($this->_spotParseUtil->specialZipstr($chunk), 900);

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
	public function postComment($user, $serverPrivKey, $newsgroup, $comment) {
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
	public function postFullSpot($user, $serverPrivKey, $newsgroup, $spot) {
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
		$header = 'X-User-Signature: ' . $this->_spotParseUtil->spotPrepareBase64($user_signature['signature']) . "\r\n";
		$header .= 'X-User-Key: ' . $spotSigning->pubkeyToXml($user_signature['publickey']) . "\r\n";
			
		# sign the header by using the users' key
		$header_signature = $spotSigning->signMessage($user['privatekey'], $spot['title'] . $spotHeader . $spot['poster']);

		# sign the XML with the users' key
		$xml_signature = $spotSigning->signMessage($user['privatekey'], $spot['spotxml']);

		# Extract the users' publickey
		$userPubKey = $spotSigning->getPublicKey($user['privatekey']);
		
		# Create the From header
		$spotnetFrom = $user['username'] . ' <' . 
							$this->_spotParseUtil->spotPrepareBase64($userPubKey['publickey']['modulo']) . 
							'.' . 
							$this->_spotParseUtil->spotPrepareBase64($user_signature['signature']) . '@';
		$header = 'From: ' . $spotnetFrom . $spotHeader . '.' . $this->_spotParseUtil->spotPrepareBase64($header_signature['signature']) . ">\r\n";
		
		# Add the Spotnet XML file, but split it in chunks of 900 characters
		$tmpXml = explode("\r\n", chunk_split($spot['spotxml'], 900));
		foreach($tmpXml as $xmlChunk) {
			if (strlen(trim($xmlChunk)) > 0) {
				$header .= 'X-XML: ' . $xmlChunk . "\r\n";
			} # if
		} # foreach
		$header .= 'X-XML-Signature: ' . $this->_spotParseUtil->spotPrepareBase64($xml_signature['signature']) . "\r\n";

		# post the message
		return $this->postSignedMessage($user, $serverPrivKey, $newsgroup, $spot, $header);
	} # postFullSpot

	function reportSpotAsSpam($user, $serverPrivKey, $newsgroup, $report) {
		/*
		 * Create the comment specific headers
		 */
		$addHeaders = 'From: ' . $user['username'] . " <" . trim($user['username']) . '@spot.net>' . "\r\n";
		$addHeaders .= 'References: <' . $report['inreplyto']. ">\r\n";

		return $this->postSignedMessage($user, $serverPrivKey, $newsgroup, $report, $addHeaders);
	} # reportSpotAsSpam
		
} # class SpotNntp
