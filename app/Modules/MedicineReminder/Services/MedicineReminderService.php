<?php

namespace App\Modules\MedicineReminder\Services;

use App\Models\Prescription;
use App\Modules\MedicineReminder\Enums\ScheduleStatus;
use App\Modules\MedicineReminder\Interfaces\MedicineReminderInterface;
use App\Modules\MedicineReminder\Models\MedicineWorkflow;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Services\Bridge\MedicineWorkflowBridge;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MedicineReminderService
{
    public const DEFAULT_MESSAGE_TEMPLATE = 'Hi {{patient_name}}, reminder from {{hospital_name}} to take {{medicine_name}}.';

    public function __construct(
        protected MedicineReminderInterface $repository,
        protected MedicineWorkflowBridge $medicineWorkflowBridge,
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
     * Build reminder schedule rows from a published generic workflow definition.
     *
     * @param  Workflow|null  $workflow  Preferred: the workflow currently executing (prescriptionAdded).
     */
    public function createSchedulesForPrescription(Prescription $prescription, ?Workflow $workflow = null): Collection
    {
        $prescription->loadMissing(['patient', 'doctor', 'hospital.organization', 'member']);

        Log::info('createSchedulesForPrescription: start', [
            'prescription_id' => $prescription->id,
            'status' => $prescription->status,
            'patient_id' => $prescription->patient_id,
            'hospital_id' => $prescription->hospital_id,
            'medications_raw' => $prescription->getRawOriginal('medications') ?? $prescription->medications,
        ]);

        $medications = array_values($prescription->medications ?? []);

        Log::info('createSchedulesForPrescription: decoded medications', [
            'prescription_id' => $prescription->id,
            'medications' => $medications,
            'medications_count' => count($medications),
        ]);

        if ($medications === []) {
            Log::warning('createSchedulesForPrescription: early return — no medications', [
                'prescription_id' => $prescription->id,
            ]);

            return collect();
        }

        $organizationId = $prescription->hospital?->organization_id;
        $workflow ??= $this->medicineWorkflowBridge->resolveActivePharmacyWorkflow(
            $organizationId ? (int) $organizationId : null
        );

        Log::info('createSchedulesForPrescription: workflow resolve', [
            'prescription_id' => $prescription->id,
            'organization_id' => $organizationId,
            'workflow_found' => (bool) $workflow,
            'workflow_id' => $workflow?->id,
            'workflow_status' => $workflow?->status,
            'trigger_type' => $workflow?->trigger_type,
        ]);

        if (! $workflow) {
            Log::warning('createSchedulesForPrescription: no active pharmacy workflow — skipping schedules', [
                'prescription_id' => $prescription->id,
                'organization_id' => $organizationId,
            ]);

            return collect();
        }

        if (! $workflow->isActive()) {
            Log::warning('createSchedulesForPrescription: early return — workflow inactive', [
                'workflow_id' => $workflow->id,
                'workflow_status' => $workflow->status,
                'prescription_id' => $prescription->id,
            ]);

            return collect();
        }

        $document = $this->medicineWorkflowBridge->definitionFor($workflow);

        Log::info('createSchedulesForPrescription: workflow configuration', [
            'prescription_id' => $prescription->id,
            'workflow_id' => $workflow->id,
            'workflow_status' => $workflow->status,
            'configuration' => $document,
        ]);

        $reminderNodeConfigs = $this->extractMedicineReminderNodeConfigs($document);

        if ($reminderNodeConfigs === []) {
            Log::warning('createSchedulesForPrescription: no medicineReminder node — using default node data', [
                'workflow_id' => $workflow->id,
                'prescription_id' => $prescription->id,
                'configuration_keys' => array_keys($document),
                'node_types' => collect($document['nodes'] ?? [])->pluck('type')->all(),
            ]);

            $reminderNodeConfigs = [$this->defaultReminderNodeData()];
        }

        Log::info('createSchedulesForPrescription: reminder node configs', [
            'prescription_id' => $prescription->id,
            'node_config_count' => count($reminderNodeConfigs),
            'reminder_node_configs' => $reminderNodeConfigs,
        ]);

        $rows = [];
        $now = Carbon::now();
        $loopCount = 0;

        foreach ($reminderNodeConfigs as $nodeConfig) {
            foreach ($medications as $index => $medication) {
                $loopCount++;
                $times = $this->calculateReminderTimes($medication, $nodeConfig, $now);

                Log::info('createSchedulesForPrescription: calculated times', [
                    'prescription_id' => $prescription->id,
                    'medication_index' => $index,
                    'medication' => $medication,
                    'times_count' => count($times),
                    'times' => array_map(fn (Carbon $t) => $t->toDateTimeString(), $times),
                ]);

                foreach ($times as $scheduledAt) {
                    $medicineId = $medication['medicine_id'] ?? null;
                    $medicineId = filled($medicineId) ? (int) $medicineId : null;

                    $row = [
                        'workflow_id' => $workflow->id,
                        'patient_id' => $prescription->patient_id,
                        'prescription_id' => $prescription->id,
                        'prescription_item_id' => $index,
                        'medicine_id' => $medicineId,
                        'scheduled_at' => $scheduledAt->toDateTimeString(),
                        'status' => ScheduleStatus::Pending->value,
                        'retry_count' => 0,
                        'next_retry_at' => null,
                        'channels' => array_values($nodeConfig['channels'] ?? ['push']),
                        'message_template' => (string) ($nodeConfig['message_template'] ?? self::DEFAULT_MESSAGE_TEMPLATE),
                    ];

                    Log::info('createSchedulesForPrescription: schedule payload before insert', [
                        'prescription_id' => $prescription->id,
                        'payload' => $row,
                    ]);

                    $rows[] = $row;
                }
            }
        }

        Log::info('createSchedulesForPrescription: loop summary', [
            'prescription_id' => $prescription->id,
            'loop_count' => $loopCount,
            'rows_prepared' => count($rows),
        ]);

        if ($rows === []) {
            Log::warning('createSchedulesForPrescription: early return — zero rows after time calculation', [
                'prescription_id' => $prescription->id,
                'reason' => 'calculateReminderTimes returned no future slots for all medications',
            ]);

            return collect();
        }

        $created = $this->repository->insertSchedules($rows);

        Log::info('createSchedulesForPrescription: insert result', [
            'prescription_id' => $prescription->id,
            'inserted_count' => $created->count(),
            'inserted_ids' => $created->pluck('id')->all(),
        ]);

        return $created;
    }

    /**
     * @param  array<string, mixed>  $medication
     * @param  array<string, mixed>  $nodeConfig
     * @return array<int, Carbon>
     */
    public function calculateReminderTimes(array $medication, array $nodeConfig, Carbon $from): array
    {
        $frequencyKey = $this->normalizeFrequencyKey((string) ($medication['frequency'] ?? ''));
        $defaultTimes = $nodeConfig['default_times'] ?? [];

        $dailyTimes = $defaultTimes[$frequencyKey] ?? null;
        if (! is_array($dailyTimes) || $dailyTimes === []) {
            $dailyTimes = $defaultTimes['once_daily'] ?? null;
        }
        if (! is_array($dailyTimes) || $dailyTimes === []) {
            $dailyTimes = ['09:00'];
        }

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

        if ($scheduled === [] && $days > 0) {
            $fallback = $from->copy()->addDay()->setTime(9, 0, 0);
            $scheduled[] = $fallback;

            Log::info('createSchedulesForPrescription: using fallback reminder time', [
                'fallback' => $fallback->toDateTimeString(),
                'frequency_key' => $frequencyKey,
                'daily_times' => $dailyTimes,
            ]);
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

    /**
     * @param  array<string, mixed>  $document
     * @return array<int, array<string, mixed>>
     */
    protected function extractMedicineReminderNodeConfigs(array $document): array
    {
        $defaultNodeData = $this->defaultReminderNodeData();
        $nodes = $document['nodes'] ?? [];
        $configs = [];
        $supportedTypes = [
            'medicineReminder',
            'medicine-reminder',
            'medicine_reminder',
            'MedicineReminder',
        ];

        foreach ($nodes as $node) {
            $data = is_array($node['data'] ?? null) ? $node['data'] : [];

            $type = (string) (
                $data['nodeType']
                ?? $node['type']
                ?? ''
            );

            Log::info('Detected Node Type', [
                'type' => $type,
                'nodeType' => $data['nodeType'] ?? null,
                'react_flow_type' => $node['type'] ?? null,
            ]);

            if (! in_array($type, $supportedTypes, true)) {
                continue;
            }

            Log::info('Medicine Reminder node detected', [
                'type' => $type,
                'node_id' => $node['id'] ?? null,
            ]);

            if (isset($data['messageTemplate']) && ! isset($data['message_template'])) {
                $data['message_template'] = $data['messageTemplate'];
            }
            if (isset($data['defaultTimes']) && ! isset($data['default_times'])) {
                $data['default_times'] = $data['defaultTimes'];
            }

            $merged = array_replace_recursive($defaultNodeData, $data);

            if (isset($data['channels']) && is_array($data['channels'])) {
                $merged['channels'] = array_values($data['channels']);
            }

            Log::info('Using Node Configuration', [
                'channels' => $merged['channels'] ?? [],
                'retry' => $merged['retry'] ?? null,
                'message_template' => $merged['message_template'] ?? null,
            ]);

            $configs[] = $merged;
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
