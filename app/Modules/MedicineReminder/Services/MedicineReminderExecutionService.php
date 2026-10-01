<?php

namespace App\Modules\MedicineReminder\Services;

use App\Modules\Automation\Events\MedicineReminderDue;
use App\Modules\MedicineReminder\Enums\ReminderLogStatus;
use App\Modules\MedicineReminder\Enums\ScheduleStatus;
use App\Modules\MedicineReminder\Interfaces\MedicineReminderInterface;
use App\Modules\MedicineReminder\Models\MedicineReminderSchedule;
use Illuminate\Support\Facades\Log;

class MedicineReminderExecutionService
{
    public function __construct(
        protected MedicineReminderInterface $repository,
    ) {}

    public function execute(int $scheduleId): void
    {
        $schedule = MedicineReminderSchedule::query()->find($scheduleId);

        if (! $schedule) {
            Log::warning('Medicine reminder schedule not found', ['schedule_id' => $scheduleId]);

            return;
        }

        if (in_array($schedule->status, [ScheduleStatus::Sent->value, ScheduleStatus::Failed->value, ScheduleStatus::Cancelled->value], true)) {
            Log::info('Medicine reminder execute skipped: already terminal', [
                'schedule_id' => $schedule->id,
                'prescription_id' => $schedule->prescription_id,
                'medicine_id' => $schedule->medicine_id,
                'workflow_execution_id' => null,
                'previous_status' => $schedule->status,
                'new_status' => $schedule->status,
                'result' => 'skipped',
            ]);

            return;
        }

        if ($schedule->status === ScheduleStatus::Pending->value) {
            $claimed = MedicineReminderSchedule::query()
                ->whereKey($schedule->id)
                ->where('status', ScheduleStatus::Pending->value)
                ->update(['status' => ScheduleStatus::Processing->value]);

            if ($claimed === 0) {
                $schedule = $schedule->fresh();
                if (! $schedule || $schedule->status !== ScheduleStatus::Processing->value) {
                    return;
                }
            } else {
                Log::info('Medicine reminder schedule status updated', [
                    'schedule_id' => $schedule->id,
                    'prescription_id' => $schedule->prescription_id,
                    'medicine_id' => $schedule->medicine_id,
                    'workflow_execution_id' => null,
                    'previous_status' => ScheduleStatus::Pending->value,
                    'new_status' => ScheduleStatus::Processing->value,
                    'result' => 'claimed',
                ]);
            }
        }

        if ($schedule->fresh()?->status !== ScheduleStatus::Processing->value) {
            return;
        }

        try {
            MedicineReminderDue::dispatch($schedule);

            $this->repository->createReminderLog(
                $schedule->id,
                ReminderLogStatus::Started->value,
                'MedicineReminderDue dispatched',
                ['occurrence_id' => MedicineReminderDue::occurrenceIdFor($schedule)]
            );
        } catch (\Throwable $e) {
            report($e);

            $this->repository->createReminderLog(
                $schedule->id,
                ReminderLogStatus::Failed->value,
                $e->getMessage()
            );

            app(MedicineReminderScheduleFinalizer::class)->onDispatchFailed(
                ['schedule_id' => $schedule->id],
                $e->getMessage()
            );
        }
    }
}
