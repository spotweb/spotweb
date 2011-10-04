<?php

class SpotPosting {
	private $_db;
	private $_settings;
	private $_nntp_post;

	function __construct(SpotDb $db, SpotSettings $settings) {
		$this->_db = $db;
		$this->_settings = $settings;
		
		$this->_nntp_post = new SpotNntp($settings->get('nntp_post'));
	} # ctor

	/*
	 * Post een comment op een spot naar de newsserver, als dit lukt komt er
	 * een 'true' terug, anders een foutmelding
	 */
	public function postComment($user, $comment) {
		$errorList = array();

		# haal de spot op waar dit een reply op is
		$spotsOverview = new SpotsOverview($this->_db, $this->_settings);
		$fullSpot = $spotsOverview->getFullSpot($comment['inreplyto'], $user['userid'], $this->_nntp_post);

		# als de hashcash al niet klopt, doen we verder geen moeite
		if (substr(sha1('<' . $comment['newmessageid'] . '>'), 0, 4) != '0000') {
			$errorList[] = array('postcomment_invalidhashcash', array());
		} # if

		# Body mag niet leeg zijn of heel kort
		$comment['body'] = trim($comment['body']);
		if (strlen($comment['body']) < 2) {
			$errorList[] = array('postcomment_bodytooshort', array());
		} # if
		
		# Rating mag niet uit de range vallen
		if (($comment['rating'] > 10) || ($comment['rating'] < 0)) {
			$errorList[] = array('postcomment_ratinginvalid', array());
		} # if
		
		# controleer dat de messageid waarop we replyen overeenkomt
		# met het newMessageid om replay-attacks te voorkomen.
		$replyToPart = substr($comment['inreplyto'], 0, strpos($comment['inreplyto'], '@'));

		if (substr($comment['newmessageid'], 0, strlen($replyToPart)) != $replyToPart) { 
			$errorList[] = array('postcomment_replayattack', array());
		} # if
		
		# controleer dat het random getal niet recentelijk ook al gebruikt
		# is voor deze messageid (hiermee voorkomen we dat de hashcash niet
		# steeds herberekend wordt voor het volspammen van 1 spot).
		if (!$this->_db->isCommentMessageIdUnique($comment['newmessageid'])) {
			$errorList[] = array('postcomment_replayattack', array());
		} # if

		# Add the title as a comment property
		$comment['title'] = 'Re: ' . $fullSpot['title'];
		
		# Body komt vanuit het form als UTF-8, maar moet verzonden worden als ISO-8859-1
		# De database wil echter alleen UTF-8, dus moeten we dat even opsplitsen
		$dbComment = $comment;
		$comment['body'] = utf8_decode($comment['body']);
		
		# en post daadwerkelijk de comment
		if (empty($errorList)) {
			$this->_nntp_post->postComment($user,
										   $this->_settings->get('privatekey'),  # Server private key
										   $this->_settings->get('comment_group'),
										   $comment);
			$this->_db->addPostedComment($user['userid'], $dbComment);
		} # if
		
		return $errorList;
	} # postComment

