<?php

class Dao_Mysql_Nntp extends Dao_Base_Nntp {

	/* 
	 * Update of insert the maximum article id in de database.
	 */
	function setMaxArticleId($server, $maxarticleid) {
		$this->_conn->modify("INSERT INTO nntp(server, maxarticleid) VALUES ('%s', '%s') ON DUPLICATE KEY UPDATE maxarticleid = '%s'",
							Array($server, (int) $maxarticleid, (int) $maxarticleid));
	} # setMaxArticleId()
	
	/*
	 * Marks the retriever as running
	 */
	function setRetrieverRunning($server, $isRunning) {
		if ($isRunning) {
			$runTime = time();
		} else {
			$runTime = 0;
		} # if

		$this->_conn->modify("INSERT INTO nntp (server, nowrunning) VALUES ('%s', %d) ON DUPLICATE KEY UPDATE nowrunning = %d",
								Array($server, (int) $runTime, (int) $runTime));
	} # setRetrieverRunning

} # Dao_Mysql_Nntp
