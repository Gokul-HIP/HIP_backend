<?php

namespace App\Modules\MedicineReminder\Notifications;

use Illuminate\Support\Facades\Log;

class WhatsAppNotificationService
{
    public function __construct(
        protected WhatsJetClient $client,
        protected WhatsAppPhoneNormalizer $phoneNormalizer,
    ) {}

    public function clientClass(): string
    {
        return $this->client::class;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $delivery
     * @return array{success: bool, response: string, log_uid?: string|null, wamid?: string|null}
     */
    public function send(?string $mobile, string $message, array $payload = [], array $delivery = []): array
    {
        $phone = $this->phoneNormalizer->normalize($mobile);
        $meta = $this->safeMeta($payload);

        Log::info('Send WhatsApp provider selected', array_merge($meta, [
            'node_type' => 'sendWhatsApp',
            'provider_class' => self::class,
            'client_class' => $this->clientClass(),
            'recipient_masked' => $this->maskPhone($phone),
        ]));

        if ($phone === null) {
            return [
                'success' => false,
                'response' => 'Patient mobile number missing or invalid for WhatsApp',
            ];
        }

        if ($this->containsUnresolvedVariables($message) || $this->containsUnresolvedVariables((string) ($delivery['caption'] ?? ''))) {
            return [
                'success' => false,
                'response' => 'WhatsApp message still contains unresolved {{variables}}',
            ];
        }

        $mode = (string) ($delivery['mode'] ?? $payload['whatsapp_mode'] ?? 'text');

        try {
            $result = match ($mode) {
                'media' => $this->sendMedia($phone, $message, $delivery),
                'template' => $this->sendTemplate($phone, $delivery, $payload),
                default => $this->client->sendText($phone, $message),
            };
        } catch (\Throwable $e) {
            Log::warning('WhatsApp send failed', array_merge($meta, [
                'error' => $e->getMessage(),
            ]));

            return [
                'success' => false,
                'response' => 'WhatsApp provider error: '.$this->redact($e->getMessage()),
            ];
        }

        Log::info(
            ($result['success'] ?? false) ? 'WhatsApp provider accepted message' : 'WhatsApp provider rejected message',
            array_merge($meta, [
                'recipient_masked' => $this->maskPhone($phone),
                'mode' => $mode,
                'log_uid' => $result['log_uid'] ?? null,
                'wamid' => $result['wamid'] ?? null,
                'success' => $result['success'] ?? false,
                'provider_class' => self::class,
                'client_class' => $this->clientClass(),
            ])
        );

        return $result;
    }

    /**
     * @param  array<string, mixed>  $delivery
     * @return array{success: bool, response: string, log_uid?: string|null, wamid?: string|null}
     */
    protected function sendMedia(string $phone, string $message, array $delivery): array
    {
        $url = trim((string) ($delivery['media_url'] ?? ''));
        $type = trim((string) ($delivery['media_type'] ?? 'document'));

        if ($url === '') {
            return [
                'success' => false,
                'response' => 'WhatsApp media URL is missing',
            ];
        }

        if (! str_starts_with($url, 'https://')) {
            return [
                'success' => false,
                'response' => 'WhatsApp media URL must be HTTPS',
            ];
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if (in_array($host, ['localhost', '127.0.0.1', '::1', ''], true)) {
            return [
                'success' => false,
                'response' => 'WhatsApp media URL is not a public host',
            ];
        }

        if (str_starts_with($url, 'file:') || preg_match('#^[A-Za-z]:\\\\#', $url) === 1) {
            return [
                'success' => false,
                'response' => 'WhatsApp media URL must not be a filesystem path',
            ];
        }

        $caption = (string) ($delivery['caption'] ?? $message);

        return $this->client->sendMedia($phone, $type !== '' ? $type : 'document', $url, $caption);
    }

    /**
     * @param  array<string, mixed>  $delivery
     * @param  array<string, mixed>  $payload
     * @return array{success: bool, response: string, log_uid?: string|null, wamid?: string|null}
     */
    protected function sendTemplate(string $phone, array $delivery, array $payload): array
    {
        $name = trim((string) (
            $delivery['template_name']
            ?? $payload['whatsapp_template_name']
            ?? ''
        ));

        if ($name === '') {
            return [
                'success' => false,
                'response' => 'WhatsApp approved template name is missing',
            ];
        }

        if ($this->containsUnresolvedVariables($name)) {
            return [
                'success' => false,
                'response' => 'WhatsApp template name still contains unresolved {{variables}}',
            ];
        }

        $language = trim((string) (
            $delivery['template_language']
            ?? $payload['whatsapp_template_language']
            ?? 'en'
        ));
        $components = $delivery['components'] ?? $payload['whatsapp_components'] ?? [];
        if (! is_array($components)) {
            $components = [];
        }

        return $this->client->sendTemplate($phone, $name, $language !== '' ? $language : 'en', $components);
    }

    protected function maskPhone(?string $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) < 4) {
            return '****';
        }

        return str_repeat('*', max(0, strlen($digits) - 4)).substr($digits, -4);
    }

    protected function containsUnresolvedVariables(string $text): bool
    {
        return (bool) preg_match('/\{\{\s*[a-zA-Z0-9_]+\s*\}\}/', $text);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function safeMeta(array $payload): array
    {
        return array_filter([
            'workflow_id' => $payload['workflow_id'] ?? null,
            'workflow_execution_id' => $payload['workflow_execution_id'] ?? $payload['execution_id'] ?? null,
            'node_id' => $payload['node_id'] ?? null,
            'source' => $payload['source'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    protected function redact(string $message): string
    {
        $token = trim((string) config('services.whatsapp.token'));
        if ($token !== '') {
            $message = str_replace($token, '[redacted]', $message);
        }

        return preg_replace('/Bearer\s+\S+/i', 'Bearer [redacted]', $message) ?? $message;
    }
}
