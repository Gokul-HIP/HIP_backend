<?php

namespace App\Modules\HospitalAutomation\Services;

use App\Models\ChatbotMessage;
use App\Models\ChatbotSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Persistent chatbot session + message storage (organization/hospital scoped).
 *
 * user_id is a UUID string matching users / healthinpocket_users primary keys.
 */
class ChatbotConversationService
{
    public const HISTORY_LIMIT = 30;

    /**
     * @return array{session: ChatbotSession, created: bool}
     */
    public function resolveOrCreateSession(
        ?string $sessionId,
        ?int $organizationId,
        ?int $hospitalId,
        ?string $userId = null,
        bool $allowCreateForEnded = false,
    ): array {
        $sessionId = trim((string) $sessionId);
        if ($sessionId === '') {
            $sessionId = (string) Str::uuid();
        }

        $userId = $this->normalizeUserId($userId);

        $existing = $this->findSession($sessionId, $organizationId, $hospitalId);

        if ($existing) {
            if (! $existing->isOpen() && ! $allowCreateForEnded) {
                throw new RuntimeException(
                    'This chat session has ended. Start a new session to continue.'
                );
            }

            $existingUserId = $this->normalizeUserId($existing->user_id);

            // Do not silently attach another authenticated user's session.
            if (
                $existingUserId !== null
                && $userId !== null
                && ! hash_equals($existingUserId, $userId)
            ) {
                Log::warning('chatbot.memory.session_user_mismatch', [
                    'session_id' => $sessionId,
                    'organization_id' => $organizationId,
                    'hospital_id' => $hospitalId,
                    'existing_user_id' => $existingUserId,
                    'authenticated_user_id' => $userId,
                ]);

                throw new RuntimeException(
                    'This chat session belongs to another user. Start a new session.'
                );
            }

            if ($userId !== null && $existingUserId === null) {
                $existing->user_id = $userId;
                $existing->save();
            }

            $this->memoryLog('chatbot.memory.session_resolved', [
                'requested_session_id' => $sessionId,
                'organization_id' => $organizationId,
                'hospital_id' => $hospitalId,
                'authenticated_user_id' => $userId,
                'found' => true,
                'created' => false,
                'existing_user_id' => $existingUserId,
                'final_user_id' => $this->normalizeUserId($existing->user_id),
                'status' => $existing->status,
            ]);

            return ['session' => $existing->fresh() ?? $existing, 'created' => false];
        }

        $session = ChatbotSession::query()->create([
            'session_id' => $sessionId,
            'organization_id' => $organizationId,
            'hospital_id' => $hospitalId,
            'user_id' => $userId,
            'status' => ChatbotSession::STATUS_ACTIVE,
            'last_message_at' => null,
            'ended_at' => null,
        ]);

        $this->memoryLog('chatbot.memory.session_resolved', [
            'requested_session_id' => $sessionId,
            'organization_id' => $organizationId,
            'hospital_id' => $hospitalId,
            'authenticated_user_id' => $userId,
            'found' => false,
            'created' => true,
            'existing_user_id' => null,
            'final_user_id' => $this->normalizeUserId($session->user_id),
            'status' => $session->status,
        ]);

        return ['session' => $session, 'created' => true];
    }

    public function findSession(
        string $sessionId,
        ?int $organizationId,
        ?int $hospitalId,
    ): ?ChatbotSession {
        $query = ChatbotSession::query()->where('session_id', $sessionId);
        $this->applyTenantScope($query, $organizationId, $hospitalId);

        return $query->first();
    }

