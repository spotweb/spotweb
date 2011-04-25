<?php

class SpotPosting {
	private $_db;
	private $_settings;
	private $_nntp_post;

	function __construct($db, $settings) {
		$this->_db = $db;
		$this->_settings = $settings;
		
		$this->_nntp_post = new SpotNntp($settings->get('nntp_nzb'));
	} # ctor

	/*
	 * Post een comment op een spot naar de newsserver, als dit lukt komt er
	 * een 'true' terug, anders een foutmelding
	 */
	public function postComment($user, $comment) {
		# haal de spot op waar dit een reply op is
		$spotsOverview = new SpotsOverview($this->_db, $this->_settings);
		$fullSpot = $spotsOverview->getFullSpot($comment['inreplyto'], $user['userid'], $this->_nntp_post);

		var_dump($comment);
		
		# als de hashcash al niet klopt, doen we verder geen moeite
		if (substr(sha1('<' . $comment['newmessageid'] . '>'), 0, 4) != '0000') {
			return array('errors' => array('postcomment_invalidhashcash'));
		} # if

		# controleer dat de messageid waarop we replyen overeenkomt
		# met het newMessageid om replay-attacks te voorkomen.
		$replyToPart = substr($comment['inreplyto'], 0, strpos($comment['inreplyto'], '@'));

		if (substr($comment['newmessageid'], 0, strlen($replyToPart)) != $replyToPart) { 
			return array('errors' => array('postcomment_replayattack'));
		} # if
		
		# controleer dat het random getal niet recentelijk ook al gebruikt
		# is voor deze messageid (hiermee voorkomen we dat de hashcash niet
		# steeds herberekend wordt voor het volspammen van 1 spot).
		if (!$this->_db->isCommentMessageIdUnique($comment['newmessageid'])) {
			return array('errors' => array('postcomment_replayattack'));
		} # if

		# en post daadwerkelijk de comment
		$this->_nntp_post->postComment($user,
									   $this->_settings->get('privatekey'),  # Server private key
									   $this->_settings->get('comment_group'),
									   $fullSpot['title'], 
									   $comment);
		$this->_db->addPostedComment($user['userid'], $comment);
		
		return array();
	} # postComment
	
} # SpotPosting
