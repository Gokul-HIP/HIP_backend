<?php

namespace App\Modules\Automation\Observers;

use App\Models\DiagnosticTestBooking;
use App\Modules\Automation\Engine\AutomationContextBuilder;
use App\Modules\Automation\Events\LabTestOrdered;
use App\Modules\Automation\Support\SafeAutomationDispatch;
use Illuminate\Support\Facades\Log;

/**
 * LabTestOrdered fires when a diagnostic/lab order is created as pending or
 * confirmed, and again when status transitions to confirmed.
 */
class DiagnosticTestBookingObserver
{
    public function created(DiagnosticTestBooking $booking): void
    {
        if (! $this->isDispatchStatus($booking->status)) {
            return;
        }

        Log::info('[lab-test-ordered] Diagnostic booking created; dispatching', [
            'diagnostic_test_booking_id' => $booking->id,
            'hospital_id' => $booking->branch_id,
            'status' => $booking->status,
        ]);

        $this->dispatch($booking);
    }

    public function updated(DiagnosticTestBooking $booking): void
    {
        if (! $booking->wasChanged('status')) {
            return;
        }

        if (strtolower((string) $booking->status) !== 'confirmed') {
            return;
        }

        Log::info('[lab-test-ordered] Diagnostic booking confirmed; dispatching', [
            'diagnostic_test_booking_id' => $booking->id,
            'hospital_id' => $booking->branch_id,
            'from_status' => $booking->getOriginal('status'),
        ]);

        $this->dispatch($booking);
    }

    protected function dispatch(DiagnosticTestBooking $booking): void
    {
        $bookingId = $booking->id;

        SafeAutomationDispatch::afterCommit(function () use ($bookingId): void {
            $fresh = DiagnosticTestBooking::query()->find($bookingId);
            if (! $fresh) {
                return;
            }

            $context = app(AutomationContextBuilder::class)->labTestOrderedEventContext($fresh);

            Log::info('[lab-test-ordered] context built', [
                'diagnostic_test_booking_id' => $context['diagnostic_test_booking_id'] ?? null,
                'hospital_id' => $context['hospital_id'] ?? null,
                'organization_id' => $context['organization_id'] ?? null,
                'order_status' => data_get($context, 'order.status'),
                'payment_status' => data_get($context, 'payment.status'),
                'payment_is_pay_by_hospital' => data_get($context, 'payment.is_pay_by_hospital'),
                'member_present' => filled($context['member_id'] ?? null),
                'event_occurrence_id' => $context['event_occurrence_id'] ?? null,
            ]);

            LabTestOrdered::dispatch($context);
        }, 'lab-test-ordered');
    }

    protected function isDispatchStatus(mixed $status): bool
    {
        return in_array(strtolower((string) $status), ['pending', 'confirmed'], true);
    }
}
