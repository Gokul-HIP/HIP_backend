<?php

namespace App\Modules\Workflow\NodeProcessors;

use App\Modules\Workflow\DTO\ExecutionNode;
use App\Modules\Workflow\DTO\NodeExecutionResult;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\NodeProcessors\AbstractNodeProcessor;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Services\Runtime\ActionDispatcher;

/**
 * Legacy builder node type — routes to prescriptionAdded or medicineReminderDue behavior.
 */
class MedicineReminderNodeProcessor extends AbstractNodeProcessor
{
    public function __construct(
        ActionDispatcher $actionDispatcher,
        protected PrescriptionAddedTriggerNodeProcessor $prescriptionAdded,
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
        if ($context->get('schedule_id')) {
            return $this->medicineReminderDue->execute($node, $execution, $context);
        }

        return $this->prescriptionAdded->execute($node, $execution, $context);
    }
}
