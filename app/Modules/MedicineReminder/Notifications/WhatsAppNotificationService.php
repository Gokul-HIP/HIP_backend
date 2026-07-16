<?php

namespace App\Modules\MedicineReminder\Notifications;

use Illuminate\Support\Facades\Log;

class WhatsAppNotificationService
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
                'response' => 'Patient mobile number missing for WhatsApp',
            ];
        }

        // Provider integration point (Twilio / Gupshup / Meta Cloud API).
        Log::info('Medicine reminder WhatsApp queued (stub)', [
            'mobile' => $mobile,
            'message' => $message,
            'payload' => $payload,
        ]);

        return [
            'success' => true,
            'response' => 'WhatsApp notification logged (provider stub)',
        ];
    }
}
