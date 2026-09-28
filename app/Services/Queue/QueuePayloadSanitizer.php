<?php

namespace App\Services\Queue;

class QueuePayloadSanitizer
{
    /**
     * @var list<string>
     */
    protected array $secretKeyFragments = [
        'password',
        'passwd',
        'secret',
        'token',
        'authorization',
        'api_key',
        'apikey',
        'access_key',
        'private_key',
        'bearer',
        'credentials',
    ];

    /**
     * @return array<string, mixed>
     */
    public function summarize(?string $payload): array
    {
        $decoded = $this->decode($payload);

        if ($decoded === null) {
            return [
                'display_name' => 'Unavailable',
                'job' => null,
                'safe' => false,
                'note' => 'Payload could not be parsed as JSON. Raw PHP serialization is not displayed.',
            ];
        }

        return [
            'uuid' => $decoded['uuid'] ?? null,
            'display_name' => $decoded['displayName'] ?? $decoded['display_name'] ?? null,
            'job' => $decoded['job'] ?? null,
            'command_name' => data_get($decoded, 'data.commandName'),
            'max_tries' => $decoded['maxTries'] ?? $decoded['max_tries'] ?? null,
            'timeout' => $decoded['timeout'] ?? null,
            'queue' => $decoded['queue'] ?? null,
            'note' => 'Serialized job command bodies are never displayed.',
        ];
    }

    public function exceptionPreview(?string $exception, int $max = 4000): ?string
    {
        if ($exception === null || $exception === '') {
            return null;
        }

        return $this->truncate($this->redactText($exception), $max);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function redact(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && $this->isSensitiveKey($key)) {
                $data[$key] = '[redacted]';
                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->redact($value);
            } elseif (is_string($value)) {
                $data[$key] = $this->redactText($value);
            }
        }

        return $data;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function decode(?string $payload): ?array
    {
        if (! is_string($payload) || $payload === '') {
            return null;
        }

        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : null;
    }

    protected function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower($key);

        foreach ($this->secretKeyFragments as $fragment) {
            if (str_contains($normalized, $fragment)) {
                return true;
            }
        }

        return false;
    }

    protected function redactText(string $value): string
    {
        $value = preg_replace('/Bearer\s+[A-Za-z0-9\-._~+\/]+=*/i', 'Bearer [redacted]', $value) ?? $value;
        $value = preg_replace('/(password|secret|token|api[_-]?key)\s*[:=]\s*\S+/i', '$1=[redacted]', $value) ?? $value;

        return $value;
    }

    protected function truncate(string $value, int $max): string
    {
        if (mb_strlen($value) <= $max) {
            return $value;
        }

        return mb_substr($value, 0, $max).'…';
    }
}
