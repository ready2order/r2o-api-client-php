<?php

declare(strict_types=1);

namespace ready2order\Exceptions;

use Exception;
use Throwable;

class ErrorResponseException extends Exception
{
    protected ?array $data = null;

    public function __construct(string $message = '', ?array $data = null, Throwable $previous = null)
    {
        if ($data) {
            $this->data = $data;
        }

        parent::__construct($message, 0, $previous);
    }

    public function getData(): ?array
    {
        return $this->data;
    }
}
