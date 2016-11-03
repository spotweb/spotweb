<?php

/**
 * Default host
 */
const NET_NNTP_PROTOCOL_CLIENT_DEFAULT_HOST = 'localhost';

/**
 * Default port
 */
const NET_NNTP_PROTOCOL_CLIENT_DEFAULT_PORT = '119';

/**
 * Class Net_NNTP_Protocol_Client
 */
class Net_NNTP_Protocol_Client
{
    /**
     * The socket resource being used to connect to the NNTP server.
     *
     * @var resource
     */
    private $socket = null;

    /**
     * Contains the last received status response code and text.
     *
     * @var array
     */
    private $currentStatusResponse = null;

    /**
     * Contains false on non-ssl connection and true when ssl.
     *
     * @var object
     */
    private $ssl = false;

    /**
     * Clears ssl errors from the openssl error stack.
     */
    private function clearSSLErrors()
    {
        if ($this->ssl) {
            while ($msg = openssl_error_string()) {
            };
        }
    }

    /**
     * Send a command to the server. A carriage return / linefeed (CRLF) sequence
     * will be appended to each command string before it is sent to the IMAP server.
     *
     * @param string $cmd The command to launch, ie: "ARTICLE 1004853".
     *
     * @return int Response code on success.
     * @throws NNTPException
     */
    private function sendCommand($cmd)
    {
        // NNTP/RFC977 only allows command up to 512 (-2) chars.
        if (!strlen($cmd) > 510) {
            throw new NNTPException(
                'Failed writing to socket! (Command to long - max 510 chars)'
            );
        }

        /**
         * Prevent new line (and possible future) characters in the NNTP commands
         * Net_NNTP does not support pipelined commands. Inserting a new line charecter
         * allows sending multiple commands and thereby making the communication between
         * NET_NNTP and the server out of sync...
         */
        if (preg_match_all('/\r?\n/', $cmd, $matches, PREG_PATTERN_ORDER)) {
            throw new NNTPException('Illegal character(s) in NNTP command!');
        }

        // Check if connected.
        if (!$this->isConnected()) {
            throw new NNTPException('Failed to write to socket! (connection lost!)');
        }

        // Send the command.
        $this->clearSSLErrors();
        $bytes = @fwrite($this->socket, $cmd . "\r\n");
        if ($bytes === false) {
            throw new NNTPException('Failed to write to socket!');
        }

        return $this->getStatusResponse();
    }

    /**
     * Get servers status response after a command.
     *
     * @return int status code on success.
     * @throws NNTPException
     */
    private function getStatusResponse()
    {
        // Retrieve a line (terminated by "\r\n") from the server.
        $this->clearSSLErrors();
        $response = @fgets($this->socket);
        if ($response === false) {
            throw new NNTPException('Failed to read from socket...!');
        }

        $streamStatus = stream_get_meta_data($this->socket);
        if ($streamStatus['timed_out']) {
            throw new NNTPException('Connection timed out');
        }

        // Trim the start of the response in case of misplaced whitespace (should not be needed).
        $response = ltrim($response);

        $this->currentStatusResponse = array(
            (int)substr($response, 0, 3),
            (string)rtrim(substr($response, 4))
        );

        return $this->currentStatusResponse[0];
    }

    /**
     * Retrieve textural data.
     * Get data until a line with only a '.' in it is read and return data.
     *
     * @return array Text response on success.
     * @throws NNTPException
     */
    private function getTextResponse()
    {
        $data = array();
        $line = '';

        // Continue until connection is lost
        while (!feof($this->socket)) {
            // Retrieve and append up to 8192 characters from the server.
            $this->clearSSLErrors();
            $received = @fgets($this->socket, 8192);

            if ($received === false) {
                throw new NNTPException('Failed to read line from socket.');
            }

            $streamStatus = stream_get_meta_data($this->socket);
            if ($streamStatus['timed_out']) {
                throw new NNTPException('Connection timed out');
            }

            $line .= $received;

            // Continue if the line is not terminated by CRLF.
            if (substr($line, -2) != "\r\n" || strlen($line) < 2) {
                usleep(25000);
                continue;
            }

            // Validate received line.
            if (false) {
                // Lines should/may not be longer than 998+2 chars (RFC2822 2.3).
                if (strlen($line) > 1000) {
                    throw new NNTPException('Invalid line received!');
                }
            }

            // Remove CRLF from the end of the line.
            $line = substr($line, 0, -2);

            // Check if the line terminates the text response.
            if ($line == '.') {
                // return all previous lines
                return $data;
            }

            // If 1st char is '.' it's doubled (NNTP/RFC977 2.4.1).
            if (substr($line, 0, 2) == '..') {
                $line = substr($line, 1);
            }

            // Add the line to the array of lines.
            $data[] = $line;

            // Reset/empty $line.
            $line = '';
        }

        throw new NNTPException('End of stream! Connection lost?');
    }

