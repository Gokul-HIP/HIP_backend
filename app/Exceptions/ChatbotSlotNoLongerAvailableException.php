<?php

namespace App\Exceptions;

use Exception;

class ChatbotSlotNoLongerAvailableException extends Exception
{
    public function __construct(
        string $message = 'This slot is no longer available. A refund is required.',
        public readonly ?int $invoiceId = null,
        public readonly ?string $razorpayPaymentId = null,
    ) {
        parent::__construct($message);
    }
}
