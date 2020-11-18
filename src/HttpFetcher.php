<?php

namespace Switchover;

use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;
use Switchover\Exceptions\FetchException;

class HttpFetcher implements FetcherInterface
{

    /** @var LoggerInterface */
    private $logger;

    /** @var Client */
    private $client;

    function __construct(LoggerInterface $logger, array $httpOptions = null)
    {
        $this->logger = $logger;

        $this->client = $this->getHttpClient($httpOptions);
    }

    /**
     * Fetches Api Response
     *
     * @param string $sdkKey
     * @param string $lastModified
     * @return ApiResponse | null
     */
    function fetchAll(string $sdkKey, string $lastModified = null): ?ApiResponse
    {
        $response = $this->doRequest($sdkKey, $lastModified);

        $statusCode = $response->getStatusCode();

        $this->logger->debug('Fetch status ' . $statusCode);

        if ($statusCode === 200) {
            $newLastModified = current($response->getHeader('Last-Modified'));

            $data = $this->parseBodyToJson($response);

            $this->logger->debug('Response Last-Modified ' . $newLastModified);

            return new ApiResponse($newLastModified, $data);
        } else if ($statusCode === 304) {
            $this->logger->debug('Config unchanged');

            $data = $this->parseBodyToJson($response);

            return new ApiResponse($lastModified, $data);
        }
        return null;
    }

    private function doRequest($sdkKey, $lastModified)
    {
        $headers = [
            'Accept' => 'application/json',
            'Cache-Control' => 'no-cache, must-revalidate',
            'X-Switchover-Client-ID' => $sdkKey,
            'X-Switchover-User-Agent' => 'switchover-js/1.0'
        ];

        if ($lastModified) {
            $this->logger->debug('Fetch using last Header Last-Modified ' . $lastModified);
            $headers['If-Modified-Since'] = $lastModified;
        }

        try {
            return $this->client->request(
                'GET',
                $sdkKey . '/toggles_v2.json',
                ['headers' => $headers]
            );
        } catch (ClientException $e) {
            throw new FetchException(
                $e->getMessage()
            );
        }
    }

    /**
     * Parse body to json, throws FetchException if json is invalid
     *
     * @param GuzzleHttp\Psr7\Response $response
     * @return array
     */
    private function parseBodyToJson(Response $response) {
        $json = json_decode($response->getBody(), true);

        if (json_last_error() != JSON_ERROR_NONE) {
            throw new FetchException(json_last_error_msg());
        }

        return $json;
    }


    /**
     *
     * @param array $httpOptions
     * @return Client
     */
    function getHttpClient(array $httpOptions)
    {

        $baseOptions = [
            'base_uri' => SdkConfig::API_ENDPOINT,
            'timeout' => 10
        ];

        $options = array_merge($baseOptions, $httpOptions ?? []);

        $client = new Client($options);

        return $client;
    }
}
