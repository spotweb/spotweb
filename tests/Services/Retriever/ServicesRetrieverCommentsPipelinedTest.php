<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../../lib/services/Nntp/Services_Nntp_PipelinedArticleResult.php';
require_once __DIR__.'/../../../lib/services/Nntp/Services_Nntp_PipelinedTransport.php';
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

    public function __construct()
    {
        parent::__construct(['host' => 'fixture', 'port' => 119, 'enc' => false, 'user' => '', 'pass' => '', 'buggy' => false]);
    }

    public function fetchArticlesPipelined(array $messageIds, $window = self::DEFAULT_PIPELINE_WINDOW)
    {
        $this->requestedWindows[] = (int) $window;
        $results = [];
        foreach ($messageIds as $messageId) {
            $results[] = new Services_Nntp_PipelinedArticleResult($messageId, 430, 'fixture missing');
        }

        return $results;
    }
}

class ServicesRetrieverCommentsPipelinedFailingSink extends Services_Retriever_CommentsCaptureSink
{
    public function commitBatch(array $comments, array $fullComments, array $spotMsgIdList, array $spotMsgIdRatingList, $lastProcessedArtNr, $lastProcessedId)
    {
        throw new Exception('fixture database failure');
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
        $this->assertCount(3, $result['article_statuses']);
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
