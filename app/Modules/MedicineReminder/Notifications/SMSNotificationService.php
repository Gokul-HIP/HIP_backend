<?php

namespace App\Modules\MedicineReminder\Notifications;

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

        // Provider integration point (Twilio / MSG91 / etc.).
        Log::info('Medicine reminder SMS queued (stub)', [
            'mobile' => $mobile,
            'message' => $message,
            'payload' => $payload,
        ]);

        return [
            'success' => true,
            'response' => 'SMS notification logged (provider stub)',
        ];
    }
}
