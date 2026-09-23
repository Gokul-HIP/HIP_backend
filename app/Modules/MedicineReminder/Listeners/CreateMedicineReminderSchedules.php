<?php

namespace App\Modules\MedicineReminder\Listeners;

use App\Modules\Automation\TriggerHandlers\HospitalAutomationTriggerService;
use App\Modules\MedicineReminder\Events\PrescriptionCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Starts generic prescriptionAdded workflows. Schedule rows are created by
 * PrescriptionAddedTriggerNodeProcessor → MedicineReminderService (no medicine_workflows).
 */
class CreateMedicineReminderSchedules implements ShouldQueue
{
    public function __construct(
        protected HospitalAutomationTriggerService $triggerService,
    ) {}

    public function handle(PrescriptionCreated $event): void
    {
        Log::info('CreateMedicineReminderSchedules: dispatching prescriptionAdded workflow', [
            'prescription_id' => $event->prescription->id ?? null,
        ]);

        try {
            $prescription = $event->prescription;
            $prescription->loadMissing(['hospital']);

            $hospitalId = $prescription->hospital_id ? (int) $prescription->hospital_id : null;
            $organizationId = $prescription->hospital?->organization_id
                ? (int) $prescription->hospital->organization_id
                : null;

            $this->triggerService->dispatch('prescriptionAdded', [
                'prescription' => $prescription,
                'hospital_id' => $hospitalId,
                'organization_id' => $organizationId,
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
