<?php

namespace App\Modules\Workflow\Events;

use Illuminate\Foundation\Events\Dispatchable;

class PaymentReceived
{
    use Dispatchable;

    public function __construct(
        public int $paymentId,
        public array $context = [],
    ) {}
}
