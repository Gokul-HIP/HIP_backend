<?php

namespace App\Modules\HospitalAutomation\Events;

use App\Models\Invoice;
use App\Modules\HospitalAutomation\Contracts\HospitalAutomationEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceGenerated implements HospitalAutomationEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Invoice $invoice) {}

    public function triggerType(): string
    {
        return 'invoiceGenerated';
    }

    public function payload(): array
    {
        return ['invoice' => $this->invoice];
    }
}
