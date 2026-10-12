<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\PharmacyOrder;
use App\Services\Pharmacy\PharmacyOrderService;
use Illuminate\Support\Facades\Log;

class PharmacyInvoiceObserver
{
    public function updated(Invoice $invoice): void
    {
        if (! $invoice->pharmacy_order_id || ! $invoice->wasChanged('status')) {
            return;
        }

        if ((string) $invoice->status !== 'completed') {
            return;
        }

        try {
            $order = PharmacyOrder::query()->find($invoice->pharmacy_order_id);
            if (! $order) {
                return;
            }

            app(PharmacyOrderService::class)->markPaid($order, $invoice);
        } catch (\Throwable $exception) {
            Log::error('Pharmacy invoice observer failed', [
                'invoice_id' => $invoice->id,
                'pharmacy_order_id' => $invoice->pharmacy_order_id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
