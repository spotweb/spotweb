<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../../lib/services/Nntp/Services_Nntp_PipelinedArticleResult.php';
require_once __DIR__.'/../../../lib/services/Nntp/Services_Nntp_PipelinedFetchException.php';
require_once __DIR__.'/../../../lib/services/Nntp/Services_Nntp_PipelineDepth.php';
require_once __DIR__.'/../../../lib/services/Nntp/Services_Nntp_PipelinedTransport.php';
require_once __DIR__.'/../../Support/NntpFixtureServer.php';

class ServicesNntpPipelinedTransportTest extends TestCase
{
    private $_childPids = [];

    protected function tearDown(): void
    {
        foreach ($this->_childPids as $pid) {
            pcntl_waitpid($pid, $status, WNOHANG);
        }
    }

    public function testOverviewHeaderLookupAndPipelinedArticles()
    {
        if (!function_exists('pcntl_fork')) {
            $this->markTestSkipped('pcntl is required for the local NNTP fixture server');
        }

        $commandLog = tempnam(sys_get_temp_dir(), 'spotweb-nntp-commands.');
        $server = $this->startFixtureServer($commandLog, 'normal');

        $transport = new Services_Nntp_PipelinedTransport([
            'host'       => $server['host'],
            'port'       => $server['port'],
            'enc'        => false,
            'user'       => '',
            'pass'       => '',
            'verifyname' => false,
            'buggy'      => false,
        ], 5);

        $group = $transport->selectGroup('free.pt');
        $this->assertSame(1, $group['first']);
        $this->assertSame(3, $group['last']);

        $overview = $transport->getOverview(1, 3);
        $this->assertCount(3, $overview);
        $this->assertSame('comment.1.1.1.1@example.invalid', $overview[0]['Message-ID']);

        $this->assertSame('comment.1.1.1.1@example.invalid', $transport->getMessageIdByArticleNumber(1));

        $results = $transport->fetchArticlesPipelined([
            'comment.1.1.1.1@example.invalid',
            'comment.2.1.1.1@example.invalid',
            'comment.3.1.1.1@example.invalid',
        ], 3);
        $transport->quit();

        $this->assertCount(3, $results);
        $this->assertTrue($results[0]->found());
        $this->assertSame(430, $results[1]->code);
        $this->assertTrue($results[2]->found());
        $this->assertSame(['body 1', '.dot stuffed'], $results[0]->body);

        $commands = file($commandLog, FILE_IGNORE_NEW_LINES);
        $articleCommands = array_values(array_filter($commands, function ($line) {
            return strpos($line, 'ARTICLE ') === 0;
        }));
        $this->assertSame([
            'ARTICLE <comment.1.1.1.1@example.invalid>',
            'ARTICLE <comment.2.1.1.1@example.invalid>',
            'ARTICLE <comment.3.1.1.1@example.invalid>',
        ], $articleCommands);
    }

    public function testDisconnectBeforeResponseReportsUnresolvedTail()
    {
        if (!function_exists('pcntl_fork')) {
            $this->markTestSkipped('pcntl is required for the local NNTP fixture server');
        }

        $commandLog = tempnam(sys_get_temp_dir(), 'spotweb-nntp-commands.');
        $server = $this->startFixtureServer($commandLog, 'disconnect-before-response');

        $transport = new Services_Nntp_PipelinedTransport([
            'host'       => $server['host'],
            'port'       => $server['port'],
            'enc'        => false,
            'user'       => '',
            'pass'       => '',
            'verifyname' => false,
            'buggy'      => false,
        ], 5);

        $transport->selectGroup('free.pt');

        try {
            $transport->fetchArticlesPipelined(['comment.1.1.1.1@example.invalid'], 1);
            $this->fail('Expected transport exception');
        } catch (Services_Nntp_PipelinedFetchException $x) {
            $this->assertSame([], $x->terminalResults());
            $this->assertSame(['comment.1.1.1.1@example.invalid'], $x->unresolvedMessageIds());
        }
    }

    public function testDisconnectDuringMultilineBodyPreservesCompletedAndUnresolvedTail()
    {
        if (!function_exists('pcntl_fork')) {
            $this->markTestSkipped('pcntl is required for the local NNTP fixture server');
        }

        $commandLog = tempnam(sys_get_temp_dir(), 'spotweb-nntp-commands.');
        $server = $this->startFixtureServer($commandLog, 'disconnect-mid-body');

        $transport = new Services_Nntp_PipelinedTransport([
            'host'       => $server['host'],
            'port'       => $server['port'],
            'enc'        => false,
            'user'       => '',
            'pass'       => '',
            'verifyname' => false,
            'buggy'      => false,
        ], 5);

        $transport->selectGroup('free.pt');

        try {
            $transport->fetchArticlesPipelined([
                'comment.1.1.1.1@example.invalid',
                'comment.2.1.1.1@example.invalid',
                'comment.3.1.1.1@example.invalid',
            ], 3);
            $this->fail('Expected transport exception');
        } catch (Services_Nntp_PipelinedFetchException $x) {
            $this->assertSame(['comment.1.1.1.1@example.invalid'], array_map(function ($result) {
                return $result->messageId;
            }, $x->terminalResults()));
            $this->assertSame([
                'comment.2.1.1.1@example.invalid',
                'comment.3.1.1.1@example.invalid',
            ], $x->unresolvedMessageIds());
        }
    }

