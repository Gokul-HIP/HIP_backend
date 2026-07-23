<?php

namespace App\Modules\Workflow\Executors\Triggers;

use App\Modules\Workflow\DTO\ExecutionNode;
use App\Modules\Workflow\DTO\NodeExecutionResult;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\Executors\AbstractNodeExecutor;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Services\Runtime\ActionDispatcher;

/**
 * Legacy builder node type — routes to prescriptionAdded or medicineReminderDue behavior.
 */
class MedicineReminderNodeExecutor extends AbstractNodeExecutor
{
    public function __construct(
        ActionDispatcher $actionDispatcher,
        protected PrescriptionAddedTriggerExecutor $prescriptionAdded,
        protected MedicineReminderDueTriggerExecutor $medicineReminderDue,
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
        if ($context->get('schedule_id')) {
            return $this->medicineReminderDue->execute($node, $execution, $context);
        }

        return $this->prescriptionAdded->execute($node, $execution, $context);
    }
}
