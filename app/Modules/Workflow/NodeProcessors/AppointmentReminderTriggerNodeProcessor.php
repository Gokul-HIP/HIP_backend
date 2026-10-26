<?php

namespace App\Modules\Workflow\NodeProcessors;

use App\Modules\Workflow\DTO\ExecutionNode;
use App\Modules\Workflow\DTO\NodeExecutionResult;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Support\NodeTypeNormalizer;

class AppointmentReminderTriggerNodeProcessor extends AbstractNodeProcessor
{
    public function type(): string { return 'appointmentReminder'; }

    protected function run(ExecutionNode $node, WorkflowExecution $execution, WorkflowContext $context): NodeExecutionResult
    {
        if (NodeTypeNormalizer::normalize((string) $execution->trigger_type) !== 'appointmentReminder') {
            return NodeExecutionResult::failed('AppointmentReminder is a workflow start trigger.');
        }
        return NodeExecutionResult::continue();
    }
}
