<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OpenRouterService
{
    /**
     * @param array<int, array{role:string, content:string}> $messages
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function chatCompletions(array $messages, array $options = []): array
    {
        $apiKey = (string) config('services.openrouter.api_key');
        $baseUrl = rtrim((string) config('services.openrouter.base_url', 'https://openrouter.ai/api/v1'), '/');
        $defaultModel = (string) config('services.openrouter.default_model', 'openai/gpt-4o-mini');
        $fallbackModel = (string) ($options['fallback_model'] ?? config('services.openrouter.fallback_model', 'openrouter/auto'));

        if ($apiKey === '') {
            throw new RuntimeException('OpenRouter API key is not configured.');
        }

        $model = (string) ($options['model'] ?? $defaultModel);
        $stream = (bool) ($options['stream'] ?? false);

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'stream' => $stream,
        ];

        if (array_key_exists('temperature', $options)) {
            $payload['temperature'] = $options['temperature'];
        }
        if (array_key_exists('max_tokens', $options)) {
            $payload['max_tokens'] = $options['max_tokens'];
        }
        if (array_key_exists('response_format', $options) && is_array($options['response_format'])) {
            $payload['response_format'] = $options['response_format'];
        }

        try {
            return $this->sendRequest($baseUrl, $apiKey, $payload, false);
        } catch (RuntimeException $e) {
            if ($fallbackModel === '' || $fallbackModel === $model) {
                throw $e;
            }

            Log::warning('OpenRouter primary model failed, trying fallback model.', [
                'model' => $model,
                'fallback_model' => $fallbackModel,
                'error' => $e->getMessage(),
            ]);

            $payload['model'] = $fallbackModel;
            $result = $this->sendRequest($baseUrl, $apiKey, $payload, true);
            $result['fallback_used'] = true;

            return $result;
        }
    }

    /**
     * Analyze OCR medical bill text and return normalized structured data.
     *
     * @return array<string, mixed>
     */
    public function analyzeMedicalBill(string $documentText, array $options = []): array
    {
        $model = (string) ($options['model'] ?? config('services.openrouter.default_model', 'openai/gpt-4o-mini'));
        $fallbackModel = (string) ($options['fallback_model'] ?? config('services.openrouter.fallback_model', 'openrouter/auto'));
        $maxRetries = (int) ($options['max_retries'] ?? 2);

        $baseSystemPrompt = <<<PROMPT
You are a medical billing extraction engine.
Extract structured information from noisy OCR text of hospital/medical bills.

Rules:
1) Return STRICT JSON only. No markdown, no commentary, no code fences.
2) If a value is missing, return empty string "" (or [] for arrays).
3) Keep numbers as strings exactly as seen when uncertain.
4) line_items must be an array of objects with keys: description, quantity, amount.
5) possible_missing_fields must list missing/uncertain fields by key name.
6) Be robust to OCR noise, mixed layouts, broken lines, and inconsistent formats.
7) summary should be concise and include suspicious/anomalous observations if found.

