<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\HospitalAutomation\Services\ChatbotWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ChatbotController extends Controller
{
    public function chat(Request $request, ChatbotWorkflowService $chatbotWorkflow): JsonResponse
    {
        $validated = $request->validate([
            'messages' => ['required', 'array', 'min:1'],
            'messages.*.role' => ['required', 'string', 'in:system,user,assistant,tool'],
            'messages.*.content' => ['required', 'string'],
            'session_id' => ['nullable', 'string', 'max:100'],
            'append_history' => ['nullable', 'boolean'],
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

        $sessionId = $validated['session_id'] ?? null;
        $appendHistory = (bool) ($validated['append_history'] ?? true);
        $incomingMessages = $validated['messages'];

        $history = [];
        if ($sessionId && $appendHistory) {
            $history = Cache::get($this->sessionCacheKey($sessionId), []);
            if (! is_array($history)) {
                $history = [];
            }
        }

        $messages = array_values(array_merge($history, $incomingMessages));

        $memberId = null;
        $user = $request->user();
        if ($user !== null) {
            $memberId = isset($user->id) ? (string) $user->id : null;
        }

        try {
            $result = $chatbotWorkflow->complete($messages, [
                'session_id' => $sessionId,
                'organization_id' => $validated['organization_id'] ?? null,
                'hospital_id' => $validated['hospital_id'] ?? null,
                'member_id' => $memberId,
                'temperature' => isset($validated['temperature']) ? (float) $validated['temperature'] : null,
            ]);
        } catch (RuntimeException $e) {
            Log::error('Chat completion failed.', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'source' => ChatbotWorkflowService::SOURCE,
            ]);

            $status = $this->statusForChatError($e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $status);
        }

        if ($sessionId && $appendHistory) {
            $persisted = $messages;
            if (($result['reply'] ?? '') !== '') {
                $persisted[] = [
                    'role' => 'assistant',
                    'content' => (string) $result['reply'],
                ];
            }
            Cache::put($this->sessionCacheKey($sessionId), $persisted, now()->addDay());
        }

        // Preserve existing response envelope; workflow metadata is additive.
        return response()->json([
            'success' => true,
            'message' => 'Chat completion successful.',
            'data' => [
                'session_id' => $sessionId,
                'model' => $validated['model'] ?? null,
                'fallback_used' => false,
                'stream' => false,
                'reply' => $result['reply'],
                'usage' => null,
                'chunks' => null,
                'workflow_id' => $result['workflow_id'],
                'execution_id' => $result['execution_id'],
                'trigger_type' => $result['trigger_type'],
            ],
        ]);
    }

    public function clearSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'string', 'max:100'],
        ]);

        Cache::forget($this->sessionCacheKey($validated['session_id']));

        return response()->json([
            'success' => true,
            'message' => 'Chat session cleared successfully.',
        ]);
    }

    private function sessionCacheKey(string $sessionId): string
    {
        return 'chat_session:'.$sessionId;
    }

    private function statusForChatError(string $message): int
    {
        $lower = strtolower($message);

        if (str_contains($lower, 'no published onchatmessage')
            || str_contains($lower, 'user message is required')
        ) {
            return 404;
        }

        if (str_contains($lower, 'wait/delay')
            || str_contains($lower, 'not supported')
        ) {
            return 422;
        }

        return 500;
    }
}
