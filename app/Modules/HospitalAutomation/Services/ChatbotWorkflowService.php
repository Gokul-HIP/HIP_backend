<?php

namespace App\Modules\HospitalAutomation\Services;

use App\Modules\HospitalAutomation\Testing\AutomationTestContextFactory;
use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use App\Modules\Workflow\Enums\WorkflowStatus;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Repositories\WorkflowRepository;
use App\Modules\Workflow\Support\NodeTypeNormalizer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Bridges HTTP /chat/completions into the Hospital Automation workflow runtime.
 *
 * Discovers a published onChatMessage (canonical: messageReceived) workflow and
 * executes it synchronously via AutomationEngine → WorkflowExecutor → SendAiChatExecutor.
 * Does not call AI providers directly.
 *
 * Inactive / unpublished workflows are never executed from this path.
 */
class ChatbotWorkflowService
{
    public const SOURCE = 'chatbot';

    public const TRIGGER = 'onChatMessage';

    public const NO_ACTIVE_WORKFLOW_MESSAGE = 'No active chatbot workflow is configured.';

    public function __construct(
        protected WorkflowRepository $workflowRepository,
        protected AutomationEngine $automationEngine,
    ) {}

    /**
     * @param  list<array{role: string, content: string}>  $messages
     * @param  array{organization_id?: int|null, hospital_id?: int|null, member_id?: string|null, session_id?: string|null, temperature?: float|null}  $options
     * @return array{
     *     reply: string,
     *     model: string|null,
     *     usage: array{prompt_tokens: int|null, completion_tokens: int|null, total_tokens: int|null}|null,
     *     fallback_used: bool,
     *     workflow_id: int,
     *     execution_id: int,
     *     trigger_type: string,
     *     status: string
     * }
     */
    public function complete(array $messages, array $options = []): array
    {
        $chatMessage = $this->extractLatestUserMessage($messages);
        if ($chatMessage === '') {
            throw new RuntimeException('A user message is required.');
        }

        $organizationId = isset($options['organization_id']) && $options['organization_id'] !== null
            ? (int) $options['organization_id']
            : null;
        $hospitalId = isset($options['hospital_id']) && $options['hospital_id'] !== null
            ? (int) $options['hospital_id']
            : null;

        $workflow = $this->resolveWorkflow($organizationId, $hospitalId);
        $this->assertWorkflowExecutable($workflow, $organizationId, $hospitalId);

        $context = $this->buildContext($messages, $chatMessage, $options, $organizationId, $hospitalId);

        Log::info('[chatbot] Executing onChatMessage workflow', [
            'source' => self::SOURCE,
            'workflow_id' => $workflow->id,
            'organization_id' => $organizationId,
            'hospital_id' => $hospitalId,
            'session_id' => $options['session_id'] ?? null,
            'workflow_status' => $workflow->status,
            'version_id' => $workflow->currentVersion?->id,
            'version_status' => $workflow->currentVersion?->status,
        ]);

        $execution = $this->automationEngine->executeWorkflow(
            $workflow,
            self::TRIGGER,
            $context
        );

        if (! $execution instanceof WorkflowExecution) {
            throw new RuntimeException('Failed to start chatbot workflow execution.');
        }

        $execution = $execution->fresh();

        if ($execution->status === WorkflowExecutionStatus::Waiting->value) {
            throw new RuntimeException(
                'Chatbot workflow entered a wait/delay state and cannot return a synchronous reply.'
            );
        }

        if ($execution->status === WorkflowExecutionStatus::Failed->value) {
            $reason = trim((string) ($execution->failure_reason ?? ''));

            throw new RuntimeException(
                $reason !== '' ? $reason : 'Chatbot workflow execution failed.'
            );
        }

        if ($execution->status !== WorkflowExecutionStatus::Completed->value) {
            throw new RuntimeException(
                'Chatbot workflow did not complete synchronously (status='.$execution->status.').'
            );
        }

        $reply = $this->extractAiReply($execution);
        if ($reply === '') {
            throw new RuntimeException(
                'Chatbot workflow completed without an AI chat response.'
            );
        }

        $variables = is_array($execution->variables) ? $execution->variables : [];
        $usage = is_array($variables['ai_chat_usage'] ?? null) ? $variables['ai_chat_usage'] : null;

        return [
            'reply' => $reply,
            'model' => isset($variables['ai_chat_model']) ? (string) $variables['ai_chat_model'] : null,
            'usage' => $usage,
            'fallback_used' => (bool) ($variables['ai_chat_fallback_used'] ?? false),
            'workflow_id' => (int) $execution->workflow_id,
            'execution_id' => (int) $execution->id,
            'trigger_type' => NodeTypeNormalizer::normalize(self::TRIGGER),
            'status' => (string) $execution->status,
        ];
    }

