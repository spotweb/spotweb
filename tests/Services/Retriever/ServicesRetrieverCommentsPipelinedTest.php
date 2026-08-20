<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../../lib/services/Nntp/Services_Nntp_PipelinedArticleResult.php';
require_once __DIR__.'/../../../lib/services/Nntp/Services_Nntp_PipelinedFetchException.php';
require_once __DIR__.'/../../../lib/services/Nntp/Services_Nntp_PipelinedFetchOutcome.php';
require_once __DIR__.'/../../../lib/services/Nntp/Services_Nntp_PipelinedRecovery.php';
require_once __DIR__.'/../../../lib/services/Nntp/Services_Nntp_PipelineDepth.php';
require_once __DIR__.'/../../../lib/services/Nntp/Services_Nntp_PipelinedTransport.php';
require_once __DIR__.'/../../../lib/exceptions/PipelinedCommentsDeferredException.php';
require_once __DIR__.'/../../../lib/services/Retriever/Services_Retriever_CommentsArticleParser.php';
require_once __DIR__.'/../../../lib/services/Retriever/Services_Retriever_CommentsSink.php';
require_once __DIR__.'/../../../lib/services/Retriever/Services_Retriever_CommentsCaptureSink.php';
require_once __DIR__.'/../../../lib/services/Retriever/Services_Retriever_CommentsPipelined.php';

class ServicesRetrieverCommentsPipelinedSettingsSource implements Services_Settings_IContainer
{
    private $_settings;

    public function __construct(array $settings)
    {
        $this->_settings = $settings;
    }

    public function initialize(array $cfg)
    {
    }

    public function getAllSettings()
    {
        return $this->_settings;
    }

    public function remove($name)
    {
        unset($this->_settings[$name]);
    }

    public function set($name, $value)
    {
        $this->_settings[$name] = $value;
    }
}

class ServicesRetrieverCommentsPipelinedStatusTarget
{
    public $statuses = [];

    public function displayStatus($cat, $txt)
    {
        $this->statuses[] = [$cat, $txt];
    }
}

class ServicesRetrieverCommentsPipelinedTransport extends Services_Nntp_PipelinedTransport
{
    public $requestedWindows = [];
    public $script = null;

    public function __construct()
    {
        parent::__construct(['host' => 'fixture', 'port' => 119, 'enc' => false, 'user' => '', 'pass' => '', 'buggy' => false]);
    }

    public function fetchArticlesPipelined(array $messageIds, $window = self::DEFAULT_PIPELINE_WINDOW)
    {
        $this->requestedWindows[] = (int) $window;
        if (is_array($this->script)) {
            $step = array_shift($this->script);
            if ($step['type'] === 'return') {
                return $step['results'];
            }

            throw new Services_Nntp_PipelinedFetchException(
                $step['message'],
                isset($step['code']) ? $step['code'] : -1,
                isset($step['terminal']) ? $step['terminal'] : [],
                isset($step['unresolved']) ? $step['unresolved'] : $messageIds,
                'transport'
            );
        }

        $results = [];
        foreach ($messageIds as $messageId) {
            $results[] = new Services_Nntp_PipelinedArticleResult($messageId, 430, 'fixture missing');
        }

        return $results;
    }

    public function withFreshConnection()
    {
    }
}

class ServicesRetrieverCommentsPipelinedFailingSink extends Services_Retriever_CommentsCaptureSink
{
    public function commitBatch(array $comments, array $fullComments, array $spotMsgIdList, array $spotMsgIdRatingList, $lastProcessedArtNr, $lastProcessedId)
    {
        throw new Exception('fixture database failure');
    }
}

class ServicesRetrieverCommentsPipelinedThrowingParser
{
    public function parse($messageId, array $article)
    {
        throw new ParseSpotXmlException('fixture malformed payload');
    }
}

class ServicesRetrieverCommentsPipelinedTest extends TestCase
{
    public function testCaptureSinkParityIsIdenticalAcrossPipelineWindows()
    {
        $expected = null;
        foreach ([1, 4, 8, 16] as $window) {
            $capture = new Services_Retriever_CommentsCaptureSink();
            $transport = new ServicesRetrieverCommentsPipelinedTransport();
            $runner = $this->newRunner($transport, $capture);
            $this->setPipelineWindow($runner, $window);

            $runner->process($this->headers(), 1, 4, microtime(true));
            $result = $capture->getCanonicalResult();

            if ($expected === null) {
                $expected = $result;
            }

            $this->assertSame($expected, $result);
            $this->assertSame([$window], $transport->requestedWindows);
        }
    }

    public function testDuplicateHeadersAreInsertedAndFetchedOnce()
    {
        $capture = new Services_Retriever_CommentsCaptureSink();
        $transport = new ServicesRetrieverCommentsPipelinedTransport();
        $runner = $this->newRunner($transport, $capture);

        $headers = $this->headers();
        $headers[] = $headers[0];
        $runner->process($headers, 1, 4, microtime(true));

        $result = $capture->getCanonicalResult();
        $this->assertCount(1, array_filter($result['batches'][0]['comments'], function ($comment) {
            return $comment['messageid'] === 'comment.1.5.1.1@example.invalid';
        }));
        $this->assertSame([32], $transport->requestedWindows);
    }

