<?php

namespace App\Modules\Automation\Services;

use App\Models\Invoice;
use App\Models\Transactions;
use App\Modules\Automation\Events\PaymentReceived;
use App\Modules\Automation\Support\AfterCommit;
use App\Modules\Automation\Support\InvoiceAutomationScope;
use Illuminate\Support\Facades\Log;

class PaymentReceivedDispatcher
{
    /**
     * @param  array<string, mixed>  $extras
     */
    public function dispatch(Invoice $invoice, Transactions $transaction, array $extras = []): void
    {
        $transaction->refresh();

        if (strtolower((string) $transaction->status) !== 'completed') {
            return;
        }

        $method = strtolower(trim((string) ($transaction->payment_method ?? '')));
        $amount = (float) ($transaction->total_amount ?? $transaction->transaction_amount ?? 0);

        if ($method === 'free' || $amount <= 0) {
            return;
        }

        $freshInvoice = $invoice->fresh() ?? $invoice;
        $freshTransaction = $transaction->fresh() ?? $transaction;
        $occurrenceId = PaymentReceived::occurrenceIdFor($freshTransaction);

        AfterCommit::run(function () use ($freshInvoice, $freshTransaction, $occurrenceId, $extras): void {
            InvoiceAutomationScope::hydrate($freshInvoice);

            Log::info('PaymentReceived dispatch', [
                'payment_id' => $freshTransaction->id,
                'invoice_id' => $freshInvoice->id,
                'hospital_id' => InvoiceAutomationScope::hospitalId($freshInvoice),
                'organization_id' => InvoiceAutomationScope::organizationId($freshInvoice),
                'event_occurrence_id' => $occurrenceId,
            ]);

            PaymentReceived::dispatch($freshInvoice, $freshTransaction, $occurrenceId, $extras);
        });
    }
}