    /**
     * Deterministic selection among published messageReceived workflows.
     * Only active workflows with a published current version are eligible.
     * automation_test workflows are already excluded by WorkflowRepository.
     */
    public function resolveWorkflow(?int $organizationId = null, ?int $hospitalId = null): Workflow
    {
        $canonical = NodeTypeNormalizer::normalize(self::TRIGGER);

        $workflows = $this->workflowRepository->findPublishedByTrigger(
            $canonical,
            $organizationId,
            $hospitalId
        );

        // Extra safety: never run automation_test workflows from chatbot HTTP.
        $workflows = $workflows
            ->filter(fn (Workflow $workflow) => $workflow->source_type !== AutomationTestContextFactory::SOURCE)
            ->filter(fn (Workflow $workflow) => $this->isExecutableChatbotWorkflow($workflow))
            ->values();

        Log::info('chatbot.workflow_lookup', [
            'source' => self::SOURCE,
            'trigger_type' => $canonical,
            'organization_id' => $organizationId,
            'hospital_id' => $hospitalId,
            'active_candidate_count' => $workflows->count(),
            'active_candidate_ids' => $workflows->pluck('id')->all(),
        ]);

        if ($workflows->isEmpty()) {
            $this->rejectWhenNoActiveWorkflow($canonical, $organizationId, $hospitalId);
        }

        // Prefer exact hospital match, then exact organization, then oldest id.
        $selected = $workflows
            ->sortBy([
                fn (Workflow $w) => $hospitalId !== null && (int) $w->hospital_id === $hospitalId ? 0 : 1,
                fn (Workflow $w) => $organizationId !== null && (int) $w->organization_id === $organizationId ? 0 : 1,
                fn (Workflow $w) => (int) $w->id,
            ])
            ->first();

        if (! $selected instanceof Workflow || ! $selected->currentVersion) {
            $this->skipExecution('missing_published_version', [
                'organization_id' => $organizationId,
                'hospital_id' => $hospitalId,
            ]);

            throw new RuntimeException(self::NO_ACTIVE_WORKFLOW_MESSAGE);
        }

        if ($selected->source_type === AutomationTestContextFactory::SOURCE) {
            $this->skipExecution('automation_test_blocked', [
                'workflow_id' => $selected->id,
            ]);

            throw new RuntimeException(self::NO_ACTIVE_WORKFLOW_MESSAGE);
        }

        Log::info('chatbot.workflow_lookup', [
            'source' => self::SOURCE,
            'trigger_type' => $canonical,
            'organization_id' => $organizationId,
            'hospital_id' => $hospitalId,
            'selected_workflow_id' => $selected->id,
            'selected_workflow_status' => $selected->status,
            'selected_version_id' => $selected->currentVersion->id,
            'selected_version_status' => $selected->currentVersion->status,
        ]);

        return $selected;
    }

    /**
     * Final gate before AutomationEngine::executeWorkflow — never run inactive chatbot graphs.
     */
    public function assertWorkflowExecutable(
        Workflow $workflow,
        ?int $organizationId = null,
        ?int $hospitalId = null,
    ): void {
        $workflow->loadMissing('currentVersion');

        if (! $this->isExecutableChatbotWorkflow($workflow)) {
            Log::warning('chatbot.workflow_inactive', [
                'source' => self::SOURCE,
                'workflow_id' => $workflow->id,
                'workflow_status' => $workflow->status,
                'version_id' => $workflow->currentVersion?->id,
                'version_status' => $workflow->currentVersion?->status,
                'organization_id' => $organizationId,
                'hospital_id' => $hospitalId,
            ]);

            $this->skipExecution('workflow_not_executable', [
                'workflow_id' => $workflow->id,
                'workflow_status' => $workflow->status,
                'version_status' => $workflow->currentVersion?->status,
                'organization_id' => $organizationId,
                'hospital_id' => $hospitalId,
            ]);

            throw new RuntimeException(self::NO_ACTIVE_WORKFLOW_MESSAGE);
        }

        // Tenant sanity: when the request scoped a hospital, refuse a mismatched workflow.
        if ($hospitalId !== null && (int) $workflow->hospital_id !== $hospitalId) {
            $this->skipExecution('hospital_mismatch', [
                'workflow_id' => $workflow->id,
                'workflow_hospital_id' => $workflow->hospital_id,
                'request_hospital_id' => $hospitalId,
            ]);

            throw new RuntimeException(self::NO_ACTIVE_WORKFLOW_MESSAGE);
        }
    }

    protected function isExecutableChatbotWorkflow(Workflow $workflow): bool
    {
        if ($workflow->source_type === AutomationTestContextFactory::SOURCE) {
            return false;
        }

        if ($workflow->status !== WorkflowStatus::Active->value) {
            return false;
        }

        $version = $workflow->currentVersion;
        if ($version === null) {
            return false;
        }

        $versionStatus = strtolower(trim((string) ($version->status ?? '')));

        return $versionStatus === '' || $versionStatus === 'published';
    }

