<?php

namespace App\Modules\HospitalAutomation\Services;

use App\Models\DoctorBooking;
use App\Modules\HospitalAutomation\Support\TriggerCatalog;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Repositories\WorkflowRepository;
use App\Modules\Workflow\Services\Runtime\WorkflowExecutor;
use App\Modules\Workflow\Support\NodeTypeNormalizer;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates hospital automation events into the existing workflow runtime.
 * Does not execute nodes itself — that is WorkflowExecutor's job.
 */
class AutomationEngine
{
    public function __construct(
        protected AutomationContextBuilder $contextBuilder,
        protected WorkflowRepository $workflowRepository,
        protected WorkflowExecutor $workflowExecutor,
    ) {}

    /**
     * Start one already-resolved published workflow through the normal runtime.
     * Used by `automation:test` so only the intended workflow runs (no hospital-wide fan-out).
     * Does not change recipient resolution — callers must supply a safe context.
     *
     * @param  array<string, mixed>  $payload
     */
    public function executeWorkflow(Workflow $workflow, string $triggerType, array $payload = []): ?WorkflowExecution
    {
        $canonical = NodeTypeNormalizer::normalize($triggerType);
        $context = $this->contextBuilder->merge($payload);
        $organizationId = $this->contextBuilder->resolveOrganizationId($context);
        $hospitalId = $this->hospitalIdFrom($payload, $context);
        $appointmentId = $this->appointmentIdFrom($payload);

        $workflow->loadMissing('currentVersion');
        $version = $workflow->currentVersion;

        if (! $version) {
            Log::warning('AutomationEngine executeWorkflow: missing published version', [
                'workflow_id' => $workflow->id,
                'trigger_type' => $canonical,
                'source' => is_array($context['meta'] ?? null) ? ($context['meta']['source'] ?? null) : null,
            ]);

            return null;
        }

        if ($this->alreadyStarted($workflow->id, $canonical, $appointmentId)) {
            Log::info('AutomationEngine executeWorkflow: skipping duplicate', [
                'workflow_id' => $workflow->id,
                'trigger_type' => $canonical,
                'appointment_id' => $appointmentId,
            ]);

            return WorkflowExecution::query()
                ->where('workflow_id', $workflow->id)
                ->where('trigger_type', $canonical)
                ->where('context->appointment_id', $appointmentId)
                ->latest('id')
                ->first();
        }

        Log::info('AutomationEngine executeWorkflow starting', [
            'workflow_id' => $workflow->id,
            'workflow_version_id' => $version->id,
            'trigger_type' => $canonical,
            'appointment_id' => $appointmentId,
            'organization_id' => $organizationId,
            'hospital_id' => $hospitalId,
            'source' => is_array($context['meta'] ?? null) ? ($context['meta']['source'] ?? null) : null,
        ]);

        return $this->workflowExecutor->start($version, $canonical, $context);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(string $triggerType, array $payload = []): void
    {
        $canonical = NodeTypeNormalizer::normalize($triggerType);

        $appointmentId = $this->appointmentIdFrom($payload);

        Log::info('AutomationEngine handling event', [
            'trigger_type' => $canonical,
            'appointment_id' => $appointmentId,
        ]);

        if (! in_array($canonical, TriggerCatalog::types(), true)) {
            Log::warning('Unknown hospital automation trigger', [
                'trigger_type' => $canonical,
                'appointment_id' => $appointmentId,
            ]);
        }

        $context = $this->contextBuilder->merge($payload);
        $organizationId = $this->contextBuilder->resolveOrganizationId($context);
        $hospitalId = $this->hospitalIdFrom($payload, $context);

        if ($canonical === 'appointmentBooked') {
            Log::info('[appointment-booked] Resolving workflows for hospital', [
                'hospital_id' => $hospitalId,
                'appointment_id' => $appointmentId,
            ]);

            if ($hospitalId === null) {
                Log::info('[appointment-booked] No published appointmentBooked workflow found for hospital', [
                    'hospital_id' => null,
                    'appointment_id' => $appointmentId,
                    'reason' => 'missing_hospital_id',
                ]);

                return;
            }
        }

        $workflows = $this->workflowRepository->findPublishedByTrigger(
            $canonical,
            $organizationId,
            $hospitalId
        );

        if ($canonical === 'appointmentBooked' && $hospitalId !== null) {
            $workflows = $workflows
                ->filter(fn (Workflow $workflow) => (int) $workflow->hospital_id === $hospitalId)
                ->values();
        }

        if ($canonical === 'appointmentBooked') {
            if ($workflows->isEmpty()) {
                Log::info('[appointment-booked] No published appointmentBooked workflow found for hospital', [
                    'hospital_id' => $hospitalId,
                    'appointment_id' => $appointmentId,
                    'organization_id' => $organizationId,
                ]);

                return;
            }

            Log::info('[appointment-booked] Matching published workflows found', [
                'hospital_id' => $hospitalId,
                'appointment_id' => $appointmentId,
                'count' => $workflows->count(),
                'workflow_ids' => $workflows->pluck('id')->all(),
            ]);
        } else {
            Log::info('Matching workflows found', [
                'trigger_type' => $canonical,
                'appointment_id' => $appointmentId,
                'organization_id' => $organizationId,
                'hospital_id' => $hospitalId,
                'count' => $workflows->count(),
                'workflow_ids' => $workflows->pluck('id')->all(),
            ]);
        }

        if ($workflows->isEmpty()) {
            return;
        }

        foreach ($workflows as $workflow) {
            $this->startWorkflow($workflow, $canonical, $context, $appointmentId, $organizationId, $hospitalId);
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function startWorkflow(
        Workflow $workflow,
        string $triggerType,
        array $context,
        mixed $appointmentId,
        ?int $organizationId,
        ?int $hospitalId = null
    ): void {
        $version = $workflow->currentVersion;

        if (! $version) {
            Log::warning('Active workflow missing published version', [
                'workflow_id' => $workflow->id,
                'trigger_type' => $triggerType,
                'appointment_id' => $appointmentId,
            ]);

            return;
        }

        if ($this->alreadyStarted($workflow->id, $triggerType, $appointmentId)) {
            Log::info('Skipping duplicate workflow execution', [
                'workflow_id' => $workflow->id,
                'trigger_type' => $triggerType,
                'appointment_id' => $appointmentId,
            ]);

            return;
        }

        if ($triggerType === 'appointmentBooked') {
            Log::info('[appointment-booked] Starting workflow execution', [
                'workflow_id' => $workflow->id,
                'workflow_version_id' => $version->id,
                'hospital_id' => $hospitalId,
                'appointment_id' => $appointmentId,
            ]);
        } else {
            Log::info('Workflow execution started', [
                'workflow_id' => $workflow->id,
                'workflow_version_id' => $version->id,
                'trigger_type' => $triggerType,
                'appointment_id' => $appointmentId,
                'organization_id' => $organizationId,
            ]);
        }

        $this->workflowExecutor->start($version, $triggerType, $context);
    }

    protected function alreadyStarted(int $workflowId, string $triggerType, mixed $appointmentId): bool
    {
        if ($appointmentId === null || $appointmentId === '') {
            return false;
        }

        return WorkflowExecution::query()
            ->where('workflow_id', $workflowId)
            ->where('trigger_type', $triggerType)
            ->where('context->appointment_id', $appointmentId)
            ->exists();
    }

    /**
     * Canonical hospital for AppointmentBooked is the DoctorBooking that raised the event.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $context
     */
    protected function hospitalIdFrom(array $payload, array $context): ?int
    {
        $appointment = $payload['appointment'] ?? $context['appointment'] ?? null;

        if ($appointment instanceof DoctorBooking && $appointment->hospital_id) {
            return (int) $appointment->hospital_id;
        }

        if (is_object($appointment) && isset($appointment->hospital_id) && $appointment->hospital_id) {
            return (int) $appointment->hospital_id;
        }

        if (isset($context['hospital_id']) && $context['hospital_id']) {
            return (int) $context['hospital_id'];
        }

        if (isset($payload['hospital_id']) && $payload['hospital_id']) {
            return (int) $payload['hospital_id'];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function appointmentIdFrom(array $payload): mixed
    {
        if (isset($payload['appointment']) && is_object($payload['appointment'])) {
            return $payload['appointment']->id ?? null;
        }

        return $payload['appointment_id'] ?? null;
    }
}