    public function testCommitFailureDoesNotProduceCaptureCheckpoint()
    {
        $capture = new ServicesRetrieverCommentsPipelinedFailingSink();
        $transport = new ServicesRetrieverCommentsPipelinedTransport();
        $runner = $this->newRunner($transport, $capture);

        try {
            $runner->process($this->headers(), 1, 4, microtime(true));
            $this->fail('Expected fixture database failure');
        } catch (Exception $x) {
            $this->assertSame('fixture database failure', $x->getMessage());
        }

        $this->assertSame([], $capture->getCanonicalResult()['batches']);
    }

    public function testMalformedArticlePayloadIsTerminalAndNonFatal()
    {
        $capture = new Services_Retriever_CommentsCaptureSink();
        $transport = new ServicesRetrieverCommentsPipelinedTransport();
        $transport->script = [
            ['type' => 'return', 'results' => [
                new Services_Nntp_PipelinedArticleResult('comment.1.5.1.1@example.invalid', 220, 'article follows', [], []),
            ]],
        ];
        $runner = $this->newRunner($transport, $capture);
        $this->setParser($runner, new ServicesRetrieverCommentsPipelinedThrowingParser());

        $runner->process([$this->headers()[0]], 1, 2, microtime(true));

        $result = $capture->getCanonicalResult();
        $this->assertSame(['articlenr' => 1, 'messageid' => 'comment.1.5.1.1@example.invalid'], $result['batches'][0]['cursor']);
        $this->assertSame(0, $result['article_statuses'][1]['code']);
        $this->assertSame([], $result['batches'][0]['fullcomments']);
    }

    public function testCursorStopsBeforeUnresolvedEarlierArticle()
    {
        $capture = new Services_Retriever_CommentsCaptureSink();
        $transport = new ServicesRetrieverCommentsPipelinedTransport();
        $transport->script = [
            ['type' => 'throw', 'message' => 'fixture disconnect', 'terminal' => [
                new Services_Nntp_PipelinedArticleResult('comment.1.5.1.1@example.invalid', 430, 'missing'),
                new Services_Nntp_PipelinedArticleResult('comment.3.11.1.1@example.invalid', 430, 'missing'),
            ], 'unresolved' => ['comment.2.0.1.1@example.invalid']],
            ['type' => 'throw', 'message' => 'fixture retry failed', 'unresolved' => ['comment.2.0.1.1@example.invalid']],
            ['type' => 'throw', 'message' => 'fixture window one failed', 'unresolved' => ['comment.2.0.1.1@example.invalid']],
        ];
        $runner = $this->newRunner($transport, $capture);

        try {
            $runner->process($this->headers(), 1, 4, microtime(true));
            $this->fail('Expected unresolved tail deferral');
        } catch (PipelinedCommentsDeferredException $x) {
            $this->assertSame(['comment.2.0.1.1@example.invalid'], $x->outcome()->unresolvedMessageIds());
        }

        $result = $capture->getCanonicalResult();
        $this->assertSame(['articlenr' => 1, 'messageid' => 'comment.1.5.1.1@example.invalid'], $result['batches'][0]['cursor']);
        $this->assertCount(1, $result['batches'][0]['comments']);
    }

    private function newRunner(Services_Nntp_PipelinedTransport $transport, Services_Retriever_CommentsSink $sink)
    {
        $settings = new Services_Settings_Container();
        $settings->addSource(new ServicesRetrieverCommentsPipelinedSettingsSource([
            'nntp_hdr'               => ['host' => 'fixture', 'port' => 119, 'enc' => false, 'user' => '', 'pass' => '', 'buggy' => false],
            'comment_group'          => 'free.pt',
            'retrieve_full_comments' => true,
            'retention'              => 0,
            'retentiontype'          => 'everything',
            'retrieve_newer_than'    => 0,
        ]));

        $factory = $this->getMockBuilder('Dao_Factory')->getMockForAbstractClass();
        $factory->method('getSpotDao')->willReturn($this->createMock('Dao_Spot'));
        $factory->method('getCommentDao')->willReturn($this->createMock('Dao_Comment'));
        $factory->method('getUsenetStateDao')->willReturn($this->createMock('Dao_UsenetState'));

        return new Services_Retriever_CommentsPipelined(
            $factory,
            $settings,
            false,
            false,
            $transport,
            $sink,
            new ServicesRetrieverCommentsPipelinedStatusTarget()
        );
    }

    private function setPipelineWindow($runner, $window)
    {
        $property = new ReflectionProperty('Services_Retriever_CommentsPipelined', '_pipelineWindow');
        $property->setAccessible(true);
        $property->setValue($runner, $window);
    }

    private function setParser($runner, $parser)
    {
        $property = new ReflectionProperty('Services_Retriever_CommentsPipelined', '_parser');
        $property->setAccessible(true);
        $property->setValue($runner, $parser);
    }

    private function headers()
    {
        return [
            [
                'Number'     => 1,
                'Subject'    => 'subject',
                'Date'       => 'Tue, 18 Aug 2026 10:00:00 +0000',
                'Message-ID' => 'comment.1.5.1.1@example.invalid',
            ],
            [
                'Number'     => 2,
                'Subject'    => 'subject',
                'Date'       => 'Tue, 18 Aug 2026 10:01:00 +0000',
                'Message-ID' => 'comment.2.0.1.1@example.invalid',
            ],
            [
                'Number'     => 3,
                'Subject'    => 'subject',
                'Date'       => 'Tue, 18 Aug 2026 10:02:00 +0000',
                'Message-ID' => 'comment.3.11.1.1@example.invalid',
            ],
        ];
    }
}
