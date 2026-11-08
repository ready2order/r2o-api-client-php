<?php

declare(strict_types=1);

namespace ready2order;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use ready2order\Exceptions\ConnectionException;
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

    private const MAX_ERROR_BODY_LENGTH = 500;

    private string $apiToken;
    private string $apiEndpoint;
    private int $timeout = 10;
    private string $language = 'en-US';
    private GuzzleClient $httpClient;

    /**
     * Create a new ready2order API client instance.
     *
     * @param string            $apiToken    Your ready2order Account Token for API authentication
     * @param null|string       $apiEndpoint Custom API endpoint URL (defaults to production API)
     * @param null|GuzzleClient $httpClient  Custom HTTP client (useful for tests with a MockHandler)
     */
    public function __construct(string $apiToken, ?string $apiEndpoint = null, ?GuzzleClient $httpClient = null)
    {
        $this->apiToken = $apiToken;
        $this->apiEndpoint = $apiEndpoint ?? self::DEFAULT_ENDPOINT;
        // Static headers live on the default client so an injected client keeps its own
        // (e.g. a custom User-Agent). Authorization and Accept-Language are set per request.
        $this->httpClient = $httpClient ?? new GuzzleClient([
            RequestOptions::HEADERS => [
                'User-Agent' => 'ready2order/r2o-api-client-php (github.com/ready2order/r2o-api-client-php)',
                'Cache-Control' => 'no-cache',
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Make a DELETE request to the API.
     *
     * @param string   $path    API endpoint path (e.g., 'products/123')
     * @param array    $args    Request body parameters
     * @param null|int $timeout Request timeout in seconds (null = use the client default)
     *
     * @throws ConnectionException       When the API could not be reached
     * @throws ErrorResponseException    When the API returns an error response
     * @throws ResourceNotFoundException When the requested resource is not found (HTTP 404)
     * @throws InvalidResponseException  When the response cannot be JSON-decoded
     *
     * @return array Decoded JSON response as associative array
     */
    public function delete(string $path, array $args = [], ?int $timeout = null): array
    {
        return $this->makeRequest('DELETE', $path, [RequestOptions::FORM_PARAMS => $args], $timeout);
    }

    /**
     * Make a GET request to the API.
     *
     * @param string   $path    API endpoint path (e.g., 'products')
     * @param array    $args    Query string parameters
     * @param null|int $timeout Request timeout in seconds (null = use the client default)
     *
     * @throws ConnectionException       When the API could not be reached
     * @throws ErrorResponseException    When the API returns an error response
     * @throws ResourceNotFoundException When the requested resource is not found (HTTP 404)
     * @throws InvalidResponseException  When the response cannot be JSON-decoded
     *
     * @return array Decoded JSON response as associative array
     */
    public function get(string $path, array $args = [], ?int $timeout = null): array
    {
        return $this->makeRequest('GET', $path, [RequestOptions::QUERY => $args], $timeout);
    }

    /**
     * Make a PATCH request to the API.
     *
     * @param string   $path    API endpoint path (e.g., 'products/123')
     * @param array    $args    Request body parameters
     * @param null|int $timeout Request timeout in seconds (null = use the client default)
     *
     * @throws ConnectionException       When the API could not be reached
     * @throws ErrorResponseException    When the API returns an error response
     * @throws ResourceNotFoundException When the requested resource is not found (HTTP 404)
     * @throws InvalidResponseException  When the response cannot be JSON-decoded
     *
     * @return array Decoded JSON response as associative array
     */
    public function patch(string $path, array $args = [], ?int $timeout = null): array
    {
        return $this->makeRequest('PATCH', $path, [RequestOptions::FORM_PARAMS => $args], $timeout);
    }

    /**
     * Make a POST request to the API.
     *
     * @param string   $path    API endpoint path (e.g., 'products')
     * @param array    $args    Request body parameters
     * @param null|int $timeout Request timeout in seconds (null = use the client default)
     *
     * @throws ConnectionException       When the API could not be reached
     * @throws ErrorResponseException    When the API returns an error response
     * @throws ResourceNotFoundException When the requested resource is not found (HTTP 404)
     * @throws InvalidResponseException  When the response cannot be JSON-decoded
     *
     * @return array Decoded JSON response as associative array
     */
    public function post(string $path, array $args = [], ?int $timeout = null): array
    {
        return $this->makeRequest('POST', $path, [RequestOptions::FORM_PARAMS => $args], $timeout);
    }

    /**
     * Make a PUT request to the API.
     *
     * @param string   $path    API endpoint path (e.g., 'products')
     * @param array    $args    Request body parameters
     * @param null|int $timeout Request timeout in seconds (null = use the client default)
     *
     * @throws ConnectionException       When the API could not be reached
     * @throws ErrorResponseException    When the API returns an error response
     * @throws ResourceNotFoundException When the requested resource is not found (HTTP 404)
     * @throws InvalidResponseException  When the response cannot be JSON-decoded
     *
     * @return array Decoded JSON response as associative array
     */
    public function put(string $path, array $args = [], ?int $timeout = null): array
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
     * @param string   $method  The HTTP verb to use: GET, POST, PUT, PATCH, DELETE
     * @param string   $path    The API endpoint path to be called
     * @param array    $args    Request options (query params or form params)
     * @param null|int $timeout Request timeout in seconds (uses default if null)
     *
     * @throws ConnectionException       When the API could not be reached
     * @throws ErrorResponseException    When the API returns an error response
     * @throws ResourceNotFoundException When the requested resource is not found (HTTP 404)
     * @throws InvalidResponseException  When the response cannot be JSON-decoded
     *
     * @return array Decoded JSON response as associative array
     */
    private function makeRequest(string $method, string $path, array $args = [], ?int $timeout = null): array
    {
        $url = rtrim($this->apiEndpoint, '/') . '/' . ltrim($path, '/');

        $requestOptions = [
            // An injected client may have http_errors disabled; error mapping below relies on it.
            RequestOptions::HTTP_ERRORS => true,
            RequestOptions::TIMEOUT => $timeout ?? $this->timeout,
            RequestOptions::HEADERS => [
                'Authorization' => $this->apiToken,
                'Accept-Language' => $this->language,
            ],
        ] + $args;

        try {
            $response = $this->httpClient->request($method, $url, $requestOptions);

            return $this->parseJsonFromResponse($response, $method, $path);
        } catch (BadResponseException $exception) {
            $response = $exception->getResponse();
            $body = (string) $response->getBody();
            $data = json_decode($body, true);
            $data = \is_array($data) ? $data : null;

            if ($data === null) {
                $msg = \sprintf('API Request (%s %s) gave invalid response which could not be JSON-decoded: %s', $method, $path, $this->truncateBody($body));
            } elseif (!empty($data['error']) && !empty($data['msg'])) {
                $msg = \is_string($data['msg']) ? $data['msg'] : (string) json_encode($data['msg']);
            } else {
                $msg = \sprintf('API Request (%s %s) failed with HTTP %d: %s', $method, $path, $response->getStatusCode(), $this->truncateBody($body));
            }

            if ($response->getStatusCode() === 404) {
                throw new ResourceNotFoundException($msg);
            }

            throw new ErrorResponseException($msg, $data, $exception);
        } catch (TransferException $exception) {
            // Everything without an HTTP error response: DNS/connect failures, timeouts,
            // connection resets, redirect loops. Must stay after BadResponseException.
            throw new ConnectionException(
                \sprintf('Request to the ready2order API failed (%s %s): %s', $method, $path, $exception->getMessage()),
                $exception
            );
        }
    }

    /**
     * Parse JSON from HTTP response body.
     *
     * @param ResponseInterface $response The HTTP response
     * @param string            $method   The HTTP verb that was used
     * @param string            $path     The API endpoint path that was called
     *
     * @throws InvalidResponseException When the response body cannot be JSON-decoded
     *
     * @return array Decoded JSON as associative array
     */
    private function parseJsonFromResponse(ResponseInterface $response, string $method, string $path): array
    {
        $body = (string) $response->getBody();
        $data = json_decode($body, true);
        if (\is_array($data)) {
            return $data;
        }

        throw new InvalidResponseException(
            \sprintf('API Response to %s %s could not be JSON-decoded: %s', $method, $path, $this->truncateBody($body))
        );
    }

    /**
     * Truncate a body for use in an exception message.
     *
     * @param string $body The response body
     *
     * @return string The body, truncated to MAX_ERROR_BODY_LENGTH characters when longer
     */
    private function truncateBody(string $body): string
    {
        if (mb_strlen($body) <= self::MAX_ERROR_BODY_LENGTH) {
            return $body;
        }

        return mb_substr($body, 0, self::MAX_ERROR_BODY_LENGTH) . ' …';
    }
}
