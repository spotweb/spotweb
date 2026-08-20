<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../../lib/services/Nntp/Services_Nntp_PipelinedArticleResult.php';
require_once __DIR__.'/../../../lib/services/Nntp/Services_Nntp_PipelinedFetchException.php';
require_once __DIR__.'/../../../lib/services/Nntp/Services_Nntp_PipelinedFetchOutcome.php';
require_once __DIR__.'/../../../lib/services/Nntp/Services_Nntp_PipelineDepth.php';
require_once __DIR__.'/../../../lib/services/Nntp/Services_Nntp_PipelinedTransport.php';
require_once __DIR__.'/../../../lib/services/Nntp/Services_Nntp_PipelinedRecovery.php';

class ServicesNntpPipelinedRecoveryFixtureTransport extends Services_Nntp_PipelinedTransport
{
    public $calls = [];
    public $freshConnections = 0;
    public $reconnectScript = [];
    private $_script;

    public function __construct(array $script)
    {
        parent::__construct(['host' => 'fixture', 'port' => 119, 'enc' => false, 'user' => '', 'pass' => '', 'buggy' => false]);
        $this->_script = $script;
    }

    public function fetchArticlesPipelined(array $messageIds, $window = self::DEFAULT_PIPELINE_WINDOW)
    {
        $this->calls[] = ['ids' => $messageIds, 'window' => (int) $window];
        $step = array_shift($this->_script);

        if ($step['type'] === 'return') {
            return $step['results'];
        }

        throw new Services_Nntp_PipelinedFetchException(
            $step['message'],
            isset($step['code']) ? $step['code'] : -1,
            isset($step['terminal']) ? $step['terminal'] : [],
            isset($step['unresolved']) ? $step['unresolved'] : $messageIds,
            isset($step['class']) ? $step['class'] : 'transport'
        );
    }

    public function withFreshConnection()
    {
        $this->freshConnections++;
        if (!empty($this->reconnectScript)) {
            $step = array_shift($this->reconnectScript);
            if ($step['type'] === 'throw') {
                throw new NntpException($step['message'], isset($step['code']) ? $step['code'] : -1);
            }
        }
    }
}

class ServicesNntpPipelinedRecoveryTest extends TestCase
{
    public function testDisconnectBeforeResponseRetriesAllIds()
    {
        $transport = new ServicesNntpPipelinedRecoveryFixtureTransport([
            ['type' => 'throw', 'message' => 'disconnect before response', 'unresolved' => ['a', 'b']],
            ['type' => 'return', 'results' => [$this->article('a'), $this->article('b')]],
        ]);

        $outcome = (new Services_Nntp_PipelinedRecovery($transport))->fetchArticles(['a', 'b'], 32);

        $this->assertSame([['ids' => ['a', 'b'], 'window' => 32], ['ids' => ['a', 'b'], 'window' => 32]], $transport->calls);
        $this->assertSame(1, $transport->freshConnections);
        $this->assertSame(2, $outcome->terminalCount());
        $this->assertSame([], $outcome->unresolvedMessageIds());
    }

    public function testDisconnectMidMultilineBodyRetriesOnlyUnresolvedTail()
    {
        $transport = new ServicesNntpPipelinedRecoveryFixtureTransport([
            ['type' => 'throw', 'message' => 'disconnect mid body', 'terminal' => [$this->article('a')], 'unresolved' => ['b', 'c']],
            ['type' => 'return', 'results' => [$this->article('b'), $this->article('c')]],
        ]);

        $outcome = (new Services_Nntp_PipelinedRecovery($transport))->fetchArticles(['a', 'b', 'c'], 16);

        $this->assertSame([['ids' => ['a', 'b', 'c'], 'window' => 16], ['ids' => ['b', 'c'], 'window' => 16]], $transport->calls);
        $this->assertSame(['a', 'b', 'c'], $this->messageIds($outcome->terminalResults()));
        $this->assertSame([], $outcome->unresolvedMessageIds());
    }

    public function testTimeoutFallsBackToWindowOneAfterConfiguredRetry()
    {
        $transport = new ServicesNntpPipelinedRecoveryFixtureTransport([
            ['type' => 'throw', 'message' => 'timeout', 'unresolved' => ['a']],
            ['type' => 'throw', 'message' => 'timeout retry', 'unresolved' => ['a']],
            ['type' => 'return', 'results' => [$this->article('a')]],
        ]);

        $outcome = (new Services_Nntp_PipelinedRecovery($transport))->fetchArticles(['a'], 64);

        $this->assertSame([64, 64, 1], array_map(function ($call) {
            return $call['window'];
        }, $transport->calls));
        $this->assertSame(2, $outcome->retryCount());
        $this->assertSame([], $outcome->unresolvedMessageIds());
    }

