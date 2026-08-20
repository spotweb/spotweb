<?php

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../lib/services/Nntp/Services_Nntp_PipelinedArticleResult.php';
require_once __DIR__.'/../lib/services/Nntp/Services_Nntp_PipelinedFetchException.php';
require_once __DIR__.'/../lib/services/Nntp/Services_Nntp_PipelinedFetchOutcome.php';
require_once __DIR__.'/../lib/services/Nntp/Services_Nntp_PipelineDepth.php';
require_once __DIR__.'/../lib/services/Nntp/Services_Nntp_PipelinedTransport.php';
require_once __DIR__.'/../lib/services/Nntp/Services_Nntp_PipelinedRecovery.php';

/*
 * Read-only Spotweb scheduled retriever ARTICLE pipeline benchmark.
 *
 * This utility never writes DAO rows, cursor state, configuration, or cache.
 * It emits JSON Lines and never prints NNTP credentials/server settings.
 */

function pcb_usage()
{
    echo 'Usage: php utils/pipelined_comments_benchmark.php --read-only --use-bootstrap-settings [--spotweb-root ROOT] --stream comments|spots|reports [--group GROUP] --first N --count N [--window N|--windows A,B,C] [--samples N] [--sweep] [--random-range FIRST-LAST] [--seed N] [--jsonl FILE]'.PHP_EOL;
    echo 'Config discovery check: php utils/pipelined_comments_benchmark.php --read-only --config-discovery-check --spotweb-root ROOT'.PHP_EOL;
    echo 'Fixture mode: php utils/pipelined_comments_benchmark.php --read-only --fixture --stream comments --group GROUP --first N --count N --windows 1,32 --samples 2 --sweep'.PHP_EOL;
}

function pcb_arg($name, $default = null)
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

function pcb_has($name)
{
    global $argv;

    return in_array('--'.$name, $argv, true);
}

function pcb_windows()
{
    $windows = pcb_arg('windows', null);
    if ($windows === null) {
        $windows = pcb_arg('window', Services_Nntp_PipelineDepth::DefaultDepth);
    }

    $result = [];
    foreach (explode(',', $windows) as $window) {
        $window = trim($window);
        if (!Services_Nntp_PipelineDepth::validate($window)) {
            throw new InvalidArgumentException('Invalid pipeline window: '.$window);
        }
        $result[] = (int) $window;
    }

    return array_values(array_unique($result));
}

function pcb_range($first, $count, $randomRange)
{
    if ($randomRange === null) {
        return [$first, $first + $count - 1];
    }

    if (!preg_match('/^([0-9]+)-([0-9]+)$/', $randomRange, $matches)) {
        throw new InvalidArgumentException('Invalid --random-range; expected FIRST-LAST');
    }

    $rangeFirst = (int) $matches[1];
    $rangeLast = (int) $matches[2];
    if (($rangeFirst < 1) || ($rangeLast < $rangeFirst) || (($rangeLast - $rangeFirst + 1) < $count)) {
        throw new InvalidArgumentException('Invalid --random-range for requested count');
    }

    $start = mt_rand($rangeFirst, $rangeLast - $count + 1);

    return [$start, $start + $count - 1];
}

function pcb_default_group_for_stream($settings, $stream)
{
    switch ($stream) {
        case 'spots':
            return $settings->get('hdr_group');
        case 'comments':
            return $settings->get('comment_group');
        case 'reports':
            return $settings->get('report_group');
    }

    throw new InvalidArgumentException('Invalid stream; expected spots, comments, or reports');
}

function pcb_spotweb_root($spotwebRoot)
{
    if (($spotwebRoot === null) || ($spotwebRoot === '')) {
        throw new InvalidArgumentException('Missing --spotweb-root');
    }

    $root = realpath($spotwebRoot);
    if (($root === false) || (!is_dir($root))) {
        throw new InvalidArgumentException('Invalid --spotweb-root');
    }

    return $root;
}

function pcb_readable_spotweb_config_files($spotwebRoot)
{
    $root = pcb_spotweb_root($spotwebRoot);
    $files = [
        'dbsettings' => $root.'/dbsettings.inc.php',
        'settings'   => $root.'/settings.php',
    ];

    foreach ($files as $file) {
        if (!is_readable($file)) {
            throw new InvalidArgumentException('Spotweb root is missing readable '.basename($file));
        }
    }

    return [$root, $files];
}

