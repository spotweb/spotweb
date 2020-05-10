<?php

interface Dao_UsenetState
{
    const State_Base = 'Base';
    const State_Spots = 'Spots';
    const State_Comments = 'Comments';
    const State_Reports = 'Reports';

    public function initialize();

    public function setMaxArticleId($infoType, $articleNumber, $messageId);

    public function getLastArticleNumber($infoType);

    public function getLastMessageId($infoType);

    public function isRetrieverRunning();

    public function setRetrieverRunning($isRunning);

    public function setLastUpdate($infoType);

    public function getLastUpdate($infoType);
} // Dao_UsenetState
