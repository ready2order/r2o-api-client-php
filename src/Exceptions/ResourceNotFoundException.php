<?php

declare(strict_types=1);

namespace ready2order\Exceptions;

/**
 * Exception thrown when the requested resource is not found (HTTP 404).
 *
 * This is thrown when attempting to access a resource that doesn't exist,
 * such as a product, customer, or order with an invalid ID.
 */
class ResourceNotFoundException extends Ready2OrderException {}
