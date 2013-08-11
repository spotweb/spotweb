<?php

interface Dao_UsenetState {
    const State_Spots           = 'spots';
    const State_Comments        = 'comments';
    const State_Reports         = 'reports';

    function initialize();

	function setMaxArticleId($infoType, $articleNumber, $messageId);
	function getLastArticleNumber($infoType);
    function getLastMessageId($infoType);

	function isRetrieverRunning();
	function setRetrieverRunning($isRunning);

	function setLastUpdate($infoType);
	function getLastUpdate($infoType);

	
} # Dao_UsenetState