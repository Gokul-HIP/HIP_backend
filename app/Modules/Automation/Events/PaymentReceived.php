<?php

namespace App\Modules\Automation\Events;

use App\Models\Invoice;
use App\Models\Transactions;
use App\Modules\Automation\Contracts\HospitalAutomationEvent;
use App\Modules\Automation\Support\InvoiceAutomationScope;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentReceived implements HospitalAutomationEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $extras
     */
    public function __construct(
        public Invoice $invoice,
        public Transactions $transaction,
        public ?string $occurrenceId = null,
        public array $extras = [],
    ) {
        $this->occurrenceId = $occurrenceId ?: self::occurrenceIdFor($transaction);
    }

    public static function occurrenceIdFor(Transactions $transaction): string
    {
        return 'payment-received:transaction:'.$transaction->id;
    }

    public function triggerType(): string
    {
        return 'paymentReceived';
    }

    public function payload(): array
    {
        InvoiceAutomationScope::hydrate($this->invoice);

        $gatewayPaymentId = $this->extras['gateway_payment_id']
            ?? $this->extras['razorpay_payment_id']
            ?? null;

        return [
            'invoice' => $this->invoice,
            'transaction' => $this->transaction,
            'invoice_id' => $this->invoice->id,
            'appointment_id' => $this->invoice->doctor_booking_id,
            'second_opinion_id' => $this->invoice->second_opinion_id,
            'diagnostic_test_booking_id' => $this->invoice->diagnostic_test_booking_id,
            'payment_status' => strtolower((string) $this->invoice->status),
            'gateway_payment_id' => $gatewayPaymentId,
            'razorpay_payment_id' => $gatewayPaymentId,
            'payment_currency' => $this->extras['currency'] ?? null,
            'hospital_id' => InvoiceAutomationScope::hospitalId($this->invoice),
            'organization_id' => InvoiceAutomationScope::organizationId($this->invoice),
            'event_occurrence_id' => $this->occurrenceId,
        ];
    }
}
