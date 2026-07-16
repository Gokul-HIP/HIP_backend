<?php

namespace App\Modules\MedicineReminder\Notifications;

use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{success: bool, response: string}
     */
    public function send(?string $userId, string $title, string $body, array $data = []): array
    {
        if (! $userId) {
            return [
                'success' => false,
                'response' => 'No member user id for push notification',
            ];
        }

        try {
            $sent = $this->notificationService->notifyUser(
                $userId,
                $title,
                $body,
                array_merge(['type' => 'medicine_reminder'], $data)
            );

            return [
                'success' => $sent,
                'response' => $sent ? 'Push notification dispatched' : 'No FCM devices registered',
            ];
        } catch (\Throwable $e) {
            Log::error('Medicine reminder push failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'response' => $e->getMessage(),
            ];
        }
    }
}
