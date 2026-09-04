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
                $normalized = $this->applyMedicalBillFallbacks($normalized, $documentText);
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
            'patient_name' => $this->sanitizePatientName((string) ($data['patient_name'] ?? '')),
            'patient_id' => trim((string) ($data['patient_id'] ?? '')),
            'bill_number' => trim((string) ($data['bill_number'] ?? '')),
            'hospital_name' => (string) ($data['hospital_name'] ?? ''),
            'invoice_date' => (string) ($data['invoice_date'] ?? ''),
            'total_bill_amount' => trim((string) ($data['total_bill_amount'] ?? '')),
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
     * @param array<string, mixed> $normalized
     * @return array<string, mixed>
     */
    private function applyMedicalBillFallbacks(array $normalized, string $documentText): array
    {
        $patientId = $this->extractPatientId($documentText);
        $patientName = $this->extractPatientName($documentText);
        $billNumber = $this->extractBillNumber($documentText);
        $totalAmount = $this->extractTotalAmount($documentText);

        if (! $this->isValidPatientId((string) ($normalized['patient_id'] ?? '')) && $patientId !== null) {
            $normalized['patient_id'] = $patientId;
        }

        if (! $this->isValidPatientName((string) ($normalized['patient_name'] ?? '')) && $patientName !== null) {
            $normalized['patient_name'] = $patientName;
        }

        if (! $this->isValidBillNumber((string) ($normalized['bill_number'] ?? '')) && $billNumber !== null) {
            $normalized['bill_number'] = $billNumber;
        }

        if ($this->normalizeAmount((string) ($normalized['total_bill_amount'] ?? '')) === null && $totalAmount !== null) {
            $normalized['total_bill_amount'] = $totalAmount;
        }

        $normalized['patient_name'] = $this->sanitizePatientName((string) ($normalized['patient_name'] ?? ''));
        if (! $this->isValidPatientName((string) $normalized['patient_name'])) {
            $normalized['patient_name'] = '';
        }

        $normalizedAmount = $this->normalizeAmount((string) ($normalized['total_bill_amount'] ?? ''));
        $normalized['total_bill_amount'] = $normalizedAmount ?? '';

        return $normalized;
    }

    private function sanitizePatientName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        $name = preg_replace('/\s*Bill\s*No\.?.*/i', '', $name) ?? $name;
        return trim($name);
    }

    private function isValidPatientName(string $name): bool
    {
        $name = trim($name);
        if ($name === '' || strlen($name) < 3 || preg_match('/\d/', $name)) {
            return false;
        }

        $blocked = ['BILL', 'PATIENT', 'DETAILS', 'INPATIENT', 'HOSPITALS'];
        return ! in_array(strtoupper($name), $blocked, true);
    }

    private function isValidPatientId(string $value): bool
    {
        return preg_match('/^[A-Z0-9\-]{3,}$/i', trim($value)) === 1;
    }

    private function isValidBillNumber(string $value): bool
    {
        return preg_match('/^[A-Z0-9\-]{3,}$/i', trim($value)) === 1;
    }

    private function extractPatientId(string $text): ?string
    {
        if (preg_match('/(?:ID|IP|1P)\s*No\.?\s*:\s*([A-Z0-9]+)/i', $text, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    private function extractPatientName(string $text): ?string
    {
        $normalizedText = preg_replace("/\r\n|\r/", "\n", $text) ?? $text;

        if (preg_match('/(?:^|\n)\s*([A-Za-z][A-Za-z .]{2,}?)\s+Bill\s*No\.?\s*:/i', $normalizedText, $matches)) {
            $candidate = trim((string) ($matches[1] ?? ''));
            if ($this->isValidPatientName($candidate)) {
                return $candidate;
            }
        }

        $lines = preg_split('/\n/', $normalizedText) ?: [];
        foreach ($lines as $index => $line) {
            if (stripos($line, 'PATIENT DETAILS') !== false) {
                for ($next = $index + 1; $next <= $index + 4; $next++) {
                    $candidate = trim((string) ($lines[$next] ?? ''));
                    if ($candidate === '') {
                        continue;
                    }

                    $candidate = preg_replace('/\s*Bill\s*No\.?.*/i', '', $candidate) ?? $candidate;
                    $candidate = trim($candidate);
                    if ($this->isValidPatientName($candidate)) {
                        return $candidate;
                    }
                }
            }
        }

        return null;
    }

    private function extractBillNumber(string $text): ?string
    {
        if (preg_match('/Bill\s*No\.?\s*:\s*([A-Z0-9]+)/i', $text, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    private function extractTotalAmount(string $text): ?string
    {
        $patterns = [
            '/Bill\s*Amount\s*[:\-]?\s*([0-9][0-9,]*\.?[0-9]{0,2})/i',
            '/Total\s*Amount\s*[:\-]?\s*([0-9][0-9,]*\.?[0-9]{0,2})/i',
            '/Amount\s*Due\s*[:\-]?\s*([0-9][0-9,]*\.?[0-9]{0,2})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }

    private function normalizeAmount(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/([0-9][0-9,]*\.?[0-9]{0,2})/', $value, $matches)) {
            $value = $matches[1];
        }

        $numeric = str_replace(',', '', $value);
        if (! is_numeric($numeric)) {
            return null;
        }

        return number_format((float) $numeric, 2, '.', '');
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

        $modelName = (string) ($data['model'] ?? $payload['model']);

        return [
            'reply' => $messageContent,
            'model' => $modelName,
            'content' => $messageContent,
            'usage' => [
                'prompt_tokens' => $usage['prompt_tokens'],
                'completion_tokens' => $usage['completion_tokens'],
                'total_tokens' => $usage['total_tokens'],
            ],
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
            'reply' => $content,
            'model' => $model,
            'content' => $content,
            'usage' => [
                'prompt_tokens' => $usage['prompt_tokens'],
                'completion_tokens' => $usage['completion_tokens'],
                'total_tokens' => $usage['total_tokens'],
            ],
            'chunks' => $chunks,
            'raw' => $body,
            'fallback_used' => false,
            'stream' => true,
        ];
    }
}

