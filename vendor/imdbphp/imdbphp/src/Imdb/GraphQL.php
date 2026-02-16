<?php

namespace Imdb;

use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

class GraphQL
{
    /**
     * @var CacheInterface
     */
    private $cache;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Config
     */
    private $config;

    /**
     * GraphQL constructor.
     * @param CacheInterface $cache
     * @param LoggerInterface $logger
     * @param Config $config
     */
    public function __construct($cache, $logger, $config)
    {
        $this->cache = $cache;
        $this->logger = $logger;
        $this->config = $config;
    }

    public function query($query, $qn = null, $variables = array())
    {
        $key = "gql.$qn." . ($variables ? json_encode($variables) : '') . md5($query) . ".json";
        $fromCache = $this->cache->get($key);

        if ($fromCache != null) {
            return json_decode($fromCache);
        }

        $result = $this->doRequest($query, $qn, $variables);

        $this->cache->set($key, json_encode($result));

        return $result;
    }

    /**
     * @param string $query
     * @param string|null $queryName
     * @param array $variables
     * @return \stdClass
     */
    private function doRequest($query, $queryName = null, $variables = array())
    {
        $request = new Request('https://api.graphql.imdb.com/', $this->config);
        $request->addHeaderLine("Content-Type", "application/json");

        $payload = json_encode(
            array(
            'operationName' => $queryName,
            'query' => $query,
            'variables' => $variables)
        );

        $this->logger->info("[GraphQL] Requesting $queryName");
        // @TODO Try use config settings for language etc?
        // graphql docs say 'Affected by headers x-imdb-detected-country, x-imdb-user-country, x-imdb-user-language'
        // x-imdb-user-country: DE changes title {titleText{text}}, but x-imdb-user-language: de does not
        $request->post($payload);

        if (200 == $request->getStatus()) {
            return json_decode($request->getResponseBody())->data;
        } else {
            $this->logger->error(
                "[GraphQL] Failed to retrieve query [{queryName}]. Response headers:{headers}. Response body:{body}",
                array('queryName' => $queryName, 'headers' => $request->getLastResponseHeaders(), 'body' => $request->getResponseBody())
            );
            if ($this->config->throwHttpExceptions) {
                $exception = new Exception\Http("Failed to retrieve query [$queryName]. Status code [{$request->getStatus()}]");
                $exception->HTTPStatusCode = $request->getStatus();
                throw $exception;
            } else {
                return new \StdClass();
            }
        }
    }
}