    protected function rejectWhenNoActiveWorkflow(
        string $canonicalTrigger,
        ?int $organizationId,
        ?int $hospitalId,
    ): void {
        $inactive = $this->findNonActiveChatbotWorkflows($canonicalTrigger, $organizationId, $hospitalId);

        if ($inactive->isNotEmpty()) {
            Log::warning('chatbot.workflow_inactive', [
                'source' => self::SOURCE,
                'trigger_type' => $canonicalTrigger,
                'organization_id' => $organizationId,
                'hospital_id' => $hospitalId,
                'inactive_workflow_ids' => $inactive->pluck('id')->all(),
                'inactive_statuses' => $inactive->pluck('status')->unique()->values()->all(),
            ]);
        }

        $this->skipExecution('no_active_workflow', [
            'trigger_type' => $canonicalTrigger,
            'organization_id' => $organizationId,
            'hospital_id' => $hospitalId,
            'inactive_workflow_count' => $inactive->count(),
        ]);

        throw new RuntimeException(self::NO_ACTIVE_WORKFLOW_MESSAGE);
    }

    /**
     * @return Collection<int, Workflow>
     */
    protected function findNonActiveChatbotWorkflows(
        string $canonicalTrigger,
        ?int $organizationId,
        ?int $hospitalId,
    ): Collection {
        return Workflow::query()
            ->where('trigger_type', $canonicalTrigger)
            ->where('status', '!=', WorkflowStatus::Active->value)
            ->whereNotNull('current_version_id')
            ->where(function ($query) {
                $query->whereNull('source_type')
                    ->orWhere('source_type', '!=', AutomationTestContextFactory::SOURCE);
            })
            ->when(
                $organizationId !== null,
                function ($query) use ($organizationId) {
                    $query->where(function ($inner) use ($organizationId) {
                        $inner->where('organization_id', $organizationId)
                            ->orWhereNull('organization_id');
                    });
                },
                function ($query) {
                    $query->whereNull('organization_id');
                }
            )
            ->when(
                $hospitalId !== null,
                fn ($query) => $query->where('hospital_id', $hospitalId),
                fn ($query) => $query->whereNull('hospital_id')
            )
            ->get();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function skipExecution(string $reason, array $context = []): void
    {
        Log::info('chatbot.workflow_execution_skipped', array_merge([
            'source' => self::SOURCE,
            'reason' => $reason,
        ], $context));
    }

    /**
     * @param  list<array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    protected function buildContext(
        array $messages,
        string $chatMessage,
        array $options,
        ?int $organizationId,
        ?int $hospitalId,
    ): array {
        $historyText = collect($messages)
            ->map(function ($row) {
                $role = (string) ($row['role'] ?? 'user');
                $content = trim((string) ($row['content'] ?? ''));

                return $content === '' ? null : strtoupper($role).': '.$content;
            })
            ->filter()
            ->implode("\n");

        return [
            'chat_message' => $chatMessage,
            'user_message' => $chatMessage,
            'messages' => $messages,
            'chat_history' => $historyText,
            'session_id' => $options['session_id'] ?? null,
            'member_id' => $options['member_id'] ?? null,
            'organization_id' => $organizationId,
            'hospital_id' => $hospitalId,
            'temperature' => $options['temperature'] ?? null,
            'meta' => [
                'source' => self::SOURCE,
                'response_mode' => true,
                'session_id' => $options['session_id'] ?? null,
            ],
        ];
    }

    /**
     * @param  list<array{role?: string, content?: string}>  $messages
     */
    protected function extractLatestUserMessage(array $messages): string
    {
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            $row = $messages[$i];
            if (($row['role'] ?? null) === 'user') {
                return trim((string) ($row['content'] ?? ''));
            }
        }

        $last = $messages[array_key_last($messages)] ?? null;

        return is_array($last) ? trim((string) ($last['content'] ?? '')) : '';
    }

    protected function extractAiReply(WorkflowExecution $execution): string
    {
        $variables = is_array($execution->variables) ? $execution->variables : [];

        $reply = trim((string) ($variables['ai_chat_message'] ?? ''));
        if ($reply !== '') {
            return $reply;
        }

        // Fallback: last scalar ai_* text if naming differs in future.
        foreach (array_reverse($variables, true) as $key => $value) {
            if (is_string($key) && str_starts_with($key, 'ai_chat') && is_scalar($value)) {
                $candidate = trim((string) $value);
                if ($candidate !== '' && $key !== 'ai_chat_prompt' && $key !== 'ai_chat_temperature' && $key !== 'ai_chat_node_id') {
                    return $candidate;
                }
            }
        }

        return '';
    }
}
