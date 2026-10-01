<?php

namespace App\Modules\Workflow\NodeProcessors;

use App\Modules\Automation\Events\MedicineReminderDue;
use App\Modules\MedicineReminder\Models\MedicineReminderSchedule;
use App\Modules\Workflow\DTO\ExecutionNode;
use App\Modules\Workflow\DTO\NodeExecutionResult;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\NodeProcessors\AbstractNodeProcessor;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Support\NodeTypeNormalizer;

class MedicineReminderDueTriggerNodeProcessor extends AbstractNodeProcessor
{
    public function type(): string
    {
        return 'medicineReminderDue';
    }

    protected function run(
        ExecutionNode $node,
        WorkflowExecution $execution,
        WorkflowContext $context
    ): NodeExecutionResult {
        if (NodeTypeNormalizer::normalize((string) $execution->trigger_type) !== 'medicineReminderDue') {
            return NodeExecutionResult::failed(
                'MedicineReminderDue is a workflow start trigger and cannot run after another trigger or action.'
            );
        }

        $scheduleId = (int) ($context->get('schedule_id') ?? 0);
        if ($scheduleId > 0 && ! $context->get('medication')) {
            $schedule = MedicineReminderSchedule::query()
                ->with(['patient', 'prescription.doctor', 'prescription.hospital.organization', 'prescription.member'])
                ->find($scheduleId);

            if ($schedule) {
                foreach ($this->scheduleFacts($schedule) as $key => $value) {
                    $context->setVariable($key, is_scalar($value) ? $value : null);
                }
            }
        }

        return NodeExecutionResult::continue();
    }

    /**
     * @return array<string, mixed>
     */
    protected function scheduleFacts(MedicineReminderSchedule $schedule): array
    {
        $prescription = $schedule->prescription;
        $medications = array_values($prescription?->medications ?? []);
        $itemId = (int) ($schedule->prescription_item_id ?? 0);
        $medication = MedicineReminderDue::medicationForItem($medications, $itemId);

        return [
            'medicine_name' => $medication['name'] ?? $medication['medicine_name'] ?? '',
            'dosage' => $medication['dosage'] ?? '',
            'frequency' => $medication['frequency'] ?? '',
            'duration' => $medication['duration'] ?? '',
            'when_to_take' => $medication['when_to_take'] ?? '',
        ];
    }
}
