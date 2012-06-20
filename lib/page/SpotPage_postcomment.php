<?php
class SpotPage_postcomment extends SpotPage_Abs {
	private $_inReplyTo;
	private $_commentForm;
	
	function __construct(SpotDb $db, SpotSettings $settings, $currentSession, $params) {
		parent::__construct($db, $settings, $currentSession);
		$this->_commentForm = $params['commentform'];
		$this->_inReplyTo = $params['inreplyto'];
	} # ctor

	function render() {
		$formMessages = array('errors' => array(),
							  'info' => array());
							  
		# Validate proper permissions
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_post_comment, '');
							  
		# Sportparser is nodig voor het escapen van de random string
		$spotParser = new SpotParser();
		
		# spot signing is nodig voor het RSA signen van de spot en dergelijke
		$spotSigning = Services_Signing_Base::newServiceSigning();
		
		# creeer een default comment zodat het form altijd
		# de waardes van het form kan renderen
		$comment = array('body' => '',
						 'rating' => 0,
						 'inreplyto' => $this->_inReplyTo,
						 'newmessageid' => '',
						 'randomstr' => '');
		
		# postcomment verzoek was standaard niet geprobeerd
		$postResult = array();
		
		# zet de page title
		$this->_pageTitle = "spot: post comment";

		/* 
		 * bring the forms' action into the local scope for 
		 * easier access
		 */
		$formAction = $this->_commentForm['action'];


		# Make sure the anonymous user and reserved usernames cannot post content
		$spotUser = new SpotUserSystem($this->_db, $this->_settings);
		if (!$spotUser->allowedToPost($this->_currentSession['user'])) {
			$postResult = array('result' => 'notloggedin');

			$formAction = '';
		} # if
		
		if ($formAction == 'post') {
			# zorg er voor dat alle variables ingevuld zijn
			$comment = array_merge($comment, $this->_commentForm);

			# vraag de users' privatekey op
			$this->_currentSession['user']['privatekey'] = 
				$spotUser->getUserPrivateRsaKey($this->_currentSession['user']['userid']);
				
			# het messageid krijgen we met <>'s, maar we werken 
			# in spotweb altijd zonder, dus die strippen we
			$comment['newmessageid'] = substr($comment['newmessageid'], 1, -1);
			
			# valideer of we deze comment kunnen posten, en zo ja, doe dat dan
			$spotPosting = new SpotPosting($this->_db, $this->_settings);
			$formMessages['errors'] = $spotPosting->postComment($this->_currentSession['user'], $comment);
			
			if (empty($formMessages['errors'])) {
				/* Format the body so we can have smilies and stuff be shown in the template */
				$tmpBody = $this->_tplHelper->formatContent($comment['body']);

				/* Try to create the avatar */
				if (!empty($this->_currentSession['user']['avatar'])) {
					$comment['user-avatar'] = $this->_currentSession['user']['avatar'];
				} else {
					$spotSigning = Services_Signing_Base::newServiceSigning();
					$tmpKey = $spotSigning->getPublicKey($this->_currentSession['user']['privatekey']);
					$comment['user-key'] = $tmpKey['publickey'];
				} # else
				$commentImage = $this->_tplHelper->makeCommenterImageUrl($comment);

				/* and return the result to the system */
				$postResult = array('result' => 'success',
									'user' => $this->_currentSession['user']['username'],
									'spotterid' => $spotSigning->calculateSpotterId($this->_currentSession['user']['publickey']),
									'rating' => $comment['rating'],
									'body' => $tmpBody,
									'commentimage' => $commentImage);
			} else {
				$postResult = array('result' => 'failure');
			} # else
		} # if
		
		#- display stuff -#
		$this->template('postcomment', array('postcommentform' => $comment,
											 'formmessages' => $formMessages,
											 'postresult' => $postResult));
	} # render
	
} # class SpotPage_postcomment