Required JSON schema:
{
  "patient_name": "",
  "patient_id": "",
  "bill_number": "",
  "hospital_name": "",
  "invoice_date": "",
  "total_bill_amount": "",
  "tax_amount": "",
  "doctor_name": "",
  "admission_date": "",
  "discharge_date": "",
  "line_items": [
    { "description": "", "quantity": "", "amount": "" }
  ],
  "summary": "",
  "possible_missing_fields": []
}
PROMPT;

        $userPrompt = "Extract the medical bill fields from this OCR text:\n\n" . $documentText;
        $messages = [
            ['role' => 'system', 'content' => $baseSystemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];

        $lastError = null;
        for ($attempt = 1; $attempt <= ($maxRetries + 1); $attempt++) {
            try {
                $completion = $this->chatCompletions($messages, [
                    'model' => $model,
                    'fallback_model' => $fallbackModel,
                    'temperature' => 0,
                    'max_tokens' => 1800,
                    'stream' => false,
                    'response_format' => ['type' => 'json_object'],
                ]);

                $decoded = $this->decodeJsonFromModelContent((string) ($completion['content'] ?? ''));
                if (! is_array($decoded)) {
                    throw new RuntimeException('Model returned non-JSON output.');
                }

                $normalized = $this->normalizeMedicalBillResponse($decoded);
                Log::info('Medical bill analysis completed.', [
                    'attempt' => $attempt,
                    'model' => $completion['model'] ?? $model,
                    'usage' => $completion['usage'] ?? null,
                    'fallback_used' => $completion['fallback_used'] ?? false,
                ]);

                return [
                    'analysis' => $normalized,
                    'usage' => $completion['usage'] ?? null,
                    'model' => $completion['model'] ?? $model,
                    'fallback_used' => (bool) ($completion['fallback_used'] ?? false),
                ];
            } catch (RuntimeException $e) {
                $lastError = $e;
                Log::warning('Medical bill analysis attempt failed.', [
                    'attempt' => $attempt,
                    'max_attempts' => $maxRetries + 1,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt <= $maxRetries) {
                    $messages[] = [
                        'role' => 'system',
                        'content' => 'Your previous output was invalid. Return STRICT JSON only that exactly matches the required schema.',
                    ];
                }
            }
        }

        throw new RuntimeException('Failed to analyze medical bill text.', previous: $lastError);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJsonFromModelContent(string $content): ?array
    {
        $trimmed = trim($content);
        if ($trimmed === '') {
            return null;
        }

        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/is', $trimmed, $matches)) {
            $decoded = json_decode(trim($matches[1]), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $start = strpos($trimmed, '{');
        $end = strrpos($trimmed, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $candidate = substr($trimmed, $start, $end - $start + 1);
            $decoded = json_decode($candidate, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeMedicalBillResponse(array $data): array
    {
        $normalized = [
            'patient_name' => (string) ($data['patient_name'] ?? ''),
            'patient_id' => (string) ($data['patient_id'] ?? ''),
            'bill_number' => (string) ($data['bill_number'] ?? ''),
            'hospital_name' => (string) ($data['hospital_name'] ?? ''),
            'invoice_date' => (string) ($data['invoice_date'] ?? ''),
            'total_bill_amount' => (string) ($data['total_bill_amount'] ?? ''),
            'tax_amount' => (string) ($data['tax_amount'] ?? ''),
            'doctor_name' => (string) ($data['doctor_name'] ?? ''),
            'admission_date' => (string) ($data['admission_date'] ?? ''),
            'discharge_date' => (string) ($data['discharge_date'] ?? ''),
            'line_items' => [],
            'summary' => (string) ($data['summary'] ?? ''),
            'possible_missing_fields' => [],
        ];

        $lineItems = $data['line_items'] ?? [];
        if (is_array($lineItems)) {
            foreach ($lineItems as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $normalized['line_items'][] = [
                    'description' => (string) ($item['description'] ?? ''),
                    'quantity' => (string) ($item['quantity'] ?? ''),
                    'amount' => (string) ($item['amount'] ?? ''),
                ];
            }
        }

        $missing = $data['possible_missing_fields'] ?? [];
        if (is_array($missing)) {
            $normalized['possible_missing_fields'] = array_values(array_unique(array_map(function ($value) {
                return (string) $value;
            }, $missing)));
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function sendRequest(string $baseUrl, string $apiKey, array $payload, bool $isFallback): array
    {
        $response = Http::timeout(90)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'HTTP-Referer' => (string) config('app.url'),
                'X-Title' => (string) config('app.name'),
                'Content-Type' => 'application/json',
            ])
            ->post($baseUrl . '/chat/completions', $payload);

        if (! $response->successful()) {
            $body = trim((string) $response->body());
            Log::error('OpenRouter request failed.', [
                'status' => $response->status(),
                'response' => $body,
                'model' => $payload['model'] ?? null,
                'is_fallback' => $isFallback,
            ]);

            throw new RuntimeException('OpenRouter request failed with status ' . $response->status() . '.');
        }

        if (($payload['stream'] ?? false) === true) {
            return $this->parseStreamResponse((string) $response->body(), (string) $payload['model']);
        }

        $data = $response->json();
        if (! is_array($data)) {
            throw new RuntimeException('Invalid OpenRouter response payload.');
        }

        $messageContent = (string) data_get($data, 'choices.0.message.content', '');
        $usage = [
            'prompt_tokens' => (int) data_get($data, 'usage.prompt_tokens', 0),
            'completion_tokens' => (int) data_get($data, 'usage.completion_tokens', 0),
            'total_tokens' => (int) data_get($data, 'usage.total_tokens', 0),
        ];

        Log::info('OpenRouter completion success.', [
            'model' => $data['model'] ?? ($payload['model'] ?? null),
            'usage' => $usage,
            'is_fallback' => $isFallback,
        ]);

        return [
            'model' => (string) ($data['model'] ?? $payload['model']),
            'content' => $messageContent,
            'usage' => $usage,
            'raw' => $data,
            'fallback_used' => $isFallback,
            'stream' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parseStreamResponse(string $body, string $model): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $body) ?: [];
        $content = '';
        $chunks = [];
        $usage = [
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0,
        ];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || ! str_starts_with($line, 'data:')) {
                continue;
            }

            $json = trim(substr($line, 5));
            if ($json === '[DONE]') {
                continue;
            }

            $decoded = json_decode($json, true);
            if (! is_array($decoded)) {
                continue;
            }

            $delta = (string) data_get($decoded, 'choices.0.delta.content', '');
            if ($delta !== '') {
                $content .= $delta;
                $chunks[] = $delta;
            }

            if (isset($decoded['usage']) && is_array($decoded['usage'])) {
                $usage = [
                    'prompt_tokens' => (int) ($decoded['usage']['prompt_tokens'] ?? 0),
                    'completion_tokens' => (int) ($decoded['usage']['completion_tokens'] ?? 0),
                    'total_tokens' => (int) ($decoded['usage']['total_tokens'] ?? 0),
                ];
            }
        }

        return [
            'model' => $model,
            'content' => $content,
            'usage' => $usage,
            'chunks' => $chunks,
            'raw' => $body,
            'fallback_used' => false,
            'stream' => true,
        ];
    }
}

