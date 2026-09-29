<?php

namespace App\Modules\Automation\Events;

use App\Models\SecondOpinion;
use App\Modules\Automation\Contracts\HospitalAutomationEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Second-opinion booking uses the published appointmentBooked trigger so
 * workflows can branch on payment.is_pay_by_hospital and appointment.status.
 * Occurrence is stamped at dispatch so pending vs confirmed are distinct.
 */
class SecondOpinionBooked implements HospitalAutomationEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public SecondOpinion $secondOpinion,
        public ?string $occurrenceId = null,
    ) {
        $this->occurrenceId = $occurrenceId ?: self::occurrenceIdFor($secondOpinion);
    }

    public static function occurrenceIdFor(SecondOpinion $booking): string
    {
        return 'appointment-booked:second-opinion:'.$booking->id.':status:'.strtolower((string) $booking->status);
    }

    public function triggerType(): string
    {
        return 'appointmentBooked';
    }

    public function payload(): array
    {
        return [
            'second_opinion' => $this->secondOpinion,
            'second_opinion_id' => $this->secondOpinion->id,
            'hospital_id' => $this->secondOpinion->branch_id,
            'event_occurrence_id' => $this->occurrenceId,
            'meta' => [
                'booking_type' => 'second_opinion',
                'booking_id' => (string) $this->secondOpinion->id,
            ],
        ];
    }
}
