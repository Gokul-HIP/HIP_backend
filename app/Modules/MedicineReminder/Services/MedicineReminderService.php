<?php

namespace App\Modules\MedicineReminder\Services;

use App\Models\Prescription;
use App\Modules\MedicineReminder\Enums\ScheduleStatus;
use App\Modules\MedicineReminder\Enums\WorkflowStatus;
use App\Modules\MedicineReminder\Interfaces\MedicineReminderInterface;
use App\Modules\MedicineReminder\Models\MedicineWorkflow;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MedicineReminderService
{
    public const DEFAULT_MESSAGE_TEMPLATE = 'Hi {{patient_name}}, reminder from {{hospital_name}} to take {{medicine_name}}.';

    public function __construct(
        protected MedicineReminderInterface $repository,
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
        $data['status'] = $data['status'] ?? WorkflowStatus::Active->value;

        return $this->repository->createWorkflow($data);
    }

    public function updateWorkflow(MedicineWorkflow $workflow, array $data): MedicineWorkflow
    {
        return $this->repository->updateWorkflow($workflow, $data);
    }

    public function deleteWorkflow(MedicineWorkflow $workflow): bool
    {
        return $this->repository->deleteWorkflow($workflow);
    }

    public function listSchedules(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->listSchedules($filters, $perPage);
    }

    public function createSchedulesForPrescription(Prescription $prescription): Collection
    {
        $prescription->loadMissing(['patient', 'doctor', 'hospital.organization', 'member']);

        $medications = array_values($prescription->medications ?? []);

        if ($medications === []) {
            return collect();
        }

        $organizationId = $prescription->hospital?->organization_id;
        $workflow = $this->repository->resolveActiveWorkflow($organizationId ? (int) $organizationId : null);

        if (! $workflow) {
            $workflow = $this->createDefaultWorkflow($organizationId ? (int) $organizationId : null);
        }

        if (! $workflow->isActive()) {
            Log::info('Medicine reminder skipped: workflow inactive', [
                'workflow_id' => $workflow->id,
                'prescription_id' => $prescription->id,
            ]);

            return collect();
        }

        $document = is_array($workflow->configuration) ? $workflow->configuration : [];
        $reminderNodeConfigs = $this->extractMedicineReminderNodeConfigs($document);

        if ($reminderNodeConfigs === []) {
            Log::info('Medicine reminder skipped: no medicineReminder node found', [
                'workflow_id' => $workflow->id,
                'prescription_id' => $prescription->id,
            ]);

            return collect();
        }

        $rows = [];
        $now = Carbon::now();

        foreach ($reminderNodeConfigs as $nodeConfig) {
            foreach ($medications as $index => $medication) {
                $times = $this->calculateReminderTimes($medication, $nodeConfig, $now);

                foreach ($times as $scheduledAt) {
                    $rows[] = [
                        'workflow_id' => $workflow->id,
                        'patient_id' => $prescription->patient_id,
                        'prescription_id' => $prescription->id,
                        'prescription_item_id' => $index,
                        'medicine_id' => isset($medication['medicine_id']) ? (int) $medication['medicine_id'] : null,
                        'scheduled_at' => $scheduledAt->toDateTimeString(),
                        'status' => ScheduleStatus::Pending->value,
                        'retry_count' => 0,
                        'next_retry_at' => null,
                        'channels' => array_values($nodeConfig['channels'] ?? ['push']),
                        'message_template' => (string) ($nodeConfig['message_template'] ?? self::DEFAULT_MESSAGE_TEMPLATE),
                    ];
                }
            }
        }

        if ($rows === []) {
            return collect();
        }

        return $this->repository->insertSchedules($rows);
    }

    /**
     * @param  array<string, mixed>  $medication
     * @param  array<string, mixed>  $nodeConfig
     * @return array<int, Carbon>
     */
    public function calculateReminderTimes(array $medication, array $nodeConfig, Carbon $from): array
    {
        $frequencyKey = $this->normalizeFrequencyKey((string) ($medication['frequency'] ?? ''));
        $dailyTimes = $nodeConfig['default_times'][$frequencyKey]
            ?? $nodeConfig['default_times']['once_daily']
            ?? ['09:00'];

        $days = $this->parseDurationDays((string) ($medication['duration'] ?? '7 Days'));
        $startDate = ($nodeConfig['delay'] ?? 'immediately') === 'immediately'
            ? $from->copy()->startOfDay()
            : $from->copy()->addDay()->startOfDay();

        $scheduled = [];

        for ($day = 0; $day < $days; $day++) {
            $date = $startDate->copy()->addDays($day);

            foreach ($dailyTimes as $time) {
                [$hour, $minute] = array_pad(array_map('intval', explode(':', (string) $time)), 2, 0);
                $at = $date->copy()->setTime($hour, $minute, 0);

                if ($at->greaterThan($from)) {
                    $scheduled[] = $at;
                }
            }
        }

        return $scheduled;
    }

    public function normalizeFrequencyKey(string $frequency): string
    {
        $normalized = strtolower(trim($frequency));

        return match (true) {
            str_contains($normalized, 'every 12') => 'every_12_hours',
            str_contains($normalized, 'every 8') => 'every_8_hours',
            str_contains($normalized, 'every 6') => 'every_6_hours',
            str_contains($normalized, 'four') || str_contains($normalized, 'qid') || preg_match('/\b4\b/', $normalized) => 'four_times_daily',
            str_contains($normalized, 'thrice') || str_contains($normalized, 'three') || str_contains($normalized, 'tid') || preg_match('/\b3\b/', $normalized) => 'thrice_daily',
            str_contains($normalized, 'twice') || preg_match('/\bbd\b/', $normalized) || preg_match('/\b2\b/', $normalized) => 'twice_daily',
            default => 'once_daily',
        };
    }

    public function parseDurationDays(string $duration): int
    {
        $duration = trim($duration);

        if (preg_match('/(\d+)\s*week/i', $duration, $m)) {
            return max(1, (int) $m[1] * 7);
        }

        if (preg_match('/(\d+)\s*month/i', $duration, $m)) {
            return max(1, (int) $m[1] * 30);
        }

        if (preg_match('/(\d+)/', $duration, $m)) {
            return max(1, min(90, (int) $m[1]));
        }

        return 7;
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultReminderNodeData(): array
    {
        return [
            'displayName' => 'Medicine Reminder',
            'triggerTiming' => '30_minutes_before',
            'delay' => 'immediately',
            'channels' => ['push'],
            'retry' => [
                'enabled' => true,
                'interval_minutes' => 15,
                'max_retries' => 3,
                'escalate' => false,
            ],
            'default_times' => [
                'once_daily' => ['09:00'],
                'twice_daily' => ['09:00', '21:00'],
                'thrice_daily' => ['09:00', '14:00', '21:00'],
                'four_times_daily' => ['08:00', '12:00', '16:00', '20:00'],
                'every_6_hours' => ['06:00', '12:00', '18:00', '00:00'],
                'every_8_hours' => ['08:00', '16:00', '00:00'],
                'every_12_hours' => ['09:00', '21:00'],
            ],
            'message_template' => self::DEFAULT_MESSAGE_TEMPLATE,
        ];
    }

    protected function createDefaultWorkflow(?int $organizationId): MedicineWorkflow
    {
        return $this->createWorkflow([
            'organization_id' => $organizationId,
            'name' => 'Default Medicine Reminder',
            'status' => WorkflowStatus::Active->value,
            'configuration' => $this->defaultWorkflowDocument(),
            'created_by' => null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultWorkflowDocument(): array
    {
        return [
            'builderVersion' => '1.0',
            'reactFlowVersion' => '12',
            'viewport' => [
                'x' => 0,
                'y' => 0,
                'zoom' => 1,
            ],
            'nodes' => [
                [
                    'id' => 'start',
                    'type' => 'workflowStart',
                    'position' => ['x' => 120, 'y' => 200],
                    'data' => ['label' => 'Workflow Start'],
                ],
                [
                    'id' => 'medicine',
                    'type' => 'medicineReminder',
                    'position' => ['x' => 450, 'y' => 200],
                    'data' => $this->defaultReminderNodeData(),
                ],
            ],
            'edges' => [
                [
                    'id' => 'edge-1',
                    'source' => 'start',
                    'target' => 'medicine',
                    'type' => 'smoothstep',
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array<int, array<string, mixed>>
     */
    protected function extractMedicineReminderNodeConfigs(array $document): array
    {
        $defaultNodeData = $this->defaultReminderNodeData();
        $nodes = $document['nodes'] ?? [];
        $configs = [];

        foreach ($nodes as $node) {
            if (($node['type'] ?? null) !== 'medicineReminder') {
                continue;
            }

            $data = is_array($node['data'] ?? null) ? $node['data'] : [];
            $configs[] = array_replace_recursive($defaultNodeData, $data);
        }

        if ($configs !== []) {
            return $configs;
        }

        if (
            isset($document['channels'])
            || isset($document['retry'])
            || isset($document['default_times'])
            || isset($document['message_template'])
        ) {
            return [array_replace_recursive($defaultNodeData, $document)];
        }

        return [];
    }
}
