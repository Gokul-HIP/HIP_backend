<?php

namespace App\Modules\MedicineReminder\Services;

use App\Modules\MedicineReminder\Enums\ScheduleStatus;
use App\Modules\MedicineReminder\Interfaces\MedicineReminderInterface;
use App\Modules\MedicineReminder\Jobs\SendMedicineReminderJob;
use Illuminate\Support\Facades\Log;

class MedicineReminderScheduler
{
    public function __construct(
        protected MedicineReminderInterface $repository
    ) {}

    /**
     * Find due pending schedules and dispatch queue jobs.
     */
    public function dispatchDueReminders(int $limit = 100): int
    {
        $schedules = $this->repository->getDueSchedules($limit);
        $dispatched = 0;

        foreach ($schedules as $schedule) {
            try {
                // Claim immediately so the next minute tick does not re-dispatch.
                $claimed = $schedule->newQuery()
                    ->whereKey($schedule->id)
                    ->where('status', ScheduleStatus::Pending->value)
                    ->update(['status' => ScheduleStatus::Processing->value]);

                if (! $claimed) {
                    continue;
                }

                SendMedicineReminderJob::dispatch($schedule->id);
                $dispatched++;
            } catch (\Throwable $e) {
                Log::error('Failed to dispatch medicine reminder job', [
                    'schedule_id' => $schedule->id,
                    'error' => $e->getMessage(),
                ]);

                $schedule->update(['status' => ScheduleStatus::Pending->value]);
            }
        }

        return $dispatched;
    }
}