    /**
     * Persist the current user turn exactly once (retry-safe).
     *
     * @return array{message: ChatbotMessage, reused: bool}
     */
    public function saveUserMessageOnce(
        ChatbotSession $session,
        string $content,
        ?string $userId = null,
    ): array {
        $content = trim($content);
        if ($content === '') {
            throw new RuntimeException('A user message is required.');
        }

        $userId = $this->normalizeUserId($userId) ?? $this->normalizeUserId($session->user_id);

        $last = $this->scopedMessagesQuery($session)
            ->orderByDesc('id')
            ->first();

        if (
            $last
            && $last->role === 'user'
            && trim((string) $last->content) === $content
        ) {
            $this->memoryLog('chatbot.memory.user_message_saved', [
                'message_id' => $last->id,
                'session_id' => $last->session_id,
                'role' => $last->role,
                'user_id' => $this->normalizeUserId($last->user_id),
                'content_length' => mb_strlen($content),
                'created_at' => optional($last->created_at)?->toIso8601String(),
                'reused' => true,
                'preview' => $this->preview($content),
            ]);

            return ['message' => $last, 'reused' => true];
        }

        $message = ChatbotMessage::query()->create([
            'session_id' => $session->session_id,
            'organization_id' => $session->organization_id,
            'hospital_id' => $session->hospital_id,
            'user_id' => $userId,
            'workflow_id' => null,
            'execution_id' => null,
            'role' => 'user',
            'content' => $content,
            'model' => null,
            'prompt_tokens' => null,
            'completion_tokens' => null,
            'total_tokens' => null,
        ]);

        $session->last_message_at = Carbon::now();
        if ($userId !== null && $this->normalizeUserId($session->user_id) === null) {
            $session->user_id = $userId;
        }
        $session->save();

        $this->memoryLog('chatbot.memory.user_message_saved', [
            'message_id' => $message->id,
            'session_id' => $message->session_id,
            'organization_id' => $message->organization_id,
            'hospital_id' => $message->hospital_id,
            'role' => $message->role,
            'user_id' => $this->normalizeUserId($message->user_id),
            'content_length' => mb_strlen($content),
            'created_at' => optional($message->created_at)?->toIso8601String(),
            'reused' => false,
            'preview' => $this->preview($content),
        ]);

        return ['message' => $message, 'reused' => false];
    }

    /**
     * @param  array{prompt_tokens?: int|null, completion_tokens?: int|null, total_tokens?: int|null}|null  $usage
     */
    public function saveAssistantMessage(
        ChatbotSession $session,
        string $content,
        ?string $model = null,
        ?array $usage = null,
        ?int $workflowId = null,
        ?int $executionId = null,
        ?string $userId = null,
    ): ChatbotMessage {
        $userId = $this->normalizeUserId($userId) ?? $this->normalizeUserId($session->user_id);

        $message = ChatbotMessage::query()->create([
            'session_id' => $session->session_id,
            'organization_id' => $session->organization_id,
            'hospital_id' => $session->hospital_id,
            'user_id' => $userId,
            'workflow_id' => $workflowId,
            'execution_id' => $executionId,
            'role' => 'assistant',
            'content' => $content,
            'model' => $model,
            'prompt_tokens' => array_key_exists('prompt_tokens', (array) $usage) && $usage['prompt_tokens'] !== null
                ? (int) $usage['prompt_tokens']
                : null,
            'completion_tokens' => array_key_exists('completion_tokens', (array) $usage) && $usage['completion_tokens'] !== null
                ? (int) $usage['completion_tokens']
                : null,
            'total_tokens' => array_key_exists('total_tokens', (array) $usage) && $usage['total_tokens'] !== null
                ? (int) $usage['total_tokens']
                : null,
        ]);

        $session->last_message_at = Carbon::now();
        $session->save();

        $this->memoryLog('chatbot.memory.assistant_message_saved', [
            'message_id' => $message->id,
            'session_id' => $message->session_id,
            'user_id' => $this->normalizeUserId($message->user_id),
            'workflow_id' => $workflowId,
            'execution_id' => $executionId,
            'model' => $model,
            'prompt_tokens' => $message->prompt_tokens,
            'completion_tokens' => $message->completion_tokens,
            'total_tokens' => $message->total_tokens,
            'response_length' => mb_strlen($content),
            'preview' => $this->preview($content),
        ]);

        return $message;
    }