    public function testUnexpectedArticleResponseReportsDequeuedIdAndFollowingTail()
    {
        if (!function_exists('pcntl_fork')) {
            $this->markTestSkipped('pcntl is required for the local NNTP fixture server');
        }

        $commandLog = tempnam(sys_get_temp_dir(), 'spotweb-nntp-commands.');
        $server = $this->startFixtureServer($commandLog, 'unexpected-response');

        $transport = new Services_Nntp_PipelinedTransport([
            'host'       => $server['host'],
            'port'       => $server['port'],
            'enc'        => false,
            'user'       => '',
            'pass'       => '',
            'verifyname' => false,
            'buggy'      => false,
        ], 5);

        $transport->selectGroup('free.pt');

        try {
            $transport->fetchArticlesPipelined([
                'comment.1.1.1.1@example.invalid',
                'comment.2.1.1.1@example.invalid',
                'comment.3.1.1.1@example.invalid',
            ], 3);
            $this->fail('Expected protocol exception');
        } catch (Services_Nntp_PipelinedFetchException $x) {
            $this->assertSame(['comment.1.1.1.1@example.invalid'], array_map(function ($result) {
                return $result->messageId;
            }, $x->terminalResults()));
            $this->assertSame([
                'comment.2.1.1.1@example.invalid',
                'comment.3.1.1.1@example.invalid',
            ], $x->unresolvedMessageIds());
            $this->assertSame('transport.protocol', $x->errorClass());
        }
    }

    public function testUnexpectedFinalArticleResponseReportsDequeuedFinalId()
    {
        if (!function_exists('pcntl_fork')) {
            $this->markTestSkipped('pcntl is required for the local NNTP fixture server');
        }

        $commandLog = tempnam(sys_get_temp_dir(), 'spotweb-nntp-commands.');
        $server = $this->startFixtureServer($commandLog, 'unexpected-final-response');

        $transport = new Services_Nntp_PipelinedTransport([
            'host'       => $server['host'],
            'port'       => $server['port'],
            'enc'        => false,
            'user'       => '',
            'pass'       => '',
            'verifyname' => false,
            'buggy'      => false,
        ], 5);

        $transport->selectGroup('free.pt');

        try {
            $transport->fetchArticlesPipelined(['comment.1.1.1.1@example.invalid'], 1);
            $this->fail('Expected protocol exception');
        } catch (Services_Nntp_PipelinedFetchException $x) {
            $this->assertSame([], $x->terminalResults());
            $this->assertSame(['comment.1.1.1.1@example.invalid'], $x->unresolvedMessageIds());
            $this->assertSame('transport.protocol', $x->errorClass());
        }
    }

    public function testSingleArticleHeadBodyAndPostUseSharedFixture()
    {
        if (!function_exists('pcntl_fork')) {
            $this->markTestSkipped('pcntl is required for the local NNTP fixture server');
        }

        $commandLog = tempnam(sys_get_temp_dir(), 'spotweb-nntp-commands.');
        $server = $this->startFixtureServer($commandLog, 'single-commands');

        $transport = new Services_Nntp_PipelinedTransport([
            'host'       => $server['host'],
            'port'       => $server['port'],
            'enc'        => false,
            'user'       => '',
            'pass'       => '',
            'verifyname' => false,
            'buggy'      => false,
        ], 5);

        $transport->selectGroup('free.pt');
        $this->assertSame(['From: Sender <s@example>', 'Date: Tue, 18 Aug 2026 10:01:00 +0000'], $transport->getHeader('comment.1.1.1.1@example.invalid'));
        $this->assertSame(['body 1', '.dot stuffed'], $transport->getBody('comment.1.1.1.1@example.invalid'));

        $article = $transport->getArticle('comment.1.1.1.1@example.invalid');
        $this->assertSame(['From: Sender <s@example>', 'Date: Tue, 18 Aug 2026 10:01:00 +0000'], $article['header']);
        $this->assertSame(['body 1', '.dot stuffed'], $article['body']);

        $this->assertTrue($transport->post([
            "Subject: Fixture\r\nNewsgroups: free.pt",
            "first body line\r\n.starts-with-dot",
        ]));
        $transport->quit();

        $commands = file($commandLog, FILE_IGNORE_NEW_LINES);
        $this->assertContains('HEAD <comment.1.1.1.1@example.invalid>', $commands);
        $this->assertContains('BODY <comment.1.1.1.1@example.invalid>', $commands);
        $this->assertContains('ARTICLE <comment.1.1.1.1@example.invalid>', $commands);
        $this->assertContains('POST', $commands);
        $this->assertContains('POST-DATA ..starts-with-dot', $commands);
        $this->assertContains('POST-DATA .', $commands);
    }

    public function testInvalidEncryptionConfigurationIsRejected()
    {
        $transport = new Services_Nntp_PipelinedTransport([
            'host'       => '127.0.0.1',
            'port'       => 119,
            'enc'        => 'invalid',
            'user'       => '',
            'pass'       => '',
            'verifyname' => false,
            'buggy'      => false,
        ], 1);

        $this->expectException('NntpException');
        $transport->connect();
    }

    private function startFixtureServer($commandLog, $mode)
    {
        $server = NntpFixtureServer::start($commandLog, $mode);
        $this->_childPids[] = $server['pid'];

        return $server;
    }
}
