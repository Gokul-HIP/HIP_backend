<?php

namespace App\Modules\Workflow\Services\Bridge;

use App\Modules\MedicineReminder\Models\MedicineReminderSchedule;
use App\Modules\Workflow\Services\Runtime\WorkflowExecutor;

class WorkflowExecutionBridge
{
    public function __construct(
        protected MedicineWorkflowBridge $medicineWorkflowBridge,
        protected WorkflowExecutor $workflowExecutor,
    ) {}

    public function executeMedicineReminderSchedule(MedicineReminderSchedule $schedule): void
    {
        $schedule->loadMissing('workflow.currentVersion');

        $workflow = $schedule->workflow;

        if (! $workflow || ! $workflow->isActive()) {
            return;
        }

        $version = $this->medicineWorkflowBridge->resolvePublishedVersion($workflow);

        if (! $version) {
            return;
        }

        $this->workflowExecutor->start(
            version: $version,
            triggerType: 'medicineReminderDue',
            payload: [
                'schedule_id' => $schedule->id,
                'prescription_id' => $schedule->prescription_id,
                'patient_id' => $schedule->patient_id,
            ],
        );
    }
}
