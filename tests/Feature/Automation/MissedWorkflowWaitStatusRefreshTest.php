<?php

namespace Tests\Feature\Automation;

use App\Models\DoctorBooking;
use App\Modules\Automation\Engine\AutomationFactsBuilder;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use App\Modules\Workflow\Jobs\ContinueWorkflowExecutionJob;
use App\Modules\Workflow\Services\Runtime\ExpressionEvaluator;
use App\Modules\Workflow\Services\Runtime\WorkflowExecutor;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\Support\WorkflowAutomationTestCase;

class MissedWorkflowWaitStatusRefreshTest extends WorkflowAutomationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->ensureDoctorBookingsTable();
    }

    public function test_case_a_same_booking_confirmed_after_wait_takes_true_sms_branch(): void
    {
        $booking = $this->createBooking(DoctorBooking::STATUS_MISSED);
        $this->createBooking(DoctorBooking::STATUS_CONFIRMED, [
            'patient_id' => 'other-patient',
        ]);

        $version = $this->publishDefinition($this->missedWaitConditionGraph(), 'appointmentMissed');
        $executor = app(WorkflowExecutor::class);

        $execution = $executor->start(
            $version,
            'appointmentMissed',
            $this->staleMissedContext($booking)
        );

        $this->assertSame(WorkflowExecutionStatus::Waiting->value, $execution->status);
        Queue::assertPushed(ContinueWorkflowExecutionJob::class);

        DoctorBooking::withoutEvents(function () use ($booking) {
            $booking->status = DoctorBooking::STATUS_CONFIRMED;
            $booking->save();
        });

        $this->assertTrue($this->evaluateConfirmed($this->staleMissedContext($booking->fresh())));

        $completed = $executor->resume($execution->fresh(), $execution->current_node_id);

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $completed->status);
        $this->assertCount(1, $this->providerSends);
        $this->assertSame('sms', $this->providerSends[0]['channel']);
        $this->assertSame('Rescheduled', $this->providerSends[0]['message']);
    }

    public function test_case_b_same_booking_still_missed_after_wait_takes_false_end_branch(): void
    {
        $booking = $this->createBooking(DoctorBooking::STATUS_MISSED);
        $this->createBooking(DoctorBooking::STATUS_CONFIRMED, [
            'patient_id' => 'other-patient',
        ]);

        $version = $this->publishDefinition($this->missedWaitConditionGraph(), 'appointmentMissed');
        $executor = app(WorkflowExecutor::class);

        $execution = $executor->start(
            $version,
            'appointmentMissed',
            $this->staleMissedContext($booking)
        );

        $this->assertSame(WorkflowExecutionStatus::Waiting->value, $execution->status);

        $this->assertFalse($this->evaluateConfirmed($this->staleMissedContext($booking->fresh())));

        $completed = $executor->resume($execution->fresh(), $execution->current_node_id);

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $completed->status);
        $this->assertSame([], $this->providerSends);
    }

    public function test_facts_reload_same_appointment_id_not_latest_or_snapshot(): void
    {
        $target = $this->createBooking(DoctorBooking::STATUS_MISSED);
        $this->createBooking(DoctorBooking::STATUS_CONFIRMED, [
            'patient_id' => $target->patient_id,
            'hospital_id' => $target->hospital_id,
        ]);

        DoctorBooking::withoutEvents(function () use ($target) {
            $target->status = DoctorBooking::STATUS_CONFIRMED;
            $target->appointment_status = DoctorBooking::APPOINTMENT_STATUS_CHECKED_IN;
            $target->save();
        });

        $payload = app(AutomationFactsBuilder::class)->enrich([
            'appointment_id' => $target->id,
            'patient_id' => $target->patient_id,
            'hospital_id' => $target->hospital_id,
            'appointment' => [
                'id' => $target->id,
                'status' => DoctorBooking::STATUS_MISSED,
                'appointment_status' => DoctorBooking::APPOINTMENT_STATUS_NEW,
            ],
            'appointment_status' => DoctorBooking::APPOINTMENT_STATUS_NEW,
        ]);

        $this->assertSame(DoctorBooking::STATUS_CONFIRMED, $payload['_facts']['appointment']['status']);
        $this->assertSame(
            DoctorBooking::APPOINTMENT_STATUS_CHECKED_IN,
            $payload['_facts']['appointment']['appointment_status']
        );
        $this->assertTrue($this->evaluateConfirmed($payload));
    }

    public function test_without_appointment_id_snapshot_status_is_preserved(): void
    {
        $payload = app(AutomationFactsBuilder::class)->enrich([
            'appointment' => [
                'status' => DoctorBooking::STATUS_MISSED,
                'appointment_status' => DoctorBooking::APPOINTMENT_STATUS_NEW,
            ],
            'appointment_status' => DoctorBooking::APPOINTMENT_STATUS_NEW,
        ]);

        $this->assertSame(DoctorBooking::STATUS_MISSED, $payload['_facts']['appointment']['status']);
        $this->assertFalse($this->evaluateConfirmed($payload));
    }

    /**
     * @return array<string, mixed>
     */
    protected function missedWaitConditionGraph(): array
    {
        return [
            'builderVersion' => '1',
            'nodes' => [
                $this->node('t1', 'appointmentMissed'),
                $this->node('w1', 'wait', [
                    'waitType' => 'duration',
                    'amount' => 2,
                    'unit' => 'days',
                ]),
                $this->node('c1', 'condition', [
                    'expression' => 'appointment.status == "confirmed"',
                ]),
                $this->node('sms1', 'sendSms', [
                    'recipient' => 'patient',
                    'message' => 'Rescheduled',
                ]),
                $this->node('e1', 'end'),
                $this->node('e2', 'end'),
            ],
            'edges' => [
                $this->edge('e1', 't1', 'w1'),
                $this->edge('e2', 'w1', 'c1'),
                $this->edge('e3', 'c1', 'sms1', 'true'),
                $this->edge('e4', 'c1', 'e2', 'false'),
                $this->edge('e5', 'sms1', 'e1'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function staleMissedContext(DoctorBooking $booking): array
    {
        return [
            'appointment_id' => $booking->id,
            'patient_id' => $booking->patient_id,
            'hospital_id' => $booking->hospital_id,
            'patient_mobile' => '9999999999',
            'patient' => [
                'id' => $booking->patient_id,
                'first_name' => 'Pat',
                'last_name' => 'Missed',
                'mobile' => '9999999999',
            ],
            'hospital' => ['id' => $booking->hospital_id, 'name' => 'Clinic'],
            'appointment' => [
                'id' => $booking->id,
                'status' => DoctorBooking::STATUS_MISSED,
                'appointment_status' => DoctorBooking::APPOINTMENT_STATUS_NEW,
            ],
            'appointment_status' => DoctorBooking::APPOINTMENT_STATUS_NEW,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createBooking(string $status, array $attributes = []): DoctorBooking
    {
        $booking = new DoctorBooking;
        $booking->forceFill(array_merge([
            'name' => 'Patient',
            'hospital_id' => 10,
            'doctor_id' => 'doc-1',
            'patient_id' => 'patient-wait-refresh',
            'booking_date' => now()->toDateString(),
            'status' => $status,
            'appointment_status' => DoctorBooking::APPOINTMENT_STATUS_NEW,
        ], $attributes));
        DoctorBooking::withoutEvents(fn () => $booking->save());

        return $booking;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function evaluateConfirmed(array $payload): bool
    {
        $enriched = isset($payload['_facts'])
            ? $payload
            : app(AutomationFactsBuilder::class)->enrich($payload);

        return (new ExpressionEvaluator)->evaluate(
            'appointment.status == "confirmed"',
            new WorkflowContext('appointmentMissed', $enriched)
        );
    }

    protected function ensureDoctorBookingsTable(): void
    {
        if (Schema::hasTable('doctor_bookings')) {
            return;
        }

        Schema::create('doctor_bookings', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->string('doctor_id')->nullable();
            $table->string('patient_id')->nullable();
            $table->date('booking_date')->nullable();
            $table->json('required_time_slots')->nullable();
            $table->string('status')->nullable();
            $table->string('appointment_status')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });
    }
}