    /**
     * Retrieve blob.
     * Get data and assume we do not hit any blind spots.
     *
     * @return array Text response on success.
     */
    private function getCompressedResponse()
    {
        /**
         * We can have two kinds of compressed support:
         * - yEnc encoding
         * - Just a gzip drop
         * We try to autodetect which one this uses.
         */
        $line = @fread($this->socket, 1024);

        if (substr($line, 0, 7) == '=ybegin') {
            $data = $this->getTextResponse();
            $data = $line . "\r\n" . implode("", $data);
            $data = $this->yencDecode($data);
            $data = explode("\r\n", gzinflate($data));

            return $data;
        }

        // We cannot use blocked I/O on this one.
        $streamMetadata = stream_get_meta_data($this->socket);
        stream_set_blocking($this->socket, false);

        // Continue until connection is lost or we don't receive any data anymore.
        $tries        = 0;
        $uncompressed = '';

        while (!feof($this->socket)) {
            // Retrieve and append up to 32k characters from the server.
            $received = @fread($this->socket, 32768);
            if (strlen($received) == 0) {
                $tries++;

                // Try decompression.
                $uncompressed = @gzuncompress($line);
                if (($uncompressed !== false) || ($tries > 500)) {
                    break;
                }

                if ($tries % 50 == 0) {
                    usleep(50000);
                }
            }

            // A error occurred.
            if ($received === false) {
                @fclose($this->socket);
                $this->socket = false;
            }

            $line .= $received;
        }

        // Set the stream to its original blocked(?) value.
        stream_set_blocking($this->socket, $streamMetadata['blocked']);
        $data      = explode("\r\n", $uncompressed);
        $dataCount = count($data);

        // Gzipped compress includes the "." and linefeed in the compressed stream.
        // skip those.
        if ($dataCount >= 2) {
            if (($data[($dataCount - 2)] == ".") && (empty($data[($dataCount - 1)]))) {
                array_pop($data);
                array_pop($data);
            }

            $data = array_filter($data);
        }

        return $data;
    }

    /**
     * @param string|array $article
     *
     * @return bool
     * @throws NNTPException
     */
    private function sendArticle($article)
    {
        // data should be in the format specified by RFC850.
        if (is_string($article)) {
            $this->clearSSLErrors();
            @fwrite($this->socket, $article);
            $this->clearSSLErrors();
            @fwrite($this->socket, "\r\n.\r\n");
        } elseif (is_array($article)) {
            $header = reset($article);
            $body   = next($article);

            // Send header (including separation line).
            $this->clearSSLErrors();
            @fwrite($this->socket, $header);
            $this->clearSSLErrors();
            @fwrite($this->socket, "\r\n");

            // Send body.
            $this->clearSSLErrors();
            @fwrite($this->socket, $body);
            $this->clearSSLErrors();
            @fwrite($this->socket, "\r\n.\r\n");
        } else {
            throw new NNTPException('Wrong type, $article is not a string or array.');
        }

        return true;
    }

    /**
     * @return string status text.
     */
    private function currentStatusResponse()
    {
        return $this->currentStatusResponse[1];
    }

