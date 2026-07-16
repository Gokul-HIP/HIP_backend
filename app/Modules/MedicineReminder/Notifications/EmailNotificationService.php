<?php

namespace App\Modules\MedicineReminder\Notifications;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailNotificationService
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array{success: bool, response: string}
     */
    public function send(?string $email, string $subject, string $message, array $payload = []): array
    {
        if (! filled($email)) {
            return [
                'success' => false,
                'response' => 'Patient email missing for email channel',
            ];
        }

        try {
            Mail::raw($message, function ($mail) use ($email, $subject) {
                $mail->to($email)->subject($subject);
            });

            return [
                'success' => true,
                'response' => 'Email sent successfully',
            ];
        } catch (\Throwable $e) {
            Log::error('Medicine reminder email failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'response' => $e->getMessage(),
            ];
        }
    }
}
