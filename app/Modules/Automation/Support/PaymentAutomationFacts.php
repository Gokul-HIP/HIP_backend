<?php

namespace App\Modules\Automation\Support;

use App\Models\Invoice;
use App\Models\Transactions;
use Illuminate\Support\Facades\Schema;

/**
 * Normalized payment facts for automation JEXL.
 *
 * Pay by hospital is the existing product label: is_online_payment === false
 * (HasBookingPaymentLabel::paymentModeLabel). This does not invent a DB column.
 */
final class PaymentAutomationFacts
{
    /**
     * @param  object{
     *     is_online_payment?: mixed,
     *     payment_status?: mixed,
     *     total_amount?: mixed,
     *     invoice_id?: mixed
     * }  $source
     * @return array{
     *     payment: array<string, mixed>,
     *     invoice: array<string, mixed>|null,
     *     transaction: Transactions|null
     * }
     */
    public static function fromPayableSource(object $source): array
    {
        $isPayByHospital = ! (bool) ($source->is_online_payment ?? false);

        $invoice = self::resolveInvoice($source);
        $transaction = self::latestTransaction($invoice);

        $paymentStatus = $transaction
            ? strtolower((string) $transaction->status)
            : strtolower((string) ($source->payment_status ?? ''));

        $amount = $transaction
            ? (float) ($transaction->total_amount ?? $transaction->transaction_amount ?? 0)
            : (float) ($source->total_amount ?? 0);

        $method = $transaction?->payment_method;
        if ($method === null || $method === '') {
            $method = null;
        }

        $payment = [
            'id' => $transaction?->id,
            'status' => $paymentStatus !== '' ? $paymentStatus : null,
            'amount' => $amount,
            'method' => $method,
            'is_pay_by_hospital' => $isPayByHospital,
            'transaction_id' => $transaction !== null
                ? 'TXN-'.str_pad((string) $transaction->id, 8, '0', STR_PAD_LEFT)
                : null,
        ];

        $invoiceView = null;
        if ($invoice) {
            $invoiceStatus = strtolower((string) $invoice->status);
            $invoiceView = [
                'id' => $invoice->id,
                'status' => $invoiceStatus,
                'total_amount' => $invoice->total_amount,
                'payment_status' => $invoiceStatus,
            ];
        }

        return [
            'payment' => $payment,
            'invoice' => $invoiceView,
            'transaction' => $transaction,
        ];
    }

    /**
     * @param  array<string, mixed>  $paymentView
     * @return array<string, mixed>
     */
    public static function withPayByHospitalFromInvoice(array $paymentView, ?Invoice $invoice): array
    {
        $source = $invoice?->doctorBooking
            ?? $invoice?->secondOpinion
            ?? $invoice?->diagnosticTestBooking;

        if ($source && isset($source->is_online_payment)) {
            $paymentView['is_pay_by_hospital'] = ! (bool) $source->is_online_payment;
        }

        return $paymentView;
    }

    protected static function resolveInvoice(object $source): ?Invoice
    {
        if (method_exists($source, 'relationLoaded') && $source->relationLoaded('invoice')) {
            $related = $source->getRelation('invoice');
            if ($related instanceof Invoice) {
                return $related;
            }
        }

        $invoiceId = $source->invoice_id ?? null;
        if (! $invoiceId || ! self::hasTable('invoices')) {
            return null;
        }

        $invoice = Invoice::query()->find($invoiceId);

        return $invoice instanceof Invoice ? $invoice : null;
    }

    protected static function latestTransaction(?Invoice $invoice): ?Transactions
    {
        if (! $invoice || ! self::hasTable('transactions')) {
            return null;
        }

        $transaction = Transactions::query()
            ->where('invoice_id', $invoice->id)
            ->latest('id')
            ->first();

        return $transaction instanceof Transactions ? $transaction : null;
    }

    protected static function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }
}