function pcb_load_settings_from_spotweb_root($spotwebRoot)
{
    list($root, $files) = pcb_readable_spotweb_config_files($spotwebRoot);

    $dbsettings = [];
    require $files['dbsettings'];
    if ((!isset($dbsettings)) || (!is_array($dbsettings)) || empty($dbsettings['engine'])) {
        throw new InvalidArgumentException('Invalid Spotweb dbsettings.inc.php');
    }

    if (($dbsettings['engine'] === 'mysql') || ($dbsettings['engine'] === 'pdo_mysql')) {
        if (!isset($dbsettings['port'])) {
            $dbsettings['port'] = '3306';
        }
        if (!isset($dbsettings['schema'])) {
            $dbsettings['schema'] = '';
        }
    } elseif ($dbsettings['engine'] === 'pdo_pgsql') {
        if (!isset($dbsettings['port'])) {
            $dbsettings['port'] = '5432';
        }
        if (!isset($dbsettings['schema'])) {
            $dbsettings['schema'] = 'public';
        }
    } else {
        if (!isset($dbsettings['port'])) {
            $dbsettings['port'] = '';
        }
        if (!isset($dbsettings['schema'])) {
            $dbsettings['schema'] = '';
        }
    }

    $dbCon = dbeng_abs::getDbFactory($dbsettings['engine']);
    $dbCon->connect(
        $dbsettings['host'],
        $dbsettings['user'],
        $dbsettings['pass'],
        $dbsettings['dbname'],
        $dbsettings['port'],
        $dbsettings['schema']
    );

    $daoFactory = Dao_Factory::getDAOFactory($dbsettings['engine']);
    $daoFactory->setConnection($dbCon);

    $settingsContainer = new Services_Settings_Container();
    $dbSource = new Services_Settings_DbContainer();
    $dbSource->initialize(['dao' => $daoFactory->getSettingDao()]);
    $settingsContainer->addSource($dbSource);

    $settings = [];
    require $files['settings'];
    $fileSource = new Services_Settings_FileContainer();
    $fileSource->initialize($settings);
    $settingsContainer->addSource($fileSource);

    return [$settingsContainer, $daoFactory, $root];
}

class PipelinedCommentsBenchmarkFixtureTransport extends Services_Nntp_PipelinedTransport
{
    public function __construct()
    {
        parent::__construct(['host' => 'fixture', 'port' => 119, 'enc' => false, 'user' => '', 'pass' => '', 'buggy' => false]);
    }

    public function selectGroup($group)
    {
        return ['count' => 100000, 'first' => 1, 'last' => 100000];
    }

    public function getOverview($first, $last)
    {
        $headers = [];
        for ($i = $first; $i <= $last; $i++) {
            $headers[] = [
                'Number'     => $i,
                'Subject'    => 'fixture',
                'From'       => 'Fixture <fixture@example>',
                'Date'       => 'Tue, 18 Aug 2026 10:00:00 +0000',
                'Message-ID' => 'fixture.'.$i.'.1.1.1@example.invalid',
                'References' => 'spot.'.$i.'@example.invalid',
            ];
        }

        return $headers;
    }

    public function fetchArticlesPipelined(array $messageIds, $window = self::DEFAULT_PIPELINE_WINDOW)
    {
        usleep((int) ceil(count($messageIds) / max(1, (int) $window)) * 1000);
        $results = [];
        foreach ($messageIds as $messageId) {
            $results[] = new Services_Nntp_PipelinedArticleResult($messageId, 220, 'fixture article', [], ['body']);
        }

        return $results;
    }
}

if (pcb_has('help')) {
    pcb_usage();
    exit(0);
}

if (!pcb_has('read-only')) {
    fwrite(STDERR, 'Refusing to run without explicit --read-only guard'.PHP_EOL);
    pcb_usage();
    exit(2);
}

