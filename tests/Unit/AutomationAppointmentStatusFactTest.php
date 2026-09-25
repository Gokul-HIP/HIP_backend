<?php

namespace Tests\Unit;

use App\Models\DoctorBooking;
use App\Modules\Automation\Engine\AutomationFactsBuilder;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\Services\Runtime\ExpressionEvaluator;
use Tests\TestCase;

class AutomationAppointmentStatusFactTest extends TestCase
{
    public function test_appointment_status_fact_uses_booking_lifecycle_not_visit_state(): void
    {
        $cases = [
            DoctorBooking::STATUS_PENDING,
            DoctorBooking::STATUS_CONFIRMED,
            DoctorBooking::STATUS_CANCELLED,
            DoctorBooking::STATUS_COMPLETED,
            DoctorBooking::STATUS_MISSED,
        ];

        foreach ($cases as $lifecycle) {
            $payload = $this->payloadFor($lifecycle, DoctorBooking::APPOINTMENT_STATUS_NEW);
            $facts = $payload['_facts'];

            $this->assertSame($lifecycle, $facts['appointment']['status'], $lifecycle);
            $this->assertSame(DoctorBooking::APPOINTMENT_STATUS_NEW, $facts['appointment']['appointment_status']);
            $this->assertTrue(
                $this->evaluate('appointment.status == "'.$lifecycle.'"', $payload),
                $lifecycle
            );
        }
    }

    public function test_visit_state_does_not_override_lifecycle_status(): void
    {
        $payload = $this->payloadFor(
            DoctorBooking::STATUS_CONFIRMED,
            DoctorBooking::APPOINTMENT_STATUS_CHECKED_IN
        );
        $facts = $payload['_facts'];

        $this->assertSame(DoctorBooking::STATUS_CONFIRMED, $facts['appointment']['status']);
        $this->assertSame(DoctorBooking::APPOINTMENT_STATUS_CHECKED_IN, $facts['appointment']['appointment_status']);
        $this->assertTrue($this->evaluate('appointment.status == "confirmed"', $payload));
        $this->assertFalse($this->evaluate('appointment.status == "checked_in"', $payload));
        $this->assertTrue($this->evaluate('appointment.appointment_status == "checked_in"', $payload));
    }

    public function test_missed_workflow_condition_is_false_until_lifecycle_is_confirmed(): void
    {
        $missed = $this->payloadFor(
            DoctorBooking::STATUS_MISSED,
            DoctorBooking::APPOINTMENT_STATUS_NEW
        );

        $this->assertFalse($this->evaluate('appointment.status == "confirmed"', $missed));
        $this->assertTrue($this->evaluate('appointment.status == "missed"', $missed));

        $confirmed = $this->payloadFor(
            DoctorBooking::STATUS_CONFIRMED,
            DoctorBooking::APPOINTMENT_STATUS_NEW
        );

        $this->assertTrue($this->evaluate('appointment.status == "confirmed"', $confirmed));
        $this->assertFalse($this->evaluate('appointment.status == "missed"', $confirmed));
    }

    /**
     * @return array<string, mixed>
     */
    protected function payloadFor(string $status, string $appointmentStatus): array
    {
        $booking = new DoctorBooking;
        $booking->forceFill([
            'status' => $status,
            'appointment_status' => $appointmentStatus,
        ]);

        return app(AutomationFactsBuilder::class)->enrich([
            'appointment' => $booking,
            'appointment_status' => $appointmentStatus,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function evaluate(string $expression, array $payload): bool
    {
        return (new ExpressionEvaluator)->evaluate(
            $expression,
            new WorkflowContext('appointmentMissed', $payload)
        );
    }
}