    /**
     * Latest N messages for the session, returned in chronological order.
     *
     * @return list<array{role: string, content: string}>
     */
    public function loadRecentMessagesChronological(
        string $sessionId,
        ?int $organizationId,
        ?int $hospitalId,
        int $limit = self::HISTORY_LIMIT,
    ): array {
        $rows = ChatbotMessage::query()
            ->where('session_id', $sessionId)
            ->where(function ($q) use ($organizationId) {
                if ($organizationId === null) {
                    $q->whereNull('organization_id');
                } else {
                    $q->where('organization_id', $organizationId);
                }
            })
            ->where(function ($q) use ($hospitalId) {
                if ($hospitalId === null) {
                    $q->whereNull('hospital_id');
                } else {
                    $q->where('hospital_id', $hospitalId);
                }
            })
            ->whereIn('role', ['system', 'user', 'assistant'])
            ->orderByDesc('id')
            ->limit(max(1, $limit))
            ->get(['id', 'role', 'content']);

        $chronological = $rows
            ->reverse()
            ->values()
            ->map(fn (ChatbotMessage $m) => [
                'role' => (string) $m->role,
                'content' => (string) $m->content,
            ])
            ->all();

        $roles = array_map(fn (array $row) => $row['role'], $chronological);

        $context = [
            'session_id' => $sessionId,
            'organization_id' => $organizationId,
            'hospital_id' => $hospitalId,
            'requested_limit' => $limit,
            'loaded_count' => count($chronological),
            'roles' => $roles,
        ];

        if ($this->memoryDebugEnabled()) {
            $context['previews'] = array_map(
                fn (array $row) => [
                    'role' => $row['role'],
                    'length' => mb_strlen($row['content']),
                    'preview' => $this->preview($row['content'], 100),
                ],
                $chronological
            );
        }

        $this->memoryLog('chatbot.memory.history_loaded', $context);

        return $chronological;
    }

    public function endSession(ChatbotSession $session): ChatbotSession
    {
        $session->status = ChatbotSession::STATUS_ENDED;
        $session->ended_at = Carbon::now();
        $session->save();

        $this->memoryLog('chatbot.memory.session_ended', [
            'session_id' => $session->session_id,
            'user_id' => $this->normalizeUserId($session->user_id),
            'ended_at' => optional($session->ended_at)?->toIso8601String(),
        ]);

        return $session;
    }

    /**
     * Normalize authenticated / stored user ids (UUID strings). Never cast to int.
     */
    public function normalizeUserId(mixed $userId): ?string
    {
        if ($userId === null) {
            return null;
        }

        $value = trim((string) $userId);
        if ($value === '' || $value === '0') {
            return null;
        }

        return $value;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\ChatbotSession|\App\Models\ChatbotMessage>  $query
     */
    protected function applyTenantScope($query, ?int $organizationId, ?int $hospitalId): void
    {
        if ($organizationId === null) {
            $query->whereNull('organization_id');
        } else {
            $query->where('organization_id', $organizationId);
        }

        if ($hospitalId === null) {
            $query->whereNull('hospital_id');
        } else {
            $query->where('hospital_id', $hospitalId);
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\ChatbotMessage>
     */
    protected function scopedMessagesQuery(ChatbotSession $session)
    {
        $query = ChatbotMessage::query()->where('session_id', $session->session_id);
        $this->applyTenantScope(
            $query,
            $session->organization_id !== null ? (int) $session->organization_id : null,
            $session->hospital_id !== null ? (int) $session->hospital_id : null,
        );

        return $query;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function memoryLog(string $event, array $context): void
    {
        // Always emit structured safe logs for memory verification.
        Log::info($event, $context);
    }

    protected function memoryDebugEnabled(): bool
    {
        return (bool) config('automation.debug_chat_memory', false)
            || (bool) config('automation.debug_chat_payload', false);
    }

    protected function preview(string $content, int $max = 80): ?string
    {
        if (! $this->memoryDebugEnabled()) {
            return null;
        }

        $trimmed = trim($content);
        if ($trimmed === '') {
            return '';
        }

        if (mb_strlen($trimmed) <= $max) {
            return $trimmed;
        }

        return mb_substr($trimmed, 0, $max).'…';
    }
}
