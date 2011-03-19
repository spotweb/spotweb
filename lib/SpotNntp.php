<?php
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
			while( (count($art) > $i) && ($art[$i] != '')) {
				$result['header'][] = $art[$i];
				$i++;
			} # while
			$i++;

			while( (count($art) > $i) && ($art[$i] != '')) {
				$result['body'][] = $art[$i];
				$i++;
			} # while
			
			return $result;
		} # getArticle
		
		function cbCommentDateSort($a, $b) {
			if ($a['date'] == $b['date']) {
				return 0;
			} # if
			
			return ($a['date'] < $b['date']) ? -1 : 1;
		} # cbCommentDateSort
		
		function getComments($commentList) {
			$comments = array();
			$spotParser = new SpotParser();
			
			# We extracten elke comment en halen daar de datum en poster uit, inclusief de body
			# als comment text zelf.
			foreach($commentList as $comment) {
				try {
					$tmpAr = $this->getArticle('<' . $comment['messageid'] . '>');	
					$tmpAr['messageid'] = $comment['messageid'];

					# extract de velden we die we willen hebben
					foreach($tmpAr['header'] as $hdr) {
						$keys = explode(':', $hdr);
						
						switch($keys[0]) {
							case 'From'				: $tmpAr['from'] = trim(substr($hdr, strlen('From: '), strpos($hdr, '<') - 1 - strlen('From: '))); break;
							case 'Date'				: $tmpAr['date'] = strtotime(substr($hdr, strlen('Date: '))); break;
							case 'X-User-Signature'	: $tmpAr['user-signature'] = base64_decode($spotParser->unspecialString(substr($hdr, 18))); break;
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
					if ((!empty($tmpAr['user-signature'])) && (!empty($tmpAr['user-key']))) {
						$tmpAr['verified'] = $spotParser->checkRsaSignature('<' . $tmpAr['messageid'] .  '>', $tmpAr['user-signature'], $tmpAr['user-key']);
						if (!$tmpAr['verified']) {
							$tmpAr['verified'] = $spotParser->checkRsaSignature('<' . $tmpAr['messageid'] .  '>' . implode("\r\n", $tmpAr['body']) . "\r\n\r\n" . $tmpAr['from'], $tmpAr['user-signature'], $tmpAr['user-key']);
						} # if
						
						if ($tmpAr['verified']) {
							$tmpAr['userid'] = $spotParser->calculateUserid($tmpAr['user-key']['modulo']);
						} # if
					} else {
						$tmpAr['verified'] = false;
					} # else

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
					case 'X-User-Signature'	: $spot['user-signature'] = base64_decode($spotParser->unspecialString(substr($str, 18))); break;
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
			if ((!empty($spot['user-signature'])) && (!empty($spot['user-key']))) {
				$spot['verified'] = $spotParser->checkRsaSignature('<' . $spot['messageid'] . '>', $spot['user-signature'], $spot['user-key']);
				
				if (!$spot['verified']) {
					$spot['verified'] = $spotParser->checkRsaSignature($spot['xml-signature'], $spot['user-signature'], $spot['user-key']);
				} # if 
			} else {
				$spot['verified'] = false;
			} # else

			# als de spot verified is, toon dan de userid van deze user
			if ($spot['verified']) {
				$spot['userid'] = $spotParser->calculateUserid($spot['user-key']['modulo']);
			} # if	
			
			# Parse nu de XML file, alles wat al gedefinieerd is eerder wordt niet overschreven
			$spot = array_merge($spotParser->parseFull($spot['fullxml']), $spot);
			
			return $spot;
		} # getFullSpot 
		
} # class SpotNntp
