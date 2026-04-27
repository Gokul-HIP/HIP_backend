<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OpenRouterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ChatbotController extends Controller
{
    public function chat(Request $request, OpenRouterService $openRouterService): JsonResponse
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
        ]);

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

        try {
            $result = $openRouterService->chatCompletions($messages, [
                'model' => $validated['model'] ?? null,
                'fallback_model' => $validated['fallback_model'] ?? null,
                'temperature' => $validated['temperature'] ?? null,
                'max_tokens' => $validated['max_tokens'] ?? null,
                'stream' => (bool) ($validated['stream'] ?? false),
            ]);
        } catch (RuntimeException $e) {
            Log::error('Chat completion failed.', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }

        if ($sessionId && $appendHistory) {
            $persisted = $messages;
            if (($result['content'] ?? '') !== '') {
                $persisted[] = [
                    'role' => 'assistant',
                    'content' => (string) $result['content'],
                ];
            }
            Cache::put($this->sessionCacheKey($sessionId), $persisted, now()->addDay());
        }

        return response()->json([
            'success' => true,
            'message' => 'Chat completion successful.',
            'data' => [
                'session_id' => $sessionId,
                'model' => $result['model'] ?? null,
                'fallback_used' => (bool) ($result['fallback_used'] ?? false),
                'stream' => (bool) ($result['stream'] ?? false),
                'reply' => $result['content'] ?? '',
                'usage' => $result['usage'] ?? null,
                'chunks' => $result['chunks'] ?? null,
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
        return 'chat_session:' . $sessionId;
    }
}

