<?php

namespace App\Exceptions;

use Exception;

class ChatbotFlowException extends Exception
{
    /**
     * @param  array<string, mixed>  $envelope
     */
    public function __construct(
        string $message,
        public readonly int $statusCode,
        public readonly array $envelope = [],
        public readonly ?string $codeKey = null,
    ) {
        parent::__construct($message, $statusCode);
    }
}
