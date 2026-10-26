<?php

namespace App\Modules\Automation\Engine;

use App\Models\DiagnosticTestBooking;
use App\Models\DoctorBooking;
use App\Models\SecondOpinion;
use App\Modules\Automation\Support\TriggerCatalog;
use App\Modules\Workflow\Enums\CommunicationStatus;
use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use App\Modules\Workflow\Models\CommunicationLog;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Models\WorkflowVersion;
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
        protected AutomationFactsBuilder $factsBuilder,
        protected WaitingExecutionSuppressor $waitingExecutionSuppressor,
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
        $context = $this->factsBuilder->enrich($this->contextBuilder->merge($payload));
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

        if (! $this->rewardPointsUpdateMatches($canonical, $version, $context)) {
            Log::info('Skipping reward points workflow for a different update type or reward source', [
                'workflow_id' => $workflow->id,
                'trigger_type' => $canonical,
                'change_type' => $context['change_type'] ?? data_get($context, 'rewards.change_type'),
                'reward_source' => $context['reward_source'] ?? data_get($context, 'rewards.source'),
            ]);

            return null;
        }

        if ($this->alreadyStarted($workflow->id, $canonical, $appointmentId, $context)) {
            if ($this->retryFailedWorkflowExecution($workflow, $canonical, $context)) {
                return WorkflowExecution::query()
                    ->where('workflow_id', $workflow->id)
                    ->where('trigger_type', $canonical)
                    ->where('context->event_occurrence_id', $context['event_occurrence_id'] ?? null)
                    ->latest('id')
                    ->first();
            }

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

        $context = $this->factsBuilder->enrich($this->contextBuilder->merge($payload));
        $organizationId = $this->contextBuilder->resolveOrganizationId($context);
        $hospitalId = $this->hospitalIdFrom($payload, $context);

        if ($canonical === 'paymentReceived') {
            Log::info('AutomationEngine handling', [
                'trigger_type' => $canonical,
                'hospital_id' => $hospitalId,
                'organization_id' => $organizationId,
            ]);
        }

        if ($canonical === 'labTestOrdered') {
            Log::info('[lab-test-ordered] context built', [
                'diagnostic_test_booking_id' => $context['diagnostic_test_booking_id'] ?? null,
                'hospital_id' => $hospitalId,
                'organization_id' => $organizationId,
                'order_status' => data_get($context, 'order.status'),
                'payment_status' => data_get($context, 'payment.status'),
                'payment_is_pay_by_hospital' => data_get($context, 'payment.is_pay_by_hospital'),
                'member_present' => filled($context['member_id'] ?? null),
                'event_occurrence_id' => $context['event_occurrence_id'] ?? null,
            ]);
        }

        if ($canonical === 'appointmentBooked') {
            Log::info('[appointment-booked] context built', [
                'appointment_id' => $appointmentId,
                'hospital_id' => $hospitalId,
                'organization_id' => $organizationId,
                'appointment_status' => data_get($context, '_facts.appointment.status'),
                'appointment_booking_type' => data_get($context, '_facts.appointment.booking_type'),
                'payment_status' => data_get($context, 'payment.status'),
                'payment_is_pay_by_hospital' => data_get($context, 'payment.is_pay_by_hospital'),
                'member_present' => filled($context['member_id'] ?? null),
                'event_occurrence_id' => $context['event_occurrence_id'] ?? null,
            ]);

            $this->waitingExecutionSuppressor->suppressWaitingForAppointment($context);

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

            if ($canonical === 'labTestOrdered') {
                Log::info('[lab-test-ordered] workflow matched', [
                    'hospital_id' => $hospitalId,
                    'organization_id' => $organizationId,
                    'count' => $workflows->count(),
                    'workflow_ids' => $workflows->pluck('id')->all(),
                ]);
            }

            if ($canonical === 'appointmentRescheduled' && $workflows->isEmpty()) {
                Log::info('[appointment-rescheduled] No published appointmentRescheduled workflow found for hospital', [
                    'hospital_id' => $hospitalId,
                    'appointment_id' => $appointmentId,
                    'organization_id' => $organizationId,
                ]);
            }
        }

        if ($workflows->isEmpty()) {
            if ($canonical === 'medicineReminderDue') {
                app(\App\Modules\MedicineReminder\Services\MedicineReminderScheduleFinalizer::class)
                    ->onNoPublishedWorkflow($context);
            }

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

        if (! $this->rewardPointsUpdateMatches($triggerType, $version, $context)) {
            Log::info('Skipping reward points workflow for a different update type or reward source', [
                'workflow_id' => $workflow->id,
                'trigger_type' => $triggerType,
                'change_type' => $context['change_type'] ?? data_get($context, 'rewards.change_type'),
                'reward_source' => $context['reward_source'] ?? data_get($context, 'rewards.source'),
            ]);

            return;
        }

        if ($this->alreadyStarted($workflow->id, $triggerType, $appointmentId, $context)) {
            if ($this->retryFailedWorkflowExecution($workflow, $triggerType, $context)) {
                return;
            }

            Log::info('Skipping duplicate workflow execution', [
                'workflow_id' => $workflow->id,
                'trigger_type' => $triggerType,
                'appointment_id' => $appointmentId,
                'event_occurrence_id' => $context['event_occurrence_id'] ?? null,
            ]);

            if ($triggerType === 'medicineReminderDue') {
                app(\App\Modules\MedicineReminder\Services\MedicineReminderScheduleFinalizer::class)
                    ->onDuplicateOccurrence($context);
            }

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

    /**
     * Sources grouped by the builder's Payment Earnings option.
     * The generic `payment` wallet source is not part of this group.
     *
     * @var list<string>
     */
    private const PAYMENT_EARNING_SOURCES = [
        'appointment_payment',
        'package_booking',
        'second_opinion_payment',
        'pharmacy_payment',
    ];

    /**
     * Reward source values the wallet already emits.
     *
     * @var list<string>
     */
    private const REWARD_POINT_SOURCES = [
        'appointment_payment',
        'package_booking',
        'second_opinion_payment',
        'pharmacy_payment',
        'payment',
        'appointment_redemption',
        'package_redemption',
        'second_opinion_redemption',
        'redemption',
        'hip_points',
        'bill_analysis',
        'wallet',
    ];

    /**
     * Reward Points Updated workflows can limit which balance changes and
     * sources start them. No selection, an empty selection, or Any allows
     * every actual balance change. Other triggers are unchanged.
     *
     * @param  array<string, mixed>  $context
     */
    protected function rewardPointsUpdateMatches(string $triggerType, WorkflowVersion $version, array $context): bool
    {
        if ($triggerType !== 'rewardPointsUpdated') {
            return true;
        }

        $selected = $this->selectedRewardPointChangeTypes($version);
        if ($selected !== null) {
            $changeType = strtolower((string) (
                $context['change_type']
                ?? data_get($context, 'rewards.change_type')
                ?? ''
            ));

            if ($changeType === '' || ! in_array($changeType, $selected, true)) {
                return false;
            }
        }

        $sources = $this->selectedRewardPointSources($version);
        if ($sources === null) {
            return true;
        }

        $rewardSource = strtolower((string) (
            $context['reward_source']
            ?? data_get($context, 'rewards.source')
            ?? ''
        ));

        return $rewardSource !== '' && in_array($rewardSource, $sources, true);
    }

    /**
     * @return list<string>|null Null means the workflow accepts every change type.
     */
    protected function selectedRewardPointChangeTypes(WorkflowVersion $version): ?array
    {
        $definition = is_array($version->definition) ? $version->definition : [];
        $nodes = is_array($definition['nodes'] ?? null) ? $definition['nodes'] : [];
        $raw = null;

        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }

            $data = is_array($node['data'] ?? null) ? $node['data'] : [];
            $nodeType = NodeTypeNormalizer::normalize((string) ($data['nodeType'] ?? $data['type'] ?? ''));
            if ($nodeType !== 'rewardPointsUpdated') {
                continue;
            }

            $raw = $data['updateType'] ?? $data['update_type'] ?? null;
            break;
        }

        $values = $this->multiSelectValues($raw);
        if ($values === []) {
            return null;
        }

        $allowed = [];
        foreach ($values as $value) {
            $token = strtolower(str_replace(['_', '-'], ' ', trim($value)));
            if (in_array($token, ['any', 'all'], true)) {
                return null;
            }

            $mapped = match ($token) {
                'points added', 'earned', 'added' => 'earned',
                'points redeemed', 'redeemed' => 'redeemed',
                'points adjusted', 'adjusted' => 'adjusted',
                default => null,
            };

            if ($mapped !== null) {
                $allowed[] = $mapped;
            }
        }

        return $allowed === [] ? null : array_values(array_unique($allowed));
    }

    /**
     * @return list<string>|null Null means the workflow accepts every reward source.
     */
    protected function selectedRewardPointSources(WorkflowVersion $version): ?array
    {
        $definition = is_array($version->definition) ? $version->definition : [];
        $nodes = is_array($definition['nodes'] ?? null) ? $definition['nodes'] : [];
        $raw = null;

        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }

            $data = is_array($node['data'] ?? null) ? $node['data'] : [];
            $nodeType = NodeTypeNormalizer::normalize((string) ($data['nodeType'] ?? $data['type'] ?? ''));
            if ($nodeType !== 'rewardPointsUpdated') {
                continue;
            }

            $raw = $data['rewardSource'] ?? $data['reward_source'] ?? null;
            break;
        }

        $values = $this->multiSelectValues($raw);
        if ($values === []) {
            return null;
        }

        $allowed = [];
        foreach ($values as $value) {
            $token = strtolower(trim($value));
            $token = str_replace(['-', ' '], '_', $token);

            if (in_array($token, ['any', 'any_source', 'all'], true)) {
                return null;
            }

            if ($token === 'payment_earnings') {
                array_push($allowed, ...self::PAYMENT_EARNING_SOURCES);
                continue;
            }

            if (in_array($token, self::REWARD_POINT_SOURCES, true)) {
                $allowed[] = $token;
            }
        }

        return $allowed === [] ? [] : array_values(array_unique($allowed));
    }

    /**
     * @return list<string>
     */
    protected function multiSelectValues(mixed $raw): array
    {
        if ($raw === null || $raw === '' || $raw === []) {
            return [];
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $raw = $decoded;
            } else {
                $raw = preg_split('/\s*,\s*/', $raw) ?: [];
            }
        }

        if (! is_array($raw)) {
            return [];
        }

        $values = [];
        foreach ($raw as $item) {
            if (! is_scalar($item)) {
                continue;
            }

            $text = trim((string) $item);
            if ($text !== '') {
                $values[] = $text;
            }
        }

        return $values;
    }

    /**
     * Failed reward and appointment reminder executions with no provider
     * acceptance can retry on the same row. Completed, running, and waiting
     * executions remain duplicates.
     *
     * @param  array<string, mixed>  $context
     */
    protected function retryFailedWorkflowExecution(Workflow $workflow, string $triggerType, array $context): bool
    {
        if (! in_array($triggerType, ['rewardTierUpgraded', 'rewardPointsUpdated', 'appointmentReminder'], true)) {
            return false;
        }

        $occurrenceId = $context['event_occurrence_id'] ?? null;
        if (! is_string($occurrenceId) || $occurrenceId === '') {
            return false;
        }

        $executions = WorkflowExecution::query()
            ->where('workflow_id', $workflow->id)
            ->where('trigger_type', $triggerType)
            ->where('context->event_occurrence_id', $occurrenceId)
            ->orderByDesc('id')
            ->get();

        $failed = null;

        foreach ($executions as $execution) {
            $status = (string) $execution->status;

            if (in_array($status, [
                WorkflowExecutionStatus::Started->value,
                WorkflowExecutionStatus::Running->value,
                WorkflowExecutionStatus::Waiting->value,
                WorkflowExecutionStatus::Completed->value,
            ], true)) {
                return false;
            }

            if ($this->notificationAlreadyAccepted($execution)) {
                return false;
            }

            if ($failed === null && $status === WorkflowExecutionStatus::Failed->value) {
                $failed = $execution;
            }
        }

        if ($failed === null) {
            return false;
        }

        Log::info('Retrying failed workflow execution', [
            'workflow_id' => $workflow->id,
            'workflow_execution_id' => $failed->id,
            'event_occurrence_id' => $occurrenceId,
        ]);

        $this->workflowExecutor->retryFailed($failed, $context);

        return true;
    }

    protected function notificationAlreadyAccepted(WorkflowExecution $execution): bool
    {
        return CommunicationLog::query()
            ->where('workflow_execution_id', $execution->id)
            ->whereIn('status', [
                CommunicationStatus::Sent->value,
                CommunicationStatus::Delivered->value,
                CommunicationStatus::Read->value,
            ])
            ->exists();
    }

    /**
     * Repeatable triggers (e.g. appointmentRescheduled) key off event_occurrence_id
     * so a second genuine reschedule can run, while a queue retry of the same
     * event does not. Other triggers keep appointment_id uniqueness.
     *
     * Any existing execution still counts as started. Reward failures that
     * never reached the provider are retried on that same row instead of skipped.
     *
     * @param  array<string, mixed>  $context
     */
    protected function alreadyStarted(int $workflowId, string $triggerType, mixed $appointmentId, array $context = []): bool
    {
        $occurrenceId = $context['event_occurrence_id'] ?? null;

        if ($occurrenceId !== null && $occurrenceId !== '') {
            return WorkflowExecution::query()
                ->where('workflow_id', $workflowId)
                ->where('trigger_type', $triggerType)
                ->where('context->event_occurrence_id', $occurrenceId)
                ->exists();
        }

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

        $secondOpinion = $payload['second_opinion'] ?? $context['second_opinion'] ?? null;
        if ($secondOpinion instanceof SecondOpinion && $secondOpinion->branch_id) {
            return (int) $secondOpinion->branch_id;
        }

        $labOrder = $payload['diagnostic_test_booking'] ?? $context['diagnostic_test_booking'] ?? null;
        if ($labOrder instanceof DiagnosticTestBooking && $labOrder->branch_id) {
            return (int) $labOrder->branch_id;
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