    public function testNoSuchArticleIsTerminalAndNotRetried()
    {
        $transport = new ServicesNntpPipelinedRecoveryFixtureTransport([
            ['type' => 'return', 'results' => [$this->missing('a'), $this->article('b')]],
        ]);

        $outcome = (new Services_Nntp_PipelinedRecovery($transport))->fetchArticles(['a', 'b'], 32);

        $this->assertCount(1, $transport->calls);
        $this->assertSame([430, 220], array_map(function ($result) {
            return $result->code;
        }, $outcome->terminalResults()));
        $this->assertSame([], $outcome->unresolvedMessageIds());
    }

    public function testFinalArticleFailureIsDeferredAfterRetryBudget()
    {
        $transport = new ServicesNntpPipelinedRecoveryFixtureTransport([
            ['type' => 'throw', 'message' => 'final disconnect', 'terminal' => [$this->article('a'), $this->article('b')], 'unresolved' => ['c']],
            ['type' => 'throw', 'message' => 'final retry failed', 'unresolved' => ['c']],
            ['type' => 'throw', 'message' => 'final window one failed', 'unresolved' => ['c']],
        ]);

        $outcome = (new Services_Nntp_PipelinedRecovery($transport))->fetchArticles(['a', 'b', 'c'], 32);

        $this->assertSame(['a', 'b'], $this->messageIds($outcome->terminalResults()));
        $this->assertSame(['c'], $outcome->unresolvedMessageIds());
        $this->assertSame(2, $outcome->retryCount());
        $this->assertSame([32, 32, 1], array_map(function ($call) {
            return $call['window'];
        }, $transport->calls));
    }

    public function testReconnectFailurePreservesCompletedAndUnresolvedTail()
    {
        $transport = new ServicesNntpPipelinedRecoveryFixtureTransport([
            ['type' => 'throw', 'message' => 'disconnect mid body', 'class' => 'transport.disconnect', 'terminal' => [$this->article('a')], 'unresolved' => ['b', 'c']],
        ]);
        $transport->reconnectScript = [
            ['type' => 'throw', 'message' => 'Error while connecting to server: refused'],
            ['type' => 'throw', 'message' => 'Error while connecting to server: refused'],
        ];

        $outcome = (new Services_Nntp_PipelinedRecovery($transport))->fetchArticles(['a', 'b', 'c'], 32);

        $this->assertSame(['a'], $this->messageIds($outcome->terminalResults()));
        $this->assertSame(['b', 'c'], $outcome->unresolvedMessageIds());
        $this->assertSame(2, $transport->freshConnections);
        $this->assertSame([['ids' => ['a', 'b', 'c'], 'window' => 32]], $transport->calls);
        $this->assertSame(['transport.disconnect', 'reconnect.connect', 'reconnect.connect'], array_map(function ($error) {
            return $error['class'];
        }, $outcome->errors()));
    }

    public function testUnexpectedArticleProtocolFailureRetriesDequeuedIdAndFollowingTail()
    {
        $transport = new ServicesNntpPipelinedRecoveryFixtureTransport([
            ['type' => 'throw', 'message' => 'Unexpected ARTICLE response: 423 no such article number', 'code' => 423, 'class' => 'transport.protocol', 'terminal' => [$this->article('a')], 'unresolved' => ['b', 'c']],
            ['type' => 'return', 'results' => [$this->article('b'), $this->article('c')]],
        ]);

        $outcome = (new Services_Nntp_PipelinedRecovery($transport))->fetchArticles(['a', 'b', 'c'], 32);

        $this->assertSame([['ids' => ['a', 'b', 'c'], 'window' => 32], ['ids' => ['b', 'c'], 'window' => 32]], $transport->calls);
        $this->assertSame(['a', 'b', 'c'], $this->messageIds($outcome->terminalResults()));
        $this->assertSame([], $outcome->unresolvedMessageIds());
        $this->assertSame('transport.protocol', $outcome->errors()[0]['class']);
    }

    private function article($messageId)
    {
        return new Services_Nntp_PipelinedArticleResult($messageId, 220, 'fixture article', ['From: Fixture <fixture@example>'], ['body']);
    }

    private function missing($messageId)
    {
        return new Services_Nntp_PipelinedArticleResult($messageId, 430, 'no such article');
    }

    private function messageIds(array $results)
    {
        return array_map(function ($result) {
            return $result->messageId;
        }, $results);
    }
}
