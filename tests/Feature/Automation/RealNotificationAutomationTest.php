<?php

namespace Tests\Feature\Automation;

use App\Models\DoctorBooking;
use App\Modules\HospitalAutomation\Jobs\RunAutomationTestJob;
use App\Modules\HospitalAutomation\Services\AutomationEngine;
use App\Modules\HospitalAutomation\Testing\AutomationTestAccountResolver;
use App\Modules\HospitalAutomation\Testing\AutomationTestContextFactory;
use App\Modules\HospitalAutomation\Testing\AutomationTestException;
use App\Modules\HospitalAutomation\Testing\AutomationTestRunner;
use App\Modules\HospitalAutomation\Testing\AutomationTestWorkflowFactory;
use App\Modules\Workflow\Enums\WorkflowStatus;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Repositories\WorkflowRepository;
use App\Modules\Workflow\Services\Runtime\ChannelManager;
use Illuminate\Support\Facades\Queue;
use Tests\Support\WorkflowAutomationTestCase;

/**
 * Automated coverage for the real-notification test utility.
 * Providers are mocked — these tests NEVER send real SMS/email/WhatsApp/push.
 *
 * Do not weaken AppointmentBookedAutomationTest or WorkflowAutomationCoverageTest.
 */
class RealNotificationAutomationTest extends WorkflowAutomationTestCase
{
    protected function enableTestConfig(array $accountOverrides = [], array $account2Overrides = []): void
    {
        config([
            'automation.test.enabled' => true,
            'automation.test.hospital_id' => 10,
            'automation.test.organization_id' => null,
            'automation.test.accounts' => [
                1 => array_merge([
                    'label' => 'Test Account 1',
                    'user_id' => 'test-user-1',
                    'phone' => '+911111111111',
                    'email' => 'automation-test@example.com',
                    'whatsapp' => '+911111111122',
                    'device_token' => null,
                ], $accountOverrides),
                2 => array_merge([
                    'label' => 'Test Account 2',
                    'user_id' => 'test-user-2',
                    'phone' => '+922222222222',
                    'email' => 'automation-test-2@example.com',
                    'whatsapp' => '+922222222233',
                    'device_token' => null,
                ], $account2Overrides),
            ],
        ]);
    }

    public function test_rejects_when_test_mode_disabled(): void
    {
        config(['automation.test.enabled' => false]);

        $this->expectException(AutomationTestException::class);
        $this->expectExceptionMessage('disabled');

        app(AutomationTestRunner::class)->run(
            trigger: 'appointmentBooked',
            accountId: 1,
            channels: ['email'],
        );
    }

    public function test_rejects_missing_test_account(): void
    {
        $this->enableTestConfig();

        $this->expectException(AutomationTestException::class);
        $this->expectExceptionMessage('not configured');

        app(AutomationTestAccountResolver::class)->resolve(99);
    }

    public function test_rejects_missing_recipient_for_channel(): void
    {
        $this->enableTestConfig([
            'email' => null,
            'phone' => '+911111111111',
            'user_id' => 'test-user-1',
            'whatsapp' => null,
        ]);

        $this->expectException(AutomationTestException::class);
        $this->expectExceptionMessage('EMAIL');

        app(AutomationTestRunner::class)->run(
            trigger: 'appointmentBooked',
            accountId: 1,
            channels: ['email'],
        );
    }

    public function test_refuses_real_patient_models_in_context(): void
    {
        $this->enableTestConfig();
        $account = app(AutomationTestAccountResolver::class)->resolve(1);
        $factory = app(AutomationTestContextFactory::class);

        $context = $factory->build('appointmentBooked', $account, ['email'], 10, null);
        $context['appointment'] = new DoctorBooking;

        $this->expectException(AutomationTestException::class);
        $this->expectExceptionMessage('DoctorBooking');

        $factory->assertSafe($context, $account);
    }

    public function test_resolves_test_recipients_and_source_metadata(): void
    {
        $this->enableTestConfig();

        $result = app(AutomationTestRunner::class)->run(
            trigger: 'appointmentBooked',
            accountId: 1,
            channels: ['push', 'email', 'sms', 'whatsapp'],
        );

        $this->assertSame('automation_test', $result->source);
        $this->assertNotNull($result->execution);

        $context = $result->execution->context;
        $this->assertSame('automation_test', $context['meta']['source'] ?? null);
        $this->assertSame('test-user-1', $context['member_id'] ?? null);
        $this->assertSame('+911111111111', $context['patient_mobile'] ?? null);
        $this->assertSame('automation-test@example.com', $context['patient_email'] ?? null);
        $this->assertNotInstanceOf(DoctorBooking::class, $context['appointment'] ?? null);
    }

