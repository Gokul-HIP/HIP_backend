<?php

namespace App\Modules\Automation\Observers;

use App\Models\SecondOpinion;
use App\Modules\Automation\Events\SecondOpinionBooked;
use App\Modules\Automation\Support\SafeAutomationDispatch;
use Illuminate\Support\Facades\Log;

/**
 * Reuses AppointmentBooked trigger semantics: dispatch on pending/confirmed
 * create and on transition to confirmed. Workflows distinguish via JEXL.
 */
class SecondOpinionObserver
{
    public function created(SecondOpinion $booking): void
    {
        if (! $this->isDispatchStatus($booking->status)) {
            return;
        }

        Log::info('[appointment-booked] Second opinion created; dispatching', [
            'second_opinion_id' => $booking->id,
            'hospital_id' => $booking->branch_id,
            'status' => $booking->status,
        ]);

        $this->dispatch($booking);
    }

    public function updated(SecondOpinion $booking): void
    {
        if (! $booking->wasChanged('status')) {
            return;
        }

        if (strtolower((string) $booking->status) !== 'confirmed') {
            return;
        }

        Log::info('[appointment-booked] Second opinion status changed to confirmed; dispatching', [
            'second_opinion_id' => $booking->id,
            'hospital_id' => $booking->branch_id,
            'from_status' => $booking->getOriginal('status'),
        ]);

        $this->dispatch($booking);
    }

    protected function dispatch(SecondOpinion $booking): void
    {
        SafeAutomationDispatch::afterCommit(
            fn () => SecondOpinionBooked::dispatch($booking),
            'appointment-booked'
        );
    }

    protected function isDispatchStatus(mixed $status): bool
    {
        return in_array(strtolower((string) $status), ['pending', 'confirmed'], true);
    }
}
