<?php

class Dao_Base_Nntp implements Dao_Nntp {
	protected $_conn;

	/*
	 * constructs a new Dao_Base_Cache object, 
	 * connection object is given
	 */
	public function __construct($conn) {
		$this->_conn = $conn;
	} # ctor
	
	
	/* 
	 * Update of insert the maximum article id in de database.
	 */
	function setMaxArticleId($server, $maxarticleid) {
		$this->_conn->exec("UPDATE nntp SET maxarticleid = '%s' WHERE server = '%s'", Array((int) $maxarticleid, $server));
		if ($this->_conn->rows() == 0) {
			$this->_conn->modify("INSERT INTO nntp(server, maxarticleid) VALUES('%s', '%s')", Array($server, (int) $maxarticleid));
		} # if
	} # setMaxArticleId()

	/*
	 * Retrieves the current article (of the NNTP server), if it doesn't
	 * exist yet, we create the record and return a 0
	 */
	function getMaxArticleId($server) {
		$artId = $this->_conn->singleQuery("SELECT maxarticleid FROM nntp WHERE server = '%s'", Array($server));
		if ($artId == null) {
			$this->setMaxArticleId($server, 0);
			$artId = 0;
		} # if

		return $artId;
	} # getMaxArticleId

	/*
	 * Is the retriever already running?
	 */
	function isRetrieverRunning($server) {
		$artId = $this->_conn->singleQuery("SELECT nowrunning FROM nntp WHERE server = '%s'", Array($server));
		return ((!empty($artId)) && ($artId > (time() - 900)));
	} # isRetrieverRunning

	/*
	 * Marks the retriever as running
	 */
	function setRetrieverRunning($server, $isRunning) {
		if ($isRunning) {
			$runTime = time();
		} else {
			$runTime = 0;
		} # if

		$this->_conn->modify("UPDATE nntp SET nowrunning = %d WHERE server = '%s'", Array((int) $runTime, $server));
		if ($this->_conn->rows() == 0) {
			$this->_conn->modify("INSERT INTO nntp(server, nowrunning) VALUES('%s', %d)", Array($server, (int) $runTime));
		} # if
	} # setRetrieverRunning

	/*
	 * Updates the timestamp of the last run of the retriever
	 */
	function setLastUpdate($server) {
		return $this->_conn->modify("UPDATE nntp SET lastrun = '%d' WHERE server = '%s'", Array(time(), $server));
	} # getLastUpdate

	/*
	 * Returns the lastrun timestamp for the server
	 */
	function getLastUpdate($server) {
		return $this->_conn->singleQuery("SELECT lastrun FROM nntp WHERE server = '%s'", Array($server));
	} # getLastUpdate
	
} # Dao_Base_Nntp
