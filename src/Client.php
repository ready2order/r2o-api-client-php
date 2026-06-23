<?php

declare(strict_types=1);

namespace ready2order;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use ready2order\Exceptions\ErrorResponseException;
use ready2order\Exceptions\InvalidResponseException;
use ready2order\Exceptions\ResourceNotFoundException;

/**
 * PHP client for the ready2order POS API v1.
 *
 * @see https://ready2order.com/api/doc API Documentation
 */
class Client
{
    private const DEFAULT_ENDPOINT = 'https://api.ready2order.com/v1';

    private string $apiToken;
    private string $apiEndpoint;
    private int $timeout = 10;
    private string $language = 'en-US';
    private GuzzleClient $httpClient;

    /**
     * Create a new ready2order API client instance.
     *
     * @param string      $apiToken    Your ready2order Account Token for API authentication
     * @param null|string $apiEndpoint Custom API endpoint URL (defaults to production API)
     */
    public function __construct(string $apiToken, ?string $apiEndpoint = null)
    {
        $this->apiToken = $apiToken;
        $this->apiEndpoint = $apiEndpoint ?? self::DEFAULT_ENDPOINT;
        $this->httpClient = new GuzzleClient([
            RequestOptions::HEADERS => [
                'Authorization' => $this->apiToken,
                'User-Agent' => 'ready2order/r2o-api-client-php (github.com/ready2order/r2o-api-client-php)',
                'Cache-Control' => 'no-cache',
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Make a DELETE request to the API.
     *
     * @param string $path    API endpoint path (e.g., 'products/123')
     * @param array  $args    Request body parameters
     * @param int    $timeout Request timeout in seconds (overrides default)
     *
     * @throws ErrorResponseException    When the API returns an error response
     * @throws ResourceNotFoundException When the requested resource is not found (HTTP 404)
     * @throws InvalidResponseException  When the response cannot be JSON-decoded
     *
     * @return array Decoded JSON response as associative array
     */
    public function delete($path, $args = [], $timeout = 10): array
    {
        return $this->makeRequest('DELETE', $path, [RequestOptions::FORM_PARAMS => $args], $timeout);
    }

    /**
     * Make a GET request to the API.
     *
     * @param string $path    API endpoint path (e.g., 'products')
     * @param array  $args    Query string parameters
     * @param int    $timeout Request timeout in seconds (overrides default)
     *
     * @throws ErrorResponseException    When the API returns an error response
     * @throws ResourceNotFoundException When the requested resource is not found (HTTP 404)
     * @throws InvalidResponseException  When the response cannot be JSON-decoded
     *
     * @return array Decoded JSON response as associative array
     */
    public function get($path, $args = [], $timeout = 10): array
    {
        return $this->makeRequest('GET', $path, [RequestOptions::QUERY => $args], $timeout);
    }

    /**
     * Make a PATCH request to the API.
     *
     * @param string $path    API endpoint path (e.g., 'products/123')
     * @param array  $args    Request body parameters
     * @param int    $timeout Request timeout in seconds (overrides default)
     *
     * @throws ErrorResponseException    When the API returns an error response
     * @throws ResourceNotFoundException When the requested resource is not found (HTTP 404)
     * @throws InvalidResponseException  When the response cannot be JSON-decoded
     *
     * @return array Decoded JSON response as associative array
     */
    public function patch($path, $args = [], $timeout = 10): array
    {
        return $this->makeRequest('PATCH', $path, [RequestOptions::FORM_PARAMS => $args], $timeout);
    }

    /**
     * Make a POST request to the API.
     *
     * @param string $path    API endpoint path (e.g., 'products')
     * @param array  $args    Request body parameters
     * @param int    $timeout Request timeout in seconds (overrides default)
     *
     * @throws ErrorResponseException    When the API returns an error response
     * @throws ResourceNotFoundException When the requested resource is not found (HTTP 404)
     * @throws InvalidResponseException  When the response cannot be JSON-decoded
     *
     * @return array Decoded JSON response as associative array
     */
    public function post($path, $args = [], $timeout = 10): array
    {
        return $this->makeRequest('POST', $path, [RequestOptions::FORM_PARAMS => $args], $timeout);
    }

    /**
     * Make a PUT request to the API.
     *
     * @param string $path    API endpoint path (e.g., 'products')
     * @param array  $args    Request body parameters
     * @param int    $timeout Request timeout in seconds (overrides default)
     *
     * @throws ErrorResponseException    When the API returns an error response
     * @throws ResourceNotFoundException When the requested resource is not found (HTTP 404)
     * @throws InvalidResponseException  When the response cannot be JSON-decoded
     *
     * @return array Decoded JSON response as associative array
     */
    public function put($path, $args = [], $timeout = 10): array
    {
        return $this->makeRequest('PUT', $path, [RequestOptions::FORM_PARAMS => $args], $timeout);
    }

    /**
     * Get the current default timeout value.
     *
     * @return int Timeout in seconds
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Set the default timeout for API requests.
     *
     * @param int $timeout Timeout in seconds
     *
     * @return $this
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * Set the Accept-Language header for API requests.
     *
     * @param string $language Language code (e.g., 'en-US', 'de-DE')
     *
     * @return $this
     */
    public function setLanguage(string $language): self
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Performs the underlying HTTP request.
     *
     * @param string   $method  The HTTP verb to use: get, post, put, patch, delete
     * @param string   $path    The API endpoint path to be called
     * @param array    $args    Request options (query params or form params)
     * @param null|int $timeout Request timeout in seconds (uses default if null)
     *
     * @throws ErrorResponseException    When the API returns an error response
     * @throws ResourceNotFoundException When the requested resource is not found (HTTP 404)
     * @throws InvalidResponseException  When the response cannot be JSON-decoded
     *
     * @return array Decoded JSON response as associative array
     */
    private function makeRequest(string $method, string $path, array $args = [], ?int $timeout = null): array
    {
        $url = $this->apiEndpoint . '/' . $path;

        $requestOptions = [
            RequestOptions::TIMEOUT => $timeout ?? $this->timeout,
            RequestOptions::HEADERS => [
                'Accept-Language' => $this->language,
            ],
        ] + $args;

        try {
            $response = $this->httpClient->request($method, $url, $requestOptions);

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

    /**
     * Parse JSON from HTTP response body.
     *
     * @param ResponseInterface $response The HTTP response
     *
     * @throws InvalidResponseException When the response body cannot be JSON-decoded
     *
     * @return array Decoded JSON as associative array
     */
    private function parseJsonFromResponse(ResponseInterface $response): array
    {
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);
        if (\is_array($data)) {
            return $data;
        }

        throw new InvalidResponseException();
    }
}
