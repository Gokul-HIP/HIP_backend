<?php

namespace App\Modules\Automation\Engine;

use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use App\Modules\Workflow\Models\WorkflowExecution;

/**
 * Cancels waiting executions whose published definition sets suppressOnAppointment.
 * Scope is same patient + same hospital only. Does not inspect campaignKey, workflow id, or name.
 */
class WaitingExecutionSuppressor
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function suppressWaitingForAppointment(array $context): int
    {
        $patientId = $context['patient_id'] ?? data_get($context, 'patient.id');
        $hospitalId = $context['hospital_id'] ?? data_get($context, 'hospital.id') ?? data_get($context, 'appointment.hospital_id');

        if ($patientId === null || $patientId === '' || $hospitalId === null || $hospitalId === '') {
            return 0;
        }

        $waiting = WorkflowExecution::query()
            ->where('status', WorkflowExecutionStatus::Waiting->value)
            ->where('context->patient_id', $patientId)
            ->where('context->hospital_id', $hospitalId)
            ->with('version')
            ->get();

        $cancelled = 0;

        foreach ($waiting as $execution) {
            $definition = is_array($execution->version?->definition) ? $execution->version->definition : [];

            if (($definition['suppressOnAppointment'] ?? false) !== true) {
                continue;
            }

            $execution->update([
                'status' => WorkflowExecutionStatus::Cancelled->value,
                'completed_at' => now(),
            ]);
            $cancelled++;
        }

        return $cancelled;
    }
}