try {
    if (pcb_has('config-discovery-check')) {
        list($root, $files) = pcb_readable_spotweb_config_files(pcb_arg('spotweb-root', null));
        echo json_encode([
            'mode' => 'config-discovery-check',
            'ok' => true,
            'spotweb_root' => $root,
            'dbsettings_present' => is_readable($files['dbsettings']),
            'settings_present' => is_readable($files['settings']),
        ]).PHP_EOL;
        exit(0);
    }

    $fixture = pcb_has('fixture');
    $first = (int) pcb_arg('first', 0);
    $count = (int) pcb_arg('count', 0);
    $samples = max(1, (int) pcb_arg('samples', 1));
    $stream = pcb_arg('stream', 'comments');
    if (!in_array($stream, ['spots', 'comments', 'reports'], true)) {
        throw new InvalidArgumentException('Invalid --stream; expected spots, comments, or reports');
    }
    $group = pcb_arg('group', '');
    $randomRange = pcb_arg('random-range', null);
    $seed = pcb_arg('seed', null);
    $jsonl = pcb_arg('jsonl', null);
    $sweep = pcb_has('sweep');
    $windows = pcb_windows();

    if ($seed !== null) {
        mt_srand((int) $seed);
    }

    if ($fixture) {
        $server = ['fixture' => true];
        if ($group === '') {
            $group = 'fixture.'.$stream;
        }
    } elseif (pcb_has('use-bootstrap-settings')) {
        $spotwebRoot = pcb_arg('spotweb-root', null);
        if ($spotwebRoot !== null) {
            list($settings, $daoFactory, $loadedSpotwebRoot) = pcb_load_settings_from_spotweb_root($spotwebRoot);
        } else {
            $bootstrap = new Bootstrap();
            list($settings, $daoFactory, $req) = $bootstrap->boot();
        }
        $server = $settings->get('nntp_hdr');
        if ($group === '') {
            $group = pcb_default_group_for_stream($settings, $stream);
        }
    } else {
        throw new InvalidArgumentException('Use --use-bootstrap-settings or --fixture');
    }

    if (($group === '') || ($count < 1) || (($first < 1) && ($randomRange === null))) {
        throw new InvalidArgumentException('Missing required group/range/count arguments');
    }

    $outputHandle = STDOUT;
    if ($jsonl !== null) {
        $outputHandle = fopen($jsonl, 'ab');
        if (!is_resource($outputHandle)) {
            throw new RuntimeException('Unable to open JSONL output file');
        }
    }

    $jobs = [];
    for ($sample = 1; $sample <= $samples; $sample++) {
        $sampleWindows = $windows;
        if ($sweep) {
            shuffle($sampleWindows);
        }
        foreach ($sampleWindows as $window) {
            $jobs[] = ['sample' => $sample, 'window' => $window];
        }
    }

    foreach ($jobs as $job) {
        list($rangeFirst, $rangeLast) = pcb_range($first, $count, $randomRange);
        $started = microtime(true);
        $row = [
            'ts' => gmdate('c'),
            'mode' => $fixture ? 'fixture' : 'bootstrap',
            'stream' => $stream,
            'group_supplied' => pcb_arg('group', '') !== '',
            'sample' => $job['sample'],
            'window' => $job['window'],
            'requested_range' => [$rangeFirst, $rangeLast],
            'requested_count' => $count,
            'header_count' => 0,
            'terminal_count' => 0,
            'unresolved_count' => 0,
            'elapsed_seconds' => 0,
            'terminal_rate_per_second' => 0,
            'connections' => 0,
            'retries' => 0,
            'errors' => [],
            'ok' => false,
        ];

        try {
            $transport = $fixture ? new PipelinedCommentsBenchmarkFixtureTransport() : new Services_Nntp_PipelinedTransport($server);
            $transport->selectGroup($group);
            $headers = $transport->getOverview($rangeFirst, $rangeLast);
            $ids = array_map(function ($header) {
                return $header['Message-ID'];
            }, $headers);
            $outcome = (new Services_Nntp_PipelinedRecovery($transport))->fetchArticles($ids, $job['window']);
            $transport->quit();

            $elapsed = microtime(true) - $started;
            $row['header_count'] = count($headers);
            $row['terminal_count'] = $outcome->terminalCount();
            $row['unresolved_count'] = $outcome->unresolvedCount();
            $row['elapsed_seconds'] = round($elapsed, 6);
            $row['terminal_rate_per_second'] = round($outcome->terminalCount() / max($elapsed, 0.000001), 4);
            $row['connections'] = $outcome->connectionsOpened();
            $row['retries'] = $outcome->retryCount();
            $row['errors'] = $outcome->errors();
            $row['ok'] = !$outcome->hasUnresolved();
        } catch (Exception $x) {
            $elapsed = microtime(true) - $started;
            $row['elapsed_seconds'] = round($elapsed, 6);
            $row['errors'][] = [
                'class' => get_class($x),
                'code' => (int) $x->getCode(),
                'message' => $x->getMessage(),
            ];
        }

        fwrite($outputHandle, json_encode($row).PHP_EOL);
    }

    if (($jsonl !== null) && is_resource($outputHandle)) {
        fclose($outputHandle);
    }
} catch (Exception $x) {
    fwrite(STDERR, $x->getMessage().PHP_EOL);
    pcb_usage();
    exit(2);
}
