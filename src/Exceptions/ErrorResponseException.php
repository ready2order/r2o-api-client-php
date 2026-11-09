<?php

declare(strict_types=1);

namespace ready2order\Exceptions;

use Throwable;

/**
 * Exception thrown when the ready2order API returns an error response.
 *
 * This exception is thrown for HTTP 4xx and 5xx responses (except 404).
 * Use getData() to access the full API error response including error codes
 * and validation messages.
 */
class ErrorResponseException extends Ready2OrderException
{
    /** @var null|array The full API error response data */
    protected ?array $data = null;

    /**
     * @param string         $message  The error message from the API
     * @param null|array     $data     The full API response data
     * @param null|Throwable $previous The previous exception (usually GuzzleHttp ClientException)
     */
    public function __construct(string $message = '', ?array $data = null, ?Throwable $previous = null)
    {
        if ($data) {
            $this->data = $data;
        }

        parent::__construct($message, 0, $previous);
    }

    /**
     * Get the full API error response data.
     *
     * @return null|array The API response containing error details, or null if not available
     */
    public function getData(): ?array
    {
        return $this->data;
    }
}
