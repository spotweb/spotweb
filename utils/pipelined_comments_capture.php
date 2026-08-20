<?php

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../lib/services/Nntp/Services_Nntp_PipelinedArticleResult.php';
require_once __DIR__.'/../lib/services/Nntp/Services_Nntp_PipelinedTransport.php';
require_once __DIR__.'/../lib/services/Retriever/Services_Retriever_CommentsSink.php';
require_once __DIR__.'/../lib/services/Retriever/Services_Retriever_CommentsCaptureSink.php';

function pc_usage()
{
    echo 'Usage: php utils/pipelined_comments_capture.php --config-json FILE --group GROUP --first N --last N [--window N]'.PHP_EOL;
    echo '   or: php utils/pipelined_comments_capture.php --use-bootstrap-settings --group GROUP --first N --last N [--window N]'.PHP_EOL;
    echo PHP_EOL;
    echo 'The bootstrap mode reads the configured nntp_hdr in memory and never prints credentials.'.PHP_EOL;
}

function pc_arg($name, $default = null)
{
    global $argv;
    foreach ($argv as $idx => $arg) {
        if ($arg === '--'.$name && isset($argv[$idx + 1])) {
            return $argv[$idx + 1];
        }
        if (strpos($arg, '--'.$name.'=') === 0) {
            return substr($arg, strlen($name) + 3);
        }
    }

    return $default;
}

if (in_array('--help', $argv, true)) {
    pc_usage();
    exit(0);
}

$first = (int) pc_arg('first', 0);
$last = (int) pc_arg('last', 0);
$group = pc_arg('group', '');
$window = (int) pc_arg('window', Services_Nntp_PipelinedTransport::DEFAULT_PIPELINE_WINDOW);

if (($first < 1) || ($last < $first) || ($group === '')) {
    pc_usage();
    exit(2);
}

if (in_array('--use-bootstrap-settings', $argv, true)) {
    $bootstrap = new Bootstrap();
    list($settings, $daoFactory, $req) = $bootstrap->boot();
    $server = $settings->get('nntp_hdr');
} else {
    $configFile = pc_arg('config-json', '');
    if ($configFile === '') {
        pc_usage();
        exit(2);
    }
    $server = json_decode(file_get_contents($configFile), true);
    if (!is_array($server)) {
        fwrite(STDERR, 'Invalid JSON config'.PHP_EOL);
        exit(2);
    }
}

$transport = new Services_Nntp_PipelinedTransport($server);
$capture = new Services_Retriever_CommentsCaptureSink();

$transport->selectGroup($group);
$headers = $transport->getOverview($first, $last);
$articles = $transport->fetchArticlesPipelined(
    array_map(function ($header) {
        return $header['Message-ID'];
    }, $headers),
    $window
);

foreach ($articles as $article) {
    $capture->recordArticleStatus($article->messageId, $article->code, $article->message);
}

$transport->quit();

echo json_encode(
    [
        'window'      => $window,
        'range'       => [$first, $last],
        'headers'     => count($headers),
        'connections' => $transport->getOpenedConnectionCount(),
        'capture'     => $capture->getCanonicalResult(),
    ],
    JSON_PRETTY_PRINT
).PHP_EOL;