	/*
	 * Post a spot to the usenet server. 
	 */
	public function postSpot($user, $spot, $imageFilename, $nzbFilename) {
		$errorList = array();
		$hdr_newsgroup = $this->_settings->get('hdr_group');
		$bin_newsgroup = $this->_settings->get('nzb_group');
		
		$hdr_newsgroup = 'alt.test';
		$bin_newsgroup = 'alt.test';

		# If the hashcash doesn't match, we will never post it
		if (substr(sha1('<' . $spot['newmessageid'] . '>'), 0, 4) != '0000') {
			$errorList[] = array('postspot_invalidhashcash', array());
		} # if

		# Body cannot be empty or very short
		$spot['body'] = trim($spot['body']);
		if (strlen($spot['body']) < 30) {
			$errorList[] = array('postspot_bodytooshort', array());
		} # if

		# Title cannot be empty or very short
		$spot['title'] = trim($spot['title']);
		if (strlen($spot['title']) < 5) {
			$errorList[] = array('postspot_titletooshort', array());
		} # if
		
		# Subcategory should be valid
		if (($spot['category'] < 0) || ($spot['category'] > count(SpotCategories::$_head_categories))) {
			$errorList[] = array('postspot_invalidcategory', array($spot['category']));
		} # if
		
		/*
		 * To Check:
		 *
		 * - Category
		 * - SubCategory
		 * - NZB
		 * - Zelf de filesize invullen adhv de nzb
		 * - Plaatje (verplicht + < 1MB)
		 * - NZB file controleren
		 */
		 
		# Make sure we didn't use this messageid recently or at all, this
		# prevents people from not recalculating the hashcash in order to spam
		# the system
		if (!$this->_db->isNewSpotMessageIdUnique($spot['newmessageid'])) {
			$errorList[] = array('postspot_replayattack', array());
		} # if

		# Fix up some overly long spot properties and other minor issues
		$spot['tag'] = substr(trim($spot['tag'], ' |;'), 0, 99);
		$spot['http'] = substr(trim($spot['website']), 0, 449);

		# Create one list of all subcategories
		$spot['subcatlist'] = array_filter(explode('|', $spot['subcata'] . $spot['subcatb'] . $spot['subcatc'] . $spot['subcatd'] . $spot['subcatz']));

		# loop through all subcategories and check if they are valid in
		# our list of subcategories
		foreach($spot['subcatlist'] as $subCat) {
			$subCatLetter = substr($subCat, 0, 1);
			$subCatNumber = (int) substr($subCat, 1);
			
			if (!isset(SpotCategories::$_categories[$spot['category']][$subCatLetter][$subCatNumber])) {
				$errorList[] = array('postspot_invalidsubcat', array($subCat));
			} # if
		} # foreach	
		
		# en post daadwerkelijk de spot
		if (empty($errorList)) {
			/* 
			 * We save the original spot because we mangle it a little
			 * bit before posting / converting it, but we want the
			 * original to be in the database. 
			 */
			$dbSpot = $spot;
			
			# If a tag is given, add it to the subject
			if (strlen(trim($spot['tag'])) > 0) {
				$spot['title'] = $spot['title'] . ' | ' . $spot['tag'];
			} # if
			
			/*
			 * Retrieve the image information and post the image to 
			 * the appropriate newsgroup so we have the messageid list of 
			 * images
			 */
			$imgSegmentList = $this->_nntp_post->postBinaryMessage($user, $bin_newsgroup, file_get_contents($imageFilename), '');
			$tmpGdImageSize = getimagesize($imageFilename);
			$imageInfo = array('width' => $tmpGdImageSize[0],
							   'height' => $tmpGdImageSize[1],
							   'segments' => $imgSegmentList);
				
			# Post the NZB file to the appropriate newsgroups
			$nzbSegmentList = $this->_nntp_post->postBinaryMessage($user, $bin_newsgroup, gzdeflate(file_get_contents($nzbFilename)), '');
			
			# Convert the current Spotnet info, to an XML structure
			$spotParser = new SpotParser();
			$spotXml = $spotParser->convertSpotToXml($spot, $imageInfo, $nzbSegmentList);
			$spot['spotxml'] = $spotXml;
			
			# And actually post to the newsgroups
			$this->_nntp_post->postFullSpot($user,
										   $this->_settings->get('privatekey'),  # Server private key
										   $hdr_newsgroup,
										   $spot);
			$this->_db->addPostedSpot($user['userid'], $dbSpot, $spotXml);
		} # if
		
		return $errorList;
	} # postSpot
	
	/*
	 * Post een spam report van een spot naar de newsserver, als dit lukt komt er
	 * een 'true' terug, anders een foutmelding
	 */
	public function reportSpotAsSpam($user, $report) {
		$errorList = array();

		# Controleer eerst of de user al een report heeft aangemaakt, dan kunnen we gelijk stoppen.
		if ($this->_db->isReportPlaced($report['inreplyto'], $user['userid'])) {
			$errorList[] = array('postreport_alreadyreported', array());
		} # if
		
		# haal de spot op waar dit een reply op is
		$spotsOverview = new SpotsOverview($this->_db, $this->_settings);
		$fullSpot = $spotsOverview->getFullSpot($report['inreplyto'], $user['userid'], $this->_nntp_post);

		# als de hashcash al niet klopt, doen we verder geen moeite
		if (substr(sha1('<' . $report['newmessageid'] . '>'), 0, 4) != '0000') {
			$errorList[] = array('postcomment_invalidhashcash', array());
		} # if

		# Body mag niet leeg zijn of heel kort
		$report['body'] = trim($report['body']);
		if (strlen($report['body']) < 2) {
			$errorList[] = array('postcomment_bodytooshort', array());
		} # if
		
		# controleer dat de messageid waarop we replyen overeenkomt
		# met het newMessageid om replay-attacks te voorkomen.
		$replyToPart = substr($report['inreplyto'], 0, strpos($report['inreplyto'], '@'));

		if (substr($report['newmessageid'], 0, strlen($replyToPart)) != $replyToPart) { 
			$errorList[] = array('postcomment_replayattack', array());
		} # if
		
		# controleer dat het random getal niet recentelijk ook al gebruikt
		# is voor deze messageid (hiermee voorkomen we dat de hashcash niet
		# steeds herberekend wordt voor het volspammen van 1 spot).
		if (!$this->_db->isReportMessageIdUnique($report['newmessageid'])) {
			$errorList[] = array('postcomment_replayattack', array());
		} # if

		# Body komt vanuit het form als UTF-8, maar moet verzonden worden als ISO-8859-1
		# De database wil echter alleen UTF-8, dus moeten we dat even opsplitsen
		$dbReport = $report;
		$report['body'] = utf8_decode($report['body']);
		$report['title'] = 'REPORT <' . $report['inreplyto'] . '> ' . $fullSpot['title'];

		# en post daadwerkelijk de report
		if (empty($errorList)) {
			$this->_nntp_post->reportSpotAsSpam($user,
										   $this->_settings->get('privatekey'),  # Server private key
										   $this->_settings->get('report_group'),
										   $report);
			$this->_db->addPostedReport($user['userid'], $dbReport);
		} # if
		
		return $errorList;
	} # reportSpotAsSpam
	
} # SpotPosting
