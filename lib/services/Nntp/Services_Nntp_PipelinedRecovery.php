<?php

/*
 * Internal Spotweb NNTP pipeline transport.
 *
 * Shared recovery policy for bounded FIFO ARTICLE retrieval. This class has no
 * comments/DAO knowledge and can be reused by later Spotweb retrievers.
 */

class Services_Nntp_PipelinedRecovery
{
    const CONFIGURED_WINDOW_RETRIES = 1;
    const WINDOW_ONE_RETRIES = 1;

    private $_transport;

    public function __construct(Services_Nntp_PipelinedTransport $transport)
    {
        $this->_transport = $transport;
    }

    public function fetchArticles(array $messageIds, $configuredWindow)
    {
        $outcome = new Services_Nntp_PipelinedFetchOutcome();
        $remaining = array_values($messageIds);
        $configuredWindow = max(1, (int) $configuredWindow);
        $attempt = 0;

        $remaining = $this->attemptFetch($remaining, $configuredWindow, $attempt, $outcome);
        if (empty($remaining)) {
            $outcome->setConnectionsOpened($this->_transport->getOpenedConnectionCount());

            return $outcome;
        }

        for ($i = 0; $i < self::CONFIGURED_WINDOW_RETRIES && !empty($remaining); $i++) {
            $attempt++;
            $outcome->incrementRetryCount();
            $this->_transport->withFreshConnection();
            $remaining = $this->attemptFetch($remaining, $configuredWindow, $attempt, $outcome);
        }

        for ($i = 0; $i < self::WINDOW_ONE_RETRIES && !empty($remaining); $i++) {
            $attempt++;
            $outcome->incrementRetryCount();
            $this->_transport->withFreshConnection();
            $remaining = $this->attemptFetch($remaining, 1, $attempt, $outcome);
        }

        $outcome->setUnresolvedMessageIds($remaining);
        $outcome->setConnectionsOpened($this->_transport->getOpenedConnectionCount());

        return $outcome;
    }

    private function attemptFetch(array $messageIds, $window, $attempt, Services_Nntp_PipelinedFetchOutcome $outcome)
    {
        if (empty($messageIds)) {
            return [];
        }

        try {
            $results = $this->_transport->fetchArticlesPipelined($messageIds, $window);
            $outcome->addTerminalResults($results);

            return [];
        } catch (Services_Nntp_PipelinedFetchException $x) {
            $outcome->addTerminalResults($x->terminalResults());
            $outcome->addError($x, $window, $attempt);

            return $x->unresolvedMessageIds();
        } catch (Exception $x) {
            $outcome->addError($x, $window, $attempt);

            return $messageIds;
        }
    }
}
