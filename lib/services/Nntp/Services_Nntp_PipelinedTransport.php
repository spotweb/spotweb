<?php

/*
 * Internal Spotweb NNTP pipeline transport.
 *
 * This file contains original Spotweb stream/framing code. It does not copy
 * source from external NNTP packages. It is intentionally protocol-oriented
 * and contains no comments/DAO knowledge.
 */

class Services_Nntp_PipelinedTransport
{
    const DEFAULT_TIMEOUT = 10;
    const DEFAULT_PIPELINE_WINDOW = Services_Nntp_PipelineDepth::DefaultDepth;
    const IDEMPOTENT_RETRY_LIMIT = 3;
    const IDEMPOTENT_RETRY_BACKOFF_USEC = 100000;

    private $_server;
    private $_stream = null;
    private $_currentGroup = '';
    private $_inputBuffer = '';
    private $_writeBuffer = '';
    private $_timeout;
    private $_openedConnections = 0;
    private $_role;

    public function __construct(array $server, $timeout = self::DEFAULT_TIMEOUT, $role = 'direct')
    {
        $this->_server = $server;
        $this->_timeout = $timeout;
        $this->_role = $role;
    }

    public function getOpenedConnectionCount()
    {
        return $this->_openedConnections;
    }

    public function connect()
    {
        if (is_resource($this->_stream)) {
            return;
        }

        if (empty($this->_server['host'])) {
            throw new NntpException('Servername is empty', -1);
        }

        if ((!isset($this->_server['port'])) || (!is_numeric($this->_server['port'])) || ((int) $this->_server['port'] < 1)) {
            throw new NntpException('A server portnumber has to be entered', -1);
        }

        $enc = isset($this->_server['enc']) ? strtolower((string) $this->_server['enc']) : '';
        if (($enc !== '') && ($enc !== '0') && ($enc !== 'false') && ($enc !== 'ssl') && ($enc !== 'tls')) {
            throw new NntpException('Invalid encryption method specified ('.$enc.')', -1);
        }

        $verifyName = true;
        if (array_key_exists('verifyname', $this->_server)) {
            $verifyName = (bool) $this->_server['verifyname'];
        }

        $context = stream_context_create([
            'ssl' => [
                'verify_peer'      => $verifyName,
                'verify_peer_name' => $verifyName,
                'peer_name'        => $this->_server['host'],
            ],
        ]);

        $scheme = ($enc === 'ssl') ? 'ssl' : 'tcp';
        $target = $scheme.'://'.$this->_server['host'].':'.(int) $this->_server['port'];
        $errno = 0;
        $errstr = '';
        $stream = @stream_socket_client($target, $errno, $errstr, $this->_timeout, STREAM_CLIENT_CONNECT, $context);
        if (!is_resource($stream)) {
            throw new NntpException('Error while connecting to server: '.$errstr, $errno);
        }

        stream_set_blocking($stream, false);
        $this->_stream = $stream;
        $this->_inputBuffer = '';
        $this->_writeBuffer = '';
        $this->_openedConnections++;
        $this->log(SpotDebug::DEBUG, 'nntp.connect', ['operation' => 'connect']);

        $this->readStatusLine([200, 201]);

        if ($enc === 'tls') {
            $this->simpleCommandRaw('STARTTLS', [382]);
            $enabled = @stream_socket_enable_crypto($this->_stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if ($enabled !== true) {
                $this->disconnect();
                throw new NntpException('Unable to enable STARTTLS for NNTP connection', -1);
            }
        }

        if (!empty($this->_server['user'])) {
            $authUser = $this->simpleCommandRaw('AUTHINFO USER '.$this->_server['user'], [281, 381]);
            if ($authUser['code'] === 381) {
                $this->simpleCommandRaw('AUTHINFO PASS '.$this->_server['pass'], [281]);
            }
            $this->log(SpotDebug::TRACE, 'nntp.auth', ['operation' => 'auth']);
        }
    }

    public function disconnect()
    {
        if (!is_resource($this->_stream)) {
            return;
        }

        $stream = $this->_stream;
        $this->_stream = null;
        $this->_currentGroup = '';
        $this->_inputBuffer = '';
        $this->_writeBuffer = '';

        @fclose($stream);
        $this->log(SpotDebug::DEBUG, 'nntp.disconnect', ['operation' => 'disconnect']);
    }

    public function quit()
    {
        if (!is_resource($this->_stream)) {
            return;
        }

        try {
            $this->simpleCommandRaw('QUIT', [205]);
        } catch (Exception $x) {
            /* Best effort only during shutdown. */
        }

        $this->disconnect();
    }

    public function selectGroup($group)
    {
        return $this->executeIdempotent('group', function () use ($group) {
            return $this->selectGroupRaw($group);
        });
    }

    public function getOverview($first, $last)
    {
        $started = microtime(true);
        $response = $this->executeIdempotent('xover', function () use ($first, $last) {
            return $this->multiLineCommandRaw('XOVER '.(int) $first.'-'.(int) $last, [224]);
        });
        $overview = [];

        foreach ($response['lines'] as $line) {
            $parsed = $this->parseOverviewLine($line);
            if ($parsed !== null) {
                $overview[] = $parsed;
            }
        }

        $this->log(SpotDebug::TRACE, 'nntp.xover', ['operation' => 'xover', 'first' => (int) $first, 'last' => (int) $last, 'count' => count($overview), 'elapsed_ms' => $this->elapsedMs($started)]);

        return $overview;
    }

    public function getMessageIdByArticleNumber($articleNumber)
    {
        $list = $this->getMessageIdList($articleNumber, $articleNumber);
        if (empty($list)) {
            return '';
        }

        return reset($list);
    }

    public function getMessageIdList($first, $last)
    {
        $started = microtime(true);
        $response = $this->executeIdempotent('xhdr', function () use ($first, $last) {
            return $this->multiLineCommandRaw('XHDR Message-ID '.(int) $first.'-'.(int) $last, [221]);
        });
        $ids = [];

        foreach ($response['lines'] as $line) {
            $parts = preg_split('/\s+/', $line, 2);
            if (count($parts) === 2) {
                $ids[(int) $parts[0]] = $this->stripMessageId($parts[1]);
            }
        }

        $this->log(SpotDebug::TRACE, 'nntp.xhdr', ['operation' => 'xhdr', 'field' => 'Message-ID', 'first' => (int) $first, 'last' => (int) $last, 'count' => count($ids), 'elapsed_ms' => $this->elapsedMs($started)]);

        return $ids;
    }

    public function sendNoop()
    {
        $this->executeIdempotent('noop', function () {
            return $this->simpleCommandRaw('NOOP', [200]);
        });
    }

    public function resetErrorCount()
    {
        /* Central transport recovery is handled by Services_Nntp_PipelinedRecovery. */
    }

    public function getHeader($messageId)
    {
        $started = microtime(true);
        $response = $this->directArticleReadCommand('head', 'HEAD '.$this->formatMessageId($messageId), [221]);
        $this->log(SpotDebug::TRACE, 'nntp.head', ['operation' => 'head', 'status' => $response['code'], 'line_count' => count($response['lines']), 'elapsed_ms' => $this->elapsedMs($started)]);

        return $response['lines'];
    }

    public function getBody($messageId)
    {
        $started = microtime(true);
        $response = $this->directArticleReadCommand('body', 'BODY '.$this->formatMessageId($messageId), [222]);
        $this->log(SpotDebug::TRACE, 'nntp.body', ['operation' => 'body', 'status' => $response['code'], 'line_count' => count($response['lines']), 'elapsed_ms' => $this->elapsedMs($started)]);

        return $response['lines'];
    }

    public function getArticle($messageId)
    {
        $started = microtime(true);
        $response = $this->directArticleReadCommand('article', 'ARTICLE '.$this->formatMessageId($messageId), [220]);
        $article = $this->splitArticleLines($response['lines']);
        $this->log(SpotDebug::TRACE, 'nntp.article', ['operation' => 'article', 'status' => $response['code'], 'header_count' => count($article['header']), 'body_count' => count($article['body']), 'elapsed_ms' => $this->elapsedMs($started)]);

        return $article;
    }

    public function post(array $article)
    {
        if (count($article) !== 2) {
            throw new NntpException('POST expects [headers, body]', -1);
        }

        $started = microtime(true);
        try {
            $this->simpleCommandRaw('POST', [340]);
            $this->writeMultilinePayload($this->buildPostPayload($article[0], $article[1]));
            $response = $this->readStatusLine([240]);
            $this->log(SpotDebug::DEBUG, 'nntp.post', ['operation' => 'post', 'status' => $response['code'], 'elapsed_ms' => $this->elapsedMs($started)]);
        } catch (Exception $x) {
            $this->logFailure('post', $x, 0, $started, false);
            throw $x;
        }

        return true;
    }

    public function validateServer($group = 'free.pt')
    {
        $this->selectGroup($group);
        $this->quit();
    }

    public function fetchArticlesPipelined(array $messageIds, $window = self::DEFAULT_PIPELINE_WINDOW)
    {
        $started = microtime(true);
        $window = max(1, (int) $window);
        $pending = array_values($messageIds);
        $inFlight = [];
        $results = [];
        $currentArticle = null;
        $currentResponseMessageId = null;
        $expected = count($pending);

        try {
            $this->connect();

            while (count($results) < $expected) {
                while ((count($inFlight) < $window) && !empty($pending)) {
                    $messageId = array_shift($pending);
                    $this->_writeBuffer .= 'ARTICLE '.$this->formatMessageId($messageId)."\r\n";
                    $inFlight[] = $messageId;
                }

                $read = [ $this->_stream ];
                $write = ($this->_writeBuffer !== '') ? [ $this->_stream ] : null;
                $except = null;

                $selected = @stream_select($read, $write, $except, $this->_timeout, 0);
                if ($selected === false) {
                    throw new NntpException('NNTP stream_select failed', -1);
                }
                if ($selected === 0) {
                    throw new NntpException('NNTP timed out while waiting for pipelined ARTICLE responses', -1);
                }

                if (!empty($write)) {
                    $this->flushWriteBuffer();
                }

                if (!empty($read)) {
                    $chunk = @fread($this->_stream, 8192);
                    if ($chunk === false) {
                        throw new NntpException('NNTP read failed', -1);
                    }
                    if (($chunk === '') && feof($this->_stream)) {
                        throw new NntpException('NNTP connection closed during pipelined ARTICLE retrieval', -1);
                    }
                    $this->_inputBuffer .= $chunk;

                    while (($line = $this->shiftLine()) !== null) {
                        if ($currentArticle === null) {
                            if (empty($inFlight)) {
                                throw new NntpException('NNTP returned an ARTICLE response without a pending request', -1);
                            }

                            $messageId = array_shift($inFlight);
                            $currentResponseMessageId = $messageId;
                            $code = (int) substr($line, 0, 3);
                            if ($code === 220) {
                                $currentArticle = [
                                    'messageid' => $messageId,
                                    'code'      => $code,
                                    'message'   => substr($line, 4),
                                    'lines'     => [],
                                ];
                                $currentResponseMessageId = null;
                            } elseif ($code === 430) {
                                $results[] = new Services_Nntp_PipelinedArticleResult(
                                    $this->stripMessageId($messageId),
                                    $code,
                                    substr($line, 4)
                                );
                                $currentResponseMessageId = null;
                            } else {
                                throw new NntpException('Unexpected ARTICLE response: '.$line, $code);
                            }
                        } else {
                            if ($line === '.') {
                                $article = $this->splitArticleLines($currentArticle['lines']);
                                $results[] = new Services_Nntp_PipelinedArticleResult(
                                    $this->stripMessageId($currentArticle['messageid']),
                                    $currentArticle['code'],
                                    $currentArticle['message'],
                                    $article['header'],
                                    $article['body']
                                );
                                $currentArticle = null;
                            } else {
                                if (strpos($line, '..') === 0) {
                                    $line = substr($line, 1);
                                }
                                $currentArticle['lines'][] = $line;
                            }
                        }
                    }
                }
            }
        } catch (Exception $x) {
            $unresolved = $this->unresolvedMessageIds($currentArticle, $currentResponseMessageId, $inFlight, $pending);
            $errorClass = Services_Nntp_PipelinedFetchException::classify($x, 'transport');
            $this->logPipelineFailure(
                $x,
                $started,
                $window,
                $expected,
                count($results),
                count($unresolved),
                count($inFlight),
                count($pending),
                $currentArticle !== null,
                $errorClass
            );

            throw new Services_Nntp_PipelinedFetchException(
                $x->getMessage(),
                $x->getCode(),
                $results,
                $unresolved,
                $errorClass
            );
        }

        $this->log(SpotDebug::TRACE, 'nntp.article.pipeline', ['operation' => 'article-pipeline', 'window' => $window, 'requested' => $expected, 'terminal' => count($results), 'elapsed_ms' => $this->elapsedMs($started)]);

        return $results;
    }

    public function withFreshConnection()
    {
        $group = $this->_currentGroup;
        $this->disconnect();
        $this->connect();
        if ($group !== '') {
            $this->selectGroupRaw($group);
        }
    }

    private function simpleCommandRaw($command, array $expectedCodes)
    {
        $this->connect();
        $this->writeLine($command);

        return $this->readStatusLine($expectedCodes);
    }

    private function multiLineCommandRaw($command, array $expectedCodes)
    {
        $response = $this->simpleCommandRaw($command, $expectedCodes);
        $response['lines'] = $this->readMultilineBlock();

        return $response;
    }

    private function directArticleReadCommand($operation, $command, array $expectedCodes)
    {
        $response = $this->executeIdempotent($operation, function () use ($command, $expectedCodes) {
            $response = $this->simpleCommandRaw($command, array_merge($expectedCodes, [430]));
            if ($response['code'] === 430) {
                return [
                    'code' => 430,
                    'message' => $response['message'],
                    'lines' => [],
                ];
            }

            $response['lines'] = $this->readMultilineBlock();

            return $response;
        });

        if ($response['code'] === 430) {
            $x = new NntpException('NNTP article unavailable: '.$response['message'], 430);
            $this->logFailure($operation, $x, 0, microtime(true), true);
            throw $x;
        }

        return $response;
    }

    private function selectGroupRaw($group)
    {
        $response = $this->simpleCommandRaw('GROUP '.$group, [211]);
        $parts = preg_split('/\s+/', trim($response['message']));
        $this->_currentGroup = $group;
        $this->log(SpotDebug::TRACE, 'nntp.group', ['operation' => 'group', 'group' => $group, 'status' => $response['code']]);

        return [
            'count' => isset($parts[0]) ? (int) $parts[0] : 0,
            'first' => isset($parts[1]) ? (int) $parts[1] : 0,
            'last'  => isset($parts[2]) ? (int) $parts[2] : 0,
        ];
    }

    private function executeIdempotent($operation, $callback)
    {
        $started = microtime(true);
        $attempt = 0;
        $groupForRetry = $this->_currentGroup;

        while (true) {
            try {
                $result = $callback();
                if (($attempt > 0) && is_array($result)) {
                    $this->log(SpotDebug::DEBUG, 'nntp.read.recovered', ['operation' => $operation, 'attempt' => $attempt, 'elapsed_ms' => $this->elapsedMs($started)]);
                }

                return $result;
            } catch (Exception $x) {
                $terminal = ((int) $x->getCode() === 430);
                $this->logFailure($operation, $x, $attempt, $started, $terminal);
                if ($terminal) {
                    throw $x;
                }

                if ($attempt >= self::IDEMPOTENT_RETRY_LIMIT) {
                    throw $x;
                }

                $attempt++;
                usleep(self::IDEMPOTENT_RETRY_BACKOFF_USEC * $attempt);

                try {
                    $this->reconnectForRetry($groupForRetry);
                } catch (Exception $reconnectFailure) {
                    $this->logFailure('reconnect', $reconnectFailure, $attempt, $started, false);
                    if ($attempt >= self::IDEMPOTENT_RETRY_LIMIT) {
                        throw $reconnectFailure;
                    }
                }
            }
        }
    }

    private function reconnectForRetry($group)
    {
        $this->disconnect();
        $this->connect();
        if ($group !== '') {
            $this->selectGroupRaw($group);
        }
    }

    private function readStatusLine(array $expectedCodes)
    {
        $line = $this->readLineBlocking();
        $code = (int) substr($line, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            throw new NntpException('Unexpected NNTP response: '.$line, $code);
        }

        return [
            'code'    => $code,
            'message' => strlen($line) > 4 ? substr($line, 4) : '',
        ];
    }

    private function readMultilineBlock()
    {
        $lines = [];
        while (true) {
            $line = $this->readLineBlocking();
            if ($line === '.') {
                return $lines;
            }

            if (strpos($line, '..') === 0) {
                $line = substr($line, 1);
            }
            $lines[] = $line;
        }
    }

    private function writeLine($line)
    {
        $this->_writeBuffer .= $line."\r\n";
        while ($this->_writeBuffer !== '') {
            $write = [ $this->_stream ];
            $read = null;
            $except = null;
            $selected = @stream_select($read, $write, $except, $this->_timeout, 0);
            if ($selected === false) {
                throw new NntpException('NNTP stream_select failed while writing', -1);
            }
            if ($selected === 0) {
                throw new NntpException('NNTP timed out while writing', -1);
            }
            $this->flushWriteBuffer();
        }
    }

    private function writeMultilinePayload($payload)
    {
        $lines = preg_split('/\r\n|\r|\n/', $payload);
        foreach ($lines as $line) {
            if (strpos($line, '.') === 0) {
                $line = '.'.$line;
            }
            $this->writeLine($line);
        }
        $this->writeLine('.');
    }

    private function buildPostPayload($headers, $body)
    {
        return rtrim((string) $headers, "\r\n")."\r\n\r\n".(string) $body;
    }

    private function flushWriteBuffer()
    {
        if ($this->_writeBuffer === '') {
            return;
        }

        $written = @fwrite($this->_stream, $this->_writeBuffer);
        if ($written === false) {
            throw new NntpException('NNTP write failed', -1);
        }
        if ($written > 0) {
            $this->_writeBuffer = substr($this->_writeBuffer, $written);
        }
    }

    private function readLineBlocking()
    {
        $this->connect();

        while (($line = $this->shiftLine()) === null) {
            $read = [ $this->_stream ];
            $write = null;
            $except = null;
            $selected = @stream_select($read, $write, $except, $this->_timeout, 0);
            if ($selected === false) {
                throw new NntpException('NNTP stream_select failed while reading', -1);
            }
            if ($selected === 0) {
                throw new NntpException('NNTP timed out while reading', -1);
            }

            $chunk = @fread($this->_stream, 8192);
            if ($chunk === false) {
                throw new NntpException('NNTP read failed', -1);
            }
            if (($chunk === '') && feof($this->_stream)) {
                throw new NntpException('NNTP connection closed unexpectedly', -1);
            }

            $this->_inputBuffer .= $chunk;
        }

        return $line;
    }

    private function shiftLine()
    {
        $pos = strpos($this->_inputBuffer, "\n");
        if ($pos === false) {
            return null;
        }

        $line = substr($this->_inputBuffer, 0, $pos + 1);
        $this->_inputBuffer = substr($this->_inputBuffer, $pos + 1);

        return rtrim($line, "\r\n");
    }

    private function parseOverviewLine($line)
    {
        $parts = explode("\t", $line);
        if (count($parts) < 5) {
            return null;
        }

        return [
            'Number'     => (int) $parts[0],
            'Subject'    => isset($parts[1]) ? $parts[1] : '',
            'From'       => isset($parts[2]) ? $parts[2] : '',
            'Date'       => isset($parts[3]) ? $parts[3] : '',
            'Message-ID' => $this->stripMessageId($parts[4]),
            'References' => isset($parts[5]) ? $this->stripMessageId($parts[5]) : '',
            'Bytes'      => isset($parts[6]) ? $parts[6] : '',
            'Lines'      => isset($parts[7]) ? $parts[7] : '',
        ];
    }

    private function splitArticleLines(array $lines)
    {
        $header = [];
        $body = [];
        $inHeader = true;

        foreach ($lines as $line) {
            if ($inHeader && ($line === '')) {
                $inHeader = false;
                continue;
            }

            if ($inHeader) {
                $header[] = $line;
            } else {
                $body[] = $line;
            }
        }

        return [
            'header' => $header,
            'body'   => $body,
        ];
    }

    private function unresolvedMessageIds($currentArticle, $currentResponseMessageId, array $inFlight, array $pending)
    {
        $unresolved = [];
        if ($currentArticle !== null) {
            $unresolved[] = $this->stripMessageId($currentArticle['messageid']);
        }
        if ($currentResponseMessageId !== null) {
            $unresolved[] = $this->stripMessageId($currentResponseMessageId);
        }

        foreach ($inFlight as $messageId) {
            $unresolved[] = $this->stripMessageId($messageId);
        }

        foreach ($pending as $messageId) {
            $unresolved[] = $this->stripMessageId($messageId);
        }

        return $unresolved;
    }

    private function formatMessageId($messageId)
    {
        $messageId = trim((string) $messageId);
        if ($messageId === '') {
            return '<>';
        }

        if ($messageId[0] !== '<') {
            $messageId = '<'.$messageId;
        }
        if (substr($messageId, -1) !== '>') {
            $messageId .= '>';
        }

        return $messageId;
    }

    private function stripMessageId($messageId)
    {
        $messageId = trim((string) $messageId);
        if ((strlen($messageId) >= 2) && ($messageId[0] === '<') && (substr($messageId, -1) === '>')) {
            return substr($messageId, 1, -1);
        }

        return $messageId;
    }

    private function log($level, $message, array $context = [])
    {
        $context = $this->sanitizeLogContext(array_merge([
            'role' => $this->_role,
            'group' => $this->_currentGroup,
        ], $context));
        SpotDebug::msg($level, $message, $context);
    }

    private function logFailure($operation, Exception $exception, $attempt, $started, $terminal)
    {
        $this->log($terminal ? SpotDebug::DEBUG : SpotDebug::WARN, 'nntp.read.failure', [
            'operation' => $operation,
            'attempt' => (int) $attempt,
            'terminal' => (bool) $terminal,
            'error_class' => Services_Nntp_PipelinedFetchException::classify($exception, 'transport'),
            'code' => (int) $exception->getCode(),
            'elapsed_ms' => $this->elapsedMs($started),
        ]);
    }

    private function logPipelineFailure(Exception $exception, $started, $window, $requested, $terminalCount, $unresolvedCount, $inFlightCount, $pendingCount, $activeArticle, $errorClass)
    {
        $this->log(SpotDebug::WARN, 'nntp.article.pipeline.failure', [
            'operation' => 'article-pipeline',
            'window' => (int) $window,
            'requested' => (int) $requested,
            'terminal_count' => (int) $terminalCount,
            'unresolved_count' => (int) $unresolvedCount,
            'inflight_count' => (int) $inFlightCount,
            'pending_count' => (int) $pendingCount,
            'active_article' => (bool) $activeArticle,
            'error_class' => $errorClass,
            'code' => (int) $exception->getCode(),
            'elapsed_ms' => $this->elapsedMs($started),
        ]);
    }

    private function sanitizeLogContext(array $context)
    {
        foreach (['user', 'username', 'pass', 'password', 'auth', 'command', 'payload', 'body', 'headers', 'article', 'content', 'nzb'] as $key) {
            unset($context[$key]);
        }

        return $context;
    }

    private function elapsedMs($started)
    {
        return (int) round((microtime(true) - $started) * 1000);
    }
}