    public function test_appointment_booked_runs_through_automation_engine_runtime(): void
    {
        $this->enableTestConfig();

        $result = app(AutomationTestRunner::class)->run(
            trigger: 'appointmentBooked',
            accountId: 1,
            channels: ['email'],
        );

        $this->assertNotNull($result->execution);
        $this->assertSame('appointmentBooked', $result->execution->trigger_type);
        $this->assertDatabaseCount('workflow_executions', 1);
    }

    public function test_each_channel_reaches_test_recipient_only(): void
    {
        $this->enableTestConfig();

        app(AutomationTestRunner::class)->run(
            trigger: 'appointmentBooked',
            accountId: 1,
            channels: ['push', 'email', 'sms', 'whatsapp'],
        );

        $byChannel = collect($this->providerSends)->keyBy('channel');

        $this->assertSame('test-user-1', $byChannel['push']['recipient'] ?? null);
        $this->assertSame('automation-test@example.com', $byChannel['email']['recipient'] ?? null);
        $this->assertSame('+911111111111', $byChannel['sms']['recipient'] ?? null);
        $this->assertSame('+911111111122', $byChannel['whatsapp']['recipient'] ?? null);

        foreach ($this->providerSends as $send) {
            $this->assertNotSame('+919999999999', $send['recipient'] ?? null);
            $this->assertNotSame('ada@example.com', $send['recipient'] ?? null);
            $this->assertNotSame('member-1', $send['recipient'] ?? null);
        }
    }

    public function test_provider_failure_is_reported(): void
    {
        $this->enableTestConfig();
        $this->bindNotificationMocks(success: false, failChannel: 'sms');

        $result = app(AutomationTestRunner::class)->run(
            trigger: 'appointmentBooked',
            accountId: 1,
            channels: ['sms'],
        );

        $sms = collect($result->nodes)->firstWhere('node', 'sendSms');
        $this->assertSame('FAILED', $sms['status'] ?? null);
        $this->assertStringContainsString('provider down', (string) ($sms['detail'] ?? ''));
    }

    public function test_no_duplicate_execution_for_same_appointment_id(): void
    {
        $this->enableTestConfig();

        $account = app(AutomationTestAccountResolver::class)->resolve(1);
        $context = app(AutomationTestContextFactory::class)->build(
            'appointmentBooked',
            $account,
            ['email'],
            10,
            null,
        );

        $resolved = app(AutomationTestWorkflowFactory::class)
            ->resolve('appointmentBooked', ['email'], $account, 10, null);

        $engine = app(AutomationEngine::class);
        $first = $engine->executeWorkflow($resolved['workflow'], 'appointmentBooked', $context);
        $second = $engine->executeWorkflow($resolved['workflow'], 'appointmentBooked', $context);

        $this->assertNotNull($first);
        $this->assertSame($first->id, $second?->id);
        $this->assertSame(1, WorkflowExecution::query()->count());
    }

    public function test_production_recipient_resolution_unchanged(): void
    {
        config(['automation.test.enabled' => false]);

        $manager = new class(
            \Mockery::mock(\App\Modules\MedicineReminder\Notifications\PushNotificationService::class),
            \Mockery::mock(\App\Modules\MedicineReminder\Notifications\WhatsAppNotificationService::class),
            \Mockery::mock(\App\Modules\MedicineReminder\Notifications\SMSNotificationService::class),
            \Mockery::mock(\App\Modules\MedicineReminder\Notifications\EmailNotificationService::class),
            \Mockery::mock(\App\Modules\Workflow\Services\Runtime\AiVoiceCallService::class),
        ) extends ChannelManager {
            /** @param  array<string, mixed>  $context */
            public function exposeResolve(?string $recipient, string $channel, array $context): ?string
            {
                return $this->resolveRecipient(
                    $recipient,
                    $this->normalizeChannelType($channel),
                    $context
                );
            }
        };

        $resolved = $manager->exposeResolve('patient', 'email', [
            'patient_email' => 'real-patient@example.com',
            'meta' => ['source' => 'hospital_automation'],
        ]);

        $this->assertSame('real-patient@example.com', $resolved);
    }

    public function test_artisan_command_prints_success_with_mocked_providers(): void
    {
        $this->enableTestConfig();

        $this->artisan('automation:test', [
            'trigger' => 'appointmentBooked',
            '--account' => 1,
            '--channels' => 'email',
        ])
            ->expectsOutputToContain('[Automation Test]')
            ->expectsOutputToContain('Source: automation_test')
            ->expectsOutputToContain('sendEmail')
            ->assertSuccessful();
    }

    public function test_artisan_command_refuses_when_disabled(): void
    {
        config(['automation.test.enabled' => false]);

        $this->artisan('automation:test', [
            'trigger' => 'appointmentBooked',
            '--account' => 1,
            '--channels' => 'email',
        ])->assertFailed();
    }

