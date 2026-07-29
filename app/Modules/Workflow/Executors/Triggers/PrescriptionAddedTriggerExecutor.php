<?php

namespace App\Modules\Workflow\Executors\Triggers;

use App\Modules\MedicineReminder\Services\MedicineReminderService;
use App\Modules\Workflow\DTO\ExecutionNode;
use App\Modules\Workflow\DTO\NodeExecutionResult;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\Executors\AbstractNodeExecutor;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Services\Runtime\ActionDispatcher;

class PrescriptionAddedTriggerExecutor extends AbstractNodeExecutor
{
    public function __construct(
        ActionDispatcher $actionDispatcher,
        protected MedicineReminderService $medicineReminderService,
    ) {
        parent::__construct($actionDispatcher);
    }

    public function type(): string
    {
        return 'prescriptionAdded';
    }

    protected function run(
        ExecutionNode $node,
        WorkflowExecution $execution,
        WorkflowContext $context
    ): NodeExecutionResult {
        $prescription = $context->get('prescription');

        if (! $prescription) {
            return NodeExecutionResult::failed('Prescription context missing for prescriptionAdded trigger.');
        }

        $execution->loadMissing('workflow.currentVersion');

        $schedules = $this->medicineReminderService->createSchedulesForPrescription(
            $prescription,
            $execution->workflow
        );

        $context->setVariable('schedules_created', $schedules->count());

        return NodeExecutionResult::continue();
    }
}
