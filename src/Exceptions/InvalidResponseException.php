<?php

declare(strict_types=1);

namespace ready2order\Exceptions;

/**
 * Exception thrown when the API response cannot be JSON-decoded.
 *
 * This typically indicates an unexpected response format from the API,
 * such as HTML error pages, empty responses, or malformed JSON.
 */
class InvalidResponseException extends Ready2OrderException {}
