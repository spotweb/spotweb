<?php

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../lib/services/Nntp/Services_Nntp_PipelinedArticleResult.php';
require_once __DIR__.'/../lib/services/Nntp/Services_Nntp_PipelinedTransport.php';

/*
 * Synthetic, DB-free benchmark harness. It compares local fixture transport
 * overhead for window 1 and pipelined windows. It does not contact a real NNTP
 * provider and must not be used as a provider performance claim.
 */

$count = isset($argv[1]) ? (int) $argv[1] : 1000;
$windows = [1, 4, 8, 16, 32];

class PipelinedCommentsSyntheticBenchmarkTransport extends Services_Nntp_PipelinedTransport
{
    private $_latencyUs;

    public function __construct($latencyUs)
    {
        parent::__construct(['host' => 'fixture', 'port' => 119, 'enc' => false, 'user' => '', 'pass' => '', 'buggy' => false]);
        $this->_latencyUs = $latencyUs;
    }

    public function fetchArticlesPipelined(array $messageIds, $window = self::DEFAULT_PIPELINE_WINDOW)
    {
        $batches = (int) ceil(count($messageIds) / max(1, (int) $window));
        usleep($batches * $this->_latencyUs);
        $results = [];
        foreach ($messageIds as $messageId) {
            $results[] = new Services_Nntp_PipelinedArticleResult($messageId, 220, 'fixture article follows', ['From: Fixture <fixture@example>'], ['body']);
        }

        return $results;
    }
}

$ids = [];
for ($i = 1; $i <= $count; $i++) {
    $ids[] = 'fixture.'.$i.'.1.1.1@example.invalid';
}

$rows = [];
foreach ($windows as $window) {
    $transport = new PipelinedCommentsSyntheticBenchmarkTransport(2000);
    $start = microtime(true);
    $transport->fetchArticlesPipelined($ids, $window);
    $elapsed = microtime(true) - $start;
    $rows[] = [
        'window' => $window,
        'items'  => $count,
        'seconds' => round($elapsed, 4),
        'items_per_second' => round($count / max($elapsed, 0.000001), 2),
    ];
}

echo json_encode(
    [
        'type' => 'synthetic-local-no-provider-no-db',
        'caveat' => 'Models latency hiding only; real provider benchmark must be run later under user control.',
        'results' => $rows,
    ],
    JSON_PRETTY_PRINT
).PHP_EOL;
