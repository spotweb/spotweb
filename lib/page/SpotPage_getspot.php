<?php
require_once "lib/page/SpotPage_Abs.php";
require_once "SpotCategories.php";

class SpotPage_getspot extends SpotPage_Abs {
	private $_messageid;
	
	function __construct($db, $settings, $prefs, $messageid) {
		parent::__construct($db, $settings, $prefs);
		$this->_messageid = $messageid;
	} # ctor


	function render() {
		$spotnntp = new SpotNntp($this->_settings['nntp_hdr']);
		$spotnntp->connect();
		
		# Vraag de volledige spot informatie op -- dit doet ook basic
		# sanity en validatie checking
		$fullSpot = $spotnntp->getFullSpot($this->_messageid);

		# Vraag een lijst op met alle comments messageid's
		$commentList = $this->_db->getCommentRef($fullSpot['messageid']);
		$comments = $spotnntp->getComments($commentList);
		
		# zet de page title
		$this->_pageTitle = "spot: " . $fullSpot['title'];
		
		#- display stuff -#
		$this->template('header');
		$this->template('spotinfo', array('spot' => $fullSpot, 'comments' => $comments));
		$this->template('footer');
	} # render
	
} # class SpotPage_getspot
