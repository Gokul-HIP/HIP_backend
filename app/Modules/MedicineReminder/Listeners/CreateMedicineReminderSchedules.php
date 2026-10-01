<?php

namespace App\Modules\MedicineReminder\Listeners;

use App\Modules\MedicineReminder\Events\PrescriptionCreated;
use App\Modules\MedicineReminder\Services\MedicineReminderScheduleService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Domain schedule rows from submitted prescription medications.
 * Does not inspect workflow graphs. Workflows run via PrescriptionAdded automation.
 */
class CreateMedicineReminderSchedules implements ShouldQueue
{
    public function __construct(
        protected MedicineReminderScheduleService $scheduleService,
    ) {}

    public function handle(PrescriptionCreated $event): void
    {
        try {
            $this->scheduleService->createFromPrescription($event->prescription);
        } catch (\Throwable $e) {
            Log::error('CreateMedicineReminderSchedules failed', [
                'prescription_id' => $event->prescription->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
