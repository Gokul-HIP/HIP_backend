<?php

namespace App\Modules\MedicineReminder\Services;

use App\Modules\MedicineReminder\Enums\ReminderLogStatus;
use App\Modules\MedicineReminder\Enums\ScheduleStatus;
use App\Modules\MedicineReminder\Interfaces\MedicineReminderInterface;
use App\Modules\MedicineReminder\Models\MedicineReminderSchedule;
use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Support\NodeTypeNormalizer;
use Illuminate\Support\Facades\Log;

class MedicineReminderScheduleFinalizer
{
    public function __construct(
        protected MedicineReminderInterface $repository,
    ) {}

    public function onWorkflowCompleted(WorkflowExecution $execution): void
    {
        $this->finalizeFromExecution($execution, success: true);
    }

    public function onWorkflowFailed(WorkflowExecution $execution): void
    {
        $this->finalizeFromExecution($execution, success: false);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function onNoPublishedWorkflow(array $context): void
    {
        $scheduleId = $this->scheduleIdFrom($context);
        if ($scheduleId === null) {
            return;
        }

        $this->transition(
            $scheduleId,
            ScheduleStatus::Failed->value,
            'failed',
            null,
            'No published medicineReminderDue workflow for hospital/organization'
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function onDispatchFailed(array $context, string $message): void
    {
        $scheduleId = $this->scheduleIdFrom($context);
        if ($scheduleId === null) {
            return;
        }

        $this->transition($scheduleId, ScheduleStatus::Failed->value, 'failed', null, $message);
    }

    /**
     * Duplicate occurrence: adopt the existing execution's terminal status.
     *
     * @param  array<string, mixed>  $context
     */
    public function onDuplicateOccurrence(array $context): void
    {
        $scheduleId = $this->scheduleIdFrom($context);
        if ($scheduleId === null) {
            return;
        }

        $occurrenceId = (string) ($context['event_occurrence_id'] ?? '');
        $query = WorkflowExecution::query()
            ->where('trigger_type', 'medicineReminderDue');

        if ($occurrenceId !== '') {
            $query->where('context->event_occurrence_id', $occurrenceId);
        } else {
            $query->where('context->schedule_id', $scheduleId);
        }

        $executions = $query->get();
        if ($executions->contains(fn (WorkflowExecution $e) => $e->status === WorkflowExecutionStatus::Completed->value)) {
            $this->transition(
                $scheduleId,
                ScheduleStatus::Sent->value,
                'sent',
                (int) $executions->firstWhere('status', WorkflowExecutionStatus::Completed->value)?->id,
                'duplicate occurrence already completed'
            );

            return;
        }

        if ($executions->every(fn (WorkflowExecution $e) => $e->status === WorkflowExecutionStatus::Failed->value)) {
            $failed = $executions->first();
            $this->transition(
                $scheduleId,
                ScheduleStatus::Failed->value,
                'failed',
                $failed?->id,
                (string) ($failed?->failure_reason ?? 'duplicate occurrence already failed')
            );
        }
    }

    protected function finalizeFromExecution(WorkflowExecution $execution, bool $success): void
    {
        if (NodeTypeNormalizer::normalize((string) $execution->trigger_type) !== 'medicineReminderDue') {
            return;
        }

        $context = is_array($execution->context) ? $execution->context : [];
        $scheduleId = $this->scheduleIdFrom($context);
        if ($scheduleId === null) {
            return;
        }

        if ($success) {
            $this->transition(
                $scheduleId,
                ScheduleStatus::Sent->value,
                'sent',
                $execution->id,
                'workflow execution completed'
            );

            return;
        }

        $siblings = WorkflowExecution::query()
            ->where('trigger_type', 'medicineReminderDue')
            ->where('context->schedule_id', $scheduleId)
            ->get();

        if ($siblings->contains(fn (WorkflowExecution $e) => $e->status === WorkflowExecutionStatus::Completed->value)) {
            $this->transition(
                $scheduleId,
                ScheduleStatus::Sent->value,
                'sent',
                $execution->id,
                'sibling workflow execution completed'
            );

            return;
        }

        $inFlight = $siblings->contains(fn (WorkflowExecution $e) => in_array($e->status, [
            WorkflowExecutionStatus::Running->value,
            WorkflowExecutionStatus::Waiting->value,
            WorkflowExecutionStatus::Started->value,
        ], true));

        if ($inFlight) {
            return;
        }

        $this->transition(
            $scheduleId,
            ScheduleStatus::Failed->value,
            'failed',
            $execution->id,
            (string) ($execution->failure_reason ?? 'workflow execution failed')
        );
    }

    protected function transition(
        int $scheduleId,
        string $newStatus,
        string $result,
        ?int $workflowExecutionId,
        string $message
    ): void {
        $schedule = MedicineReminderSchedule::query()->find($scheduleId);
        if (! $schedule) {
            return;
        }

        if (in_array($schedule->status, [ScheduleStatus::Sent->value, ScheduleStatus::Cancelled->value], true)) {
            Log::info('Medicine reminder schedule already terminal', [
                'schedule_id' => $schedule->id,
                'prescription_id' => $schedule->prescription_id,
                'medicine_id' => $schedule->medicine_id,
                'workflow_execution_id' => $workflowExecutionId,
                'previous_status' => $schedule->status,
                'new_status' => $schedule->status,
                'result' => 'ignored_'.$result,
            ]);

            return;
        }

        if (! in_array($schedule->status, [ScheduleStatus::Pending->value, ScheduleStatus::Processing->value], true)) {
            return;
        }

        $previous = $schedule->status;
        $payload = [
            'status' => $newStatus,
            'next_retry_at' => null,
        ];

        if ($newStatus === ScheduleStatus::Failed->value) {
            $payload['retry_count'] = (int) $schedule->retry_count + 1;
        }

        $updated = MedicineReminderSchedule::query()
            ->whereKey($schedule->id)
            ->whereIn('status', [ScheduleStatus::Pending->value, ScheduleStatus::Processing->value])
            ->update($payload);

        if ($updated === 0) {
            return;
        }

        $logStatus = $newStatus === ScheduleStatus::Sent->value
            ? ReminderLogStatus::Completed->value
            : ReminderLogStatus::Failed->value;

        $this->repository->createReminderLog(
            $schedule->id,
            $logStatus,
            $message,
            [
                'workflow_execution_id' => $workflowExecutionId,
                'previous_status' => $previous,
                'new_status' => $newStatus,
                'result' => $result,
            ]
        );

        Log::info('Medicine reminder schedule status updated', [
            'schedule_id' => $schedule->id,
            'prescription_id' => $schedule->prescription_id,
            'medicine_id' => $schedule->medicine_id,
            'workflow_execution_id' => $workflowExecutionId,
            'previous_status' => $previous,
            'new_status' => $newStatus,
            'result' => $result,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function scheduleIdFrom(array $context): ?int
    {
        $raw = $context['schedule_id']
            ?? data_get($context, 'meta.schedule_id')
            ?? data_get($context, 'schedule.id');

        if ($raw === null || $raw === '') {
            return null;
        }

        $id = (int) $raw;

        return $id > 0 ? $id : null;
    }
}
