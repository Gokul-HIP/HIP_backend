<?php

namespace App\Modules\MedicineReminder\Listeners;

use App\Modules\MedicineReminder\Events\PrescriptionCreated;
use App\Modules\MedicineReminder\Interfaces\MedicineReminderInterface;
use App\Modules\MedicineReminder\Services\MedicineReminderService;
use App\Modules\Workflow\Enums\WorkflowStatus;
use App\Modules\Workflow\Services\Bridge\MedicineWorkflowBridge;
use App\Modules\Workflow\Services\Runtime\WorkflowTriggerDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class CreateMedicineReminderSchedules implements ShouldQueue
{
    public function __construct(
        protected MedicineReminderInterface $repository,
        protected MedicineReminderService $medicineReminderService,
        protected MedicineWorkflowBridge $medicineWorkflowBridge,
        protected WorkflowTriggerDispatcher $triggerDispatcher,
    ) {}

    public function handle(PrescriptionCreated $event): void
    {
        Log::info('CreateMedicineReminderSchedules: routing through WorkflowExecutor', [
            'prescription_id' => $event->prescription->id ?? null,
        ]);

        try {
            $prescription = $event->prescription;
            $prescription->loadMissing(['hospital']);

            $organizationId = $prescription->hospital?->organization_id;
            $medicineWorkflow = $this->repository->resolveActiveWorkflow(
                $organizationId ? (int) $organizationId : null
            );

            if ($medicineWorkflow) {
                $genericWorkflow = $this->medicineWorkflowBridge->syncFromMedicineWorkflow($medicineWorkflow);

                if (
                    $genericWorkflow->status === WorkflowStatus::Active->value
                    && $genericWorkflow->currentVersion
                ) {
                    $this->triggerDispatcher->dispatch(
                        triggerType: 'prescriptionAdded',
                        payload: ['prescription' => $prescription],
                        organizationId: $organizationId ? (int) $organizationId : null,
                    );

                    Log::info('CreateMedicineReminderSchedules: workflow trigger dispatched', [
                        'prescription_id' => $prescription->id,
                    ]);

                    return;
                }
            }

            $schedules = $this->medicineReminderService->createSchedulesForPrescription($prescription);

            Log::info('CreateMedicineReminderSchedules: legacy fallback completed', [
                'prescription_id' => $prescription->id,
                'schedules_created' => $schedules->count(),
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
