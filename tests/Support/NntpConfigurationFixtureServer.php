<?php

/* Local-only TLS/authentication fixture; never connects to an NNTP provider. */
class NntpConfigurationFixtureServer
{
    public static function start($mode, $certificate, $auth)
    {
        $context = stream_context_create(['ssl' => ['local_cert' => $certificate]]);
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);
        if (!$socket) {
            throw new RuntimeException('Cannot start local configuration fixture');
        }
        $address = stream_socket_get_name($socket, false);
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new RuntimeException('Cannot fork configuration fixture');
        }
        if ($pid === 0) {
            $connection = stream_socket_accept($socket, 5);
            if (!$connection) {
                exit(1);
            }
            stream_set_timeout($connection, 3);
            if ($mode === 'ssl' && @stream_socket_enable_crypto($connection, true, STREAM_CRYPTO_METHOD_TLS_SERVER) !== true) {
                exit(0);
            }
            fwrite($connection, "200 local fixture ready\r\n");
            while (($line = fgets($connection)) !== false) {
                $line = rtrim($line, "\r\n");
                if ($line === 'STARTTLS') {
                    fwrite($connection, "382 begin TLS\r\n");
                    if ($mode === 'tls-stall') {
                        usleep(2000000);
                        break;
                    }
                    if (@stream_socket_enable_crypto($connection, true, STREAM_CRYPTO_METHOD_TLS_SERVER) !== true) {
                        break;
                    }
                } elseif ($line === 'AUTHINFO USER fixture-user') {
                    fwrite($connection, $auth === 'user-only' ? "281 authenticated\r\n" : "381 password required\r\n");
                } elseif (strpos($line, 'AUTHINFO PASS ') === 0) {
                    fwrite($connection, $line === 'AUTHINFO PASS fixture-password' ? "281 authenticated\r\n" : "481 authentication rejected\r\n");
                } elseif ($line === 'NOOP') {
                    fwrite($connection, "200 okay\r\n");
                } elseif ($line === 'QUIT') {
                    fwrite($connection, "205 goodbye\r\n");
                    break;
                } else {
                    fwrite($connection, "500 unexpected fixture command\r\n");
                }
            }
            fclose($connection);
            exit(0);
        }
        fclose($socket);

        return ['host' => 'localhost', 'port' => (int) substr(strrchr($address, ':'), 1), 'pid' => $pid];
    }
}
