<?php

namespace App\Modules\HospitalAutomation\Services;

use App\Modules\HospitalAutomation\Testing\AutomationTestContextFactory;
use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Repositories\WorkflowRepository;
use App\Modules\Workflow\Support\NodeTypeNormalizer;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Bridges HTTP /chat/completions into the Hospital Automation workflow runtime.
 *
 * Discovers a published onChatMessage (canonical: messageReceived) workflow and
 * executes it synchronously via AutomationEngine → WorkflowExecutor → SendAiChatExecutor.
 * Does not call AI providers directly.
 */
class ChatbotWorkflowService
{
    public const SOURCE = 'chatbot';

    public const TRIGGER = 'onChatMessage';

    public function __construct(
        protected WorkflowRepository $workflowRepository,
        protected AutomationEngine $automationEngine,
    ) {}

    /**
     * @param  list<array{role: string, content: string}>  $messages
     * @param  array{organization_id?: int|null, hospital_id?: int|null, member_id?: string|null, session_id?: string|null, temperature?: float|null}  $options
     * @return array{
     *     reply: string,
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

        $context = $this->buildContext($messages, $chatMessage, $options, $organizationId, $hospitalId);

        Log::info('[chatbot] Executing onChatMessage workflow', [
            'source' => self::SOURCE,
            'workflow_id' => $workflow->id,
            'organization_id' => $organizationId,
            'hospital_id' => $hospitalId,
            'session_id' => $options['session_id'] ?? null,
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

        return [
            'reply' => $reply,
            'workflow_id' => (int) $execution->workflow_id,
            'execution_id' => (int) $execution->id,
            'trigger_type' => NodeTypeNormalizer::normalize(self::TRIGGER),
            'status' => (string) $execution->status,
        ];
    }

    /**
     * Deterministic selection among published messageReceived workflows.
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
            ->values();

        if ($workflows->isEmpty()) {
            throw new RuntimeException(
                'No published onChatMessage workflow is available for this context.'
            );
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
            throw new RuntimeException('Selected onChatMessage workflow is missing a published version.');
        }

        if ($selected->source_type === AutomationTestContextFactory::SOURCE) {
            throw new RuntimeException('Refusing to execute automation_test workflow from chatbot.');
        }

        return $selected;
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
