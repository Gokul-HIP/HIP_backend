<?php

namespace App\Modules\Automation\Events;

use App\Models\Invoice;
use App\Modules\Automation\Contracts\HospitalAutomationEvent;
use App\Modules\Automation\Support\InvoiceAutomationScope;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceGenerated implements HospitalAutomationEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public ?string $occurrenceId = null,
    ) {
        $this->occurrenceId = $occurrenceId ?: self::occurrenceIdFor($invoice);
    }

    public static function occurrenceIdFor(Invoice $invoice): string
    {
        return 'invoice-generated:invoice:'.$invoice->id;
    }

    public function triggerType(): string
    {
        return 'invoiceGenerated';
    }

    public function payload(): array
    {
        InvoiceAutomationScope::hydrate($this->invoice);

        return [
            'invoice' => $this->invoice,
            'invoice_id' => $this->invoice->id,
            'appointment_id' => $this->invoice->doctor_booking_id,
            'second_opinion_id' => $this->invoice->second_opinion_id,
            'diagnostic_test_booking_id' => $this->invoice->diagnostic_test_booking_id,
            'payment_status' => 'completed',
            'hospital_id' => InvoiceAutomationScope::hospitalId($this->invoice),
            'organization_id' => InvoiceAutomationScope::organizationId($this->invoice),
            'event_occurrence_id' => $this->occurrenceId,
        ];
    }
}