    /**
     * Connect to a NNTP server.
     *
     * @param string $host The address of the NNTP-server to connect to, defaults to 'localhost'.
     * @param mixed  $encryption
     * @param int    $port The port number to connect to, defaults to 119.
     * @param int    $timeout
     *
     * @return bool on success (true when posting allowed, otherwise false).
     * @throws NNTPException
     */
    public function connect($host = null, $encryption = null, $port = null, $timeout = null)
    {
        if ($this->isConnected()) {
            throw new NNTPException('Already connected, disconnect first!');
        }

        // v1.0.x API
        if (is_int($encryption)) {
            throw new NNTPException('You are using deprecated API v1.0 in Net_NNTP_Protocol_Client: connect() !');
        }

        if (is_null($host)) {
            $host = 'localhost';
        }

        // Choose transport based on encryption, and if no port is given, use default for that encryption.
        switch ($encryption) {
            case null:
            case false:
                $transport = 'tcp';
                $port      = is_null($port) ? 119 : $port;
                break;
            case 'ssl':
            case 'tls':
                $transport = $encryption;
                $port      = is_null($port) ? 563 : $port;
                $this->ssl = true;
                break;
            default:
                throw new NNTPException('$encryption parameter must be either tcp, tls or ssl.');
        }

        if (is_null($timeout)) {
            $timeout = 15;
        }

        // Open Connection
        $socket = stream_socket_client($transport . '://' . $host . ':' . $port, $errno, $errstr, $timeout);
        if ($socket === false) {
            return false;
        }

        $this->socket = $socket;

        // set a stream timeout for each operation
        stream_set_timeout($this->socket, 240);

        // Retrieve the server's initial response.
        $response = $this->getStatusResponse();

        if (!in_array($response, array(
            NET_NNTP_PROTOCOL_RESPONSECODE_READY_POSTING_ALLOWED,
            NET_NNTP_PROTOCOL_RESPONSECODE_READY_POSTING_PROHIBITED
        ), true)
        ) {
            throw new NNTPException('', $response);
        }

        return true;
    }

    /**
     * Disconnect from server.
     * Public alias for cmdQuit().
     *
     * @return bool
     */
    public function disconnect()
    {
        return $this->cmdQuit();
    }

    /**
     * Returns servers capabilities.
     *
     * @return array List of capabilities on success.
     * @throws NNTPException
     */
    protected function cmdCapabilities()
    {
        // tell the news server we want an article.
        $response = $this->sendCommand('CAPABILITIES');

        if ($response !== NET_NNTP_PROTOCOL_RESPONSECODE_CAPABILITIES_FOLLOW) {
            throw new NNTPException('', $response);
        }

        return $this->getTextResponse();
    }

    /**
     * @return bool True when posting allowed or false when posting is disallowed.
     * @throws NNTPException
     */
    protected function cmdModeReader()
    {
        // tell the newsserver we want an article
        $response = $this->sendCommand('MODE READER');

        if ($response === NET_NNTP_PROTOCOL_RESPONSECODE_READY_POSTING_ALLOWED) {
            return true;
        } elseif ($response === NET_NNTP_PROTOCOL_RESPONSECODE_READY_POSTING_PROHIBITED) {
            return false;
        } else {
            throw new NNTPException('', $response);
        }
    }

    /**
     * Disconnect from the NNTP server.
     *
     * @return bool
     * @throws NNTPException
     */
    protected function cmdQuit()
    {
        // Tell the server to close the connection.
        $response = $this->sendCommand('QUIT');

        if ($response !== NET_NNTP_PROTOCOL_RESPONSECODE_DISCONNECTING_REQUESTED) {
            throw new NNTPException('', $response);
        }

        // If socket is still open, close it.
        if ($this->isConnected()) {
            fclose($this->socket);
        }

        return true;
    }

    /**
     * @return bool
     * @throws NNTPException
     */
    protected function cmdStartTLS()
    {
        $response = $this->sendCommand('STARTTLS');

        if ($response === 382) {
            $encrypted = stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if ($encrypted === true) {
                return true;
            } else {
                throw new NNTPException('', $response);
            }
        } else {
            throw new NNTPException('', $response);
        }
    }

    /**
     * Selects a news group (issue a GROUP command to the server).
     *
     * @param string $newsgroup The newsgroup name.
     *
     * @return array groupinfo on success.
     * @throws NNTPException
     */
    protected function cmdGroup($newsgroup)
    {
        $response = $this->sendCommand('GROUP ' . $newsgroup);

        if ($response === NET_NNTP_PROTOCOL_RESPONSECODE_GROUP_SELECTED) {
            $response_arr = explode(' ', trim($this->currentStatusResponse()));

            return array(
                'group' => $response_arr[3],
                'first' => $response_arr[1],
                'last'  => $response_arr[2],
                'count' => $response_arr[0]
            );
        } else {
            throw new NNTPException('', $response);
        }
    }

