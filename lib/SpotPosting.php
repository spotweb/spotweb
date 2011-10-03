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

		# Body komt vanuit het form als UTF-8, maar moet verzonden worden als ISO-8859-1
		# De database wil echter alleen UTF-8, dus moeten we dat even opsplitsen
		$dbComment = $comment;
		$comment['body'] = utf8_decode($comment['body']);

		# en post daadwerkelijk de comment
		if (empty($errorList)) {
			$this->_nntp_post->postComment($user,
										   $this->_settings->get('privatekey'),  # Server private key
										   $this->_settings->get('comment_group'),
										   $fullSpot['title'], 
										   $comment);
			$this->_db->addPostedComment($user['userid'], $dbComment);
		} # if
		
		return $errorList;
	} # postComment
	
	/*
	 * Post een spam report van een spot naar de newsserver, als dit lukt komt er
	 * een 'true' terug, anders een foutmelding
	 */
	public function reportSpotAsSpam($user, $report) {
		$errorList = array();

		#controleer eerst of de user al een report heeft aangemaakt, dan kunnen we gelijk stoppen.
		if (!$this->_db->isReportPlaced($report['inreplyto'], $user['userid'])) {
			$errorList[] = array('postreport_alreadyreported', array());
		}
		
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

		# en post daadwerkelijk de report
		if (empty($errorList)) {
			$this->_nntp_post->reportSpotAsSpam($user,
										   $this->_settings->get('privatekey'),  # Server private key
										   $fullSpot['title'], 
										   $report);
			$this->_db->addPostedReport($user['userid'], $dbReport);
		} # if
		
		return $errorList;
	} # reportSpotAsSpam
	
} # SpotPosting
