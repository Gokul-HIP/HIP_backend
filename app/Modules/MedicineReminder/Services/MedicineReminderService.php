<?php

namespace App\Modules\MedicineReminder\Services;

use App\Models\Prescription;
use App\Modules\MedicineReminder\Interfaces\MedicineReminderInterface;
use App\Modules\MedicineReminder\Models\MedicineWorkflow;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Services\Bridge\MedicineWorkflowBridge;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use RuntimeException;

class MedicineReminderService
{
    public function __construct(
        protected MedicineReminderInterface $repository,
        protected MedicineWorkflowBridge $medicineWorkflowBridge,
        protected MedicineReminderScheduleService $scheduleService,
    ) {}

    public function listWorkflows(?int $organizationId = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->listWorkflows($organizationId, $perPage);
    }

    public function findWorkflowOrFail(int $id): MedicineWorkflow
    {
        $workflow = $this->repository->findWorkflow($id);

        if (! $workflow) {
            abort(404, 'Medicine workflow not found');
        }

        return $workflow;
    }

    public function createWorkflow(array $data): MedicineWorkflow
    {
        throw new RuntimeException(
            'Legacy medicine_workflows creation is disabled. Create Medicine Reminder workflows via POST /api/workflows.'
        );
    }

    public function updateWorkflow(MedicineWorkflow $workflow, array $data): MedicineWorkflow
    {
        throw new RuntimeException(
            'Legacy medicine_workflows updates are disabled. Update Medicine Reminder workflows via PUT /api/workflows/{id}.'
        );
    }

    public function deleteWorkflow(MedicineWorkflow $workflow): bool
    {
        throw new RuntimeException(
            'Legacy medicine_workflows deletion is disabled. Delete Medicine Reminder workflows via DELETE /api/workflows/{id}.'
        );
    }

    public function listSchedules(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->listSchedules($filters, $perPage);
    }

    /**
     * Domain scheduling from prescription medications. Independent of workflow graphs.
     */
    public function createSchedulesForPrescription(Prescription $prescription, ?Workflow $workflow = null): Collection
    {
        unset($workflow);

        return $this->scheduleService->createFromPrescription($prescription);
    }

    /**
     * @param  array<string, mixed>  $medication
     * @return list<Carbon>
     */
    public function calculateReminderTimes(array $medication, array $nodeConfig, Carbon $from): array
    {
        unset($nodeConfig);

        return $this->scheduleService->calculateReminderTimes($medication, $from);
    }

    public function normalizeFrequencyKey(string $frequency): string
    {
        return $this->scheduleService->normalizeFrequencyKey($frequency);
    }

    public function parseDurationDays(string $duration): int
    {
        return $this->scheduleService->parseDurationDays($duration) ?? 0;
    }
}
