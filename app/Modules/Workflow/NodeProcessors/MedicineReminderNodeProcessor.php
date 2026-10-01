<?php

namespace App\Modules\Workflow\NodeProcessors;

use App\Modules\Workflow\DTO\ExecutionNode;
use App\Modules\Workflow\DTO\NodeExecutionResult;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\NodeProcessors\AbstractNodeProcessor;
use App\Modules\Workflow\Models\WorkflowExecution;

/**
 * Frontend catalog type medicineReminder is the Medicine Reminder Due start trigger.
 */
class MedicineReminderNodeProcessor extends AbstractNodeProcessor
{
    public function __construct(
        \App\Modules\Workflow\Services\Runtime\ActionDispatcher $actionDispatcher,
        protected MedicineReminderDueTriggerNodeProcessor $medicineReminderDue,
    ) {
        parent::__construct($actionDispatcher);
    }

    public function type(): string
    {
        return 'medicineReminder';
    }

    protected function run(
        ExecutionNode $node,
        WorkflowExecution $execution,
        WorkflowContext $context
    ): NodeExecutionResult {
        return $this->medicineReminderDue->execute($node, $execution, $context);
    }
}