    /**
     * @param string $newsgroup
     * @param mixed  $range
     *
     * @return array
     * @throws NNTPException
     */
    protected function cmdListgroup($newsgroup = null, $range = null)
    {
        if (is_null($newsgroup)) {
            $command = 'LISTGROUP';
        } else {
            if (is_null($range)) {
                $command = 'LISTGROUP ' . $newsgroup;
            } else {
                $command = 'LISTGROUP ' . $newsgroup . ' ' . $range;
            }
        }

        $response = $this->sendCommand($command);

        if ($response !== NET_NNTP_PROTOCOL_RESPONSECODE_GROUP_SELECTED) {
            throw new NNTPException('', $response);
        }

        $articles     = $this->getTextResponse();
        $response_arr = explode(' ', trim($this->currentStatusResponse()), 4);

        // If server does not return group summary in status response, return null'ed array.
        if (!is_numeric($response_arr[0])
            || !is_numeric($response_arr[1])
            || !is_numeric($response_arr[2])
            || empty($response_arr[3])
        ) {
            return array(
                'group'    => null,
                'first'    => null,
                'last'     => null,
                'count'    => null,
                'articles' => $articles
            );
        }

        return array(
            'group'    => $response_arr[3],
            'first'    => $response_arr[1],
            'last'     => $response_arr[2],
            'count'    => $response_arr[0],
            'articles' => $articles
        );
    }

    /**
     * @return mixed or (string) or (int).
     * @throws NNTPException
     */
    protected function cmdLast()
    {
        $response = $this->sendCommand('LAST');
        /**
         * 223, RFC977: 'n a article retrieved - request text separately
         * (n = article number, a = unique article id)'.
         */
        if ($response !== NET_NNTP_PROTOCOL_RESPONSECODE_ARTICLE_SELECTED) {
            throw new NNTPException('', $response);
        }

        $response_arr = explode(' ', trim($this->currentStatusResponse()));

        return array($response_arr[0], (string)$response_arr[1]);
    }

    /**
     * @return array
     * @throws NNTPException
     */
    protected function cmdNext()
    {
        $response = $this->sendCommand('NEXT');

        if ($response !== NET_NNTP_PROTOCOL_RESPONSECODE_ARTICLE_SELECTED) {
            throw new NNTPException('', $response);
        }

        $response_arr = explode(' ', trim($this->currentStatusResponse()));

        return array($response_arr[0], (string)$response_arr[1]);
    }

    /**
     * Get an article from the currently open connection.
     *
     * @param mixed $article Either a message-id or a message-number of the article to fetch.
     *                       If null or '', then use current article.
     *
     * @return array Article on success.
     * @throws NNTPException
     */
    protected function cmdArticle($article = null)
    {
        if (is_null($article)) {
            $command = 'ARTICLE';
        } else {
            $command = 'ARTICLE ' . $article;
        }

        // tell the newsserver we want an article
        $response = $this->sendCommand($command);

        if ($response !== NET_NNTP_PROTOCOL_RESPONSECODE_ARTICLE_FOLLOWS) {
            throw new NNTPException('', $response);
        }

        return $this->getTextResponse();
    }

    /**
     * Get the headers of an article from the currently open connection.
     *
     * @param mixed $article Either a message-id or a message-number of the article to fetch the headers from. If null
     *                       or '', then use current article.
     *
     * @return array Headers on success.
     * @throws NNTPException
     */
    protected function cmdHead($article = null)
    {
        if (is_null($article)) {
            $command = 'HEAD';
        } else {
            $command = 'HEAD ' . $article;
        }

        // tell the newsserver we want the header of an article.
        $response = $this->sendCommand($command);

        if ($response !== NET_NNTP_PROTOCOL_RESPONSECODE_HEAD_FOLLOWS) {
            throw new NNTPException('', $response);
        }

        return $this->getTextResponse();
    }

    /**
     * Get the body of an article from the currently open connection.
     *
     * @param mixed $article Either a message-id or a message-number of the article to fetch the body from. If null or
     *                       '', then use current article.
     *
     * @return array Body on success.
     * @throws NNTPException
     */
    protected function cmdBody($article = null)
    {
        if (is_null($article)) {
            $command = 'BODY';
        } else {
            $command = 'BODY ' . $article;
        }

        // tell the newsserver we want the body of an article.
        $response = $this->sendCommand($command);

        if ($response !== NET_NNTP_PROTOCOL_RESPONSECODE_BODY_FOLLOWS) {
            throw new NNTPException('', $response);
        }

        return $this->getTextResponse();
    }

    /**
     * @param mixed $article
     *
     * @return mixed or (string) or (int).
     * @throws NNTPException
     */
    protected function cmdStat($article = null)
    {
        if (is_null($article)) {
            $command = 'STAT';
        } else {
            $command = 'STAT ' . $article;
        }

        // tell the newsserver we want an article.
        $response = $this->sendCommand($command);

        if ($response !== NET_NNTP_PROTOCOL_RESPONSECODE_ARTICLE_SELECTED) {
            throw new NNTPException('', $response);
        }

        $response_arr = explode(' ', trim($this->currentStatusResponse()));

        return array($response_arr[0], (string)$response_arr[1]);
    }

