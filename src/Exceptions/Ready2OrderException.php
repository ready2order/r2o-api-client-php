<?php

declare(strict_types=1);

namespace ready2order\Exceptions;

use Exception;

/**
 * Base class of all exceptions thrown by this library.
 *
 * Catch this to handle any failure of a ready2order API call in one place.
 */
abstract class Ready2OrderException extends Exception {}
