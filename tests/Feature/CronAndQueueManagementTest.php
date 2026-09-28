<?php

namespace Tests\Feature;

use App\Filament\Pages\FailedQueueJobs;
use App\Filament\Pages\PendingQueueJobs;
use App\Filament\Pages\QueueOverview;
use App\Filament\Resources\CronJobs\CronJobResource;
use App\Filament\Resources\CronJobs\Pages\ViewCronJob;
use App\Filament\Resources\CronJobs\Tables\CronJobsTable;
use App\Models\CronJob;
use App\Models\CronJobRun;
use App\Models\DatabaseQueueJob;
use App\Models\FailedQueueJob;
use App\Models\HIPUser;
use App\Modules\Automation\Events\AppointmentBooked;
use App\Modules\Automation\Events\AppointmentMissed;
use App\Modules\Automation\Events\AppointmentRescheduled;
use App\Modules\Automation\Listeners\AppointmentBookedListener;
use App\Modules\Automation\Listeners\DispatchHospitalAutomationWorkflow;
use App\Modules\Workflow\Jobs\ContinueWorkflowExecutionJob;
use App\Services\Cron\CronCommandRegistry;
use App\Services\Cron\CronJobRunner;
use App\Services\Cron\CronSchedule;
use App\Services\Queue\QueueJobManager;
use App\Services\Queue\QueueMetrics;
use App\Services\Queue\QueuePayloadSanitizer;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CronAndQueueManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    protected function migrateFreshUsing(): array
    {
        return [
            '--path' => [
                'database/migrations/0001_01_01_000002_create_jobs_table.php',
                'database/migrations/2025_11_21_014003_create_filament_users_table.php',
                'database/migrations/2025_11_21_014030_create_permission_tables.php',
                'database/migrations/2026_03_11_120000_add_hip_id_to_healthinpocket_users_table.php',
                'database/migrations/2026_03_24_173237_fix_model_has_roles_uuid.php',
                'database/migrations/2026_05_19_120000_create_settings_table.php',
                'database/migrations/2026_09_26_160000_create_cron_jobs_tables.php',
            ],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite') {
            $this->fail('Refusing to run destructive tests against '.config('database.default'));
        }

        config([
            'cron.commands.cron-test:ok' => [
                'name' => 'Cron Test OK',
                'description' => 'Test helper command',
            ],
            'cron.commands.cron-test:fail' => [
                'name' => 'Cron Test Fail',
                'description' => 'Test helper command that fails',
            ],
        ]);

        Artisan::command('cron-test:ok', function () {
            $this->info('ok');

            return 0;
        });

        Artisan::command('cron-test:fail', function () {
            throw new \RuntimeException('failed on purpose');
        });
    }

    public function test_create_cron_definition(): void
    {
        $job = $this->makeCronJob();

        $this->assertDatabaseHas('cron_jobs', [
            'id' => $job->id,
            'command' => 'cron-test:ok',
            'is_active' => true,
        ]);
        $this->assertNotNull($job->next_run_at);
    }

    public function test_active_cron_is_picked_up_and_due_cron_executes(): void
    {
        $job = $this->makeCronJob(['schedule_type' => 'every_minute']);

        $executed = app(CronJobRunner::class)->runDueJobs();

        $this->assertSame(1, $executed);
        $this->assertDatabaseHas('cron_job_runs', [
            'cron_job_id' => $job->id,
            'status' => CronJobRun::STATUS_SUCCESS,
            'triggered_by' => 'scheduler',
        ]);

        $run = $job->runs()->first();
        $this->assertNotNull($run);
        $this->assertStringContainsString('ok', (string) $run->output);

        $job->refresh();
        $this->assertNotNull($job->last_run_at);
        $this->assertSame('success', $job->last_status);
        $this->assertNotNull($job->next_run_at);
        $this->assertTrue($job->next_run_at->greaterThan($job->last_run_at));
    }

    public function test_inactive_cron_is_skipped(): void
    {
        $this->makeCronJob(['is_active' => false]);

        $executed = app(CronJobRunner::class)->runDueJobs();

        $this->assertSame(0, $executed);
        $this->assertDatabaseCount('cron_job_runs', 0);
    }

    public function test_future_cron_does_not_execute(): void
    {
        $this->makeCronJob([
            'schedule_type' => 'custom',
            'schedule' => '0 0 1 1 *',
        ]);

        $executed = app(CronJobRunner::class)->runDueJobs();

        $this->assertSame(0, $executed);
        $this->assertDatabaseCount('cron_job_runs', 0);
    }

    public function test_failed_execution_creates_failed_run(): void
    {
        $job = $this->makeCronJob(['command' => 'cron-test:fail']);

        app(CronJobRunner::class)->runDueJobs();

        $this->assertDatabaseHas('cron_job_runs', [
            'cron_job_id' => $job->id,
            'status' => CronJobRun::STATUS_FAILED,
        ]);

        $job->refresh();
        $this->assertSame('failed', $job->last_status);
        $this->assertNotNull($job->last_error);
    }

    public function test_run_now_creates_manual_run(): void
    {
        $job = $this->makeCronJob(['is_active' => false, 'schedule_type' => 'custom', 'schedule' => '0 0 1 1 *']);

        $run = app(CronJobRunner::class)->runNow($job, 'manual');

        $this->assertSame('manual', $run->triggered_by);
        $this->assertSame(CronJobRun::STATUS_SUCCESS, $run->status);
        $this->assertDatabaseHas('cron_job_runs', [
            'id' => $run->id,
            'triggered_by' => 'manual',
        ]);
    }

    public function test_invalid_schedule_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CronJob::query()->create([
            'name' => 'Bad',
            'command' => 'cron-test:ok',
            'schedule_type' => 'custom',
            'schedule' => 'not a cron',
            'timezone' => 'Asia/Kolkata',
            'is_active' => true,
        ]);
    }

    public function test_unauthorized_user_cannot_manage_cron(): void
    {
        Role::findOrCreate('hospital_admin', 'filament');

        $user = $this->makeHipUser([
            'email' => 'hospital-admin@example.com',
            'first_name' => 'Hospital',
            'last_name' => 'Admin',
        ]);
        $user->assignRole('hospital_admin');

        $this->assertFalse(Gate::forUser($user)->allows('manageCronJobs'));
        $this->assertFalse(Gate::forUser($user)->allows('viewAny', CronJob::class));
    }

    public function test_super_admin_can_manage_cron(): void
    {
        $user = $this->makeSuperAdmin();

        $this->assertTrue(Gate::forUser($user)->allows('manageCronJobs'));
        $this->assertTrue(Gate::forUser($user)->allows('viewAny', CronJob::class));
    }

    public function test_overlapping_execution_is_prevented(): void
    {
        $job = $this->makeCronJob();

        CronJobRun::query()->create([
            'cron_job_id' => $job->id,
            'started_at' => now(),
            'status' => CronJobRun::STATUS_RUNNING,
            'triggered_by' => 'scheduler',
        ]);

        $run = app(CronJobRunner::class)->runNow($job, 'manual');

        $this->assertSame(CronJobRun::STATUS_SKIPPED, $run->status);
        $this->assertSame(1, $job->runs()->where('status', CronJobRun::STATUS_RUNNING)->count());
        $this->assertSame(1, $job->runs()->where('status', CronJobRun::STATUS_SKIPPED)->count());
    }

    public function test_overlap_lock_prevents_duplicate_start(): void
    {
        $job = $this->makeCronJob();
        $lock = Cache::lock('cron-job-run:'.$job->id, 60);
        $this->assertTrue($lock->get());

        try {
            $run = app(CronJobRunner::class)->runNow($job, 'manual');
            $this->assertSame(CronJobRun::STATUS_SKIPPED, $run->status);
        } finally {
            $lock->release();
        }
    }

    public function test_approved_commands_only_and_arbitrary_commands_rejected(): void
    {
        $registry = app(CronCommandRegistry::class);

        $this->assertTrue($registry->isApproved('hospital-automation:dispatch-missed-appointments'));
        $this->assertFalse($registry->isApproved('inspire'));
        $this->assertFalse($registry->isApproved('rm -rf /'));
        $this->assertFalse($registry->isApproved('cron-test:ok; drop table users'));

        $this->expectException(InvalidArgumentException::class);
        CronJob::query()->create([
            'name' => 'Evil',
            'command' => 'inspire',
            'schedule_type' => 'hourly',
            'schedule' => '0 * * * *',
            'timezone' => 'Asia/Kolkata',
            'is_active' => true,
        ]);
    }

    public function test_runner_rejects_unapproved_command_stored_outside_model(): void
    {
        $id = \Illuminate\Support\Facades\DB::table('cron_jobs')->insertGetId([
            'name' => 'Injected',
            'command' => 'inspire',
            'schedule_type' => 'every_minute',
            'schedule' => '* * * * *',
            'timezone' => 'Asia/Kolkata',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $job = CronJob::query()->findOrFail($id);

        $this->expectException(InvalidArgumentException::class);
        app(CronJobRunner::class)->execute($job, 'scheduler');
    }

    public function test_pending_and_failed_jobs_can_be_displayed(): void
    {
        DatabaseQueueJob::query()->insert([
            'queue' => 'default',
            'payload' => json_encode([
                'displayName' => 'App\\Modules\\Workflow\\Jobs\\ContinueWorkflowExecutionJob',
                'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
                'data' => ['commandName' => 'App\\Modules\\Workflow\\Jobs\\ContinueWorkflowExecutionJob'],
            ]),
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->subMinutes(8)->timestamp,
            'created_at' => now()->subMinutes(8)->timestamp,
        ]);

        DatabaseQueueJob::query()->insert([
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'ProcessingJob']),
            'attempts' => 1,
            'reserved_at' => now()->timestamp,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);

        FailedQueueJob::query()->insert([
            'uuid' => 'failed-uuid-1',
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'RunAutomationTestJob', 'password' => 'super-secret']),
            'exception' => 'token=abc123 exploded',
            'failed_at' => now()->setTime(12, 49),
        ]);

        $metrics = app(QueueMetrics::class)->overview();

        $this->assertSame(1, $metrics['pending']);
        $this->assertSame(1, $metrics['processing']);
        $this->assertSame(1, $metrics['failed']);
        $this->assertSame('RunAutomationTestJob', $metrics['latest_failure_job']);
        $this->assertNotNull($metrics['oldest_pending_age']);
    }

    public function test_failed_job_retry_uses_laravel_mechanism(): void
    {
        $job = FailedQueueJob::query()->create([
            'uuid' => 'retry-uuid-1',
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'App\\Jobs\\AnalyzeMedicalBillJob']),
            'exception' => 'boom',
            'failed_at' => now(),
        ]);

        Artisan::shouldReceive('call')
            ->once()
            ->with('queue:retry', ['id' => ['retry-uuid-1']])
            ->andReturn(0);

        app(QueueJobManager::class)->retryFailed($job);
    }

    public function test_failed_job_deletion_works(): void
    {
        $job = FailedQueueJob::query()->create([
            'uuid' => 'forget-uuid-1',
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'App\\Jobs\\AnalyzeMedicalBillJob']),
            'exception' => 'boom',
            'failed_at' => now(),
        ]);

        Artisan::shouldReceive('call')
            ->once()
            ->with('queue:forget', ['id' => 'forget-uuid-1'])
            ->andReturn(0);

        app(QueueJobManager::class)->deleteFailed($job);
    }

    public function test_queue_payloads_are_safely_rendered_and_secrets_are_hidden(): void
    {
        $sanitizer = app(QueuePayloadSanitizer::class);

        $summary = $sanitizer->summarize(json_encode([
            'displayName' => 'App\\Jobs\\AnalyzeMedicalBillJob',
            'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
            'data' => [
                'commandName' => 'App\\Jobs\\AnalyzeMedicalBillJob',
                'command' => 'O:29:"App\\Jobs\\AnalyzeMedicalBillJob":1:{s:8:"password";s:4:"secret";}',
                'api_token' => 'should-not-leak',
            ],
        ]));

        $json = json_encode($summary);
        $this->assertStringNotContainsString('secret', $json);
        $this->assertStringNotContainsString('should-not-leak', $json);
        $this->assertStringNotContainsString('O:29:', $json);
        $this->assertSame('App\\Jobs\\AnalyzeMedicalBillJob', $summary['display_name']);

        $exception = $sanitizer->exceptionPreview('Bearer abc.def.ghi password=hunter2');
        $this->assertStringNotContainsString('hunter2', (string) $exception);
        $this->assertStringNotContainsString('abc.def.ghi', (string) $exception);
    }

    public function test_existing_queue_jobs_still_dispatch(): void
    {
        Queue::fake();

        ContinueWorkflowExecutionJob::dispatch(99, 'n1');

        Queue::assertPushed(ContinueWorkflowExecutionJob::class);
    }

    public function test_phpunit_cannot_target_production_mysql(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', (string) config('database.connections.sqlite.database'));
        $this->assertSame('sync', config('queue.default'));

        $xml = simplexml_load_file(base_path('phpunit.xml'));
        $envs = [];
        foreach ($xml->php->env as $env) {
            $envs[(string) $env['name']] = [
                'value' => (string) $env['value'],
                'force' => ((string) $env['force']) === 'true',
            ];
        }

        $this->assertSame('sqlite', $envs['DB_CONNECTION']['value']);
        $this->assertTrue($envs['DB_CONNECTION']['force']);
        $this->assertSame(':memory:', $envs['DB_DATABASE']['value']);
        $this->assertTrue($envs['DB_DATABASE']['force']);
        $this->assertSame('sync', $envs['QUEUE_CONNECTION']['value']);
        $this->assertTrue($envs['QUEUE_CONNECTION']['force']);
    }

    public function test_cron_run_database_jobs_is_registered_once_every_minute_without_overlapping(): void
    {
        $events = $this->scheduledEventsFor('cron:run-database-jobs');

        $this->assertCount(1, $events);
        $this->assertSame('* * * * *', $events->first()->expression);
        $this->assertTrue($events->first()->withoutOverlapping);
        $this->assertSame(10, $events->first()->expiresAt);
    }

    public function test_legacy_pending_payments_is_not_on_the_static_schedule(): void
    {
        $this->assertCount(0, $this->scheduledEventsFor('reminders:pending-payments'));
        $this->assertCount(0, $this->scheduledEventsFor('hospital-automation:dispatch-pending-payments'));
        $this->assertStringNotContainsString(
            "command('reminders:pending-payments')",
            file_get_contents(base_path('routes/console.php'))
        );
        $this->assertStringNotContainsString("command('reminders:pending-payments')", file_get_contents(app_path('Console/Kernel.php')));

        $this->artisan('schedule:list')
            ->expectsOutputToContain('cron:run-database-jobs')
            ->doesntExpectOutputToContain('reminders:pending-payments')
            ->assertSuccessful();
    }

    public function test_payment_pending_detector_is_owned_by_filament_cron_catalog(): void
    {
        $this->assertArrayHasKey('hospital-automation:dispatch-pending-payments', config('cron.commands'));
        $this->assertArrayNotHasKey('reminders:pending-payments', config('cron.commands'));
        $this->assertTrue(app(\App\Services\Cron\CronCommandRegistry::class)->isApproved('hospital-automation:dispatch-pending-payments'));
        $this->assertFalse(app(\App\Services\Cron\CronCommandRegistry::class)->isApproved('reminders:pending-payments'));
    }

    public function test_schedule_run_executes_due_database_cron_end_to_end(): void
    {
        $event = $this->scheduledEventsFor('cron:run-database-jobs')->first();
        $this->assertNotNull($event);
        $this->assertTrue($event->isDue($this->app));

        $job = $this->makeCronJob([
            'name' => 'Safe DB Cron',
            'command' => 'cron-test:ok',
            'schedule_type' => 'every_minute',
            'is_active' => true,
        ]);

        // schedule:run shells out a new PHP process (real .env / MySQL). Drive the
        // same artisan command in-process against sqlite so this never touches MySQL.
        $this->artisan('cron:run-database-jobs')
            ->expectsOutputToContain('Executed 1 database cron job')
            ->assertSuccessful();

        $this->assertDatabaseHas('cron_job_runs', [
            'cron_job_id' => $job->id,
            'status' => CronJobRun::STATUS_SUCCESS,
            'triggered_by' => 'scheduler',
        ]);

        $run = $job->runs()->first();
        $this->assertNotNull($run);
        $this->assertNotNull($run->duration_ms);

        $job->refresh();
        $this->assertSame('success', $job->last_status);
        $this->assertNotNull($job->last_run_at);
        $this->assertNotNull($job->next_run_at);
        $this->assertTrue($job->next_run_at->greaterThan($job->last_run_at));
    }

    public function test_schedule_run_does_not_execute_inactive_database_cron(): void
    {
        $this->assertTrue($this->scheduledEventsFor('cron:run-database-jobs')->first()->isDue($this->app));

        $job = $this->makeCronJob([
            'command' => 'cron-test:ok',
            'is_active' => false,
        ]);

        $this->artisan('cron:run-database-jobs')
            ->expectsOutputToContain('Executed 0 database cron job')
            ->assertSuccessful();

        $this->assertDatabaseCount('cron_job_runs', 0);
        $job->refresh();
        $this->assertFalse($job->is_active);
        $this->assertNull($job->last_run_at);
        $this->assertNull($job->last_status);
    }

    public function test_schedule_run_skips_when_overlap_lock_is_held(): void
    {
        $this->assertTrue($this->scheduledEventsFor('cron:run-database-jobs')->first()->isDue($this->app));

        $job = $this->makeCronJob();
        $lock = Cache::lock('cron-job-run:'.$job->id, 60);
        $this->assertTrue($lock->get());

        try {
            $this->artisan('cron:run-database-jobs')
                ->expectsOutputToContain('Executed 0 database cron job')
                ->assertSuccessful();

            $this->assertDatabaseHas('cron_job_runs', [
                'cron_job_id' => $job->id,
                'status' => CronJobRun::STATUS_SKIPPED,
                'triggered_by' => 'scheduler',
            ]);
            $this->assertSame(0, $job->runs()->where('status', CronJobRun::STATUS_SUCCESS)->count());
        } finally {
            $lock->release();
        }
    }

    public function test_run_now_uses_the_same_execute_path_as_the_scheduler(): void
    {
        $job = $this->makeCronJob([
            'is_active' => false,
            'schedule_type' => 'custom',
            'schedule' => '0 0 1 1 *',
        ]);

        $run = app(CronJobRunner::class)->runNow($job, 'manual');

        $this->assertSame('manual', $run->triggered_by);
        $this->assertSame(CronJobRun::STATUS_SUCCESS, $run->status);
        $this->assertStringContainsString('ok', (string) $run->output);
        $this->assertDatabaseHas('cron_job_runs', [
            'id' => $run->id,
            'triggered_by' => 'manual',
            'status' => CronJobRun::STATUS_SUCCESS,
        ]);

        $table = file_get_contents(app_path('Filament/Resources/CronJobs/Tables/CronJobsTable.php'));
        $view = file_get_contents(app_path('Filament/Resources/CronJobs/Pages/ViewCronJob.php'));

        $this->assertStringContainsString("app(CronJobRunner::class)->runNow(\$record, 'manual')", $table);
        $this->assertStringContainsString("app(CronJobRunner::class)->runNow(\$record, 'manual')", $view);
        $this->assertStringNotContainsString('Artisan::call', $table);
        $this->assertStringNotContainsString('Artisan::call', $view);
        $this->assertStringNotContainsString('shell_exec', $table);
        $this->assertStringNotContainsString('shell_exec', $view);
        $this->assertSame(ViewCronJob::class, CronJobResource::getPages()['view']->getPage());
    }

    public function test_automation_queue_listeners_remain_registered_under_automation_module(): void
    {
        $this->assertFalse(class_exists('App\\Modules\\HospitalAutomation\\Listeners\\DispatchHospitalAutomationWorkflow'));
        $this->assertTrue(class_exists(DispatchHospitalAutomationWorkflow::class));

        $this->assertContains(
            AppointmentBookedListener::class,
            Event::getRawListeners()[AppointmentBooked::class] ?? []
        );
        $this->assertContains(
            DispatchHospitalAutomationWorkflow::class,
            Event::getRawListeners()[AppointmentMissed::class] ?? []
        );
        $this->assertContains(
            DispatchHospitalAutomationWorkflow::class,
            Event::getRawListeners()[AppointmentRescheduled::class] ?? []
        );
        $this->assertTrue(is_subclass_of(DispatchHospitalAutomationWorkflow::class, \Illuminate\Contracts\Queue\ShouldQueue::class));
    }

    public function test_every_cron_catalog_command_is_a_registered_artisan_command(): void
    {
        $registered = array_keys(Artisan::all());

        $this->assertNotEmpty(config('cron.commands'));

        foreach (array_keys(config('cron.commands')) as $command) {
            $this->assertContains(
                $command,
                $registered,
                "config/cron.php lists [{$command}] but Laravel has not registered that Artisan command."
            );
        }

        $this->assertContains('hospital-automation:dispatch-missed-appointments', $registered);
        $this->assertArrayNotHasKey('automation:test', config('cron.commands'));
    }

    public function test_missed_appointment_command_is_invokable_by_the_cron_runner(): void
    {
        $job = $this->makeCronJob([
            'name' => 'missed appointment',
            'command' => 'hospital-automation:dispatch-missed-appointments',
            'is_active' => false,
            'schedule_type' => 'custom',
            'schedule' => '0 0 1 1 *',
        ]);

        $run = app(CronJobRunner::class)->runNow($job, 'manual');

        $this->assertSame('manual', $run->triggered_by);
        $this->assertNotSame(CronJobRun::STATUS_SKIPPED, $run->status);
        $this->assertStringNotContainsString('does not exist', (string) $run->error);
    }

    public function test_payment_pending_command_is_invokable_by_the_cron_runner(): void
    {
        $job = $this->makeCronJob([
            'name' => 'Payment Pending Detector',
            'command' => 'hospital-automation:dispatch-pending-payments',
            'is_active' => false,
            'schedule_type' => 'custom',
            'schedule' => '0 0 1 1 *',
        ]);

        $run = app(CronJobRunner::class)->runNow($job, 'manual');

        $this->assertSame('manual', $run->triggered_by);
        $this->assertNotSame(CronJobRun::STATUS_SKIPPED, $run->status);
        $this->assertStringNotContainsString('does not exist', (string) $run->error);
    }

    public function test_inactive_filament_cron_does_not_dispatch_payment_pending(): void
    {
        Event::fake([\App\Modules\Automation\Events\PaymentPending::class]);

        $this->makeCronJob([
            'name' => 'Payment Pending Detector',
            'command' => 'hospital-automation:dispatch-pending-payments',
            'schedule_type' => 'every_minute',
            'is_active' => false,
        ]);

        app(CronJobRunner::class)->runDueJobs();

        Event::assertNotDispatched(\App\Modules\Automation\Events\PaymentPending::class);
    }

    public function test_existing_missed_appointment_scheduler_still_registered(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('hospital-automation:dispatch-missed-appointments')
            ->expectsOutputToContain('cron:run-database-jobs')
            ->assertSuccessful();
    }

    public function test_artisan_command_still_exists_for_missed_appointments(): void
    {
        $this->assertArrayHasKey(
            'hospital-automation:dispatch-missed-appointments',
            Artisan::all()
        );
        $this->assertArrayHasKey('cron:run-database-jobs', Artisan::all());
    }

    public function test_queue_pages_and_cron_resource_are_gated(): void
    {
        $this->assertFalse(CronJobResource::canAccess());
        $this->assertFalse(QueueOverview::canAccess());
        $this->assertFalse(PendingQueueJobs::canAccess());
        $this->assertFalse(FailedQueueJobs::canAccess());

        $this->actingAs($this->makeSuperAdmin(), 'filament');

        $this->assertTrue(CronJobResource::canAccess());
        $this->assertTrue(QueueOverview::canAccess());
    }

    public function test_schedule_validator_accepts_laravel_expressions(): void
    {
        $schedule = app(CronSchedule::class);

        $this->assertTrue($schedule->isValid('* * * * *'));
        $this->assertTrue($schedule->isValid('*/15 * * * *'));
        $this->assertFalse($schedule->isValid(''));
        $this->assertFalse($schedule->isValid('* * *'));
    }

    public function test_cron_jobs_table_exists_and_jobs_schema_unchanged(): void
    {
        $this->assertTrue(Schema::hasTable('cron_jobs'));
        $this->assertTrue(Schema::hasTable('cron_job_runs'));
        $this->assertTrue(Schema::hasTable('jobs'));
        $this->assertTrue(Schema::hasTable('failed_jobs'));
        $this->assertTrue(Schema::hasColumn('jobs', 'payload'));
        $this->assertTrue(Schema::hasColumn('failed_jobs', 'uuid'));
    }

    /**
     * @return \Illuminate\Support\Collection<int, \Illuminate\Console\Scheduling\Event>
     */
    protected function scheduledEventsFor(string $needle): \Illuminate\Support\Collection
    {
        return collect(app(Schedule::class)->events())
            ->filter(function ($event) use ($needle): bool {
                $haystack = ($event->command ?? '').' '.($event->description ?? '');

                return str_contains($haystack, $needle);
            })
            ->values();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function makeCronJob(array $overrides = []): CronJob
    {
        return CronJob::query()->create(array_merge([
            'name' => 'Test Cron',
            'command' => 'cron-test:ok',
            'schedule_type' => 'every_minute',
            'schedule' => '* * * * *',
            'timezone' => 'Asia/Kolkata',
            'is_active' => true,
            'description' => 'Test',
        ], $overrides));
    }

    protected function makeSuperAdmin(): HIPUser
    {
        Role::findOrCreate('super-admin', 'filament');

        $user = $this->makeHipUser([
            'email' => 'super-admin@example.com',
            'first_name' => 'Super',
            'last_name' => 'Admin',
        ]);
        $user->assignRole('super-admin');

        return $user;
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    protected function makeHipUser(array $attrs = []): HIPUser
    {
        return HIPUser::withoutEvents(function () use ($attrs) {
            return HIPUser::query()->create(array_merge([
                'email' => 'user-'.uniqid().'@example.com',
                'password' => 'password',
                'first_name' => 'Test',
                'last_name' => 'User',
            ], $attrs));
        });
    }
}
