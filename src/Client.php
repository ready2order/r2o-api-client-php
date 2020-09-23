<?php

declare(strict_types=1);

namespace ready2order;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use http\Env\Request;
use Psr\Http\Message\ResponseInterface;
use ready2order\Exceptions\ErrorResponseException;
use ready2order\Exceptions\InvalidResponseException;
use ready2order\Exceptions\ResourceNotFoundException;

class Client
{
    private string $apiToken;
    private string $apiEndpoint = 'https://api.ready2order.com/v1';
    private int $timeout = 10;

    /**
     * Create a new instance.
     *
     * @param string $apiToken Your ready2order API key
     */
    public function __construct(string $apiToken)
    {
        $this->apiToken = $apiToken;
    }

    public function delete($method, $args = [], $timeout = 10): array
    {
        return $this->makeRequest('delete', $method, [RequestOptions::FORM_PARAMS => $args], $timeout);
    }

    public function get($method, $args = [], $timeout = 10): array
    {
        return $this->makeRequest('get', $method, [RequestOptions::QUERY => $args], $timeout);
    }

    public function patch($method, $args = [], $timeout = 10): array
    {
        return $this->makeRequest('patch', $method, [RequestOptions::FORM_PARAMS => $args], $timeout);
    }

    public function post($method, $args = [], $timeout = 10): array
    {
        return $this->makeRequest('post', $method, [RequestOptions::FORM_PARAMS => $args], $timeout);
    }

    public function put($method, $args = [], $timeout = 10): array
    {
        return $this->makeRequest('put', $method, [RequestOptions::FORM_PARAMS => $args], $timeout);
    }

    public function setApiEndpoint(string $apiEndpoint): void
    {
        $this->apiEndpoint = $apiEndpoint;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }

    /**
     * Performs the underlying HTTP request. Not very exciting.
     *
     * @param string $$http_verb The HTTP verb to use: get, post, put, patch, delete
     * @param string $path       The API method to be called
     * @param array  $args       Assoc array of parameters to be passed
     * @param mixed  $timeout
     *
     * @return array Assoc array of decoded result
     */
    private function makeRequest(string $method, string $path, array $args = [], ?int $timeout = null): array
    {
        $url = $this->apiEndpoint . '/' . $path;

        $client = new GuzzleClient([
            'timeout' => $timeout ?? $this->timeout,
            'headers' => [
                'Authorization' => $this->apiToken,
                'User-Agent' => 'duxthefux/ready2order-php-api-v1 (github.com/ready2order/r2o-api-client-php)',
            ],
        ]);

        try {
            $response = $client->request($method, $url, [
                    RequestOptions::HEADERS => [
                        'Cache-Control' => 'no-cache',
                        'Accept' => 'application/json',
                    ],
                ] + $args);

            $data = $this->parseJsonFromResponse($response);

            return $data;
        } catch (ClientException $exception) {
            $response = $exception->getResponse();
            $data = $this->parseJsonFromResponse($response);
            if (isset($data['error']) && $data['error'] === true && !empty($data['msg'])) {
                $msg = $data['msg'];
            } else {
                $msg = "API Request ({$method} {$path}) gave invalid response which could not be JSON-decoded: " . $response->getBody()->getContents();
            }

            if ($response->getStatusCode() == 404) {
                throw new ResourceNotFoundException($msg);
            }

            throw new ErrorResponseException($msg, $data, $exception);
        }
    }

    private function parseJsonFromResponse(ResponseInterface $response): array
    {
        $response = $response->getBody()->getContents();
        $data = json_decode($response, true);
        if (\is_array($data)) {
            return $data;
        }

        throw new InvalidResponseException();
    }
}
