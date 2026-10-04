<?php

/* Receive-only depth-32 smoke test. Supply server configurations as a JSON
 * array on stdin; credentials remain in memory and are never reported.
 * No application Bootstrap, database, cursor, cache, or posting is used.
 */
if (!in_array('--read-only', $argv, true)) {
    fwrite(STDERR, "Requires --read-only and server configurations on stdin.\n");
    exit(2);
}
ini_set('display_errors', '0');
ini_set('log_errors', '0');
$root = dirname(__DIR__);
require $root.'/vendor/autoload.php';
require $root.'/lib/services/Nntp/Services_Nntp_PipelineDepth.php';
require $root.'/lib/services/Nntp/Services_Nntp_PipelinedTransport.php';
require $root.'/lib/services/Nntp/Services_Nntp_PipelinedRecovery.php';
$servers = json_decode(stream_get_contents(STDIN), true);
if (!is_array($servers) || empty($servers)) {
    exit(2);
}
$failed = false;
foreach ($servers as $server) {
    if (!is_array($server) || empty($server['host']) || $server['enc'] !== 'ssl' || empty($server['verifyname'])) {
        exit(2);
    }
    for ($sample = 1; $sample <= 3; $sample++) {
        $transport = new Services_Nntp_PipelinedTransport($server, 15, 'validation');
        $start = microtime(true);
        $report = ['provider_host' => $server['host'], 'sample' => $sample, 'window' => 32, 'tls' => true, 'certificate_verification' => true];
        try {
            $group = $transport->selectGroup('free.usenet');
            $last = max($group['first'], $group['last'] - 5000 - ($sample * 1000));
            $first = max($group['first'], $last - 999);
            $headers = $transport->getOverview($first, $last);
            $ids = [];
            foreach ($headers as $header) {
                $ids[] = trim($header['Message-ID'], '<>');
            }
            $outcome = (new Services_Nntp_PipelinedRecovery($transport))->fetchArticles($ids, 32);
            $found = 0;
            $missing = 0;
            foreach ($outcome->terminalResults() as $result) {
                if ($result->found()) {
                    $found++;
                } elseif ($result->code === 430) {
                    $missing++;
                }
            }
            $report += ['headers' => count($headers), 'articles_found' => $found, 'missing_430' => $missing, 'terminal' => $outcome->terminalCount(), 'unresolved' => count($outcome->unresolvedMessageIds()), 'retries' => $outcome->retryCount(), 'connections' => $transport->getOpenedConnectionCount(), 'errors' => count($outcome->errors())];
            $report['passed'] = count($ids) >= 32 && $outcome->terminalCount() === count($ids) && !$outcome->hasUnresolved();
        } catch (Throwable $e) {
            $report += ['passed' => false, 'error_type' => get_class($e), 'error_code' => $e->getCode()];
        }
        $failed = $failed || !$report['passed'];
        $transport->quit();
        $report['seconds'] = round(microtime(true) - $start, 3);
        echo json_encode($report), PHP_EOL;
        flush();
    }
}
exit($failed ? 1 : 0);
