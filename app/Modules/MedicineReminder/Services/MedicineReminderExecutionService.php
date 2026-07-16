<?php

namespace App\Modules\MedicineReminder\Services;

use App\Modules\MedicineReminder\Enums\ReminderLogStatus;
use App\Modules\MedicineReminder\Enums\ScheduleStatus;
use App\Modules\MedicineReminder\Events\MedicineReminderTriggered;
use App\Modules\MedicineReminder\Interfaces\MedicineReminderInterface;
use App\Modules\MedicineReminder\Models\MedicineReminderSchedule;
use Illuminate\Support\Facades\Log;

class MedicineReminderExecutionService
{
    public function __construct(
        protected MedicineReminderInterface $repository,
        protected VariableResolverService $variableResolver,
        protected NotificationDispatcher $notificationDispatcher,
        protected MedicineReminderService $medicineReminderService,
    ) {}

    public function execute(int $scheduleId): void
    {
        $startedAt = microtime(true);

        $schedule = MedicineReminderSchedule::query()
            ->with([
                'workflow',
                'patient',
                'prescription.doctor',
                'prescription.hospital.organization',
                'prescription.member',
            ])
            ->find($scheduleId);

        if (! $schedule) {
            Log::warning('Medicine reminder schedule not found', ['schedule_id' => $scheduleId]);

            return;
        }

        if (! in_array($schedule->status, [ScheduleStatus::Pending->value, ScheduleStatus::Processing->value], true)) {
            return;
        }

        $this->repository->createReminderLog(
            $schedule->id,
            ReminderLogStatus::Started->value,
            'Reminder execution started',
            ['schedule_id' => $schedule->id]
        );

        $this->repository->markProcessing($schedule);

        try {
            event(new MedicineReminderTriggered($schedule));

            $prescription = $schedule->prescription;
            $medications = array_values($prescription?->medications ?? []);
            $medication = $medications[$schedule->prescription_item_id] ?? [];

            $context = [
                'prescription' => $prescription,
                'patient' => $schedule->patient ?? $prescription?->patient,
                'hospital' => $prescription?->hospital,
                'doctor' => $prescription?->doctor,
                'organization' => $prescription?->hospital?->organization,
                'medication' => $medication,
                'scheduled_at' => $schedule->scheduled_at,
                'member_id' => $prescription?->member_id,
                'patient_mobile' => $schedule->patient?->mobile ?? $prescription?->patient?->mobile,
                'patient_email' => $schedule->patient?->email
                    ?? $prescription?->patient?->email
                    ?? $prescription?->member?->email,
            ];

            $template = $schedule->message_template
                ?: ($schedule->workflow?->configuration['message_template'] ?? MedicineReminderService::DEFAULT_MESSAGE_TEMPLATE);

            $resolvedMessage = $this->variableResolver->resolve($template, $context);
            $channels = $schedule->channels
                ?: ($schedule->workflow?->configuration['channels'] ?? ['push']);

            $results = $this->notificationDispatcher->dispatch(
                $schedule,
                array_values($channels),
                $resolvedMessage,
                $context
            );

            $anySuccess = collect($results)->contains(fn ($r) => $r['success'] === true);
            $executionMs = (int) round((microtime(true) - $startedAt) * 1000);

            if ($anySuccess) {
                $this->repository->markSent($schedule);
                $this->repository->createReminderLog(
                    $schedule->id,
                    ReminderLogStatus::Completed->value,
                    'Reminder sent successfully',
                    [
                        'message' => $resolvedMessage,
                        'channels' => $results,
                    ],
                    $executionMs
                );

                return;
            }

            $this->handleFailure($schedule, $results, $executionMs, 'All notification channels failed');
        } catch (\Throwable $e) {
            $executionMs = (int) round((microtime(true) - $startedAt) * 1000);
            report($e);
            $this->handleFailure($schedule, [], $executionMs, $e->getMessage());
        }
    }

    /**
     * @param  array<int, array{channel: string, success: bool, response: string}>  $results
     */
    protected function handleFailure(
        MedicineReminderSchedule $schedule,
        array $results,
        int $executionMs,
        string $message
    ): void {
        $config = $this->medicineReminderService->normalizeConfiguration(
            $schedule->workflow?->configuration ?? []
        );
        $retry = $config['retry'] ?? [];

        $this->repository->createReminderLog(
            $schedule->id,
            ReminderLogStatus::Failed->value,
            $message,
            ['channels' => $results],
            $executionMs
        );

        if (! empty($retry['enabled'])) {
            $updated = $this->repository->scheduleRetry(
                $schedule->fresh(),
                (int) ($retry['interval_minutes'] ?? 15),
                (int) ($retry['max_retries'] ?? 3)
            );

            if ($updated->status === ScheduleStatus::Failed->value && ! empty($retry['escalate'])) {
                Log::warning('Medicine reminder escalated after max retries', [
                    'schedule_id' => $schedule->id,
                ]);
            }

            return;
        }

        $this->repository->markFailed($schedule, $message);
    }
}
