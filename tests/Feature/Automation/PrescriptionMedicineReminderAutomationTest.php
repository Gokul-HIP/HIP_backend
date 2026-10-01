<?php

namespace Tests\Feature\Automation;

use App\Models\DoctorBooking;
use App\Models\Hospital;
use App\Models\Prescription;
use App\Modules\Automation\Engine\AutomationContextBuilder;
use App\Modules\Automation\Engine\AutomationEngine;
use App\Modules\Automation\Events\MedicineReminderDue;
use App\Modules\Automation\Events\PrescriptionAdded as PrescriptionAddedAutomation;
use App\Modules\MedicineReminder\Enums\ScheduleStatus;
use App\Modules\MedicineReminder\Events\PrescriptionCreated;
use App\Modules\MedicineReminder\Models\MedicineReminderSchedule;
use App\Modules\MedicineReminder\Services\MedicineReminderExecutionService;
use App\Modules\MedicineReminder\Services\MedicineReminderScheduleService;
use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Services\Builder\WorkflowPublishService;
use App\Modules\Workflow\Services\Runtime\WorkflowExecutor;
use App\Services\DoctorBookingStatusService;
use App\Services\NotificationService;
use App\Services\PrescriptionService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\Support\WorkflowAutomationTestCase;

class PrescriptionMedicineReminderAutomationTest extends WorkflowAutomationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureDomainTables();

        config([
            'medicine_reminder.when_to_take_times' => [
                'morning' => '07:15',
                'afternoon' => '13:45',
                'evening' => '19:30',
                'night' => '21:00',
                'bedtime' => '21:00',
            ],
            'medicine_reminder.frequency_times' => [
                'once_daily' => ['08:05'],
                'twice_daily' => ['08:05', '20:05'],
                'thrice_daily' => ['08:05', '14:05', '20:05'],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2026-09-30 06:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_sent_prescription_dispatches_prescription_added_and_created(): void
    {
        Event::fake([PrescriptionAddedAutomation::class, PrescriptionCreated::class]);

        $prescription = $this->storePrescription(Prescription::STATUS_SENT, [
            ['name' => 'Amox', 'dosage' => '500mg', 'frequency' => 'Once Daily', 'duration' => '1 Day'],
        ]);

        Event::assertDispatched(PrescriptionAddedAutomation::class, function (PrescriptionAddedAutomation $event) use ($prescription) {
            return (int) $event->prescription->id === (int) $prescription->id
                && $event->occurrenceId === 'prescription-added:prescription:'.$prescription->id;
        });
        Event::assertDispatched(PrescriptionCreated::class);
        $this->assertSame('sent', $prescription->fresh()->status);
    }

    public function test_draft_prescription_does_not_dispatch_automation(): void
    {
        Event::fake([PrescriptionAddedAutomation::class, PrescriptionCreated::class]);

        $this->storePrescription(Prescription::STATUS_DRAFT, [
            ['name' => 'Amox', 'frequency' => 'Once Daily', 'duration' => '1 Day'],
        ]);

        Event::assertNotDispatched(PrescriptionAddedAutomation::class);
        Event::assertNotDispatched(PrescriptionCreated::class);
    }

    public function test_prescription_context_exposes_real_fields(): void
    {
        $hospital = Hospital::query()->create(['name' => 'Clinic A', 'organization_id' => 4]);
        $prescription = $this->insertPrescription([
            'hospital_id' => $hospital->id,
            'patient_id' => 'patient-77',
            'member_id' => 'member-77',
            'doctor_id' => 12,
            'status' => 'sent',
            'medications' => [[
                'prescription_item_id' => 41,
                'medicine_id' => 9,
                'name' => 'Metformin',
                'dosage' => '500mg',
                'frequency' => 'Twice Daily',
                'duration' => '5 Days',
                'quantity' => '10',
                'when_to_take' => 'Morning',
                'special_instruction' => 'After Food',
            ]],
        ]);

        $context = app(AutomationContextBuilder::class)->fromPrescription($prescription->fresh());

        $this->assertSame($prescription->id, $context['prescription_id']);
        $this->assertSame('sent', $context['prescription_status']);
        $this->assertSame('patient-77', $context['patient_id']);
        $this->assertSame('member-77', $context['member_id']);
        $this->assertSame(12, (int) $context['doctor_id']);
        $this->assertSame((int) $hospital->id, (int) $context['hospital_id']);
        $this->assertSame(4, (int) $context['organization_id']);
        $this->assertSame(41, $context['medications'][0]['prescription_item_id']);
        $this->assertSame(9, $context['medications'][0]['medicine_id']);
        $this->assertSame('Metformin', $context['medications'][0]['medicine_name']);
        $this->assertSame('After Food', $context['medications'][0]['special_instruction']);
        $this->assertSame('prescription-added:prescription:'.$prescription->id, $context['event_occurrence_id']);
        $this->assertArrayNotHasKey('invented_field', $context['medications'][0]);
    }

    public function test_schedules_created_from_prescription_data_without_workflow_node(): void
    {
        $this->assertSame(0, \App\Modules\Workflow\Models\Workflow::query()->count());

        $prescription = $this->insertPrescription([
            'patient_id' => 'patient-1',
            'medications' => [
                [
                    'prescription_item_id' => 5,
                    'medicine_id' => 101,
                    'name' => 'Drug A',
                    'frequency' => 'Once Daily',
                    'duration' => '2 Days',
                ],
                [
                    'prescription_item_id' => 6,
                    'medicine_id' => 202,
                    'name' => 'Drug B',
                    'frequency' => 'Twice Daily',
                    'duration' => '1 Day',
                ],
            ],
        ]);

        $rows = app(MedicineReminderScheduleService::class)->createFromPrescription($prescription);

        $this->assertCount(4, $rows);
        $this->assertTrue($rows->every(fn ($row) => $row->workflow_id === null));
        $this->assertTrue($rows->every(fn ($row) => $row->message_template === null));
        $this->assertSame([5, 6], $rows->pluck('prescription_item_id')->unique()->sort()->values()->all());
        $this->assertFalse($rows->contains(fn ($row) => str_contains((string) $row->message_template, 'reminder from')));
    }

    public function test_three_medication_1844_prescription_inserts_six_independent_schedule_rows(): void
    {
        config([
            'medicine_reminder.when_to_take_times' => [
                'morning' => '09:00',
                'afternoon' => '14:00',
                'evening' => '18:00',
                'night' => '21:00',
                'bedtime' => '21:00',
            ],
            'medicine_reminder.frequency_times' => [
                'once_daily' => ['09:00'],
                'twice_daily' => ['09:00', '21:00'],
            ],
        ]);

        $createdAt = Carbon::parse('2026-09-30 18:44:00');
        Carbon::setTestNow($createdAt);

        $prescription = $this->insertPrescription([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
            'medications' => [
                [
                    'medicine_id' => 39,
                    'name' => 'Amlodipine 250mg',
                    'frequency' => 'Twice Daily',
                    'duration' => '1 Day',
                    'when_to_take' => 'After Food, Afternoon, Morning',
                    'quantity' => 2,
                ],
                [
                    'medicine_id' => 22,
                    'name' => 'Aspirin 50mg',
                    'frequency' => 'Once Daily',
                    'duration' => '1 Day',
                    'when_to_take' => 'After Food',
                    'quantity' => 1,
                ],
                [
                    'medicine_id' => 10,
                    'name' => 'Calcium Carbonate 10mg',
                    'frequency' => 'Once Daily',
                    'duration' => '3 Days',
                    'when_to_take' => 'After Food, Night',
                    'quantity' => 3,
                ],
            ],
        ]);

        $rows = app(MedicineReminderScheduleService::class)->createFromPrescription($prescription->fresh());

        $this->assertCount(6, $rows);
        $this->assertTrue($rows->every(fn ($row) => $row->workflow_id === null));
        $this->assertTrue($rows->every(fn ($row) => $row->message_template === null));

        $byMedicine = $rows->groupBy(fn ($row) => (int) $row->medicine_id);
        $this->assertSame([
            '2026-10-01 09:00:00',
            '2026-10-01 14:00:00',
        ], $byMedicine->get(39)->pluck('scheduled_at')->map->format('Y-m-d H:i:s')->values()->all());
        $this->assertSame([
            '2026-10-01 09:00:00',
        ], $byMedicine->get(22)->pluck('scheduled_at')->map->format('Y-m-d H:i:s')->values()->all());
        $this->assertSame([
            '2026-09-30 21:00:00',
            '2026-10-01 21:00:00',
            '2026-10-02 21:00:00',
        ], $byMedicine->get(10)->pluck('scheduled_at')->map->format('Y-m-d H:i:s')->values()->all());
    }

    public function test_idempotent_schedule_insert_on_retry(): void
    {
        $prescription = $this->insertPrescription([
            'medications' => [[
                'prescription_item_id' => 1,
                'name' => 'Drug A',
                'frequency' => 'Once Daily',
                'duration' => '1 Day',
            ]],
        ]);

        $service = app(MedicineReminderScheduleService::class);
        $first = $service->createFromPrescription($prescription);
        $second = $service->createFromPrescription($prescription);

        $this->assertCount(1, $first);
        $this->assertSame(1, MedicineReminderSchedule::query()->where('prescription_id', $prescription->id)->count());
        $this->assertGreaterThanOrEqual(1, $second->count());
    }

    public function test_scheduling_failure_does_not_prevent_prescription_commit(): void
    {
        Event::fake([PrescriptionAddedAutomation::class]);

        $schedules = Mockery::mock(MedicineReminderScheduleService::class);
        $schedules->shouldReceive('createFromPrescription')->andThrow(new \RuntimeException('schedule boom'));
        $this->app->instance(MedicineReminderScheduleService::class, $schedules);

        $prescription = $this->storePrescription(Prescription::STATUS_SENT, [
            ['name' => 'Amox', 'frequency' => 'Once Daily', 'duration' => '1 Day'],
        ]);

        $this->assertNotNull($prescription->id);
        $this->assertSame('sent', $prescription->fresh()->status);
        Event::assertDispatched(PrescriptionAddedAutomation::class);
    }

    public function test_medicine_reminder_due_is_not_executed_inside_prescription_added_graph(): void
    {
        $version = $this->publishDefinition([
            'builderVersion' => '1',
            'nodes' => [
                $this->node('t1', 'prescriptionAdded'),
                $this->node('m1', 'medicineReminder'),
                $this->node('e1', 'end'),
            ],
            'edges' => [
                $this->edge('e1', 't1', 'm1'),
                $this->edge('e2', 'm1', 'e1'),
            ],
        ], 'prescriptionAdded');

        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'prescriptionAdded',
            $this->sampleAppointmentContext(['prescription_id' => 1])
        );

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertStringContainsString('start trigger', (string) $execution->fresh()->failure_reason);
    }

    public function test_due_schedule_emits_medicine_reminder_due_and_runs_hospital_workflow(): void
    {
        $hospital = Hospital::query()->create(['name' => 'H5', 'organization_id' => 1]);
        $this->publishDefinition(
            $this->linearGraph('medicineReminderDue', 'sendPush', [
                'title' => 'Take medicine',
                'body' => '{{medicine_name}} at {{when_to_take}}',
                'recipient' => 'patient',
            ]),
            'medicineReminderDue',
            (int) $hospital->id,
            1
        );

        $prescription = $this->insertPrescription([
            'hospital_id' => $hospital->id,
            'patient_id' => 'patient-1',
            'member_id' => 'member-1',
            'medications' => [[
                'prescription_item_id' => 0,
                'medicine_id' => 55,
                'name' => 'Paracetamol',
                'dosage' => '650mg',
                'frequency' => 'Once Daily',
                'duration' => '1 Day',
                'when_to_take' => 'Afternoon',
            ]],
        ]);

        $schedule = MedicineReminderSchedule::query()->create([
            'workflow_id' => null,
            'patient_id' => 'patient-1',
            'prescription_id' => $prescription->id,
            'prescription_item_id' => 0,
            'medicine_id' => 55,
            'scheduled_at' => Carbon::parse('2026-09-30 13:45:00'),
            'status' => ScheduleStatus::Pending->value,
            'message_template' => null,
        ]);

        app(MedicineReminderExecutionService::class)->execute($schedule->id);

        $this->assertSame(1, WorkflowExecution::query()->where('trigger_type', 'medicineReminderDue')->count());
        $this->assertSame(ScheduleStatus::Sent->value, $schedule->fresh()->status);
        $execution = WorkflowExecution::query()->first();
        $this->assertSame('medicine-reminder-due:schedule:'.$schedule->id, $execution->context['event_occurrence_id'] ?? null);
        $this->assertSame((int) $hospital->id, (int) ($execution->context['hospital_id'] ?? 0));
        $this->assertSame('patient-1', $execution->context['patient_id'] ?? null);
        $this->assertSame('Paracetamol', $execution->context['medicine_name'] ?? null);
        $this->assertNotEmpty($this->providerSends);
        $this->assertSame('push', $this->providerSends[0]['channel']);
        $this->assertStringContainsString('Paracetamol', $this->providerSends[0]['message']);
        $this->assertStringNotContainsString('reminder from {{hospital_name}}', $this->providerSends[0]['message']);
    }

    public function test_duplicate_medicine_reminder_due_occurrence_does_not_re_execute(): void
    {
        $hospital = Hospital::query()->create(['name' => 'H5', 'organization_id' => 1]);
        $this->publishDefinition(
            $this->triggerEndGraph('medicineReminderDue'),
            'medicineReminderDue',
            (int) $hospital->id,
            1
        );

        $payload = [
            'hospital_id' => $hospital->id,
            'organization_id' => 1,
            'event_occurrence_id' => 'medicine-reminder-due:schedule:9001',
            'schedule_id' => 9001,
        ];

        $engine = app(AutomationEngine::class);
        $engine->handle('medicineReminderDue', $payload);
        $engine->handle('medicineReminderDue', $payload);

        $this->assertSame(1, WorkflowExecution::query()->count());
    }

    public function test_regression_triggers_complete_linear_message_graphs(): void
    {
        foreach ([
            'paymentPending',
            'invoiceGenerated',
            'paymentReceived',
            'appointmentBooked',
            'labTestOrdered',
        ] as $trigger) {
            $this->providerSends = [];
            $version = $this->publishDefinition(
                $this->triggerEndGraph($trigger),
                $trigger
            );

            $execution = app(WorkflowExecutor::class)->start(
                $version,
                $trigger,
                $this->sampleAppointmentContext()
            );

            $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status, $trigger);
        }

        $this->providerSends = [];
        $whatsAppVersion = $this->publishDefinition(
            $this->linearGraph('appointmentBooked', 'sendWhatsApp', [
                'messageTemplate' => 'Hello {{patient_name}}',
                'recipient' => 'patient',
            ]),
            'appointmentBooked'
        );
        $whatsAppExecution = app(WorkflowExecutor::class)->start(
            $whatsAppVersion,
            'appointmentBooked',
            $this->sampleAppointmentContext()
        );
        $this->assertSame(WorkflowExecutionStatus::Completed->value, $whatsAppExecution->fresh()->status, (string) $whatsAppExecution->fresh()->failure_reason);
        $this->assertSame('whatsapp', $this->providerSends[0]['channel'] ?? null);
        $this->assertStringContainsString('Ada', $this->providerSends[0]['message'] ?? '');
    }

    public function test_publish_service_rejects_prescription_added_to_medicine_reminder_due(): void
    {
        $errors = app(WorkflowPublishService::class)->validate([
            'nodes' => [
                $this->node('t1', 'paymentReceived'),
                $this->node('m1', 'medicineReminderDue'),
                $this->node('e1', 'end'),
            ],
            'edges' => [
                $this->edge('e1', 't1', 'm1'),
                $this->edge('e2', 'm1', 'e1'),
            ],
        ]);

        $this->assertFalse($errors['valid']);
        $this->assertContains('trigger_not_start', array_column($errors['errors'], 'code'));
    }

    protected function storePrescription(string $status, array $medications): Prescription
    {
        $booking = $this->insertBooking();

        $notifications = Mockery::mock(NotificationService::class);
        $notifications->shouldReceive('notifyUser')->andReturnNull();
        $bookingStatus = Mockery::mock(DoctorBookingStatusService::class);

        $service = new PrescriptionService($notifications, $bookingStatus);

        return $service->store($booking, $medications, [], [], null, null, $status, false);
    }

    protected function insertBooking(): DoctorBooking
    {
        $hospital = Hospital::query()->create(['name' => 'Sunrise', 'organization_id' => 1]);

        $id = DoctorBooking::query()->insertGetId([
            'hospital_id' => $hospital->id,
            'branch_id' => $hospital->id,
            'doctor_id' => 1,
            'patient_id' => 'patient-1',
            'member_id' => 'member-1',
            'status' => 'confirmed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DoctorBooking::query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    protected function insertPrescription(array $attrs): Prescription
    {
        $prescription = new Prescription(array_merge([
            'doctor_id' => 1,
            'patient_id' => 'patient-1',
            'member_id' => 'member-1',
            'hospital_id' => Hospital::query()->value('id') ?: Hospital::query()->create(['name' => 'H', 'organization_id' => 1])->id,
            'status' => 'sent',
            'medications' => [],
        ], $attrs));

        Prescription::withoutEvents(fn () => $prescription->save());

        return $prescription->fresh();
    }

    protected function ensureDomainTables(): void
    {
        if (! Schema::hasTable('organizations')) {
            Schema::create('organizations', function (Blueprint $table) {
                $table->id();
                $table->string('org_name')->nullable();
                $table->string('org_city')->nullable();
                $table->string('org_address')->nullable();
                $table->string('org_logo')->nullable();
                $table->string('status')->nullable();
                $table->timestamps();
            });
        }

        if (\Illuminate\Support\Facades\DB::table('organizations')->where('id', 1)->doesntExist()) {
            \Illuminate\Support\Facades\DB::table('organizations')->insert([
                'id' => 1,
                'org_name' => 'Org 1',
                'org_city' => 'City',
                'org_address' => 'Address',
                'org_logo' => 'logo.png',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! Schema::hasTable('healthinpocket_users')) {
            Schema::create('healthinpocket_users', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('email')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('persons')) {
            Schema::create('persons', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('mobile')->nullable();
                $table->string('email')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('doctors')) {
            Schema::create('doctors', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('hospitals')) {
            Schema::create('hospitals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organization_id')->nullable();
                $table->string('name')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('doctor_bookings')) {
            Schema::create('doctor_bookings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('hospital_id')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->unsignedBigInteger('doctor_id')->nullable();
                $table->string('patient_id')->nullable();
                $table->string('member_id')->nullable();
                $table->string('status')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('prescriptions')) {
            Schema::create('prescriptions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('doctor_id')->nullable();
                $table->string('patient_id')->nullable();
                $table->string('member_id')->nullable();
                $table->unsignedBigInteger('hospital_id')->nullable();
                $table->unsignedBigInteger('doctor_booking_id')->nullable();
                $table->unsignedBigInteger('follow_up_booking_id')->nullable();
                $table->json('medications')->nullable();
                $table->json('lab_tests')->nullable();
                $table->json('document_ids')->nullable();
                $table->text('clinical_notes')->nullable();
                $table->date('follow_up_date')->nullable();
                $table->text('vitals')->nullable();
                $table->string('diagnosis')->nullable();
                $table->string('status')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('medicine_reminder_schedules')) {
            Schema::create('medicine_reminder_schedules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('workflow_id')->nullable();
                $table->string('patient_id')->nullable();
                $table->unsignedBigInteger('prescription_id');
                $table->unsignedInteger('prescription_item_id')->nullable();
                $table->unsignedBigInteger('medicine_id')->nullable();
                $table->dateTime('scheduled_at');
                $table->string('status')->default('pending');
                $table->unsignedInteger('retry_count')->default(0);
                $table->dateTime('next_retry_at')->nullable();
                $table->json('channels')->nullable();
                $table->text('message_template')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('medicine_reminder_logs')) {
            Schema::create('medicine_reminder_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('schedule_id');
                $table->string('status');
                $table->text('message')->nullable();
                $table->json('payload')->nullable();
                $table->unsignedInteger('execution_time')->nullable();
                $table->timestamps();
            });
        }
    }
}
