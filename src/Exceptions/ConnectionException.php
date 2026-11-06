<?php

declare(strict_types=1);

namespace ready2order\Exceptions;

use Exception;
use Throwable;

/**
 * Exception thrown when the ready2order API cannot be reached.
 *
 * This covers DNS failures, refused connections and request timeouts.
 */
class ConnectionException extends Exception
{
    /**
     * @param string         $message  The error message
     * @param null|Throwable $previous The previous exception (usually GuzzleHttp ConnectException)
     */
    public function __construct(string $message = '', ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
