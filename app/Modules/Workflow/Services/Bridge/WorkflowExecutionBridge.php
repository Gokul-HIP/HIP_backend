<?php

namespace App\Modules\Workflow\Services\Bridge;

use App\Modules\Automation\Events\MedicineReminderDue;
use App\Modules\Automation\TriggerHandlers\HospitalAutomationTriggerService;
use App\Modules\MedicineReminder\Models\MedicineReminderSchedule;

class WorkflowExecutionBridge
{
    public function __construct(
        protected HospitalAutomationTriggerService $triggerService,
    ) {}

    public function executeMedicineReminderSchedule(MedicineReminderSchedule $schedule): void
    {
        $event = new MedicineReminderDue($schedule);
        $this->triggerService->dispatch($event->triggerType(), $event->payload());
    }
}
