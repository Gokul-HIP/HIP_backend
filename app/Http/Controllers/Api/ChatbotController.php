<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Automation\Services\ChatbotConversationService;
use App\Modules\Automation\Services\ChatbotWorkflowService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ChatbotController extends Controller
{
    public function chat(
        Request $request,
        ChatbotWorkflowService $chatbotWorkflow,
        ChatbotConversationService $conversations,
    ): JsonResponse {
        $validated = $request->validate([
            'messages' => ['required_without:end_conversation', 'array', 'min:1'],
            'messages.*.role' => ['required_with:messages', 'string', 'in:system,user,assistant,tool'],
            'messages.*.content' => ['required_with:messages', 'string'],
            'session_id' => ['nullable', 'string', 'max:100'],
            'append_history' => ['nullable', 'boolean'],
            'end_conversation' => ['nullable', 'boolean'],
            'model' => ['nullable', 'string'],
            'fallback_model' => ['nullable', 'string'],
            'temperature' => ['nullable', 'numeric', 'between:0,2'],
            'max_tokens' => ['nullable', 'integer', 'min:1', 'max:8192'],
            'stream' => ['nullable', 'boolean'],
            'organization_id' => ['nullable', 'integer', 'min:1'],
            'hospital_id' => ['nullable', 'integer', 'min:1'],
        ]);

        if (! empty($validated['stream'])) {
            return response()->json([
                'success' => false,
                'message' => 'Streaming is not supported for workflow-backed chat completions.',
            ], 422);
        }

        $organizationId = isset($validated['organization_id']) ? (int) $validated['organization_id'] : null;
        $hospitalId = isset($validated['hospital_id']) ? (int) $validated['hospital_id'] : null;
        $endConversation = (bool) ($validated['end_conversation'] ?? false);

        $userId = $this->resolveAuthenticatedUserId($request);

        Log::info('chatbot.memory.auth_resolved', [
            'authenticated' => $request->user() !== null,
            'user_class' => $request->user() !== null ? $request->user()::class : null,
            'user_id' => $userId,
            'user_id_type' => $userId !== null ? gettype($userId) : null,
            'organization_id' => $organizationId,
            'hospital_id' => $hospitalId,
            'session_id' => $validated['session_id'] ?? null,
        ]);

        try {
            $resolved = $conversations->resolveOrCreateSession(
                sessionId: $validated['session_id'] ?? null,
                organizationId: $organizationId,
                hospitalId: $hospitalId,
                userId: $userId,
            );
            $session = $resolved['session'];
            $sessionId = (string) $session->session_id;

            if ($endConversation) {
                $conversations->endSession($session);
                Cache::forget($this->sessionCacheKey($sessionId));

                return response()->json([
                    'success' => true,
                    'message' => 'Chat session ended successfully.',
                    'data' => [
                        'session_id' => $sessionId,
                        'model' => null,
                        'fallback_used' => false,
                        'stream' => false,
                        'reply' => null,
                        'usage' => null,
                        'chunks' => null,
                        'workflow_id' => null,
                        'execution_id' => null,
                        'trigger_type' => null,
                        'status' => 'ended',
                    ],
                ]);
            }

            $incomingMessages = array_values($validated['messages'] ?? []);
            $chatMessage = $this->extractLatestUserMessage($incomingMessages);
            if ($chatMessage === '') {
                throw new RuntimeException('A user message is required.');
            }

            // Persist the current user turn once (retry-safe). Executor must not re-save it.
            // Resolve/validate an active workflow first so inactive graphs never run or call OpenRouter.
            $chatbotWorkflow->resolveWorkflow($organizationId, $hospitalId);

            $saved = $conversations->saveUserMessageOnce($session, $chatMessage, $userId);

            Log::info('chatbot.memory.user_turn_accepted', [
                'session_id' => $sessionId,
                'message_id' => $saved['message']->id,
                'reused' => $saved['reused'],
                'user_id' => $conversations->normalizeUserId($saved['message']->user_id),
                'content_length' => mb_strlen($chatMessage),
            ]);

            // Pass only the current turn into the workflow; DB history is loaded in SendAiChatNodeProcessor.
            $workflowMessages = [
                ['role' => 'user', 'content' => $chatMessage],
            ];

            $result = $chatbotWorkflow->complete($workflowMessages, [
                'session_id' => $sessionId,
                'organization_id' => $organizationId,
                'hospital_id' => $hospitalId,
                'member_id' => $userId,
                'temperature' => isset($validated['temperature']) ? (float) $validated['temperature'] : null,
            ]);
        } catch (RuntimeException $e) {
            Log::error('Chat completion failed.', [
                'error' => $e->getMessage(),
                'session_id' => $validated['session_id'] ?? null,
                'source' => ChatbotWorkflowService::SOURCE,
            ]);

            $status = $this->statusForChatError($e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $status);
        }

        // Keep a lightweight cache mirror for clients still using append_history/clear.
        if ((bool) ($validated['append_history'] ?? true)) {
            $persisted = $conversations->loadRecentMessagesChronological(
                $sessionId,
                $organizationId,
                $hospitalId,
                ChatbotConversationService::HISTORY_LIMIT,
            );
            Cache::put($this->sessionCacheKey($sessionId), $persisted, now()->addDay());
        }

        return response()->json([
            'success' => true,
            'message' => 'Chat completion successful.',
            'data' => [
                'session_id' => $sessionId,
                'model' => $result['model'] ?? ($validated['model'] ?? null),
                'fallback_used' => (bool) ($result['fallback_used'] ?? false),
                'stream' => false,
                'reply' => $result['reply'],
                'usage' => $result['usage'] ?? null,
                'chunks' => null,
                'workflow_id' => $result['workflow_id'],
                'execution_id' => $result['execution_id'],
                'trigger_type' => $result['trigger_type'],
            ],
        ]);
    }

    public function clearSession(
        Request $request,
        ChatbotConversationService $conversations,
    ): JsonResponse {
        $validated = $request->validate([
            'session_id' => ['required', 'string', 'max:100'],
            'organization_id' => ['nullable', 'integer', 'min:1'],
            'hospital_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $sessionId = $validated['session_id'];
        Cache::forget($this->sessionCacheKey($sessionId));

        $session = $conversations->findSession(
            $sessionId,
            isset($validated['organization_id']) ? (int) $validated['organization_id'] : null,
            isset($validated['hospital_id']) ? (int) $validated['hospital_id'] : null,
        );

        if ($session !== null && $session->isOpen()) {
            $userId = $this->resolveAuthenticatedUserId($request);
            $ownerId = $conversations->normalizeUserId($session->user_id);
            if (
                $ownerId !== null
                && $userId !== null
                && ! hash_equals($ownerId, $userId)
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'This chat session belongs to another user.',
                ], 403);
            }
            $conversations->endSession($session);
        }

        return response()->json([
            'success' => true,
            'message' => 'Chat session cleared successfully.',
        ]);
    }

    /**
     * Resolve Sanctum/HIPUser id as a UUID string. Never cast to int.
     */
    private function resolveAuthenticatedUserId(Request $request): ?string
    {
        $user = $request->user();
        if (! $user instanceof Authenticatable) {
            return null;
        }

        $raw = $user->getAuthIdentifier();
        if ($raw === null && isset($user->id)) {
            $raw = $user->id;
        }

        $value = trim((string) $raw);
        if ($value === '' || $value === '0') {
            return null;
        }

        return $value;
    }

    private function sessionCacheKey(string $sessionId): string
    {
        return 'chat_session:'.$sessionId;
    }

    /**
     * @param  list<array{role?: string, content?: string}>  $messages
     */
    private function extractLatestUserMessage(array $messages): string
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

    private function statusForChatError(string $message): int
    {
        $lower = strtolower($message);

        if (str_contains($lower, 'no published onchatmessage')
            || str_contains($lower, 'no active chatbot workflow')
            || str_contains($lower, 'user message is required')
        ) {
            return 404;
        }

        if (str_contains($lower, 'wait/delay')
            || str_contains($lower, 'not supported')
            || str_contains($lower, 'session has ended')
            || str_contains($lower, 'belongs to another user')
        ) {
            return 422;
        }

        return 500;
    }
}
