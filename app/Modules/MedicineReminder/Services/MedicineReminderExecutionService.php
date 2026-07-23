<?php

namespace App\Modules\MedicineReminder\Services;

use App\Modules\MedicineReminder\Enums\ReminderLogStatus;
use App\Modules\MedicineReminder\Enums\ScheduleStatus;
use App\Modules\MedicineReminder\Events\MedicineReminderTriggered;
use App\Modules\MedicineReminder\Interfaces\MedicineReminderInterface;
use App\Modules\MedicineReminder\Models\MedicineReminderSchedule;
use App\Modules\Workflow\Services\Bridge\WorkflowExecutionBridge;
use Illuminate\Support\Facades\Log;

class MedicineReminderExecutionService
{
    public function __construct(
        protected MedicineReminderInterface $repository,
        protected WorkflowExecutionBridge $workflowExecutionBridge,
    ) {}

    public function execute(int $scheduleId): void
    {
        $schedule = MedicineReminderSchedule::query()
            ->with([
                'workflow',
                'patient',
                'prescription.doctor',
                'prescription.hospital.organization',
                'prescription.member',
            ])
            ->find($scheduleId);

        if (! $schedule) {
            Log::warning('Medicine reminder schedule not found', ['schedule_id' => $scheduleId]);

            return;
        }

        if (! in_array($schedule->status, [ScheduleStatus::Pending->value, ScheduleStatus::Processing->value], true)) {
            return;
        }

        try {
            event(new MedicineReminderTriggered($schedule));

            $this->workflowExecutionBridge->executeMedicineReminderSchedule($schedule);
        } catch (\Throwable $e) {
            report($e);

            $this->repository->createReminderLog(
                $schedule->id,
                ReminderLogStatus::Failed->value,
                $e->getMessage()
            );

            $this->repository->markFailed($schedule, $e->getMessage());
        }
    }
}
