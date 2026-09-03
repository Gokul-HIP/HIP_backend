<?php

namespace App\Modules\HospitalAutomation\Jobs;

use App\Modules\HospitalAutomation\Testing\AutomationTestRunner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Optional queue path for `php artisan automation:test --queue`.
 * Does not start a worker — dispatch only.
 * Always cleans up ephemeral automation_test workflows in finally.
 */
class RunAutomationTestJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public int $workflowId,
        public string $trigger,
        public array $context,
        public int $accountId,
        public bool $ephemeral = true,
        public bool $keepWorkflow = false,
    ) {}

    public function handle(AutomationTestRunner $runner): void
    {
        Log::info('[automation_test] Queue job starting', [
            'source' => 'automation_test',
            'workflow_id' => $this->workflowId,
            'trigger' => $this->trigger,
            'account_id' => $this->accountId,
        ]);

        try {
            $execution = $runner->executeQueued($this->workflowId, $this->trigger, $this->context);

            Log::info('[automation_test] Queue job finished', [
                'source' => 'automation_test',
                'workflow_id' => $this->workflowId,
                'workflow_execution_id' => $execution->id,
                'account_id' => $this->accountId,
                'trigger' => $this->trigger,
                'status' => $execution->status,
            ]);
        } catch (Throwable $e) {
            Log::error('[automation_test] Queue job failed', [
                'source' => 'automation_test',
                'workflow_id' => $this->workflowId,
                'account_id' => $this->accountId,
                'trigger' => $this->trigger,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } finally {
            if ($this->ephemeral && ! $this->keepWorkflow) {
                $runner->cleanupTestWorkflow($this->workflowId);
            }
        }
    }
}