    /**
     * Post an article to a newsgroup.
     *
     * @return mixed true on success.
     * @throws NNTPException
     */
    protected function cmdPost()
    {
        // tell the newsserver we want to post an article.
        $response = $this->sendCommand('POST');

        if ($response !== NET_NNTP_PROTOCOL_RESPONSECODE_POSTING_SEND) {
            throw new NNTPException('', $response);
        }
    }

    /**
     * Post an article to a newsgroup.
     *
     * @param string|array $article
     *
     * @return bool
     * @throws NNTPException
     */
    protected function cmdPost2($article)
    {
        // should be presented in the format specified by RFC850.
        $this->sendArticle($article);

        // Retrieve server's response.
        $response = $this->getStatusResponse();

        if ($response !== NET_NNTP_PROTOCOL_RESPONSECODE_POSTING_SUCCESS) {
            throw new NNTPException('', $response);
        }

        return true;
    }

    /**
     * @param string $id
     *
     * @return bool
     * @throws NNTPException
     */
    protected function cmdIhave($id)
    {
        // tell the newsserver we want to post an article
        $response = $this->sendCommand('IHAVE ' . $id);

        if ($response !== NET_NNTP_PROTOCOL_RESPONSECODE_TRANSFER_SEND) {
            throw new NNTPException('', $response);
        }

        return true;
    }

    /**
     * @param string|array $article
     *
     * @return bool
     * @throws NNTPException
     */
    protected function cmdIhave2($article)
    {
        // should be presented in the format specified by RFC850.
        $this->sendArticle($article);

        // Retrieve server's response.
        $response = $this->getStatusResponse();

        if ($response !== NET_NNTP_PROTOCOL_RESPONSECODE_TRANSFER_SUCCESS) {
            throw new NNTPException('', $response);
        }

        return true;
    }

    /**
     * Get the date from the news server format of returned date.
     *
     * @return mixed 'YYYYMMDDhhmmss' / (int) timestamp.
     * @throws NNTPException
     */
    protected function cmdDate()
    {
        $response = $this->sendCommand('DATE');

        if ($response !== NET_NNTP_PROTOCOL_RESPONSECODE_SERVER_DATE) {
            throw new NNTPException('', $response);
        }

        return $this->currentStatusResponse();
    }

    /**
     * Returns the server's help text.
     *
     * @return array help text on success.
     * @throws NNTPException
     */
    protected function cmdHelp()
    {
        // tell the newsserver we want an article
        $response = $this->sendCommand('HELP');

        if ($response !== NET_NNTP_PROTOCOL_RESPONSECODE_HELP_FOLLOWS) {
            throw new NNTPException('', $response);
        }

        return $this->getTextResponse();
    }

    /**
     * Fetches a list of all newsgroups created since a specified date.
     *
     * @param int    $time          Last time you checked for groups (timestamp).
     * @param string $distributions Deprecated in rfc draft.
     *
     * @return array Nested array with information about existing newsgroups.
     * @throws NNTPException
     */
    protected function cmdNewgroups($time, $distributions = null)
    {
        $date = gmdate('ymd His', $time);

        if (is_null($distributions)) {
            $command = 'NEWGROUPS ' . $date . ' GMT';
        } else {
            $command = 'NEWGROUPS ' . $date . ' GMT <' . $distributions . '>';
        }

        $response = $this->sendCommand($command);

        if ($response !== NET_NNTP_PROTOCOL_RESPONSECODE_NEW_GROUPS_FOLLOW) {
            throw new NNTPException('', $response);
        }

        $data = $this->getTextResponse();

        $groups = array();
        foreach ($data as $line) {
            $arr = explode(' ', trim($line));

            $group = array(
                'group'   => $arr[0],
                'last'    => $arr[1],
                'first'   => $arr[2],
                'posting' => $arr[3]
            );

            $groups[$group['group']] = $group;
        }

        return $groups;
    }

