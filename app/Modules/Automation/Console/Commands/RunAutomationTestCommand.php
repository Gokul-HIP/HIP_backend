<?php

namespace App\Modules\Automation\Console\Commands;

use App\Modules\Automation\Testing\AutomationTestException;
use App\Modules\Automation\Testing\AutomationTestRunner;
use Illuminate\Console\Command;

class RunAutomationTestCommand extends Command
{
    protected $signature = 'automation:test
        {trigger=appointmentBooked : Trigger type to test (e.g. appointmentBooked)}
        {--account=1 : Test account id from config/automation.php}
        {--channels= : Comma-separated channels: push,email,sms,whatsapp (default: all)}
        {--workflow-id= : Use an existing workflow marked source_type=automation_test (production workflows are rejected)}
        {--queue : Dispatch through the queue (does not start a worker)}
        {--keep-workflow : Keep ephemeral workflow active after the run}';

    protected $description = 'Run a real-notification automation workflow against a dedicated TEST account (never patients).';

    public function handle(AutomationTestRunner $runner): int
    {
        $trigger = (string) $this->argument('trigger');
        $accountId = (int) $this->option('account');
        $channelsOption = $this->option('channels');
        $channels = null;

        if (is_string($channelsOption) && trim($channelsOption) !== '') {
            $channels = array_values(array_filter(array_map('trim', explode(',', $channelsOption))));
        }

        $workflowId = $this->option('workflow-id');
        $workflowId = filled($workflowId) ? (int) $workflowId : null;

        try {
            $result = $runner->run(
                trigger: $trigger,
                accountId: $accountId,
                channels: $channels,
                queue: (bool) $this->option('queue'),
                workflowId: $workflowId,
                keepWorkflow: (bool) $this->option('keep-workflow'),
            );
        } catch (AutomationTestException $e) {
            $this->error('[Automation Test] '.$e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->line('[Automation Test]');
        $this->line('Trigger: '.$result->trigger);
        $this->line('Account: '.$result->account->label.' (#'.$result->account->id.')');
        $this->line('Source: '.$result->source);
        if ($result->workflowId) {
            $this->line('Workflow: '.$result->workflowId.($result->ephemeralWorkflow ? ' (ephemeral)' : ''));
        }
        if ($result->execution) {
            $this->line('Execution: '.$result->execution->id.' ('.$result->execution->status.')');
        }
        $this->newLine();

        if ($result->queued) {
            $this->warn($result->message ?? 'Queued.');

            return self::SUCCESS;
        }

        foreach ($result->nodes as $node) {
            $label = str_pad($node['node'], 14);
            $status = $node['status'];
            $line = "{$label} {$status}";
            if (! empty($node['detail'])) {
                $line .= '  — '.$node['detail'];
            }
            if ($status === 'SUCCESS') {
                $this->info($line);
            } else {
                $this->error($line);
            }
        }

        $this->newLine();

        return $result->allSucceeded() ? self::SUCCESS : self::FAILURE;
    }
}
