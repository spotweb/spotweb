<?php

class SpotNntp {
	public $_nntpEngine;
	public $_nntpReading;
	public $_nntpPosting;

	/*
	 * constructor
	 */
	function __construct($server) { 
		$this->_nntpEngine = new Services_Nntp_Engine($server);
		$this->_nntpReading = new Services_Nntp_SpotReading($this->_nntpEngine);
		$this->_nntpPosting = new Services_Nntp_SpotPosting($this->_nntpEngine);
		$this->_spotParser = new Services_Format_Parsing();
		$this->_spotParseUtil = new Services_Format_Util();
	} # ctor


	function selectGroup($group) {
		return $this->_nntpEngine->selectGroup($group);
	} # selectGroup()
	
	function getOverview($first, $last) {
		return $this->_nntpEngine->getOverview($first, $last);
	} # getOverview()

	function getMessageIdList($first, $last) {
		return $this->_nntpEngine->getMessageIdList($first, $last);
	} # getMessageIdList()
	
	function quit() {
		return $this->_nntpEngine->quit();
	} # quit()

	function sendNoop() {
		return $this->_nntpEngine->sendNoop();
	} # sendnoop()

	function post($article) {
		return $this->_nntpEngine->post($article);
	} # post()
	
	function connect() {
		return $this->_nntpEngine->connect();
	} # connect()
	
	function getComments($commentList) {
		return $this->_nntpReading->readComments($commentList);
	} # getComments

	public function postBinaryMessage($user, $newsgroup, $body, $additionalHeaders) {
		return $this->_nntpPosting->postBinaryMessage($user, $newsgroup, $body, $additionalHeaders);
	} # postBinaryMessage

	public function postComment($user, $serverPrivKey, $newsgroup, $comment) {
		return $this->_nntpPosting->postComment($user, $serverPrivKey, $newsgroup, $comment);
	} # postComment

	public function postFullSpot($user, $serverPrivKey, $newsgroup, $spot) {
		return $this->_nntpPosting->postFullSpot($user, $serverPrivKey, $newsgroup, $spot);
	} # postFullSpot

	function reportSpotAsSpam($user, $serverPrivKey, $newsgroup, $report) {
		return $this->_nntpPosting->reportSpotAsSpam($user, $serverPrivKey, $newsgroup, $report);
	} # reportSpotAsSpam
		
} # class SpotNntp
