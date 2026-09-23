<?php

namespace App\Modules\Workflow\NodeProcessors;

use App\Modules\MedicineReminder\Enums\ReminderLogStatus;
use App\Modules\MedicineReminder\Enums\ScheduleStatus;
use App\Modules\MedicineReminder\Interfaces\MedicineReminderInterface;
use App\Modules\MedicineReminder\Models\MedicineReminderSchedule;
use App\Modules\MedicineReminder\Services\MedicineReminderService;
use App\Modules\Workflow\DTO\ExecutionNode;
use App\Modules\Workflow\DTO\NodeExecutionResult;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\NodeProcessors\AbstractNodeProcessor;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Services\Runtime\ActionDispatcher;
use App\Modules\Workflow\Services\Runtime\ChannelManager;
use App\Modules\Workflow\Services\Runtime\TemplateManager;
use App\Modules\Workflow\Services\Runtime\VariableResolver;

class MedicineReminderDueTriggerNodeProcessor extends AbstractNodeProcessor
{
    public function __construct(
        ActionDispatcher $actionDispatcher,
        protected MedicineReminderInterface $repository,
        protected VariableResolver $variableResolver,
        protected TemplateManager $templateManager,
        protected ChannelManager $channelManager,
        protected MedicineReminderService $medicineReminderService,
    ) {
        parent::__construct($actionDispatcher);
    }

    public function type(): string
    {
        return 'medicineReminderDue';
    }

    protected function run(
        ExecutionNode $node,
        WorkflowExecution $execution,
        WorkflowContext $context
    ): NodeExecutionResult {
        $scheduleId = (int) ($context->get('schedule_id') ?? 0);
        $schedule = MedicineReminderSchedule::query()
            ->with(['workflow', 'patient', 'prescription.doctor', 'prescription.hospital.organization', 'prescription.member'])
            ->find($scheduleId);

        if (! $schedule) {
            return NodeExecutionResult::failed('Medicine reminder schedule not found.');
        }

        if (! in_array($schedule->status, [ScheduleStatus::Pending->value, ScheduleStatus::Processing->value], true)) {
            return NodeExecutionResult::complete();
        }

        $this->repository->createReminderLog(
            $schedule->id,
            ReminderLogStatus::Started->value,
            'Workflow medicineReminderDue trigger started',
            ['execution_id' => $execution->id]
        );

        $this->repository->markProcessing($schedule);

        $dispatchContext = $this->buildDispatchContext($schedule);
        $nodeData = $node->data;
        $template = (string) ($nodeData['messageTemplate'] ?? $nodeData['message_template'] ?? $schedule->message_template ?? MedicineReminderService::DEFAULT_MESSAGE_TEMPLATE);
        $channels = $nodeData['channels'] ?? $schedule->channels ?? ['push'];

        if (! is_array($channels)) {
            $channels = ['push'];
        }

        $message = $this->variableResolver->resolve($template, $dispatchContext);
        $anySuccess = false;

        foreach (array_values($channels) as $channel) {
            $result = $this->channelManager->send(
                channel: (string) $channel,
                execution: $execution,
                nodeId: $node->id,
                message: $message,
                context: $dispatchContext,
                subject: 'Medicine Reminder',
            );

            if ($result['success'] ?? false) {
                $anySuccess = true;
            }
        }

        if ($anySuccess) {
            $this->repository->markSent($schedule);
            $this->repository->createReminderLog(
                $schedule->id,
                ReminderLogStatus::Completed->value,
                'Reminder sent via workflow engine',
                ['execution_id' => $execution->id]
            );

            return NodeExecutionResult::continue();
        }

        $this->repository->markFailed($schedule, 'All notification channels failed');

        return NodeExecutionResult::failed('All notification channels failed');
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildDispatchContext(MedicineReminderSchedule $schedule): array
    {
        $prescription = $schedule->prescription;
        $medications = array_values($prescription?->medications ?? []);
        $medication = $medications[$schedule->prescription_item_id] ?? [];

        return [
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
            'meta' => [
                'schedule_id' => (string) $schedule->id,
                'prescription_id' => (string) $schedule->prescription_id,
            ],
        ];
    }
}
