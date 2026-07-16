<?php

namespace App\Modules\MedicineReminder\Listeners;

use App\Modules\MedicineReminder\Events\PrescriptionCreated;
use App\Modules\MedicineReminder\Services\MedicineReminderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class CreateMedicineReminderSchedules implements ShouldQueue
{
    public function __construct(
        protected MedicineReminderService $medicineReminderService
    ) {}

    public function handle(PrescriptionCreated $event): void
    {
        Log::info('CreateMedicineReminderSchedules: listener started', [
            'prescription_id' => $event->prescription->id ?? null,
        ]);

        try {
            $schedules = $this->medicineReminderService->createSchedulesForPrescription(
                $event->prescription
            );

            Log::info('CreateMedicineReminderSchedules: listener finished', [
                'prescription_id' => $event->prescription->id,
                'schedules_created' => $schedules->count(),
                'schedule_ids' => $schedules->pluck('id')->all(),
            ]);
        } catch (\Throwable $e) {
            Log::error('CreateMedicineReminderSchedules: exception', [
                'prescription_id' => $event->prescription->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
