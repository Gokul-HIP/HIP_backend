<?php

namespace Tests\Feature\Automation;

use App\Livewire\Admin\DoctorBooking\Reschedule as AdminReschedule;
use App\Models\DoctorBooking;
use App\Modules\Automation\Engine\AutomationContextBuilder;
use App\Modules\Automation\Engine\AutomationEngine;
use App\Modules\Automation\Engine\AutomationFactsBuilder;
use App\Modules\Automation\Events\AppointmentBooked;
use App\Modules\Automation\Events\AppointmentMissed;
use App\Modules\Automation\Events\AppointmentRescheduled;
use App\Modules\Automation\Listeners\DispatchHospitalAutomationWorkflow;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use App\Modules\Workflow\Jobs\ContinueWorkflowExecutionJob;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Services\Runtime\ExpressionEvaluator;
use App\Modules\Workflow\Services\Runtime\WorkflowExecutor;
use App\Services\DoctorBookingStatusService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Mockery;
use Tests\Support\WorkflowAutomationTestCase;

class AppointmentRescheduleAutomationTest extends WorkflowAutomationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Carbon::setTestNow(Carbon::parse('2026-09-25 12:00:00'));
        $this->ensureDoctorBookingsTable();
        $this->app->instance(NotificationService::class, Mockery::mock(NotificationService::class)->shouldIgnoreMissing());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_reschedule_updates_date_and_time_on_the_same_booking(): void
    {
        Event::fake([AppointmentRescheduled::class]);
        $booking = $this->createBooking();
        $id = $booking->id;

        $updated = $this->service()->reschedule($booking, '2026-09-28', '15:00');

        $this->assertSame($id, $updated->id);
        $this->assertSame(12, (int) $updated->hospital_id);
        $this->assertSame('patient-reschedule', $updated->patient_id);
        $this->assertSame('doc-1', $updated->doctor_id);
        $this->assertSame(DoctorBooking::STATUS_CONFIRMED, $updated->status);
        $this->assertSame(DoctorBooking::APPOINTMENT_STATUS_NEW, $updated->appointment_status);
        $this->assertSame('2026-09-28', $updated->booking_date->toDateString());
        $this->assertSame('3:00 PM', $updated->required_time_slots[0]);
        Event::assertDispatched(AppointmentRescheduled::class, function (AppointmentRescheduled $event) use ($id) {
            return $event->appointment->id === $id
                && $event->previousDate === '2026-09-25'
                && $event->payload()['appointment_id'] === $id
                && (int) $event->payload()['hospital_id'] === 12
                && filled($event->payload()['event_occurrence_id']);
        });
    }

    public function test_unchanged_date_and_time_does_not_dispatch_rescheduled(): void
    {
        Event::fake([AppointmentRescheduled::class]);
        $booking = $this->createBooking([
            'booking_date' => '2026-09-28',
            'required_time_slots' => ['3:00 PM'],
        ]);

        $this->service()->reschedule($booking, '2026-09-28', '15:00');

        Event::assertNotDispatched(AppointmentRescheduled::class);
        $this->assertSame(0, WorkflowExecution::query()->count());
        $this->assertSame('2026-09-28', $booking->fresh()->booking_date->toDateString());
        $this->assertSame('3:00 PM', $booking->fresh()->required_time_slots[0]);
    }

    public function test_invalid_date_is_rejected_and_does_not_dispatch(): void
    {
        Event::fake([AppointmentRescheduled::class]);
        $booking = $this->createBooking();

        try {
            $this->service()->reschedule($booking, 'not-a-date', '10:00');
            $this->fail('Expected validation exception');
        } catch (ValidationException) {
        }

        $this->assertSame('2026-09-25', $booking->fresh()->booking_date->toDateString());
        Event::assertNotDispatched(AppointmentRescheduled::class);
        $this->assertSame(0, WorkflowExecution::query()->count());
    }

    public function test_past_date_is_rejected_and_does_not_dispatch(): void
    {
        Event::fake([AppointmentRescheduled::class]);
        $booking = $this->createBooking();

        try {
            $this->service()->reschedule($booking, '2020-01-01', '10:00');
            $this->fail('Expected validation exception');
        } catch (ValidationException) {
        }

        $this->assertSame('10:00 AM', $booking->fresh()->required_time_slots[0]);
        Event::assertNotDispatched(AppointmentRescheduled::class);
        $this->assertSame(0, WorkflowExecution::query()->count());
    }

    public function test_empty_time_is_rejected_and_does_not_dispatch(): void
    {
        Event::fake([AppointmentRescheduled::class]);
        $booking = $this->createBooking();

        try {
            $this->service()->reschedule($booking, '2026-09-28', '   ');
            $this->fail('Expected validation exception');
        } catch (ValidationException) {
        }

        $this->assertSame('2026-09-25', $booking->fresh()->booking_date->toDateString());
        $this->assertSame('10:00 AM', $booking->fresh()->required_time_slots[0]);
        Event::assertNotDispatched(AppointmentRescheduled::class);
        $this->assertSame(0, WorkflowExecution::query()->count());
    }

    public function test_missed_paid_online_reschedule_becomes_confirmed_and_keeps_payment(): void
    {
        Event::fake([AppointmentRescheduled::class]);
        $booking = $this->createBooking([
            'status' => DoctorBooking::STATUS_MISSED,
            'is_online_payment' => true,
            'payment_status' => 'paid',
            'invoice_id' => 44,
        ]);
        $id = $booking->id;

        $updated = $this->service()->reschedule($booking, '2026-09-28', '11:00');

        $this->assertSame($id, $updated->id);
        $this->assertSame(DoctorBooking::STATUS_CONFIRMED, $updated->status);
        $this->assertSame('paid', $updated->payment_status);
        $this->assertTrue((bool) $updated->is_online_payment);
        $this->assertSame(44, (int) $updated->invoice_id);
        $this->assertSame(DoctorBooking::APPOINTMENT_STATUS_NEW, $updated->appointment_status);
        $this->assertSame('2026-09-28', $updated->booking_date->toDateString());
        $this->assertSame('11:00 AM', $updated->required_time_slots[0]);
        Event::assertDispatchedTimes(AppointmentRescheduled::class, 1);
    }

    public function test_missed_pay_at_hospital_reschedule_becomes_pending_and_keeps_payment(): void
    {
        Event::fake([AppointmentRescheduled::class]);
        $booking = $this->createBooking([
            'status' => DoctorBooking::STATUS_MISSED,
            'is_online_payment' => false,
            'payment_status' => 'unpaid',
            'invoice_id' => 45,
        ]);

        $updated = $this->service()->reschedule($booking, '2026-09-28', '11:00');

        $this->assertSame($booking->id, $updated->id);
        $this->assertSame(DoctorBooking::STATUS_PENDING, $updated->status);
        $this->assertSame('unpaid', $updated->payment_status);
        $this->assertFalse((bool) $updated->is_online_payment);
        $this->assertSame(45, (int) $updated->invoice_id);
        Event::assertDispatchedTimes(AppointmentRescheduled::class, 1);
    }

    public function test_failed_reschedule_transaction_does_not_dispatch(): void
    {
        Event::fake([AppointmentRescheduled::class]);
        $booking = $this->createBooking([
            'status' => DoctorBooking::STATUS_MISSED,
            'is_online_payment' => true,
            'payment_status' => 'paid',
        ]);

        Schema::drop('doctor_bookings');

        try {
            $this->service()->reschedule($booking, '2026-09-28', '11:00');
            $this->fail('Expected reschedule to fail after the bookings table was dropped');
        } catch (\Throwable) {
        }

        Event::assertNotDispatched(AppointmentRescheduled::class);
    }

    public function test_mark_confirmed_payment_finalize_behavior_is_unchanged(): void
    {
        $booking = $this->createBooking([
            'status' => DoctorBooking::STATUS_PENDING,
            'is_online_payment' => true,
            'payment_status' => 'paid',
        ]);

        $this->service()->markConfirmed($booking);

        $fresh = $booking->fresh();
        $this->assertSame(DoctorBooking::STATUS_CONFIRMED, $fresh->status);
        $this->assertSame(DoctorBooking::APPOINTMENT_STATUS_NEW, $fresh->appointment_status);
        $this->assertSame('paid', $fresh->payment_status);
    }

    public function test_missed_paid_online_reschedule_execution_context_and_condition_are_confirmed(): void
    {
        $this->ensureHospitalsTable();
        $booking = $this->createBooking([
            'status' => DoctorBooking::STATUS_MISSED,
            'is_online_payment' => true,
            'payment_status' => 'paid',
            'mobile_number' => '9999999999',
            'member_id' => 'member-reschedule-1',
        ]);
        $version = $this->publishDefinition(
            $this->rescheduleWaitConditionGraph(),
            'appointmentRescheduled',
            hospitalId: 12,
        );

        $updated = $this->service()->reschedule($booking, '2026-09-28', '11:00');
        $this->assertSame(DoctorBooking::STATUS_CONFIRMED, $updated->status);

        $queued = Queue::listenersPushed(DispatchHospitalAutomationWorkflow::class)->first();
        $this->assertNotNull($queued);
        $event = $queued->data[0] ?? null;
        $this->assertInstanceOf(AppointmentRescheduled::class, $event);
        $this->assertSame(DoctorBooking::STATUS_CONFIRMED, $event->appointment->status);

        $event->appointment->unsetRelation('hospital');
        $event->appointment->setRelation('doctor', null);
        $event->appointment->setRelation('patient', null);
        $event->appointment->setRelation('department', null);

        app(DispatchHospitalAutomationWorkflow::class)->handle($event);

        $execution = WorkflowExecution::query()
            ->where('workflow_id', $version->workflow_id)
            ->where('trigger_type', 'appointmentRescheduled')
            ->first();

        $this->assertNotNull($execution);
        $this->assertSame(
            WorkflowExecutionStatus::Waiting->value,
            $execution->status,
            (string) $execution->failure_reason
        );
        $this->assertSame($booking->id, $execution->context['appointment_id'] ?? null);
        $this->assertSame(
            DoctorBooking::STATUS_CONFIRMED,
            $execution->context['_facts']['appointment']['status']
                ?? data_get($execution->context, 'appointment.status')
        );

        $this->assertTrue($this->evaluateConfirmed($execution->context));

        Queue::assertPushed(ContinueWorkflowExecutionJob::class);

        $completed = app(WorkflowExecutor::class)->resume($execution->fresh(), $execution->current_node_id);

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $completed->status);
        $this->assertTrue(collect($this->providerSends)->contains(
            fn (array $send) => $send['channel'] === 'push'
        ));
        $this->assertTrue(collect($this->providerSends)->contains(
            fn (array $send) => $send['channel'] === 'whatsapp'
        ));
    }

    public function test_reschedule_starts_published_appointment_rescheduled_workflow(): void
    {
        $booking = $this->createBooking();
        $version = $this->publishDefinition(
            $this->triggerEndGraph('appointmentRescheduled'),
            'appointmentRescheduled',
            hospitalId: 12,
        );

        Event::listen(AppointmentRescheduled::class, function (AppointmentRescheduled $event) {
            $this->attachEmptyRelations($event->appointment);
            app(DispatchHospitalAutomationWorkflow::class)->handle($event);
        });

        $this->service()->reschedule($booking, '2026-09-29', '11:30');

        $execution = WorkflowExecution::query()
            ->where('workflow_id', $version->workflow_id)
            ->where('trigger_type', 'appointmentRescheduled')
            ->latest('id')
            ->first();

        $this->assertNotNull($execution);
        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->status);
        $this->assertSame($booking->id, $execution->context['appointment_id'] ?? null);
        $this->assertSame(12, (int) ($execution->context['hospital_id'] ?? 0));
        $this->assertSame('2026-09-29', $execution->context['appointment_date'] ?? null);
        $this->assertSame('11:30 AM', $execution->context['appointment_time'] ?? null);
        $this->assertNotEmpty($execution->context['event_occurrence_id'] ?? null);
        $this->assertArrayHasKey('appointment', $execution->context);
        $this->assertArrayHasKey('patient', $execution->context);
        $this->assertArrayHasKey('doctor', $execution->context);
        $this->assertArrayHasKey('organization_id', $execution->context);
    }

    public function test_retrying_the_same_reschedule_event_does_not_duplicate_execution(): void
    {
        $booking = $this->createBooking();
        $version = $this->publishDefinition(
            $this->triggerEndGraph('appointmentRescheduled'),
            'appointmentRescheduled',
            hospitalId: 12,
        );

        $this->attachEmptyRelations($booking);
        $event = new AppointmentRescheduled($booking, '2026-09-25', 'occ-retry-1');
        $listener = app(DispatchHospitalAutomationWorkflow::class);
        $listener->handle($event);
        $listener->handle($event);

        $this->assertSame(1, WorkflowExecution::query()
            ->where('workflow_id', $version->workflow_id)
            ->where('trigger_type', 'appointmentRescheduled')
            ->count());
    }

    public function test_two_genuine_reschedules_create_two_executions(): void
    {
        $booking = $this->createBooking();
        $version = $this->publishDefinition(
            $this->triggerEndGraph('appointmentRescheduled'),
            'appointmentRescheduled',
            hospitalId: 12,
        );

        $this->attachEmptyRelations($booking);
        $listener = app(DispatchHospitalAutomationWorkflow::class);
        $listener->handle(new AppointmentRescheduled($booking, '2026-09-25', 'occ-one'));
        $listener->handle(new AppointmentRescheduled($booking, '2026-09-27', 'occ-two'));

        $occurrenceIds = WorkflowExecution::query()
            ->where('workflow_id', $version->workflow_id)
            ->where('trigger_type', 'appointmentRescheduled')
            ->pluck('context')
            ->map(fn ($context) => is_array($context) ? ($context['event_occurrence_id'] ?? null) : null)
            ->all();

        $this->assertCount(2, $occurrenceIds);
        $this->assertEqualsCanonicalizing(['occ-one', 'occ-two'], $occurrenceIds);
    }

    public function test_appointment_missed_duplicate_protection_still_uses_appointment_id(): void
    {
        $booking = $this->createBooking(['status' => DoctorBooking::STATUS_MISSED]);
        $version = $this->publishDefinition(
            $this->triggerEndGraph('appointmentMissed'),
            'appointmentMissed',
            hospitalId: 12,
        );

        $listener = app(DispatchHospitalAutomationWorkflow::class);
        $listener->handle(new AppointmentMissed($booking));
        $listener->handle(new AppointmentMissed($booking));

        $this->assertSame(1, WorkflowExecution::query()
            ->where('workflow_id', $version->workflow_id)
            ->where('trigger_type', 'appointmentMissed')
            ->count());
    }

    public function test_appointment_booked_duplicate_protection_still_uses_appointment_id(): void
    {
        $booking = $this->createBooking(['status' => DoctorBooking::STATUS_CONFIRMED]);
        $version = $this->publishDefinition(
            $this->triggerEndGraph('appointmentBooked'),
            'appointmentBooked',
            hospitalId: 12,
        );

        $listener = app(DispatchHospitalAutomationWorkflow::class);
        $listener->handle(new AppointmentBooked($booking));
        $listener->handle(new AppointmentBooked($booking));

        $this->assertSame(1, WorkflowExecution::query()
            ->where('workflow_id', $version->workflow_id)
            ->where('trigger_type', 'appointmentBooked')
            ->count());
    }

    public function test_engine_uses_the_event_appointment_not_another_booking(): void
    {
        $target = $this->createBooking(['patient_id' => 'patient-a']);
        $this->createBooking(['patient_id' => 'patient-a', 'hospital_id' => 12]);

        $version = $this->publishDefinition(
            $this->triggerEndGraph('appointmentRescheduled'),
            'appointmentRescheduled',
            hospitalId: 12,
        );

        $this->attachEmptyRelations($target);
        app(AutomationEngine::class)->handle('appointmentRescheduled', [
            'appointment' => $target,
            'appointment_id' => $target->id,
            'hospital_id' => $target->hospital_id,
            'event_occurrence_id' => 'occ-target',
        ]);

        $execution = WorkflowExecution::query()
            ->where('workflow_id', $version->workflow_id)
            ->first();

        $this->assertSame($target->id, $execution->context['appointment_id'] ?? null);
        $this->assertNotSame('patient-a', $execution->context['appointment_id'] ?? null);
    }

    public function test_event_context_is_built_from_the_same_rescheduled_booking(): void
    {
        $booking = $this->createBooking([
            'booking_date' => '2026-09-28',
            'required_time_slots' => ['3:00 PM'],
        ]);
        $event = new AppointmentRescheduled($booking, '2026-09-25');
        $context = app(AutomationContextBuilder::class)->merge($event->payload());

        $this->assertSame($booking->id, $context['appointment_id']);
        $this->assertSame($booking, $context['appointment']);
        $this->assertSame(12, (int) $context['hospital_id']);
        $this->assertSame('patient-reschedule', $context['patient_id']);
        $this->assertSame('doc-1', $context['doctor_id']);
        $this->assertSame('2026-09-28', $context['appointment_date']);
        $this->assertSame('3:00 PM', $context['appointment_time']);
        $this->assertArrayHasKey('organization_id', $context);
        $this->assertArrayHasKey('patient', $context);
        $this->assertArrayHasKey('doctor', $context);
        $this->assertSame($event->occurrenceId, $context['event_occurrence_id']);
    }

    public function test_real_reschedule_processes_queued_listener_into_workflow_execution(): void
    {
        $this->ensureHospitalsTable();
        $booking = $this->createBooking();
        $version = $this->publishDefinition(
            $this->triggerEndGraph('appointmentRescheduled'),
            'appointmentRescheduled',
            hospitalId: 12,
        );

        $this->service()->reschedule($booking, '2026-09-30', '16:00');

        Queue::assertPushed(CallQueuedListener::class, function (CallQueuedListener $job) {
            return $job->class === DispatchHospitalAutomationWorkflow::class;
        });

        $queued = Queue::listenersPushed(DispatchHospitalAutomationWorkflow::class)->first();
        $this->assertNotNull($queued);
        $event = $queued->data[0] ?? null;
        $this->assertInstanceOf(AppointmentRescheduled::class, $event);
        $this->assertNotEmpty($event->occurrenceId);
        $this->assertSame($booking->id, $event->appointment->id);
        $this->assertSame(12, (int) $event->payload()['hospital_id']);

        $event->appointment->unsetRelation('hospital');
        $event->appointment->setRelation('doctor', null);
        $event->appointment->setRelation('patient', null);
        $event->appointment->setRelation('department', null);

        app(DispatchHospitalAutomationWorkflow::class)->handle($event);

        $execution = WorkflowExecution::query()
            ->where('workflow_id', $version->workflow_id)
            ->where('trigger_type', 'appointmentRescheduled')
            ->first();

        $this->assertNotNull($execution);
        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->status);
        $this->assertSame($booking->id, $execution->context['appointment_id'] ?? null);
        $this->assertSame(12, (int) ($execution->context['hospital_id'] ?? 0));
        $this->assertSame(7, (int) ($execution->context['organization_id'] ?? 0));
        $this->assertSame($event->occurrenceId, $execution->context['event_occurrence_id'] ?? null);
    }

    public function test_html_time_and_date_round_trip_without_timezone_shift(): void
    {
        $this->assertSame('Asia/Kolkata', config('app.timezone'));
        $booking = $this->createBooking();

        $updated = $this->service()->reschedule($booking, '2026-10-01', '09:30');

        $this->assertSame('2026-10-01', $updated->booking_date->toDateString());
        $this->assertSame('9:30 AM', $updated->required_time_slots[0]);
        $this->assertSame('09:30', Carbon::parse((string) $updated->required_time_slots[0])->format('H:i'));
        $this->assertSame('Oct 01, 2026', $updated->booking_date->format('M d, Y'));
    }

    public function test_admin_reschedule_livewire_writes_through_the_service(): void
    {
        $booking = $this->createBooking();

        Livewire::test(AdminReschedule::class)
            ->call('openRescheduleModal', $booking->id)
            ->assertSet('patientName', 'Pat Reschedule')
            ->assertSet('currentDateLabel', 'Sep 25, 2026')
            ->assertSet('currentTimeLabel', '10:00 AM')
            ->assertSet('newDate', '2026-09-25')
            ->assertSet('newTime', '10:00')
            ->set('newDate', '2026-09-28')
            ->set('newTime', '15:00')
            ->call('rescheduleAppointment')
            ->assertDispatched('refreshDoctorBookings')
            ->assertDispatched('toast');

        $fresh = $booking->fresh();
        $this->assertSame('2026-09-28', $fresh->booking_date->toDateString());
        $this->assertSame('3:00 PM', $fresh->required_time_slots[0]);
        $this->assertSame(DoctorBooking::STATUS_CONFIRMED, $fresh->status);
    }

    public function test_role_action_menus_keep_existing_items(): void
    {
        $admin = file_get_contents(resource_path('views/livewire/admin/doctor-booking/index.blade.php'));
        $hospital = file_get_contents(resource_path('views/livewire/hospital-admin/bookings/doctor-booking.blade.php'));
        $receptionist = file_get_contents(resource_path('views/livewire/receptionist-admin/bookings/doctor-booking.blade.php'));

        foreach ([$admin, $hospital, $receptionist] as $menu) {
            $this->assertStringContainsString('openRescheduleModal', $menu);
            $this->assertStringContainsString('Reschedule', $menu);
            $this->assertStringContainsString('Delete', $menu);
            $this->assertStringContainsString('Update Status', $menu);
            $this->assertStringContainsString('View', $menu);
        }

        $this->assertStringContainsString('editDoctorBooking', $admin);
        $this->assertStringContainsString('> Edit', $admin);
        $this->assertStringNotContainsString('editDoctorBooking', $hospital);
        $this->assertStringNotContainsString('editDoctorBooking', $receptionist);
        $this->assertStringNotContainsString('> Edit', $hospital);
        $this->assertStringNotContainsString('> Edit', $receptionist);
    }

    protected function service(): DoctorBookingStatusService
    {
        return app(DoctorBookingStatusService::class);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createBooking(array $attributes = []): DoctorBooking
    {
        $booking = new DoctorBooking;
        $booking->forceFill(array_merge([
            'name' => 'Pat Reschedule',
            'hospital_id' => 12,
            'doctor_id' => 'doc-1',
            'patient_id' => 'patient-reschedule',
            'booking_date' => '2026-09-25',
            'required_time_slots' => ['10:00 AM'],
            'status' => DoctorBooking::STATUS_CONFIRMED,
            'appointment_status' => DoctorBooking::APPOINTMENT_STATUS_NEW,
        ], $attributes));
        DoctorBooking::withoutEvents(fn () => $booking->save());
        $this->attachEmptyRelations($booking);

        if (Schema::hasTable('persons') && filled($booking->patient_id)) {
            if (! DB::table('persons')->where('id', $booking->patient_id)->exists()) {
                DB::table('persons')->insert([
                    'id' => $booking->patient_id,
                    'first_name' => 'Pat',
                    'last_name' => 'Reschedule',
                    'mobile' => $booking->mobile_number ?? '9999999999',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return $booking;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rescheduleWaitConditionGraph(): array
    {
        return [
            'builderVersion' => '1',
            'nodes' => [
                $this->node('t1', 'appointmentRescheduled'),
                $this->node('push1', 'sendPush', [
                    'title' => 'Rescheduled',
                    'body' => 'Your appointment was rescheduled.',
                    'recipient' => 'patient',
                ]),
                $this->node('w1', 'wait', [
                    'waitType' => 'duration',
                    'amount' => 30,
                    'unit' => 'minutes',
                ]),
                $this->node('c1', 'condition', [
                    'expression' => 'appointment.status == "confirmed"',
                ]),
                $this->node('wa1', 'sendWhatsApp', [
                    'recipient' => 'patient',
                    'message' => 'Confirmed after reschedule',
                ]),
                $this->node('e1', 'end'),
                $this->node('e2', 'end'),
            ],
            'edges' => [
                $this->edge('e1', 't1', 'push1'),
                $this->edge('e2', 'push1', 'w1'),
                $this->edge('e3', 'w1', 'c1'),
                $this->edge('e4', 'c1', 'wa1', 'true'),
                $this->edge('e5', 'c1', 'e2', 'false'),
                $this->edge('e6', 'wa1', 'e1'),
            ],
        ];
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
            new WorkflowContext('appointmentRescheduled', $enriched)
        );
    }

    protected function attachEmptyRelations(DoctorBooking $booking): void
    {
        $booking->setRelation('doctor', null);
        $booking->setRelation('hospital', null);
        $booking->setRelation('patient', null);
        $booking->setRelation('department', null);
    }

    protected function ensureDoctorBookingsTable(): void
    {
        if (! Schema::hasTable('doctors')) {
            Schema::create('doctors', function ($table) {
                $table->string('id')->primary();
                $table->string('name')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('hospitals')) {
            Schema::create('hospitals', function ($table) {
                $table->id();
                $table->unsignedBigInteger('organization_id')->nullable();
                $table->string('name')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('persons')) {
            Schema::create('persons', function ($table) {
                $table->string('id')->primary();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('mobile')->nullable();
                $table->string('email')->nullable();
                $table->timestamps();
            });
        }

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
            $table->boolean('is_follow_up')->nullable();
            $table->boolean('is_online_payment')->nullable();
            $table->string('payment_status')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('mobile_number')->nullable();
            $table->string('member_id')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });
    }

    protected function ensureHospitalsTable(): void
    {
        $this->ensureDoctorBookingsTable();

        if (! DB::table('hospitals')->where('id', 12)->exists()) {
            DB::table('hospitals')->insert([
                'id' => 12,
                'organization_id' => 7,
                'name' => 'Reschedule Hospital',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
