<?php
require_once "lib/page/SpotPage_Abs.php";
require_once "SpotCategories.php";

class SpotPage_getspot extends SpotPage_Abs {

	function render() {
		$spotnntp = new SpotNntp($this->_settings['nntp_hdr']['host'],
								 $this->_settings['nntp_hdr']['enc'],
								 $this->_settings['nntp_hdr']['port'],
								 $this->_settings['nntp_hdr']['user'],
								 $this->_settings['nntp_hdr']['pass']);
		$spotnntp->connect();
		$header = $spotnntp->getFullSpot($this->_req->getDef('messageid', ''));
		
		$xmlar['spot'] = $header['info'];
		$xmlar['messageid'] = $this->_req->getDef('messageid', '');
		$xmlar['spot']['sabnzbdurl'] = sabnzbdurl($xmlar['spot']);
		$xmlar['spot']['searchurl'] = makesearchurl($xmlar['spot']);
		$xmlar['spot']['messageid'] = $xmlar['messageid'];
		$xmlar['spot']['userid'] = $header['userid'];
		$xmlar['spot']['verified'] = $header['verified'];

		# Vraag een lijst op met alle comments messageid's
		$commentList = $this->_db->getCommentRef($xmlar['messageid']);
		$comments = $spotnntp->getComments($commentList);
		
		# zet de page title
		$pagetitle = "spot: " . $xmlar['spot']['title'];

		#- display stuff -#
		template('header');
		template('spotinfo', array('spot' => $xmlar['spot'], 'rawspot' => $xmlar, 'comments' => $comments));
		template('footer');
	} # render
	
} # class SpotPage_getspot
