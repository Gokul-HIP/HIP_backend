<?php

namespace App\Modules\Workflow\Listeners;

use App\Modules\MedicineReminder\Events\PrescriptionCreated;
use App\Modules\Workflow\Events\PrescriptionAdded;
use App\Modules\Workflow\Services\Bridge\MedicineWorkflowBridge;
use App\Modules\Workflow\Services\Runtime\WorkflowTriggerDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class StartWorkflowOnPrescriptionAdded implements ShouldQueue
{
    public function __construct(
        protected WorkflowTriggerDispatcher $triggerDispatcher,
        protected MedicineWorkflowBridge $medicineWorkflowBridge,
    ) {}

    public function handle(PrescriptionCreated|PrescriptionAdded $event): void
    {
        $prescription = $event->prescription;
        $prescription->loadMissing(['hospital']);

        Log::info('StartWorkflowOnPrescriptionAdded: dispatching workflow trigger', [
            'prescription_id' => $prescription->id,
        ]);

        $organizationId = $prescription->hospital?->organization_id;

        $this->triggerDispatcher->dispatch(
            triggerType: 'prescriptionAdded',
            payload: ['prescription' => $prescription],
            organizationId: $organizationId ? (int) $organizationId : null,
        );
    }
}
