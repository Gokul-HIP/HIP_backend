<?php

namespace App\Modules\Automation\Events;

use App\Models\Invoice;
use App\Modules\Automation\Contracts\HospitalAutomationEvent;
use App\Modules\Automation\Support\InvoiceAutomationScope;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentPending implements HospitalAutomationEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public ?string $occurrenceId = null,
    ) {
        $this->occurrenceId = $occurrenceId ?: self::occurrenceIdFor($invoice);
    }

    /**
     * Stable key for one 30-minute reminder occurrence of an invoice.
     * Matches the legacy last_reminder_sent_at window so queue retries
     * reuse the same execution while a later eligible reminder can start another.
     */
    public static function occurrenceIdFor(Invoice $invoice, ?DateTimeInterface $at = null): string
    {
        $timestamp = Carbon::instance(
            \DateTimeImmutable::createFromInterface($at ?? now())
        )->getTimestamp();

        $window = intdiv($timestamp, 30 * 60);

        return 'payment-pending:invoice:'.$invoice->id.':window:'.$window;
    }

    public function triggerType(): string
    {
        return 'paymentPending';
    }

    public function payload(): array
    {
        InvoiceAutomationScope::hydrate($this->invoice);

        return [
            'invoice' => $this->invoice,
            'invoice_id' => $this->invoice->id,
            'appointment_id' => $this->invoice->doctor_booking_id,
            'payment_status' => 'pending',
            'hospital_id' => InvoiceAutomationScope::hospitalId($this->invoice),
            'organization_id' => InvoiceAutomationScope::organizationId($this->invoice),
            'event_occurrence_id' => $this->occurrenceId,
        ];
    }
}
