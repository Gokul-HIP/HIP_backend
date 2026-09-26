<?php

namespace App\Modules\Automation\Testing;

use App\Models\UserDevice;
use App\Modules\Automation\Jobs\RunAutomationTestJob;
use App\Modules\Automation\Engine\AutomationEngine;
use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use App\Modules\Workflow\Enums\WorkflowStatus;
use App\Modules\Workflow\Models\CommunicationLog;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Models\WorkflowExecution;
use Illuminate\Support\Facades\Log;

class AutomationTestRunner
{
    public function __construct(
        protected AutomationTestAccountResolver $accounts,
        protected AutomationTestContextFactory $contexts,
        protected AutomationTestWorkflowFactory $workflows,
        protected AutomationEngine $engine,
    ) {}

    /**
     * @param  list<string>|null  $channels
     */
    public function run(
        string $trigger = 'appointmentBooked',
        int $accountId = 1,
        ?array $channels = null,
        bool $queue = false,
        ?int $workflowId = null,
        bool $keepWorkflow = false,
    ): AutomationTestResult {
        $this->accounts->assertEnabled();

        $channels = $this->normalizeChannels($channels);
        $account = $this->accounts->resolve($accountId);
        $account->assertRecipientsForChannels($channels);

        $hospitalId = config('automation.test.hospital_id');
        $organizationId = config('automation.test.organization_id');

        if ($hospitalId === null && $workflowId === null) {
            throw new AutomationTestException(
                'Set AUTOMATION_TEST_HOSPITAL_ID (or pass --workflow-id=) so the test workflow can be scoped.'
            );
        }

        $context = $this->contexts->build(
            $trigger,
            $account,
            $channels,
            $hospitalId !== null ? (int) $hospitalId : null,
            $organizationId !== null ? (int) $organizationId : null,
        );

        if (in_array('push', $channels, true) && filled($account->deviceToken) && filled($account->userId)) {
            $this->ensureTestDevice($account);
        }

        $resolved = $this->workflows->resolve(
            $trigger,
            $channels,
            $account,
            $hospitalId !== null ? (int) $hospitalId : null,
            $organizationId !== null ? (int) $organizationId : null,
            $workflowId,
        );

        $workflow = $resolved['workflow'];
        $ephemeral = $resolved['ephemeral'];

        Log::info('[automation_test] Starting real-notification automation test', [
            'source' => AutomationTestContextFactory::SOURCE,
            'trigger' => $trigger,
            'account_id' => $account->id,
            'account_label' => $account->label,
            'channels' => $channels,
            'workflow_id' => $workflow->id,
            'queue' => $queue,
            'appointment_id' => $context['appointment_id'] ?? null,
        ]);

        if ($queue) {
            RunAutomationTestJob::dispatch(
                workflowId: (int) $workflow->id,
                trigger: $trigger,
                context: $context,
                accountId: $account->id,
                ephemeral: $ephemeral,
                keepWorkflow: $keepWorkflow,
            );

            return new AutomationTestResult(
                trigger: $trigger,
                account: $account,
                source: AutomationTestContextFactory::SOURCE,
                queued: true,
                execution: null,
                nodes: [],
                workflowId: (int) $workflow->id,
                ephemeralWorkflow: $ephemeral,
                message: 'Dispatched to queue. Run `php artisan queue:work` (or sync driver) separately — this command does not start a worker. Ephemeral cleanup runs inside the job.',
            );
        }

        try {
            $execution = $this->engine->executeWorkflow($workflow, $trigger, $context);

            if (! $execution) {
                throw new AutomationTestException('AutomationEngine did not start a workflow execution.');
            }

            $nodes = $this->collectNodeResults($execution, $channels);

            Log::info('[automation_test] Completed real-notification automation test', [
                'source' => AutomationTestContextFactory::SOURCE,
                'trigger' => $trigger,
                'account_id' => $account->id,
                'workflow_id' => $workflow->id,
                'workflow_execution_id' => $execution->id,
                'status' => $execution->status,
                'nodes' => collect($nodes)->map(fn (array $n) => [
                    'node' => $n['node'],
                    'channel' => $n['channel'],
                    'status' => $n['status'],
                    'recipient' => $n['recipient'],
                ])->all(),
            ]);

            return new AutomationTestResult(
                trigger: $trigger,
                account: $account,
                source: AutomationTestContextFactory::SOURCE,
                queued: false,
                execution: $execution->fresh(),
                nodes: $nodes,
                workflowId: (int) $workflow->id,
                ephemeralWorkflow: $ephemeral,
            );
        } finally {
            $this->removeTestDevice($account);
            if ($ephemeral && ! $keepWorkflow) {
                $this->cleanupTestWorkflow((int) $workflow->id);
            }
        }
    }

    /**
     * Execute a previously resolved workflow (used by the queued job).
     *
     * @param  array<string, mixed>  $context
     */
    public function executeQueued(int $workflowId, string $trigger, array $context): WorkflowExecution
    {
        $this->accounts->assertEnabled();

        $workflow = Workflow::query()
            ->with('currentVersion')
            ->find($workflowId);

        if (! $workflow || ! $workflow->currentVersion) {
            throw new AutomationTestException("Queued automation test workflow {$workflowId} not found.");
        }

        if ($workflow->source_type !== AutomationTestContextFactory::SOURCE) {
            throw new AutomationTestException(
                "Queued workflow {$workflowId} is not marked source_type=automation_test."
            );
        }

        $accountId = (int) ($context['meta']['automation_test_account_id'] ?? 0);
        $account = $this->accounts->resolve($accountId > 0 ? $accountId : 1);
        $this->contexts->assertSafe($context, $account);

        $channels = is_array($context['meta']['automation_test_channels'] ?? null)
            ? $context['meta']['automation_test_channels']
            : [];
        if ($channels !== []) {
            $account->assertRecipientsForChannels($this->normalizeChannels($channels));
        }

        $execution = null;

        try {
            $execution = $this->engine->executeWorkflow($workflow, $trigger, $context);

            if (! $execution) {
                throw new AutomationTestException('Queued automation test failed to start execution.');
            }

            Log::info('[automation_test] Queued execution finished', [
                'source' => AutomationTestContextFactory::SOURCE,
                'trigger' => $trigger,
                'account_id' => $account->id,
                'workflow_id' => $workflowId,
                'workflow_execution_id' => $execution->id,
                'status' => $execution->status,
            ]);

            return $execution;
        } finally {
            $this->removeTestDevice($account);
        }
    }

