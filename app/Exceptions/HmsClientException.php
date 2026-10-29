<?php

namespace App\Exceptions;

use RuntimeException;

class HmsClientException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $statusCode = 400,
        public readonly array $context = [],
    ) {
        parent::__construct($message);
    }
}
