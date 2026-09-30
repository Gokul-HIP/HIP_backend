<?php

namespace App\Modules\MedicineReminder\Notifications;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WhatsJetClient
{
    /**
     * @return array{success: bool, response: string, log_uid?: string|null, wamid?: string|null, contact_uid?: string|null, provider_status?: string|null}
     */
    public function sendText(string $phoneNumber, string $messageBody): array
    {
        return $this->post('contact/send-message', [
            'phone_number' => $phoneNumber,
            'message_body' => $messageBody,
        ]);
    }

    /**
     * @return array{success: bool, response: string, log_uid?: string|null, wamid?: string|null, contact_uid?: string|null, provider_status?: string|null}
     */
    public function sendMedia(
        string $phoneNumber,
        string $mediaType,
        string $mediaUrl,
        string $caption = ''
    ): array {
        return $this->post('contact/send-media-message', [
            'phone_number' => $phoneNumber,
            'media_type' => $mediaType,
            'media_url' => $mediaUrl,
            'caption' => $caption,
        ]);
    }

    /**
     * @param  array<int|string, mixed>  $components
     * @return array{success: bool, response: string, log_uid?: string|null, wamid?: string|null, contact_uid?: string|null, provider_status?: string|null}
     */
    public function sendTemplate(
        string $phoneNumber,
        string $templateName,
        string $templateLanguage,
        array $components = []
    ): array {
        return $this->post('contact/send-template-message', [
            'phone_number' => $phoneNumber,
            'template_name' => $templateName,
            'template_language' => $templateLanguage,
            'components' => $components,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listTemplates(): array
    {
        $response = $this->request('get', 'contact/template-list');
        $interpreted = $this->interpret($response);

        if (! ($interpreted['success'] ?? false)) {
            throw new RuntimeException($interpreted['response'] ?? 'WhatsApp provider error: failed to list templates');
        }

        $json = $response->json();
        $rows = $json['data'] ?? $json['templates'] ?? $json;

        if (! is_array($rows)) {
            return [];
        }

        $safe = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $safe[] = [
                'name' => $row['name'] ?? $row['template_name'] ?? null,
                'language' => $row['language'] ?? $row['template_language'] ?? null,
                'status' => $row['status'] ?? null,
                'category' => $row['category'] ?? null,
            ];
        }

        return $safe;
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array{success: bool, response: string, log_uid?: string|null, wamid?: string|null, contact_uid?: string|null, provider_status?: string|null}
     */
    protected function post(string $path, array $body): array
    {
        Log::info('WhatsJet send starting', [
            'path' => $path,
        ]);

        try {
            $response = $this->request('post', $path, $body);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp provider request failed', [
                'path' => $path,
                'error' => $this->safeError($e->getMessage()),
            ]);

            return [
                'success' => false,
                'response' => 'WhatsApp provider error: '.$this->safeError($e->getMessage()),
            ];
        }

        $interpreted = $this->interpret($response);

        Log::info('WhatsJet HTTP response', [
            'path' => $path,
            'http_status' => $response->status(),
            'success' => (bool) ($interpreted['success'] ?? false),
            'log_uid' => $interpreted['log_uid'] ?? null,
            'wamid' => $interpreted['wamid'] ?? null,
        ]);

        return $interpreted;
    }

    /**
     * @param  array<string, mixed>  $body
     */
    protected function request(string $method, string $path, array $body = []): Response
    {
        $token = trim((string) config('services.whatsapp.token'));
        $vendor = trim((string) config('services.whatsapp.vendor_uid'));
        $base = rtrim((string) config('services.whatsapp.base_url'), '/');

        if ($base === '' || $vendor === '' || $token === '') {
            throw new RuntimeException(
                'WhatsApp provider is not configured (WHATSAPP_PROVIDER_BASE_URL, WHATSAPP_PROVIDER_VENDOR_UID, WHATSAPP_PROVIDER_TOKEN)'
            );
        }

        $timeout = (int) config('services.whatsapp.timeout', 20);
        $url = $base.'/'.$vendor.'/'.ltrim($path, '/');

        $pending = Http::timeout($timeout > 0 ? $timeout : 20)
            ->acceptJson()
            ->withToken($token);

        return $method === 'get'
            ? $pending->get($url)
            : $pending->asJson()->post($url, $body);
    }

    /**
     * @return array{success: bool, response: string, log_uid?: string|null, wamid?: string|null, contact_uid?: string|null, provider_status?: string|null}
     */
    protected function interpret(Response $response): array
    {
        $status = $response->status();
        $json = $response->json();
        $json = is_array($json) ? $json : [];

        if ($status === 401) {
            return $this->fail('Invalid token');
        }

        $providerMessage = $this->providerMessage($json, $status);

        if (in_array($status, [403, 404], true)) {
            return $this->fail($providerMessage !== '' ? $providerMessage : 'Invalid or inactive vendor');
        }

        if ($status === 422) {
            return $this->fail($providerMessage !== '' ? $providerMessage : 'Validation failed');
        }

        $resultFlag = strtolower((string) ($json['result'] ?? $json['status'] ?? ''));
        $data = is_array($json['data'] ?? null) ? $json['data'] : $json;

        if (in_array($resultFlag, ['failed', 'fail', 'error'], true)) {
            return $this->fail($providerMessage !== '' ? $providerMessage : 'Provider result = failed');
        }

        if ($status >= 500) {
            return $this->fail($providerMessage !== '' ? $providerMessage : 'Provider HTTP '.$status);
        }

        if ($status >= 400) {
            return $this->fail($providerMessage !== '' ? $providerMessage : 'Provider HTTP '.$status);
        }

        $logUid = $this->scalar($data['log_uid'] ?? $json['log_uid'] ?? null);
        $wamid = $this->scalar($data['wamid'] ?? $json['wamid'] ?? null);

        return [
            'success' => true,
            'response' => json_encode([
                'log_uid' => $logUid,
                'wamid' => $wamid,
                'contact_uid' => $this->scalar($data['contact_uid'] ?? $json['contact_uid'] ?? null),
                'status' => $this->scalar($data['status'] ?? $json['status'] ?? 'accepted'),
            ], JSON_UNESCAPED_SLASHES) ?: 'accepted',
            'log_uid' => $logUid,
            'wamid' => $wamid,
            'contact_uid' => $this->scalar($data['contact_uid'] ?? $json['contact_uid'] ?? null),
            'provider_status' => $this->scalar($data['status'] ?? $json['status'] ?? 'accepted'),
        ];
    }

    /**
     * @param  array<string, mixed>  $json
     */
    protected function providerMessage(array $json, int $httpStatus): string
    {
        $raw = $json['message'] ?? $json['error'] ?? $json['error_message'] ?? $json['errors'] ?? '';

        if (is_array($raw)) {
            $raw = json_encode($raw) ?: '';
        }

        $text = $this->safeError((string) $raw);

        if ($text === '' && isset($json['plan'])) {
            $text = 'API plan unavailable';
        }

        return $text;
    }

    /**
     * @return array{success: bool, response: string}
     */
    protected function fail(string $message): array
    {
        return [
            'success' => false,
            'response' => 'WhatsApp provider error: '.$this->safeError($message),
        ];
    }

    protected function safeError(string $message): string
    {
        $token = trim((string) config('services.whatsapp.token'));
        if ($token !== '') {
            $message = str_replace($token, '[redacted]', $message);
        }

        $message = preg_replace('/Bearer\s+\S+/i', 'Bearer [redacted]', $message) ?? $message;

        return trim($message);
    }

    protected function scalar(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }
}
