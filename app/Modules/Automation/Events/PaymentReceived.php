<?php

namespace App\Modules\Automation\Events;

use App\Models\Invoice;
use App\Modules\Automation\Contracts\HospitalAutomationEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentReceived implements HospitalAutomationEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Invoice $invoice) {}

    public function triggerType(): string
    {
        return 'paymentReceived';
    }

    public function payload(): array
    {
        return ['invoice' => $this->invoice, 'payment_status' => 'completed'];
    }
}
