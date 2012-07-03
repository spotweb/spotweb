<?php

class Services_Nntp_SpotReading {
	private $_nntpEngine;
	private $_spotParseUtil;
	
	/*
	 * constructor
	 */
	function __construct(Services_Nntp_Engine $nntpEngine) { 
		$this->_spotParseUtil = new Services_Format_Util();
		$this->_nntpEngine = $nntpEngine;
	} # ctor

	/*
	 * Parse an header and extract specific fields
	 * from it
	 */
	private function parseHeader($headerList, $tmpAr) {
		/*
		 * Interprets the header fields in a global way
		 */
		foreach($headerList as $hdr) {
			$keys = explode(':', $hdr);

			switch($keys[0]) {
				case 'From'				: $tmpAr['fromhdr'] = utf8_encode(trim(substr($hdr, strlen('From: '), strpos($hdr, '<') - 1 - strlen('From: ')))); break;
				case 'Date'				: $tmpAr['stamp'] = strtotime(substr($hdr, strlen('Date: '))); break;
				case 'X-XML' 			: $tmpAr['fullxml'] .= substr($hdr, 7); break;
				case 'X-User-Signature'	: $tmpAr['user-signature'] = $this->_spotParseUtil->spotUnprepareBase64(substr($hdr, 18)); break;
				case 'X-XML-Signature'	: $tmpAr['xml-signature'] = $this->_spotParseUtil->spotUnprepareBase64(substr($hdr, 17)); break;
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
	private function cbCommentDateSort($a, $b) {
		if ($a['stamp'] == $b['stamp']) {
			return 0;
		} # if
		
		return ($a['stamp'] < $b['stamp']) ? -1 : 1;
	} # cbCommentDateSort
	
	/*
	 * Returns a list of comments
	 */
	public function readComments($commentList) {
		$comments = array();
		$spotSigning = Services_Signing_Base::factory();

		/*
		 * We retrieve all comments from the server
		 */		
		foreach($commentList as $comment) {
			try {
				$commentTpl = array('messageid' => '', 'fromhdr' => '', 'stamp' => 0, 'user-signature' => '', 
									'user-key' => '', 'spotterid' => '', 'verified' => false,
									'user-avatar' => '', 'fullxml' => '', 'messageid' => $comment['messageid']);
									
				$article = array_merge($commentTpl, $this->_nntpEngine->getArticle('<' . $comment['messageid'] . '>'));
				$tmpAr = $this->parseHeader($article['header'], $article);

				/*
				 * Validate the XML signature of this comment
				 */
				$tmpAr['verified'] = $spotSigning->verifyComment($tmpAr);
				if ($tmpAr['verified']) {
					$tmpAr['spotterid'] = $this->_spotParseUtil->calculateSpotterId($tmpAr['user-key']['modulo']);
				} # if

				# encode the body for UTF8
				$tmpAr['body'] = array_map('utf8_encode', $tmpAr['body']);

				$comments[] = $tmpAr; 
			} catch(Exception $x) {
				/*
				 * Sometimes retrieval of a comment fails. This is weird because the comments
				 * are retrieved from the server using XOVER, but we just ignore the
				 * error as there is no way to handle this really.
				 */
				;
			} # catch
		} # foreach

		# Sort comments on date
		usort($comments, array($this, 'cbCommentDateSort'));

		return $comments;
	} # readComments


	/*
	 * Returns an image poted to the newsgroup
	 */	
	public function readBinary($segmentList, $compressed) {
		$bin = '';
		
		foreach($segmentList as $seg) {
			$bin .= implode('', $this->_nntpEngine->getBody('<' . $seg . '>'));
		} # foreach

		if ($compressed) {
			return gzinflate($this->_spotParseUtil->unspecialZipStr($bin));
		} else {
			return $this->_spotParseUtil->unspecialZipStr($bin);
		} # else
	} # readBinary

	/*
	 * Retrieve the fullspot from the NNTP server
	 */
	public function readFullSpot($msgId) {
		# initialize some variables
		$spotSigning = Services_Signing_Base::factory();
		
		$spot = array('fullxml' => '',
					  'user-signature' => '',
					  'user-key' => '',
					  'verified' => false,
					  'messageid' => $msgId,
					  'spotterid' => '',
					  'xml-signature' => '',
					  'moderated' => 0,
					  'user-avatar' => '');

		/* 
		 * Retrieve the header of the given spot 
		 */
		$header = $this->_nntpEngine->getHeader('<' . $msgId . '>');
		$spot = array_merge($spot, $this->parseHeader($header, $spot));

		/*
		 * Validate the XML signature of the spot
		 */		
		$spot['verified'] = $spotSigning->verifyFullSpot($spot);
		
		/*
		 * if the spot is verified, lets calculate the spotterid as well
		 * so the user can safely store it in the database
		 */
		if ($spot['verified']) {
			$spot['spotterid'] = $this->_spotParseUtil->calculateSpotterId($spot['user-key']['modulo']);
		} # if	
		
		/*
		 * Parse the XML structure of the spot, technically not necessary
		 */
		$spotParser = new Services_Format_Parsing();
		$spot = array_merge($spotParser->parseFull($spot['fullxml']), $spot);
		
		return $spot;
	} # readFullSpot

} # Services_Nntp_SpotReading
