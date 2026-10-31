<?php

namespace App\Exceptions;

use RuntimeException;

class HmsPaymentSyncException extends RuntimeException
{
    public function __construct(
        public readonly string $error,
        string $message,
        public readonly int $statusCode = 422,
    ) {
        parent::__construct($message);
    }
}
