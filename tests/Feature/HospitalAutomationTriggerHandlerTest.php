<?php

namespace Tests\Feature;

use App\Models\DoctorBooking;
use App\Models\Hospital;
use App\Models\Persons;
use App\Models\Prescription;
use App\Modules\Automation\Events\AnniversaryReached;
use App\Modules\Automation\Events\AppointmentBooked;
use App\Modules\Automation\Events\AppointmentCancelled;
use App\Modules\Automation\Events\AppointmentCompleted;
use App\Modules\Automation\Events\BirthdayReached;
use App\Modules\Automation\Events\PatientRegistered;
use App\Modules\Automation\Events\ScheduledEvent;
use App\Modules\Automation\Listeners\AppointmentBookedListener;
use App\Modules\Automation\Listeners\DispatchHospitalAutomationWorkflow;
use App\Modules\Automation\Engine\AutomationEngine;
use App\Modules\Automation\TriggerHandlers\HospitalAutomationTriggerService;
use App\Modules\MedicineReminder\Events\PrescriptionCreated;
use App\Modules\MedicineReminder\Listeners\CreateMedicineReminderSchedules;
use App\Modules\MedicineReminder\Models\MedicineReminderSchedule;
use App\Modules\Workflow\Enums\WorkflowStatus;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Models\WorkflowVersion;
use App\Modules\Workflow\Repositories\WorkflowRepository;
use App\Modules\Workflow\Services\Bridge\WorkflowExecutionBridge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class HospitalAutomationTriggerHandlerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    protected function migrateFreshUsing(): array
    {
        return [
            '--path' => [
                'database/migrations/2025_12_10_111601_create_organizations_table.php',
                'database/migrations/2026_02_14_173508_create_user_devices_table.php',
                'database/migrations/2026_07_21_100000_create_workflows_table.php',
                'database/migrations/2026_07_21_100001_create_workflow_versions_table.php',
                'database/migrations/2026_07_21_100002_create_workflow_executions_table.php',
                'database/migrations/2026_07_21_100003_create_communication_logs_table.php',
                'database/migrations/2026_07_21_130000_add_draft_version_id_to_workflows_table.php',
            ],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasColumn('workflows', 'hospital_id')) {
            Schema::table('workflows', function ($table) {
                $table->unsignedBigInteger('hospital_id')->nullable();
            });
        }

        $this->seedOrganizations();
        $this->ensureBookingStubTables();
    }

    protected function seedOrganizations(): void
    {
        foreach ([1, 2] as $organizationId) {
            DB::table('organizations')->insertOrIgnore([
                'id' => $organizationId,
                'org_name' => 'Org '.$organizationId,
                'org_city' => 'City',
                'org_address' => 'Address',
                'org_logo' => 'logo.png',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    protected function ensureBookingStubTables(): void
    {
        if (! Schema::hasTable('hospitals')) {
            Schema::create('hospitals', function ($table) {
                $table->id();
                $table->unsignedBigInteger('organization_id')->nullable();
                $table->string('name')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('doctors')) {
            Schema::create('doctors', function ($table) {
                $table->id();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('persons')) {
            Schema::create('persons', function ($table) {
                $table->string('id')->primary();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('specialities_masters')) {
            Schema::create('specialities_masters', function ($table) {
                $table->id();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('doctor_bookings')) {
            Schema::create('doctor_bookings', function ($table) {
                $table->id();
                $table->unsignedBigInteger('hospital_id')->nullable();
                $table->unsignedBigInteger('doctor_id')->nullable();
                $table->string('patient_id')->nullable();
                $table->unsignedBigInteger('department_id')->nullable();
                $table->string('status')->nullable();
                $table->timestamps();
            });
        }
    }

    public function test_appointment_booked_handler_starts_published_workflow(): void
    {
        $workflow = $this->publishWorkflow('appointmentBooked', 1, 5);

        $this->trigger()->dispatch('appointmentBooked', $this->bookingPayload(1, 5, 101));

        $this->assertSame(1, WorkflowExecution::query()->where('workflow_id', $workflow->id)->count());
    }

    public function test_appointment_completed_handler_starts_published_workflow(): void
    {
        $workflow = $this->publishWorkflow('appointmentCompleted', 1, 5);

        $this->trigger()->dispatch('appointmentCompleted', $this->bookingPayload(1, 5, 102));

        $this->assertSame(1, WorkflowExecution::query()->where('workflow_id', $workflow->id)->count());
        $this->assertSame('appointmentCompleted', WorkflowExecution::query()->value('trigger_type'));
    }

    public function test_appointment_cancelled_handler_starts_published_workflow(): void
    {
        $workflow = $this->publishWorkflow('appointmentCancelled', 1, 5);

        $this->trigger()->dispatch('appointmentCancelled', $this->bookingPayload(1, 5, 103));

        $this->assertSame(1, WorkflowExecution::query()->where('workflow_id', $workflow->id)->count());
        $this->assertSame('appointmentCancelled', WorkflowExecution::query()->value('trigger_type'));
    }

    public function test_patient_registered_handler_uses_payload_organization_and_hospital(): void
    {
        $workflow = $this->publishWorkflow('patientRegistered', 1, 5);

        $this->trigger()->dispatch('patientRegistered', $this->patientPayload(1, 5));

        $this->assertSame(1, WorkflowExecution::query()->where('workflow_id', $workflow->id)->count());
    }

    public function test_prescription_added_handler_scopes_to_prescription_hospital(): void
    {
        $matching = $this->publishWorkflow('prescriptionAdded', 1, 5, 'Rx H5');
        $this->publishWorkflow('prescriptionAdded', 1, 9, 'Rx H9');

        $this->trigger()->dispatch('prescriptionAdded', [
            'prescription_id' => 44,
            'hospital_id' => 5,
            'organization_id' => 1,
        ]);

        $this->assertSame(1, WorkflowExecution::query()->count());
        $this->assertSame($matching->id, WorkflowExecution::query()->value('workflow_id'));
    }

    public function test_medicine_reminder_due_executes_only_the_linked_workflow_via_bridge(): void
    {
        $linked = $this->publishWorkflow('medicineReminderDue', 1, 5, 'Linked reminder');
        $this->publishWorkflow('medicineReminderDue', 1, 5, 'Other reminder');

        $schedule = new MedicineReminderSchedule;
        $schedule->forceFill([
            'id' => 77,
            'workflow_id' => $linked->id,
            'prescription_id' => 12,
            'patient_id' => 'person-1',
        ]);
        $schedule->setRelation('workflow', $linked->fresh(['currentVersion']));

        $this->app->make(WorkflowExecutionBridge::class)->executeMedicineReminderSchedule($schedule);

        $this->assertSame(1, WorkflowExecution::query()->count());
        $this->assertSame($linked->id, WorkflowExecution::query()->value('workflow_id'));
        $this->assertSame('medicineReminderDue', WorkflowExecution::query()->value('trigger_type'));
    }

    public function test_birthday_handler_starts_published_workflow(): void
    {
        $workflow = $this->publishWorkflow('birthday', 1, 5);

        $this->trigger()->dispatch('birthday', $this->patientPayload(1, 5));

        $this->assertSame(1, WorkflowExecution::query()->where('workflow_id', $workflow->id)->count());
    }

    public function test_anniversary_reached_handler_starts_published_workflow(): void
    {
        $workflow = $this->publishWorkflow('anniversaryReached', 1, 5);

        $this->trigger()->dispatch('anniversaryReached', array_merge(
            $this->patientPayload(1, 5),
            ['anniversaryType' => 'womens_day']
        ));

        $this->assertSame(1, WorkflowExecution::query()->where('workflow_id', $workflow->id)->count());
    }

    public function test_scheduled_event_handler_starts_published_workflow(): void
    {
        $workflow = $this->publishWorkflow('scheduledEvent', 1, 5);

        $this->trigger()->dispatch('scheduledEvent', array_merge(
            $this->patientPayload(1, 5),
            ['reason' => 'inactive_30']
        ));

        $this->assertSame(1, WorkflowExecution::query()->where('workflow_id', $workflow->id)->count());
        $this->assertArrayNotHasKey('appointment', WorkflowExecution::query()->first()->context ?? []);
    }

    public function test_generic_listener_routes_domain_events_through_trigger_service_once(): void
    {
        $engine = Mockery::mock(AutomationEngine::class);
        $engine->shouldReceive('handle')->once()->with('birthday', Mockery::type('array'));

        $listener = new DispatchHospitalAutomationWorkflow(
            new HospitalAutomationTriggerService($engine)
        );

        $listener->handle(new BirthdayReached($this->makePerson(), 1, 5));
    }

    public function test_organization_isolation(): void
    {
        $this->publishWorkflow('appointmentCompleted', 2, 5);

        $this->trigger()->dispatch('appointmentCompleted', $this->bookingPayload(1, 5, 201));

        $this->assertSame(0, WorkflowExecution::query()->count());
    }

    public function test_hospital_isolation(): void
    {
        $this->publishWorkflow('appointmentCompleted', 1, 9);

        $this->trigger()->dispatch('appointmentCompleted', $this->bookingPayload(1, 5, 202));

        $this->assertSame(0, WorkflowExecution::query()->count());
    }

    public function test_two_published_workflows_same_trigger_and_hospital_execute_exactly_twice(): void
    {
        $workflowA = $this->publishWorkflow('appointmentBooked', 1, 5, 'Workflow A');
        $workflowB = $this->publishWorkflow('appointmentBooked', 1, 5, 'Workflow B');

        $engineCalls = 0;
        $realEngine = $this->app->make(AutomationEngine::class);
        $engine = Mockery::mock(AutomationEngine::class);
        $engine->shouldReceive('handle')
            ->once()
            ->andReturnUsing(function (string $type, array $payload) use ($realEngine, &$engineCalls) {
                $engineCalls++;
                $realEngine->handle($type, $payload);
            });

        (new HospitalAutomationTriggerService($engine))
            ->dispatch('appointmentBooked', $this->bookingPayload(1, 5, 301));

        $this->assertSame(1, $engineCalls);

        $this->assertSame(2, WorkflowExecution::query()->count());
        $this->assertSame(1, WorkflowExecution::query()->where('workflow_id', $workflowA->id)->count());
        $this->assertSame(1, WorkflowExecution::query()->where('workflow_id', $workflowB->id)->count());
    }

    public function test_unpublished_workflow_is_excluded(): void
    {
        $this->createDraftWorkflow('appointmentBooked', 1, 5);

        $this->trigger()->dispatch('appointmentBooked', $this->bookingPayload(1, 5, 302));

        $this->assertSame(0, WorkflowExecution::query()->count());
    }

    public function test_missing_hospital_on_appointment_booked_does_not_execute(): void
    {
        $this->publishWorkflow('appointmentBooked', 1, 5);

        $this->trigger()->dispatch('appointmentBooked', [
            'appointment' => (object) ['id' => 303, 'hospital_id' => null],
            'appointment_id' => 303,
            'organization_id' => 1,
        ]);

        $this->assertSame(0, WorkflowExecution::query()->count());
    }

    public function test_appointment_booked_listener_aborts_when_hospital_id_missing(): void
    {
        $this->insertBooking(401, null, DoctorBooking::STATUS_CONFIRMED);

        $trigger = Mockery::mock(HospitalAutomationTriggerService::class);
        $trigger->shouldNotReceive('dispatch');

        $booking = new DoctorBooking;
        $booking->forceFill(['id' => 401, 'hospital_id' => null, 'status' => DoctorBooking::STATUS_CONFIRMED]);

        (new AppointmentBookedListener($trigger))->handle(new AppointmentBooked($booking));
    }

    public function test_appointment_booked_listener_dispatches_trigger_service_once(): void
    {
        $this->insertBooking(402, 5, DoctorBooking::STATUS_CONFIRMED);

        $trigger = Mockery::mock(HospitalAutomationTriggerService::class);
        $trigger->shouldReceive('dispatch')
            ->once()
            ->with('appointmentBooked', Mockery::on(function (array $payload) {
                return ($payload['appointment'] instanceof DoctorBooking)
                    && (int) $payload['appointment']->id === 402
                    && (int) $payload['appointment']->hospital_id === 5;
            }));

        $booking = new DoctorBooking;
        $booking->forceFill(['id' => 402, 'hospital_id' => 5, 'status' => DoctorBooking::STATUS_CONFIRMED]);

        (new AppointmentBookedListener($trigger))->handle(new AppointmentBooked($booking));
    }

    public function test_already_started_prevents_duplicate_execution(): void
    {
        $workflow = $this->publishWorkflow('appointmentBooked', 1, 5);
        $payload = $this->bookingPayload(1, 5, 404);

        $this->trigger()->dispatch('appointmentBooked', $payload);
        $this->trigger()->dispatch('appointmentBooked', $payload);

        $this->assertSame(1, WorkflowExecution::query()->where('workflow_id', $workflow->id)->count());
    }

    public function test_prescription_created_listener_passes_hospital_id_to_trigger_service(): void
    {
        $hospital = new Hospital;
        $hospital->forceFill(['id' => 5, 'organization_id' => 1]);

        $prescription = new Prescription;
        $prescription->forceFill(['id' => 55, 'hospital_id' => 5]);
        $prescription->setRelation('hospital', $hospital);

        $trigger = Mockery::mock(HospitalAutomationTriggerService::class);
        $trigger->shouldReceive('dispatch')
            ->once()
            ->with('prescriptionAdded', Mockery::on(function (array $payload) {
                return ($payload['hospital_id'] ?? null) === 5
                    && ($payload['organization_id'] ?? null) === 1
                    && ($payload['prescription'] instanceof Prescription);
            }));

        (new CreateMedicineReminderSchedules($trigger))->handle(new PrescriptionCreated($prescription));
    }

    public function test_trigger_service_copies_hospital_id_from_prescription_model(): void
    {
        $engine = Mockery::mock(AutomationEngine::class);
        $engine->shouldReceive('handle')
            ->once()
            ->with('prescriptionAdded', Mockery::on(function (array $payload) {
                return ($payload['hospital_id'] ?? null) === 8;
            }));

        $prescription = new Prescription;
        $prescription->forceFill(['id' => 9, 'hospital_id' => 8]);

        (new HospitalAutomationTriggerService($engine))->dispatch('prescriptionAdded', [
            'prescription' => $prescription,
        ]);
    }

    public function test_trigger_service_normalizes_trigger_alias_and_calls_engine_once(): void
    {
        $engine = Mockery::mock(AutomationEngine::class);
        $engine->shouldReceive('handle')
            ->once()
            ->with('appointmentBooked', Mockery::type('array'));

        (new HospitalAutomationTriggerService($engine))->dispatch('appointment_booked', [
            'hospital_id' => 5,
            'organization_id' => 1,
        ]);
    }

    public function test_completed_and_cancelled_events_use_booking_hospital_not_another_hospital_workflow(): void
    {
        $completed = $this->publishWorkflow('appointmentCompleted', 1, 5);
        $this->publishWorkflow('appointmentCancelled', 1, 5);

        $listener = new DispatchHospitalAutomationWorkflow($this->trigger());

        $booking = new DoctorBooking;
        $booking->forceFill(['id' => 501, 'hospital_id' => 5]);
        $hospital = new Hospital;
        $hospital->forceFill(['id' => 5, 'organization_id' => 1]);
        $booking->setRelation('hospital', $hospital);

        $listener->handle(new AppointmentCompleted($booking));

        $this->assertSame(1, WorkflowExecution::query()->count());
        $this->assertSame($completed->id, WorkflowExecution::query()->value('workflow_id'));

        WorkflowExecution::query()->delete();

        $listener->handle(new AppointmentCancelled($booking));

        $this->assertSame(1, WorkflowExecution::query()->count());
        $this->assertSame('appointmentCancelled', WorkflowExecution::query()->value('trigger_type'));
    }

    public function test_patient_registered_and_scheduled_event_payloads_keep_event_hospital(): void
    {
        $registered = $this->publishWorkflow('patientRegistered', 1, 5);
        $scheduled = $this->publishWorkflow('scheduledEvent', 1, 5);
        $this->publishWorkflow('patientRegistered', 1, 9);

        $person = $this->makePerson();
        $generic = new DispatchHospitalAutomationWorkflow($this->trigger());

        $generic->handle(new PatientRegistered($person, 1, 5));
        $this->assertSame(1, WorkflowExecution::query()->where('workflow_id', $registered->id)->count());

        $generic->handle(new ScheduledEvent($person, [
            'organization_id' => 1,
            'hospital_id' => 5,
        ]));
        $this->assertSame(1, WorkflowExecution::query()->where('workflow_id', $scheduled->id)->count());
    }

    public function test_anniversary_event_through_generic_listener(): void
    {
        $workflow = $this->publishWorkflow('anniversaryReached', 1, 5);

        (new DispatchHospitalAutomationWorkflow($this->trigger()))->handle(
            new AnniversaryReached($this->makePerson(), 'womens_day', 1, 5)
        );

        $this->assertSame(1, WorkflowExecution::query()->where('workflow_id', $workflow->id)->count());
    }

    public function test_appointment_booked_listener_constructor_uses_trigger_service(): void
    {
        $params = (new \ReflectionClass(AppointmentBookedListener::class))
            ->getConstructor()
            ?->getParameters() ?? [];

        $this->assertNotEmpty($params);
        $this->assertSame(HospitalAutomationTriggerService::class, $params[0]->getType()?->getName());
    }

    protected function trigger(): HospitalAutomationTriggerService
    {
        return $this->app->make(HospitalAutomationTriggerService::class);
    }

    /**
     * @return array<string, mixed>
     */
    protected function bookingPayload(int $organizationId, int $hospitalId, int $appointmentId): array
    {
        return [
            'appointment' => (object) ['id' => $appointmentId, 'hospital_id' => $hospitalId],
            'appointment_id' => $appointmentId,
            'hospital_id' => $hospitalId,
            'organization_id' => $organizationId,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function patientPayload(int $organizationId, int $hospitalId): array
    {
        return [
            'patient' => $this->makePerson(),
            'patient_id' => 'person-1',
            'organization_id' => $organizationId,
            'hospital_id' => $hospitalId,
        ];
    }

    protected function makePerson(): Persons
    {
        $person = new Persons;
        $person->forceFill([
            'id' => 'person-1',
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'is_primary' => true,
        ]);

        return $person;
    }

    protected function insertBooking(int $id, ?int $hospitalId, string $status): void
    {
        DB::table('doctor_bookings')->insert([
            'id' => $id,
            'hospital_id' => $hospitalId,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function publishWorkflow(
        string $triggerType,
        int $organizationId,
        ?int $hospitalId,
        string $name = 'Published workflow'
    ): Workflow {
        $workflow = Workflow::query()->create([
            'name' => $name,
            'status' => WorkflowStatus::Draft->value,
            'trigger_type' => $triggerType,
            'organization_id' => $organizationId,
            'hospital_id' => $hospitalId,
        ]);

        $this->app->make(WorkflowRepository::class)
            ->publishVersion($workflow, $this->definition($triggerType));

        return $workflow->fresh(['currentVersion']);
    }

    protected function createDraftWorkflow(string $triggerType, int $organizationId, int $hospitalId): Workflow
    {
        $workflow = Workflow::query()->create([
            'name' => 'Draft workflow',
            'status' => WorkflowStatus::Draft->value,
            'trigger_type' => $triggerType,
            'organization_id' => $organizationId,
            'hospital_id' => $hospitalId,
        ]);

        $draft = WorkflowVersion::query()->create([
            'workflow_id' => $workflow->id,
            'version_number' => 0,
            'definition' => $this->definition($triggerType),
            'compiled_graph' => null,
            'status' => 'draft',
        ]);

        $workflow->update(['draft_version_id' => $draft->id]);

        return $workflow->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    protected function definition(string $triggerType): array
    {
        return [
            'builderVersion' => '1',
            'reactFlowVersion' => '12.x',
            'nodes' => [
                [
                    'id' => 't1',
                    'type' => 'workflow',
                    'position' => ['x' => 0, 'y' => 0],
                    'data' => [
                        'category' => 'triggers',
                        'nodeType' => $triggerType,
                        'label' => $triggerType,
                    ],
                ],
                [
                    'id' => 'e1',
                    'type' => 'workflow',
                    'position' => ['x' => 400, 'y' => 0],
                    'data' => ['nodeType' => 'end'],
                ],
            ],
            'edges' => [
                ['id' => 'e-t-e', 'source' => 't1', 'target' => 'e1'],
            ],
        ];
    }
}
