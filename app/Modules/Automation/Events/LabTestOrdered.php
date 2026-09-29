<?php

namespace App\Modules\Automation\Events;

use App\Models\DiagnosticTestBooking;
use App\Modules\Automation\Contracts\HospitalAutomationEvent;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Lab/diagnostic order automation trigger.
 *
 * Contract: first constructor argument is always a normalized context array
 * (never a DiagnosticTestBooking model). Production TypeError was caused by
 * dispatching the Eloquent model into an array-typed constructor.
 *
 * @phpstan-type LabTestOrderedContext array{
 *     diagnostic_test_booking_id: int|string|null,
 *     hospital_id: int|string|null,
 *     organization_id?: int|string|null,
 *     member_id?: mixed,
 *     patient_id?: mixed,
 *     order: array{id: mixed, status: mixed},
 *     payment: array<string, mixed>,
 *     event_occurrence_id: string,
 *     meta: array<string, mixed>
 * }
 */
class LabTestOrdered implements HospitalAutomationEvent
{
    use Dispatchable;

    /** @param  array<string, mixed>  $context */
    public function __construct(public array $context = [])
    {
        if (($this->context['event_occurrence_id'] ?? null) === null || $this->context['event_occurrence_id'] === '') {
            $this->context['event_occurrence_id'] = self::occurrenceIdFrom($this->context);
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function occurrenceIdFrom(array $context): string
    {
        $id = $context['diagnostic_test_booking_id']
            ?? data_get($context, 'order.id')
            ?? 'unknown';
        $status = strtolower((string) (data_get($context, 'order.status') ?? $context['status'] ?? ''));

        return 'lab-test-ordered:booking:'.$id.':status:'.$status;
    }

    public static function occurrenceIdFor(DiagnosticTestBooking $booking): string
    {
        return self::occurrenceIdFrom([
            'diagnostic_test_booking_id' => $booking->id,
            'order' => ['id' => $booking->id, 'status' => $booking->status],
        ]);
    }

    public function triggerType(): string
    {
        return 'labTestOrdered';
    }

    public function payload(): array
    {
        return $this->context;
    }
}
