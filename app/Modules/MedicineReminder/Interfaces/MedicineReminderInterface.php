<?php

namespace App\Modules\MedicineReminder\Interfaces;

use App\Modules\MedicineReminder\Models\MedicineReminderSchedule;
use App\Modules\MedicineReminder\Models\MedicineWorkflow;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface MedicineReminderInterface
{
    public function listWorkflows(?int $organizationId = null, int $perPage = 15): LengthAwarePaginator;

    public function findWorkflow(int $id): ?MedicineWorkflow;

    public function createWorkflow(array $data): MedicineWorkflow;

    public function updateWorkflow(MedicineWorkflow $workflow, array $data): MedicineWorkflow;

    public function deleteWorkflow(MedicineWorkflow $workflow): bool;

    public function resolveActiveWorkflow(?int $organizationId = null): ?MedicineWorkflow;

    /** @param array<int, array<string, mixed>> $rows */
    public function insertSchedules(array $rows): Collection;

    /** @return Collection<int, MedicineReminderSchedule> */
    public function getDueSchedules(int $limit = 100): Collection;

    public function listSchedules(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function markProcessing(MedicineReminderSchedule $schedule): MedicineReminderSchedule;

    public function markSent(MedicineReminderSchedule $schedule): MedicineReminderSchedule;

    public function markFailed(MedicineReminderSchedule $schedule, ?string $reason = null): MedicineReminderSchedule;

    public function scheduleRetry(MedicineReminderSchedule $schedule, int $intervalMinutes, int $maxRetries): MedicineReminderSchedule;

    public function createReminderLog(int $scheduleId, string $status, ?string $message = null, ?array $payload = null, ?int $executionTimeMs = null): void;

    public function createNotificationLog(int $scheduleId, string $channel, string $status, ?string $response = null): void;
}
