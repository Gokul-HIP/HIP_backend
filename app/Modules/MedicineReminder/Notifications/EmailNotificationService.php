<?php

namespace App\Modules\MedicineReminder\Notifications;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailNotificationService
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  list<array{filename?: string, content?: string, mime?: string}>  $attachments
     * @return array{success: bool, response: string}
     */
    public function send(?string $email, string $subject, string $message, array $payload = [], array $attachments = []): array
    {
        if (! filled($email)) {
            return [
                'success' => false,
                'response' => 'Patient email missing for email channel',
            ];
        }

        try {
            Mail::raw($message, function ($mail) use ($email, $subject, $attachments) {
                $mail->to($email)->subject($subject);

                foreach ($attachments as $attachment) {
                    $filename = (string) ($attachment['filename'] ?? 'invoice.pdf');
                    $content = (string) ($attachment['content'] ?? '');
                    $mime = (string) ($attachment['mime'] ?? 'application/pdf');

                    if ($content === '') {
                        throw new \RuntimeException('Email attachment "'.$filename.'" is empty.');
                    }

                    $mail->attachData($content, $filename, ['mime' => $mime]);
                }
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
