<?php

namespace App\Modules\MedicineReminder\Jobs;

use App\Modules\MedicineReminder\Enums\ReminderLogStatus;
use App\Modules\MedicineReminder\Interfaces\MedicineReminderInterface;
use App\Modules\MedicineReminder\Models\MedicineReminderSchedule;
use App\Modules\MedicineReminder\Services\MedicineReminderExecutionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendMedicineReminderJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public int $scheduleId
    ) {}

    public function handle(MedicineReminderExecutionService $executionService): void
    {
        $executionService->execute($this->scheduleId);
    }

    public function failed(?\Throwable $exception): void
    {
        $schedule = MedicineReminderSchedule::query()->find($this->scheduleId);

        if (! $schedule) {
            return;
        }

        app(MedicineReminderInterface::class)->markFailed(
            $schedule,
            $exception?->getMessage() ?? 'Queue job failed'
        );

        app(MedicineReminderInterface::class)->createReminderLog(
            $schedule->id,
            ReminderLogStatus::Failed->value,
            $exception?->getMessage() ?? 'Queue job failed permanently'
        );
    }
}
