<?php

declare(strict_types=1);

namespace ready2order\Exceptions;

use Exception;

/**
 * Base class of all exceptions thrown for a failed ready2order API call.
 *
 * Catch this to handle any such failure in one place. Invalid configuration
 * (e.g. a malformed endpoint URL) is not covered and still surfaces as a plain
 * InvalidArgumentException from the HTTP client.
 */
abstract class Ready2OrderException extends Exception {}
