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
	public function postComment($user, $body, $rating, $inReplyTo, $newMessageId) {
		# haal de spot op waar dit een reply op is
		$spotsOverview = new SpotsOverview($this->_db, $this->_settings);
		$fullSpot = $spotsOverview->getFullSpot($inReplyTo, $user['userid'], $this->_nntp_post);

var_dump($fullSpot);
		
		# als de hashcash al niet klopt, doen we verder geen moeite
		if (substr(sha1($newMessageId), 0, 4) != '0000') {
			return array('errors' => array('invalidhashcash'));
		} # if

		# controleer dat de messageid waarop we replyen overeenkomt
		# met het newMessageid om replay-attacks te voorkomen.
		// FIXME
		
		# controleer dat het random getal niet recentelijk ook al gebruikt
		# is voor deze messageid (hiermee voorkomen we dat de hashcash niet
		# steeds herberekend wordt voor het volspammen van 1 spot).
		// FIXME
echo 'alles ok, we gaan posten!';
die();
		
		# en post daadwerkelijk de comment
		$this->_nntp_post->postComment($this->_currentSession['user'],
									   $this->_settings->get('privatekey'),  # Server private key
									   $rating,
									   $settings->get('comment_group'),
									   $inReplyTo, 
									   $fullSpot['title'], 
									   $body);
	} # postComment
	
} # SpotPosting
