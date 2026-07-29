<?php

namespace App\Modules\MedicineReminder\Listeners;

use App\Modules\MedicineReminder\Events\PrescriptionCreated;
use App\Modules\Workflow\Services\Runtime\WorkflowTriggerDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Starts generic prescriptionAdded workflows. Schedule rows are created by
 * PrescriptionAddedTriggerExecutor → MedicineReminderService (no medicine_workflows).
 */
class CreateMedicineReminderSchedules implements ShouldQueue
{
    public function __construct(
        protected WorkflowTriggerDispatcher $triggerDispatcher,
    ) {}

    public function handle(PrescriptionCreated $event): void
    {
        Log::info('CreateMedicineReminderSchedules: dispatching prescriptionAdded workflow', [
            'prescription_id' => $event->prescription->id ?? null,
        ]);

        try {
            $prescription = $event->prescription;
            $prescription->loadMissing(['hospital']);

            $organizationId = $prescription->hospital?->organization_id;

            $this->triggerDispatcher->dispatch(
                triggerType: 'prescriptionAdded',
                payload: ['prescription' => $prescription],
                organizationId: $organizationId ? (int) $organizationId : null,
            );
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
