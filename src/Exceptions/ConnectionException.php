<?php

declare(strict_types=1);

namespace ready2order\Exceptions;

use Throwable;

/**
 * Exception thrown when a request to the ready2order API fails without an HTTP error response.
 *
 * This covers DNS failures, refused connections, timeouts, connection resets
 * and redirect loops. The underlying Guzzle exception is available via getPrevious().
 */
class ConnectionException extends Ready2OrderException
{
    /**
     * @param string         $message  The error message
     * @param null|Throwable $previous The previous exception (a GuzzleHttp TransferException)
     */
    public function __construct(string $message = '', ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
