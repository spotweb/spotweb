<?php
/* Externe library */
require_once "Net/NNTP/Client.php";

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

			# Set pear error handling to be used by exceptions
			PEAR::setErrorHandling(PEAR_ERROR_EXCEPTION);			
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
			return $this->_nntp->post($article);
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
			
			$ret = $this->_nntp->connect($this->_server, $this->_serverenc, $this->_serverport, 10);
			if (!empty($this->_user)) {
				$authed = $this->_nntp->authenticate($this->_user, $this->_pass);
			} # if
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
										
					$tmpAr = array_merge($commentTpl, $this->getArticle('<' . $comment . '>'));
					$tmpAr['messageid'] = $comment;

					# extract de velden we die we willen hebben
					foreach($tmpAr['header'] as $hdr) {
						$keys = explode(':', $hdr);
						
						switch($keys[0]) {
							case 'From'				: $tmpAr['fromhdr'] = trim(substr($hdr, strlen('From: '), strpos($hdr, '<') - 1 - strlen('From: '))); break;
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

		function postComment($user, $hashCash, $serverPrivKey, $rating, $newsgroup, $inReplyTo, $title, $content) {
			# FIXME: Het aantal nullen (minimaal 4) instelbaar maken via settings.php
		
			# We genereren een uniek messageid dat ook nog eens als eerste vier bytes 0000 geeft
			# van een SHA1 hash. 
			
			# Rating is 1 t/m 10, en bevat een rating van de spot waar dit commentaar over gaat
			# Spotweb zou in het overzicht bij elke spot de gemiddelde beoordeling kunnen tonen.
			$newMessageId = $spotSigning->makeExpensiveHash("<" . $inReplyTo . "." . $rating . "." . 
								$spotParser->specialString(base64_encode($spotSigning->makeRandomStr(4))), 
								"@spot.net>");
			
			# en sign het messageid
			$user_signature = $spotSigning->signMessage($user['privatekey'], $newMessageId);
			
			# ook door de php server 
			$server_signature = $spotSigning->signMessage($serverPrivKey, $newMessageId);
			
			$header = 'From: ' . $user['name'] . " <" . trim($user['name']) . '@spot.net>' . "\r\n";
			$header .= 'Subject: Re: ' . $title . "\r\n";
			$header .= 'Newsgroups: ' . $newsgroup . "\r\n";
			$header .= 'Message-ID: ' . $newMessageId . "\r\n";
			$header .= 'References: <' . $inReplyTo. ">\r\n";
			$header .= 'X-User-Signature: ' . $spotParser->specialString($user_signature['signature']) . "\r\n";
			$header .= 'X-Server-Signature: ' . $spotParser->specialString($server_signature['signature']) . "\r\n";
			$header .= 'X-User-Key: ' . $spotSigning->pubkeyToXml($user_signature['publickey']) . "\r\n";
			$header .= 'X-Server-Key: ' . $spotSigning->pubkeyToXml($server_signature['publickey']) . "\r\n";
			$header .= 'X-User-Rating: ' . $rating . "\r\n";
			
			# $header .= 'X-User-Avatar: ' 
			# Message-ID van een avatar

			# $header .= 'X-User-Gravatar: ' 
			# Hashcode van een Gravatar

			$header .= "X-Newsreader: SpotWeb 0.9\r\n";
			$header .= "X-No-Archive: yes\r\n";
			
			return $this->post(array($header, $content));

		} # postComment
		
		function getImage($segment) {
			$nzb = implode('', $this->getBody('<' . $segment . '>'));
			$spotParser = new SpotParser();
			return $spotParser->unspecialZipStr($nzb);
		} # getImage
		
		function getNzb($segList) {
			$nzb = '';
			
			foreach($segList as $seg) {
				$nzb .= implode('', $this->getBody('<' . $seg . '>'));
			} # foreach

			$spotParser = new SpotParser();
			return gzinflate( $spotParser->unspecialZipStr($nzb) );
		} # getNzb

		
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
		
} # class SpotNntp
