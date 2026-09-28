<?php

namespace App\Modules\Automation\Services;

use App\Models\Invoice;
use App\Modules\Automation\Events\PaymentPending;
use App\Modules\Automation\Support\InvoiceAutomationScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PendingPaymentDetector
{
    public const CHUNK_SIZE = 100;

    public const REMINDER_INTERVAL_MINUTES = 30;

    /**
     * @return array{dispatched: int, skipped: int}
     */
    public function detectAndDispatch(?Carbon $now = null): array
    {
        $now = $now ?? now();
        $dispatched = 0;
        $skipped = 0;

        $this->candidateQuery($now)
            ->orderBy('id')
            ->chunkById(self::CHUNK_SIZE, function ($invoices) use ($now, &$dispatched, &$skipped): void {
                foreach ($invoices as $invoice) {
                    $result = $this->processInvoice($invoice, $now);

                    if ($result === 'dispatched') {
                        $dispatched++;
                    } elseif ($result === 'skipped') {
                        $skipped++;
                    }
                }
            });

        return ['dispatched' => $dispatched, 'skipped' => $skipped];
    }

    /**
     * Same eligibility as reminders:pending-payments: status pending and
     * last_reminder_sent_at null or older than 30 minutes.
     *
     * @return \Illuminate\Database\Eloquent\Builder<Invoice>
     */
    public function candidateQuery(Carbon $now)
    {
        $threshold = $now->copy()->subMinutes(self::REMINDER_INTERVAL_MINUTES);

        return Invoice::query()
            ->where('status', 'pending')
            ->where(function ($query) use ($threshold) {
                $query->whereNull('last_reminder_sent_at')
                    ->orWhere('last_reminder_sent_at', '<=', $threshold);
            })
            ->with([
                'person',
                'primaryPerson',
                'creator',
                'doctorBooking.hospital.organization',
                'doctorBooking.doctor',
                'doctorBooking.patient',
                'secondOpinion',
                'diagnosticTestBooking',
            ]);
    }

    public function isEligible(Invoice $invoice, ?Carbon $now = null): bool
    {
        $now = $now ?? now();

        if ((string) $invoice->status !== 'pending') {
            return false;
        }

        if ($invoice->last_reminder_sent_at === null) {
            return true;
        }

        return $invoice->last_reminder_sent_at->lte(
            $now->copy()->subMinutes(self::REMINDER_INTERVAL_MINUTES)
        );
    }

    /**
     * @return 'dispatched'|'skipped'|'ineligible'
     */
    public function processInvoice(Invoice $invoice, Carbon $now): string
    {
        return DB::transaction(function () use ($invoice, $now) {
            $locked = Invoice::query()
                ->whereKey($invoice->id)
                ->lockForUpdate()
                ->first();

            if (! $locked || ! $this->isEligible($locked, $now)) {
                return 'ineligible';
            }

            InvoiceAutomationScope::hydrate($locked);
            $hospitalId = InvoiceAutomationScope::hospitalId($locked);

            if ($hospitalId === null) {
                Log::info('[payment-pending] Skipping invoice without hospital scope', [
                    'invoice_id' => $locked->id,
                ]);

                return 'skipped';
            }

            $occurrenceId = PaymentPending::occurrenceIdFor($locked, $now);

            PaymentPending::dispatch($locked, $occurrenceId);

            $locked->last_reminder_sent_at = $now;
            $locked->save();

            Log::info('[payment-pending] Dispatched PaymentPending', [
                'invoice_id' => $locked->id,
                'hospital_id' => $hospitalId,
                'organization_id' => InvoiceAutomationScope::organizationId($locked),
                'event_occurrence_id' => $occurrenceId,
            ]);

            return 'dispatched';
        });
    }
}
