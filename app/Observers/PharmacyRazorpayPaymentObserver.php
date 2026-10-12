<?php

namespace App\Observers;

use App\Enums\PharmacyPaymentStatus;
use App\Models\PharmacyOrder;
use App\Models\RazorpayPayment;
use App\Services\Pharmacy\PharmacyOrderService;
use Illuminate\Support\Facades\Log;

class PharmacyRazorpayPaymentObserver
{
    public function created(RazorpayPayment $payment): void
    {
        $this->markOrderFailed($payment);
    }

    public function updated(RazorpayPayment $payment): void
    {
        if (! $payment->wasChanged('payment_status')) {
            return;
        }

        $this->markOrderFailed($payment);
    }

    private function markOrderFailed(RazorpayPayment $payment): void
    {
        if ((string) $payment->payment_status !== 'failed') {
            return;
        }

        try {
            $invoice = $payment->relationLoaded('invoice')
                ? $payment->invoice
                : $payment->invoice()->first();

            if (! $invoice || ! $invoice->pharmacy_order_id) {
                return;
            }

            $order = PharmacyOrder::query()->find($invoice->pharmacy_order_id);
            if (! $order || $order->payment_status === PharmacyPaymentStatus::Paid) {
                return;
            }

            app(PharmacyOrderService::class)->markPaymentFailed($order);
        } catch (\Throwable $exception) {
            Log::error('Pharmacy Razorpay observer failed', [
                'razorpay_payment_id' => $payment->id,
                'invoice_id' => $payment->invoice_id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
