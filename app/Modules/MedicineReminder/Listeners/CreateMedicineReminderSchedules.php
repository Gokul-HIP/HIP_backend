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
        try {
            $schedules = $this->medicineReminderService->createSchedulesForPrescription(
                $event->prescription
            );

            Log::info('Medicine reminder schedules created', [
                'prescription_id' => $event->prescription->id,
                'count' => $schedules->count(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to create medicine reminder schedules', [
                'prescription_id' => $event->prescription->id,
                'error' => $e->getMessage(),
            ]);
            report($e);
        }
    }
}
