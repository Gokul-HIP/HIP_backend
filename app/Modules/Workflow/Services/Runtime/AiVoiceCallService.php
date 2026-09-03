<?php

namespace App\Modules\Workflow\Services\Runtime;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Outbound AI voice-call provider adapter.
 *
 * There is no in-repo Twilio/Exotel/TTS SDK. Delivery is delegated to an external
 * HTTP endpoint (WORKFLOW_AI_VOICE_ENDPOINT). Fails closed when unset.
 */
class AiVoiceCallService
{
    /**
     * @param  array<string, mixed>  $options
     * @return array{success: bool, response: string, call_id?: string|null}
     */
    public function send(?string $phone, string $script, array $options = []): array
    {
        if (! filled($phone)) {
            return [
                'success' => false,
                'response' => 'Recipient phone number missing for AI voice call',
            ];
        }

        $script = trim($script);
        if ($script === '') {
            return [
                'success' => false,
                'response' => 'AI voice call script is empty',
            ];
        }

        $endpoint = config('services.workflow_ai_voice.endpoint');
        if (! is_string($endpoint) || trim($endpoint) === '') {
            return [
                'success' => false,
                'response' => 'AI voice call endpoint is not configured (services.workflow_ai_voice.endpoint / WORKFLOW_AI_VOICE_ENDPOINT)',
            ];
        }

        $payload = [
            'type' => 'ai_voice',
            'to' => $phone,
            'script' => $script,
            'voice_provider' => (string) ($options['voice_provider'] ?? 'default'),
            'language' => (string) ($options['language'] ?? 'en'),
            'voice' => (string) ($options['voice'] ?? ''),
            'gender' => (string) ($options['gender'] ?? 'neutral'),
            'retry_count' => (int) ($options['retry_count'] ?? 0),
        ];

        foreach (['workflow_id', 'workflow_execution_id', 'execution_id', 'node_id', 'source', 'appointment_id'] as $key) {
            if (array_key_exists($key, $options) && $options[$key] !== null && $options[$key] !== '') {
                $payload[$key] = $options[$key];
            }
        }

        try {
            $response = Http::timeout(60)->post($endpoint, $payload);
        } catch (\Throwable $e) {
            Log::warning('AI voice call provider request failed', [
                'error' => $e->getMessage(),
                'endpoint' => $endpoint,
            ]);

            return [
                'success' => false,
                'response' => 'AI voice call provider request failed: '.$e->getMessage(),
            ];
        }

        if (! $response->successful()) {
            return [
                'success' => false,
                'response' => 'AI voice call provider returned HTTP '.$response->status(),
            ];
        }

        $success = $response->json('success');
        if ($success === false) {
            return [
                'success' => false,
                'response' => (string) ($response->json('message') ?? $response->json('error') ?? 'AI voice call provider reported failure'),
            ];
        }

        $callId = $response->json('call_id')
            ?? $response->json('id')
            ?? $response->json('message_id');

        return [
            'success' => true,
            'response' => (string) ($response->json('message') ?? 'AI voice call accepted'),
            'call_id' => is_scalar($callId) ? (string) $callId : null,
        ];
    }
}
