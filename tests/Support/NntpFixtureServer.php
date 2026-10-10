<?php

class NntpFixtureServer
{
    public static function start($commandLog, $mode)
    {
        $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if (!is_resource($server)) {
            throw new RuntimeException($errstr);
        }

        $name = stream_socket_get_name($server, false);
        list($host, $port) = explode(':', $name);

        $pid = pcntl_fork();
        if ($pid === 0) {
            self::serve($server, $commandLog, $mode);
            exit(0);
        }

        fclose($server);

        return [
            'host' => $host,
            'port' => (int) $port,
            'pid'  => $pid,
        ];
    }

    private static function serve($server, $commandLog, $mode)
    {
        $connectionNumber = 0;
        while (($conn = stream_socket_accept($server, 10)) !== false) {
            $connectionNumber++;
            if ($mode === 'close-before-greeting') {
                fclose($conn);
                exit(0);
            }
            $keepServing = self::handleConnection($conn, $commandLog, $mode, $connectionNumber);
            if (!$keepServing) {
                exit(0);
            }
        }

        exit(1);
    }

    private static function handleConnection($conn, $commandLog, $mode, $connectionNumber)
    {
        fwrite($conn, "200 fixture ready\r\n");
        $articleCommands = [];
        while (($line = fgets($conn)) !== false) {
            $line = rtrim($line, "\r\n");
            self::logLine($commandLog, $line);

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
            } elseif (strpos($line, 'HEAD ') === 0) {
                if (($mode === 'direct-head-disconnect-once') && ($connectionNumber === 1)) {
                    fclose($conn);

                    return true;
                }
                if ($mode === 'direct-read-430') {
                    fwrite($conn, "430 no such article\r\n");
                } else {
                    self::writeFixtureHeader($conn);
                }
            } elseif (strpos($line, 'BODY ') === 0) {
                if (($mode === 'direct-body-disconnect-once') && ($connectionNumber === 1)) {
                    fclose($conn);

                    return true;
                }
                if ($mode === 'direct-read-430') {
                    fwrite($conn, "430 no such article\r\n");
                } else {
                    self::writeFixtureBody($conn);
                }
            } elseif ($line === 'POST') {
                fwrite($conn, "340 send article\r\n");
                while (($bodyLine = fgets($conn)) !== false) {
                    $bodyLine = rtrim($bodyLine, "\r\n");
                    self::logLine($commandLog, 'POST-DATA '.$bodyLine);
                    if ($bodyLine === '.') {
                        break;
                    }
                }
                fwrite($conn, "240 article posted\r\n");
            } elseif (strpos($line, 'ARTICLE ') === 0) {
                if (($mode === 'direct-article-disconnect-once') && ($connectionNumber === 1)) {
                    fclose($conn);

                    return true;
                }
                if ($mode === 'direct-read-430') {
                    fwrite($conn, "430 no such article\r\n");
                    continue;
                }
                if ($mode === 'disconnect-before-response') {
                    fclose($conn);

                    return false;
                }
                $articleCommands[] = $line;
                if (($mode === 'unexpected-final-response') && (count($articleCommands) === 1)) {
                    fwrite($conn, "500 fixture protocol failure\r\n");
                    fclose($conn);

                    return false;
                }
                if ((($mode === 'single-commands') || ($mode === 'direct-article-disconnect-once')) && (count($articleCommands) === 1)) {
                    self::writeFixtureArticle($conn, 1);
                } elseif (count($articleCommands) === 3) {
                    self::writeFixtureArticle($conn, 1);
                    if ($mode === 'unexpected-response') {
                        fwrite($conn, "423 no such article number\r\n");
                        fclose($conn);

                        return false;
                    }
                    if ($mode === 'disconnect-mid-body') {
                        fwrite($conn, "220 2 <comment.2.1.1.1@example.invalid> article follows\r\n");
                        fwrite($conn, "From: Sender <s@example>\r\n");
                        fwrite($conn, "\r\npartial body");
                        fclose($conn);

                        return false;
                    }
                    fwrite($conn, "430 no such article\r\n");
                    self::writeFixtureArticle($conn, 3);
                }
            } elseif ($line === 'QUIT') {
                fwrite($conn, "205 goodbye\r\n");
                fclose($conn);

                return false;
            }
        }

        return false;
    }

    private static function writeFixtureHeader($conn)
    {
        fwrite($conn, "221 1 <comment.1.1.1.1@example.invalid> headers follow\r\n");
        fwrite($conn, "From: Sender <s@example>\r\n");
        fwrite($conn, "Date: Tue, 18 Aug 2026 10:01:00 +0000\r\n");
        fwrite($conn, ".\r\n");
    }

    private static function writeFixtureBody($conn)
    {
        fwrite($conn, "222 1 <comment.1.1.1.1@example.invalid> body follows\r\n");
        fwrite($conn, "body 1\r\n");
        fwrite($conn, "..dot stuffed\r\n");
        fwrite($conn, ".\r\n");
    }

    private static function writeFixtureArticle($conn, $number)
    {
        $payload = '220 '.$number.' <comment.'.$number.".1.1.1@example.invalid> article follows\r\n".
            "From: Sender <s@example>\r\n".
            'Date: Tue, 18 Aug 2026 10:0'.$number.":00 +0000\r\n".
            "\r\n".
            'body '.$number."\r\n".
            "..dot stuffed\r\n".
            ".\r\n";

        foreach (str_split($payload, 7) as $part) {
            fwrite($conn, $part);
            usleep(1000);
        }
    }

    private static function logLine($commandLog, $line)
    {
        file_put_contents($commandLog, $line.PHP_EOL, FILE_APPEND);
    }
}
