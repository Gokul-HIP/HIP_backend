<?php

namespace App\Modules\Automation\Testing;

use App\Modules\Workflow\Models\WorkflowExecution;

final class AutomationTestResult
{
    /**
     * @param  list<array{node: string, channel: ?string, status: string, detail: ?string, recipient: ?string}>  $nodes
     */
    public function __construct(
        public readonly string $trigger,
        public readonly AutomationTestAccount $account,
        public readonly string $source,
        public readonly bool $queued,
        public readonly ?WorkflowExecution $execution,
        public readonly array $nodes,
        public readonly ?int $workflowId,
        public readonly bool $ephemeralWorkflow,
        public readonly ?string $message = null,
    ) {}

    public function allSucceeded(): bool
    {
        if ($this->queued) {
            return true;
        }

        foreach ($this->nodes as $node) {
            if (($node['status'] ?? '') !== 'SUCCESS') {
                return false;
            }
        }

        return $this->nodes !== [];
    }
}
