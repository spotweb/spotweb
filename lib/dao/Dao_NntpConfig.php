<?php

interface Dao_NntpConfig {

	function setMaxArticleId($server, $maxarticleid);
	function getMaxArticleId($server);
	function isRetrieverRunning($server);
	function setRetrieverRunning($server, $isRunning);
	function setLastUpdate($server);
	function getLastUpdate($server);

	
} # Dao_NntpConfig