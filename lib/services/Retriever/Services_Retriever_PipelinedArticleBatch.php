<?php

/*
 * Shared scheduled retriever ARTICLE pipeline helper.
 *
 * This class is intentionally content-independent. It owns no Spotweb DAO
 * rules and does not parse spots/comments/reports. Scheduled streams provide
 * the parser and decide how a contiguous terminal prefix is persisted.
 */

class Services_Retriever_PipelinedArticleBatch
{
    private $_recovery;

    public function __construct(Services_Nntp_PipelinedRecovery $recovery)
    {
        $this->_recovery = $recovery;
    }

    public function apply(array &$items, $pipelineWindow, $parser, $targetField, $malformedMessage, $statusRecorder = null)
    {
        $messageIds = [];
        foreach ($items as $item) {
            if (!empty($item['need_article'])) {
                $messageIds[$item['messageid']] = $item['messageid'];
            }
        }

        $outcome = new Services_Nntp_PipelinedFetchOutcome();
        if (empty($messageIds)) {
            return $outcome;
        }

        $outcome = $this->_recovery->fetchArticles(array_values($messageIds), $pipelineWindow);
        $results = [];
        foreach ($outcome->terminalResults() as $result) {
            $results[$result->messageId] = $result;
        }
        $unresolved = array_fill_keys($outcome->unresolvedMessageIds(), true);

        foreach ($items as &$item) {
            if (empty($item['need_article'])) {
                continue;
            }

            if (isset($unresolved[$item['messageid']]) || !isset($results[$item['messageid']])) {
                $item['terminal'] = false;
                continue;
            }

            $result = $results[$item['messageid']];
            $item['terminal'] = true;
            $item['article_status'] = ['code' => $result->code, 'message' => $result->message];
            $this->recordStatus($statusRecorder, $result->messageId, $result->code, $result->message);

            if (!$result->found()) {
                continue;
            }

            try {
                $item[$targetField] = $parser->parse($result->messageId, $result->article());
            } catch (Exception $x) {
                $item['article_status'] = ['code' => 0, 'message' => $malformedMessage];
                $item[$targetField] = null;
                $this->recordStatus($statusRecorder, $result->messageId, 0, $malformedMessage);
            }
        }
        unset($item);

        return $outcome;
    }

    public function contiguousPrefix(array $items)
    {
        $prefix = [];
        foreach ($items as $item) {
            if (empty($item['terminal'])) {
                break;
            }
            $prefix[] = $item;
        }

        return $prefix;
    }

    private function recordStatus($statusRecorder, $messageId, $code, $message)
    {
        if ($statusRecorder === null) {
            return;
        }

        if (is_callable($statusRecorder)) {
            call_user_func($statusRecorder, $messageId, $code, $message);
        }
    }
}