    /**
     * @param int             $time
     * @param string|string[] $newsgroups
     * @param string|string[] $distribution
     *
     * @return array
     * @throws NNTPException
     */
    protected function cmdNewnews($time, $newsgroups, $distribution = null)
    {
        $date = gmdate('ymd His', $time);

        if (is_array($newsgroups)) {
            $newsgroups = implode(',', $newsgroups);
        }

        if (is_null($distribution)) {
            $command = 'NEWNEWS ' . $newsgroups . ' ' . $date . ' GMT';
        } else {
            if (is_array($distribution)) {
                $distribution = implode(',', $distribution);
            }

            $command = 'NEWNEWS ' . $newsgroups . ' ' . $date . ' GMT <' . $distribution . '>';
        }

        $response = $this->sendCommand($command);

        if ($response !== NET_NNTP_PROTOCOL_RESPONSECODE_NEW_ARTICLES_FOLLOW) {
            throw new NNTPException('', $response);
        }

        $messages = array();
        foreach ($this->getTextResponse() as $line) {
            $messages[] = $line;
        }

        return $messages;
    }

    /**
     * Fetches a list of all avaible newsgroups.
     *
     * @return array Nested array with information about existing newsgroups.
     * @throws NNTPException
     */
    protected function cmdList()
    {
        $response = $this->sendCommand('LIST');

        if ($response !== NET_NNTP_PROTOCOL_RESPONSECODE_GROUPS_FOLLOW) {
            throw new NNTPException('', $response);
        }

        $data   = $this->getTextResponse();
        $groups = array();
        foreach ($data as $line) {
            $arr = explode(' ', trim($line));

            $group = array(
                'group'   => $arr[0],
                'last'    => $arr[1],
                'first'   => $arr[2],
                'posting' => $arr[3]
            );

            $groups[$group['group']] = $group;
        }

        return $groups;
    }

    /**
     * Fetches a list of all avaible newsgroups.
     *
     * @param string $wildmat
     *
     * @return array Nested array with information about existing newsgroups.
     * @throws NNTPException
     */
    protected function cmdListActive($wildmat = null)
    {
        if (is_null($wildmat)) {
            $command = 'LIST ACTIVE';
        } else {
            $command = 'LIST ACTIVE ' . $wildmat;
        }

        $response = $this->sendCommand($command);

        if ($response !== NET_NNTP_PROTOCOL_RESPONSECODE_GROUPS_FOLLOW) {
            throw new NNTPException('', $response);
        }

        $data = $this->getTextResponse();

        $groups = array();
        foreach ($data as $line) {
            $arr = explode(' ', trim($line));

            $group = array(
                'group'   => $arr[0],
                'last'    => $arr[1],
                'first'   => $arr[2],
                'posting' => $arr[3]
            );

            $groups[$group['group']] = $group;
        }

        return $groups;
    }

    /**
     * Fetches a list of (all) avaible newsgroup descriptions.
     *
     * @param string $wildmat Wildmat of the groups, that is to be listed, defaults to null.
     *
     * @return array nested array with description of existing newsgroups.
     * @throws NNTPException
     */
    protected function cmdListNewsgroups($wildmat = null)
    {
        if (is_null($wildmat)) {
            $command = 'LIST NEWSGROUPS';
        } else {
            $command = 'LIST NEWSGROUPS ' . $wildmat;
        }

        $response = $this->sendCommand($command);

        if ($response !== NET_NNTP_PROTOCOL_RESPONSECODE_GROUPS_FOLLOW) {
            throw new NNTPException('', $response);
        }

        $data = $this->getTextResponse();

        $groups = array();

        foreach ($data as $line) {
            if (preg_match("/^(\S+)\s+(.*)$/", ltrim($line), $matches)) {
                $groups[$matches[1]] = (string)$matches[2];
            }
        }

        return $groups;
    }

    /**
     * Fetch message header from message number $first until $last
     * The format of the returned array is:
     * $messages[][header_name].
     *
     * @param string $range Articles to fetch.
     *
     * @return array Nested array of message and their headers.
     * @throws NNTPException
     */
    protected function cmdOver($range = null)
    {
        if (is_null($range)) {
            $command = 'OVER';
        } else {
            $command = 'OVER ' . $range;
        }

        $response = $this->sendCommand($command);

        if ($response !== NET_NNTP_PROTOCOL_RESPONSECODE_OVERVIEW_FOLLOWS) {
            throw new NNTPException('', $response);
        }

        $data = $this->getTextResponse();

        foreach ($data as $key => $value) {
            $data[$key] = explode("\t", trim($value));
        }

        return $data;
    }

