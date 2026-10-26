<?php

namespace Tests\Feature;

use App\Models\DiagnosticTestBooking;
use App\Models\DoctorBooking;
use App\Models\SecondOpinion;
use App\Modules\Automation\Engine\AutomationContextBuilder;
use App\Modules\Automation\Engine\AutomationEngine;
use App\Modules\Automation\Models\AppointmentReminderSchedule;
use App\Modules\Automation\Services\AppointmentReminderScheduler;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Models\WorkflowVersion;
use App\Modules\Workflow\NodeProcessorRegistry;
use App\Modules\Workflow\NodeProcessors\AbstractNodeProcessor;
use App\Modules\Workflow\DTO\ExecutionNode;
use App\Modules\Workflow\DTO\NodeExecutionResult;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\Services\Runtime\ActionDispatcher;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AppointmentReminderSchedulingTest extends TestCase
{
    use RefreshDatabase;

    protected function migrateFreshUsing(): array
    {
        return ['--path' => [
            'database/migrations/2025_12_10_111601_create_organizations_table.php',
            'database/migrations/2026_07_21_100000_create_workflows_table.php',
            'database/migrations/2026_07_21_100001_create_workflow_versions_table.php',
            'database/migrations/2026_07_21_100002_create_workflow_executions_table.php',
            'database/migrations/2026_07_21_100003_create_communication_logs_table.php',
            'database/migrations/2026_07_21_130000_add_draft_version_id_to_workflows_table.php',
            'database/migrations/2026_10_26_000001_create_appointment_reminder_schedules_table.php',
            'database/migrations/2026_10_26_000002_add_timezone_to_appointment_reminder_schedules_table.php',
        ]];
    }

    protected function setUp(): void
    {
        parent::setUp();
        if (! Schema::hasColumn('workflows', 'hospital_id')) {
            Schema::table('workflows', fn (Blueprint $table) => $table->unsignedBigInteger('hospital_id')->nullable());
        }
        Schema::create('hospitals', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('organization_id')->nullable(); $table->string('name')->nullable();
            $table->string('timezone')->nullable(); $table->timestamps();
        });
        Schema::create('doctor_bookings', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('hospital_id')->nullable(); $table->date('booking_date')->nullable();
            $table->json('required_time_slots')->nullable(); $table->string('status')->default('pending');
            $table->string('appointment_status')->nullable(); $table->string('created_by')->nullable(); $table->string('updated_by')->nullable(); $table->timestamps();
        });
        Schema::create('second_opinions', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('branch_id')->nullable(); $table->date('preferred_date')->nullable();
            $table->json('preferred_time_slots')->nullable(); $table->string('status')->default('pending'); $table->string('created_by')->nullable(); $table->string('updated_by')->nullable(); $table->timestamps();
        });
        Schema::create('diagnostic_test_bookings', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('branch_id')->nullable(); $table->unsignedBigInteger('package_id')->nullable();
            $table->date('booking_date')->nullable(); $table->json('required_time_slots')->nullable();
            $table->string('status')->default('pending'); $table->string('created_by')->nullable(); $table->string('updated_by')->nullable(); $table->timestamps();
        });
        foreach ([1, 2] as $orgId) {
            DB::table('organizations')->insert(['id' => $orgId, 'org_name' => "Org {$orgId}", 'org_city' => 'x', 'org_address' => 'x', 'org_logo' => 'x', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach ([[11, 1], [22, 2]] as [$id, $orgId]) {
            DB::table('hospitals')->insert(['id' => $id, 'organization_id' => $orgId, 'name' => "Hospital {$id}", 'timezone' => 'Asia/Kolkata', 'created_at' => now(), 'updated_at' => now()]);
        }
        $registry = $this->app->make(NodeProcessorRegistry::class);
        $registry->register(new class($this->app->make(ActionDispatcher::class)) extends AbstractNodeProcessor {
            public int $calls = 0;
            public function type(): string { return 'sendPush'; }
            protected function run(ExecutionNode $node, WorkflowExecution $execution, WorkflowContext $context): NodeExecutionResult
            {
                $this->calls++;
                return NodeExecutionResult::continue();
            }
        });
        Carbon::setTestNow(Carbon::parse('2026-10-20 08:00:00', 'Asia/Kolkata'));
        config(['app.timezone' => 'Asia/Kolkata']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Mockery::close();
        parent::tearDown();
    }

    public static function timingCases(): array
    {
        return [
            '24 hours' => ['before_24h', ['primary' => '2026-10-20 12:00:00', 'one_hour_before' => '2026-10-21 11:00:00']],
            '12 hours' => ['before_12h', ['primary' => '2026-10-21 00:00:00', 'one_hour_before' => '2026-10-21 11:00:00']],
            '6 hours' => ['before_6h', ['primary' => '2026-10-21 06:00:00', 'one_hour_before' => '2026-10-21 11:00:00']],
            '1 hour' => ['before_1h', ['primary' => '2026-10-21 11:00:00']],
            'at appointment' => ['on_time', ['one_hour_before' => '2026-10-21 11:00:00', 'primary' => '2026-10-21 12:00:00']],
        ];
    }

    #[DataProvider('timingCases')]
    public function test_doctor_booking_schedules_configured_primary_and_one_hour_stage(string $timing, array $expected): void
    {
        $workflow = $this->publishReminder(11, 1, $timing);
        $booking = $this->doctorBooking(11, '2026-10-21', '12:00 PM');

        app(AppointmentReminderScheduler::class)->synchronizeAndDispatch();

        $rows = AppointmentReminderSchedule::query()->where('booking_id', $booking->id)->where('workflow_id', $workflow->id)->get()->keyBy('stage');
        $actual = $rows->map(fn ($row) => $row->scheduled_at->timezone('Asia/Kolkata')->format('Y-m-d H:i:s'))->all();
        $this->assertEqualsCanonicalizing($expected, $actual);
        $this->assertCount(count($expected), $rows);
        $this->assertSame($rows->count(), $rows->pluck('identity')->unique()->count());
    }

    public function test_late_booking_does_not_receive_past_primary_but_keeps_future_stage(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-21 08:00:00', 'Asia/Kolkata'));
        $workflow = $this->publishReminder(11, 1, 'before_6h');
        $booking = $this->doctorBooking(11, '2026-10-21', '12:00 PM');

        app(AppointmentReminderScheduler::class)->synchronizeAndDispatch();

        $this->assertSame(['one_hour_before'], AppointmentReminderSchedule::query()->where('booking_id', $booking->id)->pluck('stage')->all());
        $this->assertSame('2026-10-21 11:00:00', AppointmentReminderSchedule::query()->where('booking_id', $booking->id)->firstOrFail()->scheduled_at->timezone('Asia/Kolkata')->format('Y-m-d H:i:s'));
        $this->assertSame(1, AppointmentReminderSchedule::query()->where('workflow_id', $workflow->id)->count());
    }

    public function test_hospital_timezone_is_used_for_slot_interpretation_and_utc_persistence(): void
    {
        DB::table('hospitals')->where('id', 11)->update(['timezone' => 'America/Los_Angeles']);
        $this->publishReminder(11, 1, 'before_24h');
        $booking = $this->doctorBooking(11, '2026-10-21', '12:00 PM');

        app(AppointmentReminderScheduler::class)->synchronizeAndDispatch();

        $schedule = AppointmentReminderSchedule::query()->where('booking_id', $booking->id)->where('stage', 'primary')->firstOrFail();
        $this->assertSame('America/Los_Angeles', $schedule->timezone);
        $this->assertSame('2026-10-20 19:00:00', $schedule->scheduled_at->utc()->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-21 19:00:00', $schedule->appointment_at->utc()->format('Y-m-d H:i:s'));
    }

    public function test_published_timing_change_cancels_old_pending_occurrences_and_creates_new_ones(): void
    {
        $workflow = $this->publishReminder(11, 1, 'before_24h');
        $booking = $this->doctorBooking(11, '2026-10-21', '12:00 PM');
        $scheduler = app(AppointmentReminderScheduler::class);
        $scheduler->synchronizeAndDispatch();
        $this->republishReminder($workflow, 'before_6h');

        $scheduler->synchronizeAndDispatch();

        $this->assertSame(2, AppointmentReminderSchedule::query()->where('booking_id', $booking->id)->where('status', 'cancelled')->count());
        $pending = AppointmentReminderSchedule::query()->where('booking_id', $booking->id)->where('status', 'pending')->orderBy('scheduled_at')->get();
        $this->assertSame(['2026-10-21 06:00:00', '2026-10-21 11:00:00'], $pending->map(fn ($s) => $s->scheduled_at->timezone('Asia/Kolkata')->format('Y-m-d H:i:s'))->all());
        $this->assertSame(1, $pending->pluck('workflow_version_id')->unique()->count());
    }

    public function test_execution_exception_keeps_schedule_retryable_with_the_same_occurrence_identity(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-20 11:00:00', 'Asia/Kolkata'));
        $this->publishReminder(11, 1, 'before_24h');
        $booking = $this->doctorBooking(11, '2026-10-21', '12:00 PM');
        $scheduler = app(AppointmentReminderScheduler::class);
        $scheduler->synchronizeAndDispatch();
        $schedule = AppointmentReminderSchedule::query()->where('booking_id', $booking->id)->where('stage', 'primary')->firstOrFail();

        $contextBuilder = Mockery::mock(AutomationContextBuilder::class);
        $contextBuilder->shouldReceive('fromAppointment')->twice()->andReturn(['appointment_id' => $booking->id, 'hospital_id' => 11, 'organization_id' => 1]);
        $this->app->instance(AutomationContextBuilder::class, $contextBuilder);
        $calls = 0;
        $engine = Mockery::mock(AutomationEngine::class);
        $engine->shouldReceive('executeWorkflow')->twice()->andReturnUsing(function () use (&$calls) {
            if (++$calls === 1) throw new \RuntimeException('temporary workflow failure');
            return new WorkflowExecution;
        });
        $this->app->instance(AutomationEngine::class, $engine);

        Carbon::setTestNow(Carbon::parse('2026-10-20 12:00:00', 'Asia/Kolkata'));
        $scheduler->synchronizeAndDispatch();
        $this->assertSame(1, $calls);
        $this->assertSame('pending', $schedule->fresh()->status);
        $scheduler->synchronizeAndDispatch();
        $this->assertSame('dispatched', $schedule->fresh()->status);
        $this->assertSame(2, $calls);
    }

    public function test_failed_execution_result_remains_pending_until_a_retry_succeeds(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-20 11:00:00', 'Asia/Kolkata'));
        $this->publishReminder(11, 1, 'before_24h');
        $booking = $this->doctorBooking(11, '2026-10-21', '12:00 PM');
        $scheduler = app(AppointmentReminderScheduler::class);
        $scheduler->synchronizeAndDispatch();
        $schedule = AppointmentReminderSchedule::query()->where('booking_id', $booking->id)->where('stage', 'primary')->firstOrFail();
        $contextBuilder = Mockery::mock(AutomationContextBuilder::class);
        $contextBuilder->shouldReceive('fromAppointment')->twice()->andReturn(['appointment_id' => $booking->id, 'hospital_id' => 11, 'organization_id' => 1]);
        $this->app->instance(AutomationContextBuilder::class, $contextBuilder);
        $engine = Mockery::mock(AutomationEngine::class);
        $engine->shouldReceive('executeWorkflow')->twice()->andReturn(new WorkflowExecution(['status' => 'failed']), new WorkflowExecution(['status' => 'completed']));
        $this->app->instance(AutomationEngine::class, $engine);

        Carbon::setTestNow(Carbon::parse('2026-10-20 12:00:00', 'Asia/Kolkata'));
        $scheduler->synchronizeAndDispatch();
        $this->assertSame('pending', $schedule->fresh()->status);
        $scheduler->synchronizeAndDispatch();
        $this->assertSame('dispatched', $schedule->fresh()->status);
    }

    public function test_completed_booking_is_cancelled_when_its_pending_reminder_becomes_due(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-20 11:00:00', 'Asia/Kolkata'));
        $this->publishReminder(11, 1, 'before_24h');
        $booking = $this->doctorBooking(11, '2026-10-21', '12:00 PM');
        $scheduler = app(AppointmentReminderScheduler::class);
        $scheduler->synchronizeAndDispatch();
        DB::table('doctor_bookings')->where('id', $booking->id)->update(['status' => 'completed']);
        $engine = Mockery::mock(AutomationEngine::class);
        $engine->shouldNotReceive('executeWorkflow');
        $this->app->instance(AutomationEngine::class, $engine);

        Carbon::setTestNow(Carbon::parse('2026-10-20 12:00:00', 'Asia/Kolkata'));
        $scheduler->synchronizeAndDispatch();
        $this->assertSame('cancelled', AppointmentReminderSchedule::query()->where('booking_id', $booking->id)->where('stage', 'primary')->value('status'));
    }

    public function test_second_opinion_and_diagnostic_package_use_their_real_date_time_and_branch(): void
    {
        $workflow = $this->publishReminder(11, 1, 'before_12h');
        $second = SecondOpinion::query()->create(['branch_id' => 11, 'preferred_date' => '2026-10-21', 'preferred_time_slots' => ['02:00 PM'], 'status' => 'confirmed']);
        $package = DiagnosticTestBooking::query()->create(['branch_id' => 11, 'package_id' => 27, 'booking_date' => '2026-10-22', 'required_time_slots' => ['10:30 AM'], 'status' => 'confirmed']);

        app(AppointmentReminderScheduler::class)->synchronizeAndDispatch();

        $this->assertSame('2026-10-21 14:00:00', AppointmentReminderSchedule::query()->where('booking_type', 'second_opinion')->where('booking_id', $second->id)->where('stage', 'primary')->firstOrFail()->appointment_at->timezone('Asia/Kolkata')->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-22 10:30:00', AppointmentReminderSchedule::query()->where('booking_type', 'diagnostic_package')->where('booking_id', $package->id)->where('stage', 'primary')->firstOrFail()->appointment_at->timezone('Asia/Kolkata')->format('Y-m-d H:i:s'));
        $this->assertSame($workflow->id, AppointmentReminderSchedule::query()->where('booking_type', 'diagnostic_package')->where('booking_id', $package->id)->value('workflow_id'));
    }

    public function test_hospitals_select_only_their_own_published_workflows_and_workflows_keep_independent_timings(): void
    {
        $a = $this->publishReminder(11, 1, 'before_24h');
        $b = $this->publishReminder(22, 2, 'before_6h');
        $secondAtA = $this->publishReminder(11, 1, 'before_12h', 'Second workflow for A');
        $wrongOrganization = $this->publishReminder(11, 2, 'before_6h', 'Wrong organization for A');
        $bookingA = $this->doctorBooking(11, '2026-10-21', '12:00 PM');
        $bookingB = $this->doctorBooking(22, '2026-10-21', '12:00 PM');

        app(AppointmentReminderScheduler::class)->synchronizeAndDispatch();

        $rowsA = AppointmentReminderSchedule::query()->where('booking_id', $bookingA->id)->get();
        $rowsB = AppointmentReminderSchedule::query()->where('booking_id', $bookingB->id)->get();
        $this->assertEqualsCanonicalizing([$a->id, $secondAtA->id], $rowsA->pluck('workflow_id')->unique()->all());
        $this->assertNotContains($wrongOrganization->id, $rowsA->pluck('workflow_id')->all());
        $this->assertSame([$b->id], $rowsB->pluck('workflow_id')->unique()->all());
        $this->assertSame(24 * 60, abs((int) $rowsA->firstWhere('workflow_id', $a->id)->appointment_at->diffInMinutes($rowsA->firstWhere('workflow_id', $a->id)->scheduled_at)));
        $this->assertSame(6 * 60, abs((int) $rowsB->firstWhere('workflow_id', $b->id)->appointment_at->diffInMinutes($rowsB->firstWhere('workflow_id', $b->id)->scheduled_at)));
    }

    public function test_existing_booking_is_scheduled_and_repeated_scan_does_not_duplicate_rows(): void
    {
        $booking = $this->doctorBooking(11, '2026-10-21', '12:00 PM');
        $this->publishReminder(11, 1, 'before_24h');
        $scheduler = app(AppointmentReminderScheduler::class);
        $scheduler->synchronizeAndDispatch();
        $scheduler->synchronizeAndDispatch();

        $this->assertSame(2, AppointmentReminderSchedule::query()->where('booking_id', $booking->id)->count());
    }

    public function test_booking_and_its_schedules_roll_back_together_when_the_booking_transaction_fails(): void
    {
        $this->publishReminder(11, 1, 'before_24h');
        DB::beginTransaction();
        $booking = $this->doctorBooking(11, '2026-10-21', '12:00 PM');
        app(AppointmentReminderScheduler::class)->synchronizeAndDispatch();
        $this->assertSame(2, AppointmentReminderSchedule::query()->where('booking_id', $booking->id)->count());

        DB::rollBack();

        $this->assertDatabaseMissing('doctor_bookings', ['id' => $booking->id]);
        $this->assertDatabaseMissing('appointment_reminder_schedules', ['booking_id' => $booking->id]);
    }

    public function test_reschedule_replaces_pending_occurrences_and_cancelled_booking_is_not_dispatched(): void
    {
        $this->publishReminder(11, 1, 'before_24h');
        $booking = $this->doctorBooking(11, '2026-10-21', '12:00 PM');
        $scheduler = app(AppointmentReminderScheduler::class);
        $scheduler->synchronizeAndDispatch();
        $oldIdentities = AppointmentReminderSchedule::query()->where('booking_id', $booking->id)->pluck('identity')->all();

        DB::table('doctor_bookings')->where('id', $booking->id)->update(['booking_date' => '2026-10-22']);
        $scheduler->synchronizeAndDispatch();
        $this->assertSame(2, AppointmentReminderSchedule::query()->where('booking_id', $booking->id)->where('status', 'cancelled')->count());
        $this->assertCount(2, array_diff($oldIdentities, AppointmentReminderSchedule::query()->where('booking_id', $booking->id)->where('status', 'pending')->pluck('identity')->all()));

        DB::table('doctor_bookings')->where('id', $booking->id)->update(['status' => 'cancelled']);
        Carbon::setTestNow(Carbon::parse('2026-10-21 12:00:00', 'Asia/Kolkata'));
        app(AutomationContextBuilder::class);
        $engine = Mockery::mock(AutomationEngine::class);
        $engine->shouldNotReceive('executeWorkflow');
        $this->app->instance(AutomationEngine::class, $engine);
        $scheduler->synchronizeAndDispatch();
        $this->assertSame(0, AppointmentReminderSchedule::query()->where('status', 'dispatched')->count());
    }

    public function test_due_reminder_uses_engine_with_scope_stage_occurrence_and_retry_identity(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-20 11:00:00', 'Asia/Kolkata'));
        $workflow = $this->publishReminder(11, 1, 'before_24h');
        $booking = $this->doctorBooking(11, '2026-10-21', '12:00 PM');
        $scheduler = app(AppointmentReminderScheduler::class);
        $scheduler->synchronizeAndDispatch();
        $schedule = AppointmentReminderSchedule::query()->where('booking_id', $booking->id)->where('stage', 'primary')->firstOrFail();
        $context = ['appointment_id' => $booking->id, 'hospital_id' => 11, 'organization_id' => 1];
        $contextBuilder = Mockery::mock(AutomationContextBuilder::class);
        $contextBuilder->shouldReceive('fromAppointment')->once()->with(Mockery::on(fn ($m) => $m->id === $booking->id))->andReturn($context);
        $this->app->instance(AutomationContextBuilder::class, $contextBuilder);
        $engine = Mockery::mock(AutomationEngine::class);
        $engine->shouldReceive('executeWorkflow')->once()->with(Mockery::on(fn ($w) => $w->id === $workflow->id), 'appointmentReminder', Mockery::on(function ($payload) use ($schedule) {
            return $payload['hospital_id'] === 11 && $payload['organization_id'] === 1
                && $payload['reminder_stage'] === 'primary'
                && $payload['event_occurrence_id'] === 'appointment-reminder:'.$schedule->identity;
        }))->andReturn(new WorkflowExecution);
        $this->app->instance(AutomationEngine::class, $engine);

        Carbon::setTestNow(Carbon::parse('2026-10-20 12:00:00', 'Asia/Kolkata'));
        $scheduler->synchronizeAndDispatch();
        $this->assertSame('dispatched', $schedule->fresh()->status);
        $scheduler->synchronizeAndDispatch();
        $this->assertSame('dispatched', $schedule->fresh()->status);
    }

    public function test_due_reminder_runs_the_published_graph_and_a_repeated_occurrence_is_idempotent(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-20 11:00:00', 'Asia/Kolkata'));
        $workflow = $this->publishReminder(11, 1, 'before_24h');
        $booking = $this->doctorBooking(11, '2026-10-21', '12:00 PM');
        $contextBuilder = Mockery::mock(AutomationContextBuilder::class);
        $contextBuilder->shouldReceive('fromAppointment')->twice()->andReturn([
            'appointment' => $booking, 'appointment_id' => $booking->id, 'hospital_id' => 11, 'organization_id' => 1,
        ]);
        $contextBuilder->shouldReceive('merge')->twice()->andReturnUsing(fn (array $payload) => $payload);
        $contextBuilder->shouldReceive('resolveOrganizationId')->twice()->andReturn(1);
        $this->app->instance(AutomationContextBuilder::class, $contextBuilder);
        $this->app->forgetInstance(AutomationEngine::class);
        $scheduler = app(AppointmentReminderScheduler::class);
        $scheduler->synchronizeAndDispatch();

        Carbon::setTestNow(Carbon::parse('2026-10-20 12:00:00', 'Asia/Kolkata'));
        $scheduler->synchronizeAndDispatch();
        $execution = \App\Modules\Workflow\Models\WorkflowExecution::query()->where('workflow_id', $workflow->id)->firstOrFail();
        $this->assertSame('appointmentReminder', $execution->trigger_type);
        $this->assertSame('appointment-reminder:'.AppointmentReminderSchedule::query()->where('booking_id', $booking->id)->where('stage', 'primary')->value('identity'), $execution->context['event_occurrence_id']);
        $this->assertSame(11, $execution->context['hospital_id']);
        $this->assertSame(1, $execution->context['organization_id']);
        $this->assertSame('completed', $execution->status);
        $this->assertSame(1, $this->app->make(NodeProcessorRegistry::class)->get('sendPush')->calls);

        AppointmentReminderSchedule::query()->where('booking_id', $booking->id)->where('stage', 'primary')->update(['status' => 'pending']);
        $scheduler->synchronizeAndDispatch();
        $this->assertSame(1, \App\Modules\Workflow\Models\WorkflowExecution::query()->where('workflow_id', $workflow->id)->count());
        $this->assertSame(1, $this->app->make(NodeProcessorRegistry::class)->get('sendPush')->calls);
    }

    public function test_failed_reminder_execution_retries_the_same_workflow_execution_row(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-20 11:00:00', 'Asia/Kolkata'));
        $workflow = $this->publishReminder(11, 1, 'before_24h');
        $booking = $this->doctorBooking(11, '2026-10-21', '12:00 PM');
        $scheduler = app(AppointmentReminderScheduler::class);
        $scheduler->synchronizeAndDispatch();
        $schedule = AppointmentReminderSchedule::query()->where('booking_id', $booking->id)->where('stage', 'primary')->firstOrFail();
        $occurrence = 'appointment-reminder:'.$schedule->identity;
        $failedExecution = WorkflowExecution::query()->create([
            'workflow_id' => $workflow->id, 'workflow_version_id' => $workflow->current_version_id,
            'status' => 'failed', 'trigger_type' => 'appointmentReminder',
            'context' => ['event_occurrence_id' => $occurrence, 'hospital_id' => 11, 'organization_id' => 1],
            'variables' => [], 'started_at' => now(),
        ]);
        $contextBuilder = Mockery::mock(AutomationContextBuilder::class);
        $contextBuilder->shouldReceive('fromAppointment')->once()->andReturn([
            'appointment' => $booking, 'appointment_id' => $booking->id, 'hospital_id' => 11, 'organization_id' => 1,
        ]);
        $contextBuilder->shouldReceive('merge')->once()->andReturnUsing(fn (array $payload) => $payload);
        $contextBuilder->shouldReceive('resolveOrganizationId')->once()->andReturn(1);
        $this->app->instance(AutomationContextBuilder::class, $contextBuilder);
        $this->app->forgetInstance(AutomationEngine::class);

        Carbon::setTestNow(Carbon::parse('2026-10-20 12:00:00', 'Asia/Kolkata'));
        $scheduler->synchronizeAndDispatch();

        $this->assertSame(1, WorkflowExecution::query()->where('workflow_id', $workflow->id)->count());
        $this->assertSame('completed', $failedExecution->fresh()->status);
        $this->assertSame(1, $this->app->make(NodeProcessorRegistry::class)->get('sendPush')->calls);
        $this->assertSame('dispatched', $schedule->fresh()->status);
    }

    private function doctorBooking(int $hospitalId, string $date, string $time): DoctorBooking
    {
        return DoctorBooking::query()->create(['hospital_id' => $hospitalId, 'booking_date' => $date, 'required_time_slots' => [$time], 'status' => 'confirmed']);
    }

    private function publishReminder(int $hospitalId, int $organizationId, string $timing, string $name = 'Reminder'): Workflow
    {
        $workflow = Workflow::query()->create(['name' => $name, 'status' => 'active', 'trigger_type' => 'appointmentReminder', 'organization_id' => $organizationId, 'hospital_id' => $hospitalId]);
        $version = WorkflowVersion::query()->create([
            'workflow_id' => $workflow->id, 'version_number' => 1, 'status' => 'published',
            'definition' => ['nodes' => [
                ['id' => 'trigger', 'type' => 'workflow', 'data' => ['nodeType' => 'appointmentReminder', 'triggerTiming' => $timing]],
                ['id' => 'push', 'type' => 'workflow', 'data' => ['nodeType' => 'sendPush', 'title' => 'Test reminder', 'body' => 'Reminder']],
                ['id' => 'end', 'type' => 'workflow', 'data' => ['nodeType' => 'end']],
            ], 'edges' => [['source' => 'trigger', 'target' => 'push'], ['source' => 'push', 'target' => 'end']]],
            'published_at' => now(),
        ]);
        $workflow->update(['current_version_id' => $version->id]);
        return $workflow->fresh(['currentVersion']);
    }

    private function republishReminder(Workflow $workflow, string $timing): WorkflowVersion
    {
        $version = WorkflowVersion::query()->create([
            'workflow_id' => $workflow->id, 'version_number' => 2, 'status' => 'published',
            'definition' => ['nodes' => [['id' => 'trigger', 'type' => 'workflow', 'data' => ['nodeType' => 'appointmentReminder', 'triggerTiming' => $timing]], ['id' => 'end', 'type' => 'workflow', 'data' => ['nodeType' => 'end']]], 'edges' => [['source' => 'trigger', 'target' => 'end']]],
            'published_at' => now(),
        ]);
        $workflow->update(['current_version_id' => $version->id]);
        return $version;
    }
}