    public function test_queue_option_dispatches_job_without_running_worker(): void
    {
        $this->enableTestConfig();
        Queue::fake();

        $this->artisan('automation:test', [
            'trigger' => 'appointmentBooked',
            '--account' => 1,
            '--channels' => 'email',
            '--queue' => true,
        ])
            ->expectsOutputToContain('queue')
            ->assertSuccessful();

        Queue::assertPushed(RunAutomationTestJob::class);
        $this->assertSame(0, WorkflowExecution::query()->count());
    }

    public function test_single_channel_option(): void
    {
        $this->enableTestConfig();

        app(AutomationTestRunner::class)->run(
            trigger: 'appointmentBooked',
            accountId: 1,
            channels: ['push'],
        );

        $this->assertCount(1, $this->providerSends);
        $this->assertSame('push', $this->providerSends[0]['channel']);
        $this->assertSame('test-user-1', $this->providerSends[0]['recipient']);
    }

    public function test_production_handle_does_not_discover_automation_test_workflows(): void
    {
        $this->enableTestConfig();

        $production = $this->publishDefinition(
            $this->linearGraph('appointmentBooked', 'sendEmail', [
                'subject' => 'Prod',
                'body' => 'production path',
                'recipient' => 'patient',
            ]),
            'appointmentBooked',
            10,
            null,
        );
        $production->workflow->update(['source_type' => null]);

        $account = app(AutomationTestAccountResolver::class)->resolve(1);
        $test = app(AutomationTestWorkflowFactory::class)
            ->resolve('appointmentBooked', ['email'], $account, 10, null);

        $this->assertSame('automation_test', $test['workflow']->source_type);
        $this->assertSame(WorkflowStatus::Active->value, $test['workflow']->status);

        $found = app(WorkflowRepository::class)->findPublishedByTrigger('appointmentBooked', null, 10);
        $this->assertTrue($found->contains(fn (Workflow $w) => $w->id === $production->workflow_id));
        $this->assertFalse($found->contains(fn (Workflow $w) => $w->id === $test['workflow']->id));

        app(AutomationEngine::class)->handle('appointmentBooked', [
            'appointment_id' => 'prod-booking-1',
            'hospital_id' => 10,
            'organization_id' => null,
            'patient_email' => 'real-patient@example.com',
            'patient_mobile' => '+919999999999',
            'member_id' => 'real-member',
            'meta' => ['source' => 'hospital_automation'],
        ]);

        $this->assertSame(
            1,
            WorkflowExecution::query()->where('workflow_id', $production->workflow_id)->count()
        );
        $this->assertSame(
            0,
            WorkflowExecution::query()->where('workflow_id', $test['workflow']->id)->count()
        );

        $this->assertSame('real-patient@example.com', $this->providerSends[0]['recipient'] ?? null);
    }

    public function test_execute_workflow_can_run_automation_test_workflows(): void
    {
        $this->enableTestConfig();

        $account = app(AutomationTestAccountResolver::class)->resolve(1);
        $context = app(AutomationTestContextFactory::class)->build(
            'appointmentBooked',
            $account,
            ['email'],
            10,
            null,
        );
        $resolved = app(AutomationTestWorkflowFactory::class)
            ->resolve('appointmentBooked', ['email'], $account, 10, null);

        $execution = app(AutomationEngine::class)->executeWorkflow(
            $resolved['workflow'],
            'appointmentBooked',
            $context,
        );

        $this->assertNotNull($execution);
        $this->assertSame('automation-test@example.com', $this->providerSends[0]['recipient'] ?? null);
    }

    public function test_sync_ephemeral_workflow_cleaned_up_after_success(): void
    {
        $this->enableTestConfig();

        $result = app(AutomationTestRunner::class)->run(
            trigger: 'appointmentBooked',
            accountId: 1,
            channels: ['email'],
        );

        $workflow = Workflow::query()->find($result->workflowId);
        $this->assertNotNull($workflow);
        $this->assertSame('automation_test', $workflow->source_type);
        $this->assertSame(WorkflowStatus::Inactive->value, $workflow->status);
    }

    public function test_sync_ephemeral_workflow_cleaned_up_after_failure(): void
    {
        $this->enableTestConfig();
        $this->bindNotificationMocks(success: false, failChannel: 'email');

        $result = app(AutomationTestRunner::class)->run(
            trigger: 'appointmentBooked',
            accountId: 1,
            channels: ['email'],
        );

        $this->assertSame('FAILED', collect($result->nodes)->firstWhere('node', 'sendEmail')['status'] ?? null);

        $workflow = Workflow::query()->find($result->workflowId);
        $this->assertNotNull($workflow);
        $this->assertSame(WorkflowStatus::Inactive->value, $workflow->status);
    }

