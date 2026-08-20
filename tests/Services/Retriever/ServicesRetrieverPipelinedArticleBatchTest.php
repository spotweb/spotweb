<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../../lib/services/Nntp/Services_Nntp_PipelinedArticleResult.php';
require_once __DIR__.'/../../../lib/services/Nntp/Services_Nntp_PipelinedFetchException.php';
require_once __DIR__.'/../../../lib/services/Nntp/Services_Nntp_PipelinedFetchOutcome.php';
require_once __DIR__.'/../../../lib/services/Nntp/Services_Nntp_PipelineDepth.php';
require_once __DIR__.'/../../../lib/services/Nntp/Services_Nntp_PipelinedTransport.php';
require_once __DIR__.'/../../../lib/services/Nntp/Services_Nntp_PipelinedRecovery.php';
require_once __DIR__.'/../../../lib/services/Retriever/Services_Retriever_PipelinedArticleBatch.php';

class ServicesRetrieverPipelinedArticleBatchTransport extends Services_Nntp_PipelinedTransport
{
    public $reconnectScript = [];
    private $_script;

    public function __construct(array $script)
    {
        parent::__construct(['host' => 'fixture', 'port' => 119, 'enc' => false, 'user' => '', 'pass' => '', 'buggy' => false]);
        $this->_script = $script;
    }

    public function fetchArticlesPipelined(array $messageIds, $window = self::DEFAULT_PIPELINE_WINDOW)
    {
        $step = array_shift($this->_script);
        if ($step['type'] === 'return') {
            return $step['results'];
        }

        throw new Services_Nntp_PipelinedFetchException(
            $step['message'],
            -1,
            isset($step['terminal']) ? $step['terminal'] : [],
            isset($step['unresolved']) ? $step['unresolved'] : $messageIds,
            'transport'
        );
    }

    public function withFreshConnection()
    {
        if (!empty($this->reconnectScript)) {
            $step = array_shift($this->reconnectScript);
            if ($step['type'] === 'throw') {
                throw new NntpException($step['message'], isset($step['code']) ? $step['code'] : -1);
            }
        }
    }
}

class ServicesRetrieverPipelinedArticleBatchParser
{
    private $_fail;

    public function __construct($fail = false)
    {
        $this->_fail = $fail;
    }

    public function parse($messageId, array $article)
    {
        if ($this->_fail) {
            throw new Exception('fixture malformed payload');
        }

        return ['messageid' => $messageId, 'body' => implode("\n", $article['body'])];
    }
}

class ServicesRetrieverPipelinedArticleBatchTest extends TestCase
{
    public function testMissingArticleIsTerminalForScheduledCommentsStream()
    {
        $items = [$this->item('comment-a')];
        $statuses = [];
        $batch = new Services_Retriever_PipelinedArticleBatch(new Services_Nntp_PipelinedRecovery(
            new ServicesRetrieverPipelinedArticleBatchTransport([
                ['type' => 'return', 'results' => [new Services_Nntp_PipelinedArticleResult('comment-a', 430, 'no such article')]],
            ])
        ));

        $outcome = $batch->apply($items, 32, new ServicesRetrieverPipelinedArticleBatchParser(), 'payload', 'malformed comment payload', function ($messageId, $code, $message) use (&$statuses) {
            $statuses[] = [$messageId, $code, $message];
        });

        $this->assertFalse($outcome->hasUnresolved());
        $this->assertSame([$items[0]], $batch->contiguousPrefix($items));
        $this->assertSame([['comment-a', 430, 'no such article']], $statuses);
        $this->assertNull($items[0]['payload']);
    }

    public function testMalformedArticleIsTerminalForScheduledSpotsStream()
    {
        $items = [$this->item('spot-a')];
        $batch = new Services_Retriever_PipelinedArticleBatch(new Services_Nntp_PipelinedRecovery(
            new ServicesRetrieverPipelinedArticleBatchTransport([
                ['type' => 'return', 'results' => [new Services_Nntp_PipelinedArticleResult('spot-a', 220, 'article follows', [], ['bad xml'])]],
            ])
        ));

        $outcome = $batch->apply($items, 32, new ServicesRetrieverPipelinedArticleBatchParser(true), 'payload', 'malformed spot payload');

        $this->assertFalse($outcome->hasUnresolved());
        $this->assertSame([$items[0]], $batch->contiguousPrefix($items));
        $this->assertSame(['code' => 0, 'message' => 'malformed spot payload'], $items[0]['article_status']);
    }

    public function testUnresolvedTailStopsCursorBeforeLaterScheduledReportsSuccess()
    {
        $items = [$this->item('report-a'), $this->item('report-b'), $this->item('report-c')];
        $batch = new Services_Retriever_PipelinedArticleBatch(new Services_Nntp_PipelinedRecovery(
            new ServicesRetrieverPipelinedArticleBatchTransport([
                ['type' => 'throw', 'message' => 'disconnect mid body', 'terminal' => [
                    new Services_Nntp_PipelinedArticleResult('report-a', 220, 'article follows', [], ['a']),
                    new Services_Nntp_PipelinedArticleResult('report-c', 220, 'article follows', [], ['c']),
                ], 'unresolved' => ['report-b']],
                ['type' => 'throw', 'message' => 'retry still failing', 'unresolved' => ['report-b']],
                ['type' => 'throw', 'message' => 'window one still failing', 'unresolved' => ['report-b']],
            ])
        ));

        $outcome = $batch->apply($items, 32, new ServicesRetrieverPipelinedArticleBatchParser(), 'payload', 'malformed report payload');

        $this->assertSame(['report-b'], $outcome->unresolvedMessageIds());
        $this->assertSame([$items[0]], $batch->contiguousPrefix($items));
        $this->assertSame(['messageid' => 'report-c', 'body' => 'c'], $items[2]['payload']);
    }

    public function testReconnectFailureDoesNotAdvanceCursorPastUnresolvedTail()
    {
        $items = [$this->item('comment-a'), $this->item('comment-b'), $this->item('comment-c')];
        $transport = new ServicesRetrieverPipelinedArticleBatchTransport([
            ['type' => 'throw', 'message' => 'disconnect mid body', 'terminal' => [
                new Services_Nntp_PipelinedArticleResult('comment-a', 220, 'article follows', [], ['a']),
            ], 'unresolved' => ['comment-b', 'comment-c']],
        ]);
        $transport->reconnectScript = [
            ['type' => 'throw', 'message' => 'Error while connecting to server: refused'],
            ['type' => 'throw', 'message' => 'Error while connecting to server: refused'],
        ];
        $batch = new Services_Retriever_PipelinedArticleBatch(new Services_Nntp_PipelinedRecovery($transport));

        $outcome = $batch->apply($items, 32, new ServicesRetrieverPipelinedArticleBatchParser(), 'payload', 'malformed comment payload');

        $this->assertSame(['comment-b', 'comment-c'], $outcome->unresolvedMessageIds());
        $this->assertSame([$items[0]], $batch->contiguousPrefix($items));
        $this->assertSame(['messageid' => 'comment-a', 'body' => 'a'], $items[0]['payload']);
        $this->assertFalse($items[1]['terminal']);
        $this->assertFalse($items[2]['terminal']);
    }

    private function item($messageId)
    {
        return [
            'messageid'      => $messageId,
            'articlenr'      => 1,
            'need_article'   => true,
            'terminal'       => false,
            'payload'        => null,
            'article_status' => null,
        ];
    }
}
