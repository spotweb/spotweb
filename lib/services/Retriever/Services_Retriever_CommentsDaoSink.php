<?php

class Services_Retriever_CommentsDaoSink implements Services_Retriever_CommentsSink
{
    private $_commentDao;
    private $_spotDao;
    private $_usenetStateDao;

    public function __construct(Dao_Comment $commentDao, Dao_Spot $spotDao, Dao_UsenetState $usenetStateDao)
    {
        $this->_commentDao = $commentDao;
        $this->_spotDao = $spotDao;
        $this->_usenetStateDao = $usenetStateDao;
    }

    public function matchCommentMessageIds($hdrList)
    {
        return $this->_commentDao->matchCommentMessageIds($hdrList);
    }

    public function recordArticleStatus($messageId, $code, $message)
    {
    }

    public function commitBatch(array $comments, array $fullComments, array $spotMsgIdList, array $spotMsgIdRatingList, $lastProcessedArtNr, $lastProcessedId)
    {
        $this->_commentDao->addComments($comments, $fullComments);

        if (!empty($lastProcessedId) && ($lastProcessedArtNr > 0)) {
            $this->_usenetStateDao->setMaxArticleId(Dao_UsenetState::State_Comments, $lastProcessedArtNr, $lastProcessedId);
        }

        $this->_spotDao->updateSpotRating($spotMsgIdRatingList);
        $this->_spotDao->updateSpotCommentCount($spotMsgIdList);
    }
}
