<?php

class SpotPosting {
	private $_db;
	private $_settings;
	private $_nntp_post;

	function __construct(SpotDb $db, SpotSettings $settings) {
		$this->_db = $db;
		$this->_settings = $settings;
		
		$this->_nntp_post = new SpotNntp($settings->get('nntp_nzb'));
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
		if (($comment['rating'] > 9) || ($comment['rating'] < 0)) {
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

		# en post daadwerkelijk de comment
		if (empty($errorList)) {
			$this->_nntp_post->postComment($user,
										   $this->_settings->get('privatekey'),  # Server private key
										   $this->_settings->get('comment_group'),
										   $fullSpot['title'], 
										   $comment);
			$this->_db->addPostedComment($user['userid'], $comment);
		} # if
		
		return $errorList;
	} # postComment
	
} # SpotPosting