    protected function removeTestDevice(AutomationTestAccount $account): void
    {
        if (! filled($account->userId)) {
            return;
        }

        UserDevice::query()
            ->where('user_id', $account->userId)
            ->where('device_id', 'automation-test-device-'.$account->id)
            ->delete();
    }

    /**
     * Deactivate an ephemeral automation_test workflow so it cannot linger as active.
     * Production workflows (other source_type) are never touched.
     */
    public function cleanupTestWorkflow(int $workflowId): void
    {
        $workflow = Workflow::query()->find($workflowId);

        if (! $workflow) {
            return;
        }

        if ($workflow->source_type !== AutomationTestContextFactory::SOURCE) {
            Log::warning('[automation_test] Refusing cleanup of non-test workflow', [
                'source' => AutomationTestContextFactory::SOURCE,
                'workflow_id' => $workflowId,
                'source_type' => $workflow->source_type,
            ]);

            return;
        }

        $name = (string) $workflow->name;
        if (! str_contains($name, '[ran]')) {
            $name = '[automation_test][ran] '.$name;
        }

        $workflow->update([
            'status' => WorkflowStatus::Inactive->value,
            'name' => $name,
        ]);

        Log::info('[automation_test] Cleaned up ephemeral test workflow', [
            'source' => AutomationTestContextFactory::SOURCE,
            'workflow_id' => $workflowId,
            'status' => WorkflowStatus::Inactive->value,
        ]);
    }

    /**
     * @param  list<string>|null  $channels
     * @return list<string>
     */
    public function normalizeChannels(?array $channels): array
    {
        $defaults = ['push', 'email', 'sms', 'whatsapp'];

        if ($channels === null || $channels === []) {
            return $defaults;
        }

        $normalized = [];

        foreach ($channels as $channel) {
            $key = strtolower(trim((string) $channel));
            $key = match ($key) {
                'sendpush', 'push' => 'push',
                'sendemail', 'email' => 'email',
                'sendsms', 'sms' => 'sms',
                'sendwhatsapp', 'whatsapp', 'wa' => 'whatsapp',
                default => throw new AutomationTestException("Unknown channel: {$channel}"),
            };

            if (! in_array($key, $normalized, true)) {
                $normalized[] = $key;
            }
        }

        return $normalized;
    }

    protected function ensureTestDevice(AutomationTestAccount $account): void
    {
        UserDevice::query()->updateOrCreate(
            [
                'user_id' => $account->userId,
                'device_id' => 'automation-test-device-'.$account->id,
            ],
            [
                'device_type' => 'automation_test',
                'fcm_token' => $account->deviceToken,
            ]
        );
    }

    /**
     * @param  list<string>  $channels
     * @return list<array{node: string, channel: ?string, status: string, detail: ?string, recipient: ?string}>
     */
    protected function collectNodeResults(WorkflowExecution $execution, array $channels): array
    {
        $logs = CommunicationLog::query()
            ->where('workflow_execution_id', $execution->id)
            ->orderBy('id')
            ->get();

        $rows = [];

        $channelToNode = [
            'push' => 'sendPush',
            'email' => 'sendEmail',
            'sms' => 'sendSms',
            'whatsapp' => 'sendWhatsApp',
        ];

        foreach ($channels as $channel) {
            $log = $logs->first(function (CommunicationLog $item) use ($channel) {
                $c = strtolower((string) $item->channel);

                return match ($channel) {
                    'push' => in_array($c, ['push', 'sendpush'], true),
                    'email' => in_array($c, ['email', 'sendemail'], true),
                    'sms' => in_array($c, ['sms', 'sendsms'], true),
                    'whatsapp' => in_array($c, ['whatsapp', 'sendwhatsapp'], true),
                    default => false,
                };
            });

            if (! $log) {
                $rows[] = [
                    'node' => $channelToNode[$channel] ?? $channel,
                    'channel' => $channel,
                    'status' => 'FAILED',
                    'detail' => 'No communication log (node may not have run)',
                    'recipient' => null,
                ];

                continue;
            }

            $sent = strtolower((string) $log->status) === 'sent';

            $rows[] = [
                'node' => $channelToNode[$channel] ?? $channel,
                'channel' => $channel,
                'status' => $sent ? 'SUCCESS' : 'FAILED',
                'detail' => is_string($log->provider_response) ? $log->provider_response : null,
                'recipient' => $log->recipient,
            ];
        }

        $execution->refresh();
        $endOk = in_array(
            $execution->status,
            [WorkflowExecutionStatus::Completed->value, 'completed', 'COMPLETED'],
            true
        );

        $rows[] = [
            'node' => 'end',
            'channel' => null,
            'status' => $endOk ? 'SUCCESS' : 'FAILED',
            'detail' => 'execution_status='.$execution->status,
            'recipient' => null,
        ];

        return $rows;
    }
}
