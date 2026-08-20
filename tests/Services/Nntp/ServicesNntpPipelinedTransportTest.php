<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../../lib/services/Nntp/Services_Nntp_PipelinedArticleResult.php';
require_once __DIR__.'/../../../lib/services/Nntp/Services_Nntp_PipelinedFetchException.php';
require_once __DIR__.'/../../../lib/services/Nntp/Services_Nntp_PipelineDepth.php';
require_once __DIR__.'/../../../lib/services/Nntp/Services_Nntp_PipelinedTransport.php';

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
        $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        $this->assertTrue(is_resource($server), $errstr);
        $name = stream_socket_get_name($server, false);
        list($host, $port) = explode(':', $name);

        $pid = pcntl_fork();
        if ($pid === 0) {
            $conn = stream_socket_accept($server, 10);
            if (!is_resource($conn)) {
                exit(1);
            }
            fwrite($conn, "200 fixture ready\r\n");
            $articleCommands = [];
            while (($line = fgets($conn)) !== false) {
                $line = rtrim($line, "\r\n");
                file_put_contents($commandLog, $line.PHP_EOL, FILE_APPEND);

                if ($line === 'GROUP free.pt') {
                    fwrite($conn, "211 3 1 3 free.pt\r\n");
                } elseif ($line === 'XOVER 1-3') {
                    fwrite($conn, "224 Overview follows\r\n");
                    fwrite($conn, "1\tSubject 1\tSender <s@example>\tTue, 18 Aug 2026 10:00:00 +0000\t<comment.1.1.1.1@example.invalid>\t<spot.1@example.invalid>\t100\t4\r\n");
                    fwrite($conn, "2\tSubject 2\tSender <s@example>\tTue, 18 Aug 2026 10:01:00 +0000\t<comment.2.1.1.1@example.invalid>\t<spot.2@example.invalid>\t100\t4\r\n");
                    fwrite($conn, "3\tSubject 3\tSender <s@example>\tTue, 18 Aug 2026 10:02:00 +0000\t<comment.3.1.1.1@example.invalid>\t<spot.3@example.invalid>\t100\t4\r\n");
                    fwrite($conn, ".\r\n");
                } elseif ($line === 'XHDR Message-ID 1-1') {
                    fwrite($conn, "221 Header follows\r\n");
                    fwrite($conn, "1 <comment.1.1.1.1@example.invalid>\r\n");
                    fwrite($conn, ".\r\n");
                } elseif (strpos($line, 'ARTICLE ') === 0) {
                    if ($mode === 'disconnect-before-response') {
                        fclose($conn);
                        exit(0);
                    }
                    $articleCommands[] = $line;
                    if (count($articleCommands) === 3) {
                        $this->writeFixtureArticle($conn, 1);
                        if ($mode === 'disconnect-mid-body') {
                            fwrite($conn, "220 2 <comment.2.1.1.1@example.invalid> article follows\r\n");
                            fwrite($conn, "From: Sender <s@example>\r\n");
                            fwrite($conn, "\r\npartial body");
                            fclose($conn);
                            exit(0);
                        }
                        fwrite($conn, "430 no such article\r\n");
                        $this->writeFixtureArticle($conn, 3);
                    }
                } elseif ($line === 'QUIT') {
                    fwrite($conn, "205 goodbye\r\n");
                    fclose($conn);
                    exit(0);
                }
            }
            exit(0);
        }

        $this->_childPids[] = $pid;
        fclose($server);

        return [
            'host' => $host,
            'port' => (int) $port,
        ];
    }

    private function writeFixtureArticle($conn, $number)
    {
        $payload = "220 ".$number." <comment.".$number.".1.1.1@example.invalid> article follows\r\n".
            "From: Sender <s@example>\r\n".
            "Date: Tue, 18 Aug 2026 10:0".$number.":00 +0000\r\n".
            "\r\n".
            "body ".$number."\r\n".
            "..dot stuffed\r\n".
            ".\r\n";

        foreach (str_split($payload, 7) as $part) {
            fwrite($conn, $part);
            usleep(1000);
        }
    }
}
