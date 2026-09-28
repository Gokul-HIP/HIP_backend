<?php

namespace App\Modules\Automation\Services;

use App\Models\Invoice;
use App\Modules\Automation\Events\InvoiceGenerated;
use App\Modules\Automation\Support\AfterCommit;

class InvoiceGeneratedDispatcher
{
    public function dispatch(Invoice $invoice): void
    {
        $invoice->refresh();

        if (strtolower((string) $invoice->status) !== 'completed') {
            return;
        }

        $fresh = $invoice->fresh() ?? $invoice;
        $occurrenceId = InvoiceGenerated::occurrenceIdFor($fresh);

        AfterCommit::run(function () use ($fresh, $occurrenceId): void {
            InvoiceGenerated::dispatch($fresh, $occurrenceId);
        });
    }
}