    /**
     * Fetch message header from message number $first until $last
     * The format of the returned array is:
     * $messages[message_id][header_name].
     *
     * @param string $range Articles to fetch.
     *
     * @return array Nested array of message and their headers.
     * @throws NNTPException
     */
    protected function cmdXOver($range = null)
    {
        // deprecated API (the code _is_ still in alpha state)
        if (func_num_args() > 1) {
            throw new NNTPException('The second parameter in cmdXOver() has been deprecated! Use x-y instead...');
        }

        if (is_null($range)) {
            $command = 'XOVER';
        } else {
            $command = 'XOVER ' . $range;
        }

        $response = $this->sendCommand($command);

        if ($response !== NET_NNTP_PROTOCOL_RESPONSECODE_OVERVIEW_FOLLOWS) {
            throw new NNTPException('', $response);
        }

        $data = $this->getTextResponse();

        foreach ($data as $key => $value) {
            $data[$key] = explode("\t", trim($value));
        }

        return $data;
    }

    /*
     * Based on code from http://wonko.com/software/yenc/, but
     * simplified because XZVER and the likes don't implement
     * yenc properly.
     */
    private function yencDecode($string, $destination = "")
    {
        $encoded = array();
        $header  = array();
        $decoded = '';

        // Extract the yEnc string itself.
        preg_match("/^(=ybegin.*=yend[^$]*)$/ims", $string, $encoded);
        $encoded = $encoded[1];

        // Extract the filesize and filename from the yEnc header.
        preg_match("/^=ybegin.*size=([^ $]+).*name=([^\\r\\n]+)/im", $encoded, $header);
        $filesize = $header[1];

        // Remove the header and footer from the string before parsing it.
        $encoded = preg_replace("/(^=ybegin.*\\r\\n)/im", "", $encoded, 1);
        $encoded = preg_replace("/(^=yend.*)/im", "", $encoded, 1);

        // Remove line breaks and whitespace from the string.
        $encoded = trim(str_replace("\r\n", "", $encoded));

        // Decode.
        $strLength = strlen($encoded);
        for ($i = 0; $i < $strLength; $i++) {
            $c = $encoded[$i];

            if ($c == '=') {
                $i++;
                $decoded .= chr((ord($encoded[$i]) - 64) - 42);
            } else {
                $decoded .= chr(ord($c) - 42);
            }
        }

        // Make sure the decoded filesize is the same as the size specified in the header.
        if (strlen($decoded) != $filesize) {
            throw new NNTPException("Filesize in yEnc header en filesize found do not match up");
        }

        return $decoded;
    }

    /**
     * Fetch message header from message number $first until $last
     * The format of the returned array is:
     * $messages[message_id][header_name]
     *
     * @param string $range Articles to fetch.
     *
     * @return array Nested array of message and their headers.
     * @throws NNTPException
     */
    protected function cmdXZver($range = null)
    {
        if (is_null($range)) {
            $command = 'XZVER';
        } else {
            $command = 'XZVER ' . $range;
        }

        $response = $this->sendCommand($command);

        if ($response !== NET_NNTP_PROTOCOL_RESPONSECODE_OVERVIEW_FOLLOWS) {
            throw new NNTPException('', $response);
        }

        $data = $this->getCompressedResponse();
        foreach ($data as $key => $value) {
            $data[$key] = explode("\t", trim($value));
        }

        return $data;
    }

    /**
     * Returns a list of avaible headers which are send from newsserver to client for every news message.
     *
     * @return array Header names.
     * @throws NNTPException
     */
    protected function cmdListOverviewFmt()
    {
        $response = $this->sendCommand('LIST OVERVIEW.FMT');

        if ($response !== NET_NNTP_PROTOCOL_RESPONSECODE_GROUPS_FOLLOW) {
            throw new NNTPException('', $response);
        }

        $data = $this->getTextResponse();

        $format = array();

        foreach ($data as $line) {
            // Check if postfixed by ':full' (case-insensitive).
            if (0 == strcasecmp(substr($line, -5, 5), ':full')) {
                // ':full' is _not_ included in tag, but value set to true.
                $format[substr($line, 0, -5)] = true;
            } else {
                // ':' is _not_ included in tag; value set to false.
                $format[substr($line, 0, -1)] = false;
            }
        }

        return $format;
    }

    /**
     * The format of the returned array is:
     * $messages[message_id].
     *
     * @param string $field
     * @param string $range Articles to fetch.
     *
     * @return array Nested array of message and their headers.
     * @throws NNTPException
     */
    protected function cmdXHdr($field, $range = null)
    {
        if (is_null($range)) {
            $command = 'XHDR ' . $field;
        } else {
            $command = 'XHDR ' . $field . ' ' . $range;
        }

        $response = $this->sendCommand($command);

        if ($response !== NET_NNTP_PROTOCOL_RESPONSECODE_HEAD_FOLLOWS) {
            throw new NNTPException('', $response);
        }

        $data = $this->getTextResponse();

        $return = array();
        foreach ($data as $line) {
            $line             = explode(' ', trim($line), 2);
            $return[$line[0]] = $line[1];
        }

        return $return;
    }