    public function test_queue_job_cleans_up_ephemeral_workflow_after_success(): void
    {
        $this->enableTestConfig();

        $account = app(AutomationTestAccountResolver::class)->resolve(1);
        $context = app(AutomationTestContextFactory::class)->build(
            'appointmentBooked',
            $account,
            ['email'],
            10,
            null,
        );
        $resolved = app(AutomationTestWorkflowFactory::class)
            ->resolve('appointmentBooked', ['email'], $account, 10, null);

        $job = new RunAutomationTestJob(
            workflowId: (int) $resolved['workflow']->id,
            trigger: 'appointmentBooked',
            context: $context,
            accountId: 1,
            ephemeral: true,
            keepWorkflow: false,
        );
        $job->handle(app(AutomationTestRunner::class));

        $workflow = Workflow::query()->find($resolved['workflow']->id);
        $this->assertSame(WorkflowStatus::Inactive->value, $workflow?->status);
        $this->assertSame(1, WorkflowExecution::query()->count());
    }

    public function test_queue_job_cleans_up_ephemeral_workflow_after_failure(): void
    {
        $this->enableTestConfig();

        $account = app(AutomationTestAccountResolver::class)->resolve(1);
        $context = app(AutomationTestContextFactory::class)->build(
            'appointmentBooked',
            $account,
            ['email'],
            10,
            null,
        );
        // Corrupt context so executeQueued fails assertSafe / recipient checks after load.
        $context['patient_email'] = 'leaked-patient@example.com';

        $resolved = app(AutomationTestWorkflowFactory::class)
            ->resolve('appointmentBooked', ['email'], $account, 10, null);

        $job = new RunAutomationTestJob(
            workflowId: (int) $resolved['workflow']->id,
            trigger: 'appointmentBooked',
            context: $context,
            accountId: 1,
            ephemeral: true,
            keepWorkflow: false,
        );

        try {
            $job->handle(app(AutomationTestRunner::class));
            $this->fail('Expected AutomationTestException');
        } catch (AutomationTestException $e) {
            $this->assertStringContainsString('patient_email', $e->getMessage());
        }

        $workflow = Workflow::query()->find($resolved['workflow']->id);
        $this->assertSame(WorkflowStatus::Inactive->value, $workflow?->status);
        $this->assertSame(0, WorkflowExecution::query()->count());
    }

    public function test_workflow_id_rejects_ordinary_production_workflow(): void
    {
        $this->enableTestConfig();

        $version = $this->publishDefinition(
            $this->linearGraph('appointmentBooked', 'sendEmail', [
                'subject' => 'Prod',
                'body' => 'no',
                'recipient' => 'patient',
            ]),
            'appointmentBooked',
            10,
            null,
        );
        $version->workflow->update(['source_type' => null]);

        $this->expectException(AutomationTestException::class);
        $this->expectExceptionMessage('source_type=automation_test');

        app(AutomationTestRunner::class)->run(
            trigger: 'appointmentBooked',
            accountId: 1,
            channels: ['email'],
            workflowId: (int) $version->workflow_id,
        );
    }

    public function test_workflow_id_allows_marked_automation_test_workflow(): void
    {
        $this->enableTestConfig();

        $account = app(AutomationTestAccountResolver::class)->resolve(1);
        $resolved = app(AutomationTestWorkflowFactory::class)
            ->resolve('appointmentBooked', ['email'], $account, 10, null);

        $result = app(AutomationTestRunner::class)->run(
            trigger: 'appointmentBooked',
            accountId: 1,
            channels: ['email'],
            workflowId: (int) $resolved['workflow']->id,
            keepWorkflow: true,
        );

        $this->assertNotNull($result->execution);
        $this->assertSame('automation-test@example.com', $this->providerSends[0]['recipient'] ?? null);
        $this->assertSame(WorkflowStatus::Active->value, $resolved['workflow']->fresh()->status);
    }

    public function test_multiple_test_accounts_work_independently(): void
    {
        $this->enableTestConfig();

        app(AutomationTestRunner::class)->run(
            trigger: 'appointmentBooked',
            accountId: 1,
            channels: ['email'],
        );
        app(AutomationTestRunner::class)->run(
            trigger: 'appointmentBooked',
            accountId: 2,
            channels: ['email'],
        );

        $this->assertSame(
            ['automation-test@example.com', 'automation-test-2@example.com'],
            array_column($this->providerSends, 'recipient')
        );
    }

    public function test_never_falls_back_to_real_patient_recipient(): void
    {
        $this->enableTestConfig();

        app(AutomationTestRunner::class)->run(
            trigger: 'appointmentBooked',
            accountId: 1,
            channels: ['email', 'sms', 'whatsapp', 'push'],
        );

        foreach ($this->providerSends as $send) {
            $this->assertNotSame('patient', $send['recipient'] ?? null);
            $this->assertNotSame('+919999999999', $send['recipient'] ?? null);
            $this->assertNotSame('ada@example.com', $send['recipient'] ?? null);
        }
    }
}
