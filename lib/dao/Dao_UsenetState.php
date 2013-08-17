<?php

interface Dao_UsenetState {
    const State_Base            = 'Base';
    const State_Spots           = 'Spots';
    const State_Comments        = 'Comments';
    const State_Reports         = 'Reports';

    function initialize();

	function setMaxArticleId($infoType, $articleNumber, $messageId);
	function getLastArticleNumber($infoType);
    function getLastMessageId($infoType);

	function isRetrieverRunning();
	function setRetrieverRunning($isRunning);

	function setLastUpdate($infoType);
	function getLastUpdate($infoType);

	
} # Dao_UsenetState