    /**
     * Fetches a list of (all) avaible newsgroup descriptions.
     * Deprecated as of RFC2980.
     *
     * @param string $wildmat Wildmat of the groups, that is to be listed, defaults to '*'.
     *
     * @return array Nested array with description of existing newsgroups.
     * @throws NNTPException
     */
    protected function cmdXGTitle($wildmat = '*')
    {
        $response = $this->sendCommand('XGTITLE ' . $wildmat);

        if ($response !== 282) {
            throw new NNTPException('', $response);
        }

        $data = $this->getTextResponse();

        $groups = array();

        foreach ($data as $line) {
            preg_match("/^(.*?)\s(.*?$)/", trim($line), $matches);
            $groups[$matches[1]] = (string)$matches[2];
        }

        return $groups;
    }

    /**
     * Fetch message references from message number $first to $last.
     *
     * @param string $range Articles to fetch.
     *
     * @return array Message references.
     * @throws NNTPException
     */
    protected function cmdXROver($range = null)
    {
        // Warn about deprecated API (the code _is_ still in alpha state)
        if (func_num_args() > 1) {
            throw new NNTPException('The second parameter in cmdXROver() has been deprecated! Use x-y instead...');
        }

        if (is_null($range)) {
            $command = 'XROVER';
        } else {
            $command = 'XROVER ' . $range;
        }

        $response = $this->sendCommand($command);

        if ($response !== NET_NNTP_PROTOCOL_RESPONSECODE_OVERVIEW_FOLLOWS) {
            throw new NNTPException('', $response);
        }

        $data = $this->getTextResponse();

        $return = array();
        foreach ($data as $line) {
            $line             = explode(' ', trim($line), 2);
            $return[$line[0]] = $line[1];
        }

        return $return;
    }

    /**
     * @param string $field
     * @param string $range
     * @param mixed  $wildmat
     *
     * @return array Nested array of message and their headers.
     * @throws NNTPException
     */
    protected function cmdXPat($field, $range, $wildmat)
    {
        if (is_array($wildmat)) {
            $wildmat = implode(' ', $wildmat);
        }

        $response = $this->sendCommand('XPAT ' . $field . ' ' . $range . ' ' . $wildmat);

        if ($response !== NET_NNTP_PROTOCOL_RESPONSECODE_HEAD_FOLLOWS) {
            throw new NNTPException('', $response);
        }

        $data = $this->getTextResponse();

        $return = array();
        foreach ($data as $line) {
            $line             = explode(' ', trim($line), 2);
            $return[$line[0]] = $line[1];
        }

        return $return;
    }

    /**
     * Authenticate using 'original' method.
     *
     * @param string $user The username to authenticate as.
     * @param string $pass The password to authenticate with.
     *
     * @return bool
     * @throws NNTPException
     */
    protected function cmdAuthinfo($user, $pass)
    {
        // Send the username.
        $response = $this->sendCommand('AUTHINFO user ' . $user);

        // Send the password, if the server asks.
        if (($response == 381) && ($pass !== null)) {
            // Send the password
            $response = $this->sendCommand('AUTHINFO pass ' . $pass);
        }

        if ($response !== 281) {
            throw new NNTPException('', $response);
        }

        return true;
    }

    /**
     * Authenticate using 'simple' method.
     *
     * @param string $user The username to authenticate as.
     * @param string $pass The password to authenticate with.
     *
     * @return bool
     * @throws NNTPException
     */
    protected function cmdAuthinfoSimple($user, $pass)
    {
        throw new NNTPException("The auth mode: 'simple' is has not been implemented yet");
    }

    /**
     * Authenticate using 'generic' method.
     *
     * @param string $user The username to authenticate as.
     * @param string $pass The password to authenticate with.
     *
     * @return bool
     * @throws NNTPException
     */
    protected function cmdAuthinfoGeneric($user, $pass)
    {
        throw new NNTPException("The auth mode: 'generic' is has not been implemented yet");
    }

    /**
     * Test whether we are connected or not.
     *
     * @return bool
     */
    public function isConnected()
    {
        return (is_resource($this->socket) && (!feof($this->socket)));
    }
}
