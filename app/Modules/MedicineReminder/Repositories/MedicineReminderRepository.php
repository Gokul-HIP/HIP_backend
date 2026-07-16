<?php

namespace App\Modules\MedicineReminder\Repositories;

use App\Models\Prescription;
use App\Modules\MedicineReminder\Enums\ScheduleStatus;
use App\Modules\MedicineReminder\Enums\WorkflowStatus;
use App\Modules\MedicineReminder\Interfaces\MedicineReminderInterface;
use App\Modules\MedicineReminder\Models\MedicineNotificationLog;
use App\Modules\MedicineReminder\Models\MedicineReminderLog;
use App\Modules\MedicineReminder\Models\MedicineReminderSchedule;
use App\Modules\MedicineReminder\Models\MedicineWorkflow;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MedicineReminderRepository implements MedicineReminderInterface
{
    public function listWorkflows(?int $organizationId = null, int $perPage = 15): LengthAwarePaginator
    {
        return MedicineWorkflow::query()
            ->when($organizationId, fn ($q) => $q->where('organization_id', $organizationId))
            ->latest()
            ->paginate($perPage);
    }

    public function findWorkflow(int $id): ?MedicineWorkflow
    {
        return MedicineWorkflow::query()->find($id);
    }

    public function createWorkflow(array $data): MedicineWorkflow
    {
        // TEMP: verify configuration key exists at persistence time
        Log::info('MedicineWorkflowRepository::createWorkflow', [
            'has_configuration' => array_key_exists('configuration', $data),
            'configuration_type' => gettype($data['configuration'] ?? null),
        ]);

        return MedicineWorkflow::query()->create($data);
    }

    public function updateWorkflow(MedicineWorkflow $workflow, array $data): MedicineWorkflow
    {
        // TEMP: verify configuration key exists at persistence time
        Log::info('MedicineWorkflowRepository::updateWorkflow', [
            'workflow_id' => $workflow->id,
            'has_configuration' => array_key_exists('configuration', $data),
            'configuration_type' => gettype($data['configuration'] ?? null),
        ]);

        $workflow->update($data);

        return $workflow->fresh();
    }

    public function deleteWorkflow(MedicineWorkflow $workflow): bool
    {
        return (bool) $workflow->delete();
    }

    public function resolveActiveWorkflow(?int $organizationId = null): ?MedicineWorkflow
    {
        $query = MedicineWorkflow::query()
            ->where('status', WorkflowStatus::Active->value);

        if ($organizationId) {
            $orgWorkflow = (clone $query)->where('organization_id', $organizationId)->latest()->first();
            if ($orgWorkflow) {
                return $orgWorkflow;
            }
        }

        return $query->whereNull('organization_id')->latest()->first()
            ?? $query->latest()->first();
    }

    public function insertSchedules(array $rows): Collection
    {
        if ($rows === []) {
            return collect();
        }

        $now = now();
        $payload = array_map(function (array $row) use ($now) {
            return array_merge($row, [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }, $rows);

        DB::table('medicine_reminder_schedules')->insert($payload);

        $prescriptionId = $rows[0]['prescription_id'] ?? null;

        return MedicineReminderSchedule::query()
            ->where('prescription_id', $prescriptionId)
            ->where('workflow_id', $rows[0]['workflow_id'] ?? null)
            ->where('created_at', '>=', $now->copy()->subSecond())
            ->get();
    }

    public function getDueSchedules(int $limit = 100): Collection
    {
        $now = Carbon::now();

        return MedicineReminderSchedule::query()
            ->where(function ($q) use ($now) {
                $q->where(function ($inner) use ($now) {
                    $inner->where('status', ScheduleStatus::Pending->value)
                        ->where('scheduled_at', '<=', $now);
                })->orWhere(function ($inner) use ($now) {
                    $inner->where('status', ScheduleStatus::Pending->value)
                        ->whereNotNull('next_retry_at')
                        ->where('next_retry_at', '<=', $now);
                });
            })
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get();
    }

    public function listSchedules(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return MedicineReminderSchedule::query()
            ->with(['workflow', 'patient', 'prescription'])
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['patient_id'] ?? null, fn ($q, $id) => $q->where('patient_id', $id))
            ->when($filters['prescription_id'] ?? null, fn ($q, $id) => $q->where('prescription_id', $id))
            ->when($filters['workflow_id'] ?? null, fn ($q, $id) => $q->where('workflow_id', $id))
            ->latest('scheduled_at')
            ->paginate($perPage);
    }

    public function markProcessing(MedicineReminderSchedule $schedule): MedicineReminderSchedule
    {
        $schedule->update(['status' => ScheduleStatus::Processing->value]);

        return $schedule->fresh();
    }

    public function markSent(MedicineReminderSchedule $schedule): MedicineReminderSchedule
    {
        $schedule->update([
            'status' => ScheduleStatus::Sent->value,
            'next_retry_at' => null,
        ]);

        return $schedule->fresh();
    }

    public function markFailed(MedicineReminderSchedule $schedule, ?string $reason = null): MedicineReminderSchedule
    {
        $schedule->update([
            'status' => ScheduleStatus::Failed->value,
            'next_retry_at' => null,
        ]);

        return $schedule->fresh();
    }

    public function scheduleRetry(MedicineReminderSchedule $schedule, int $intervalMinutes, int $maxRetries): MedicineReminderSchedule
    {
        $retryCount = (int) $schedule->retry_count + 1;

        if ($retryCount > $maxRetries) {
            return $this->markFailed($schedule, 'Maximum retry count exceeded');
        }

        $schedule->update([
            'status' => ScheduleStatus::Pending->value,
            'retry_count' => $retryCount,
            'next_retry_at' => Carbon::now()->addMinutes($intervalMinutes),
        ]);

        return $schedule->fresh();
    }

    public function createReminderLog(
        int $scheduleId,
        string $status,
        ?string $message = null,
        ?array $payload = null,
        ?int $executionTimeMs = null
    ): void {
        MedicineReminderLog::query()->create([
            'schedule_id' => $scheduleId,
            'status' => $status,
            'message' => $message,
            'payload' => $payload,
            'execution_time' => $executionTimeMs,
        ]);
    }

    public function createNotificationLog(
        int $scheduleId,
        string $channel,
        string $status,
        ?string $response = null
    ): void {
        MedicineNotificationLog::query()->create([
            'schedule_id' => $scheduleId,
            'channel' => $channel,
            'status' => $status,
            'response' => $response,
            'sent_at' => $status === 'sent' ? now() : null,
        ]);
    }
}
