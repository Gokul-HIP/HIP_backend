<?php

namespace App\Modules\MedicineReminder\Notifications;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SMSNotificationService
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array{success: bool, response: string}
     */
    public function send(?string $mobile, string $message, array $payload = []): array
    {
        if (! filled($mobile)) {
            return [
                'success' => false,
                'response' => 'Patient mobile number missing for SMS',
            ];
        }

        $baseUrl = config('services.sms.base_url');
        $token = config('services.sms.token');

        if (! filled($baseUrl) || ! filled($token)) {
            // Provider integration point (Twilio / MSG91 / etc.).
            // Medicine reminders keep their existing log line. Workflow SMS uses the same stub.
            $logMessage = isset($payload['schedule_id'])
                ? 'Medicine reminder SMS queued (stub)'
                : 'SMS notification logged (provider not configured)';

            Log::info($logMessage, [
                'mobile' => $mobile,
                'message' => $message,
                'payload' => $payload,
            ]);

            return [
                'success' => true,
                'response' => 'SMS notification logged (provider stub)',
            ];
        }

        try {
            $response = Http::timeout((int) config('services.sms.timeout', 20))
                ->withToken((string) $token)
                ->acceptJson()
                ->post(rtrim((string) $baseUrl, '/').'/messages', [
                    'to' => $mobile,
                    'message' => $message,
                    'sender' => config('services.sms.sender'),
                ]);
        } catch (\Throwable $e) {
            Log::warning('SMS provider request failed', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'response' => 'SMS provider request failed',
            ];
        }

        if ($response->successful()) {
            return [
                'success' => true,
                'response' => 'SMS accepted',
            ];
        }

        Log::warning('SMS provider rejected the message', [
            'status' => $response->status(),
        ]);

        return [
            'success' => false,
            'response' => 'SMS provider rejected the message',
        ];
    }
}
