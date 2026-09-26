<?php

namespace App\Services;

use App\Models\UserDevice;
use App\Models\Notification as UserNotification;
use App\Support\NotificationReadStatusHelper;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected $messaging;

    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    /**
     * Persist a notification record without sending a push.
     *
     * This helper is public so other services can create a single row when
     * they need fine‑grained control over push delivery (e.g. invoice mails).
     *
     * @param string $userId
     * @param string $title
     * @param string $body
     * @param array $data
     * @return \App\Models\Notification
     */
    public function storeNotification(string $userId, string $title, string $body, array $data = [])
    {
        $notification = UserNotification::create([
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'data' => $data
        ]);

        NotificationReadStatusHelper::broadcastForUserId((string) $userId);

        return $notification;
    }

    public function notifyUser(string $userId, string $title, string $body, array $data = []): bool
    {
        $this->storeNotification($userId, $title, $body, $data);

        $devices = UserDevice::query()
            ->forPushDelivery()
            ->where('user_id', $userId)
            ->get(['device_id', 'fcm_token']);

        $sentAny = false;
        $targets = $devices->unique('fcm_token')->values();

        if ($targets->isEmpty()) {
            Log::warning('NotifyUser: No registered devices for push', [
                'user_id' => $userId,
                'title' => $title,
            ]);
        }

        foreach ($targets as $target) {
            if ($this->sendToDevice($userId, $target->device_id, $title, $body, $data, false)) {
                $sentAny = true;
            }
        }

        return $sentAny;
    }

    public function saveToken($userId, $token, $deviceType, $deviceId)
    {
        UserDevice::updateOrCreate(
            [
                'user_id' => $userId,
                'device_id' => $deviceId,
            ],
            [
                'device_type' => $deviceType,
                'fcm_token' => $token,
            ]
        );
    }

    /**
     * Send a push to a specific device.
     *
     * @param int $userId
     * @param string $deviceId
     * @param string $title
     * @param string $body
     * @param array $data
     * @param bool $store  whether to create a notification row (default true)
     * @return bool
     */
    public function sendToDevice($userId, $deviceId, $title, $body, $data = [], bool $store = true)
    {
        $token = UserDevice::where('user_id', $userId)
            ->where('device_id', $deviceId)
            ->value('fcm_token');

        if (!$token) {
            Log::warning('SendToDevice: No FCM token found', [
                'user_id' => $userId,
                'device_id' => $deviceId,
            ]);
            return false;
        }

        $context = [
            'device_id' => $deviceId,
            'log_user_id' => $userId,
        ];
        if ($store) {
            $context['user_id'] = $userId;
        }

        return $this->sendToToken($token, $title, $body, $data, $context);
    }

    public function sendToToken(string $token, string $title, string $body, array $data = [], array $context = [])
    {
        if (trim($token) === '') {
            Log::warning('SendToToken: Empty token received', $context);
            return false;
        }

        // Ensure notification has valid title/body (required for tray + getInitialMessage)
        $title = trim((string) $title) !== '' ? (string) $title : 'Notification';
        $body = trim((string) $body) !== '' ? (string) $body : '';

        // FCM data payload must contain only string values; include title/body for getInitialMessage()
        $data = is_array($data) ? $data : [];
        $dataStrings = [];
        foreach ($data as $key => $value) {
            $dataStrings[(string) $key] = is_scalar($value) ? (string) $value : json_encode($value);
        }
        $dataStrings['title'] = $title;
        $dataStrings['body'] = $body;

        try {
            // Store notification in database if user_id is provided in context
            if (isset($context['user_id'])) {
                $this->storeNotification(
                    $context['user_id'],
                    $title,
                    $body,
                    $data
                );
            }

            // Send BOTH notification (tray + open from terminated) AND data (getInitialMessage().data)
            $message = CloudMessage::new()
                ->toToken($token)
                ->withNotification(Notification::create($title, $body))
                ->withData($dataStrings)
                ->withAndroidConfig(
                    AndroidConfig::new()
                        ->withHighMessagePriority()
                        ->withHighNotificationPriority()
                        ->withDefaultSound()
                );

                // Log::info('FCM Message Structure', [
                //     'has_notification' => isset($message->jsonSerialize()['notification']),
                //     'data_keys' => array_keys($dataStrings),
                // ]);

            $result = $this->messaging->send($message);

            Log::info('SendToDevice Success', [
                'user_id' => $context['user_id'] ?? $context['log_user_id'] ?? null,
                'device_id' => $context['device_id'] ?? null,
                'message_id' => $result,
                'notification_type' => $data['notification_type'] ?? $data['type'] ?? null,
                'appointment_id' => $data['appointment_id'] ?? null,
                'workflow_id' => $data['workflow_id'] ?? null,
                'workflow_execution_id' => $data['workflow_execution_id'] ?? $data['execution_id'] ?? null,
                'source' => $data['source'] ?? 'direct',
            ]);

            return true;

        } catch (\Kreait\Firebase\Exception\Messaging\InvalidArgument $e) {
            Log::error('SendToDevice: Invalid Argument', [
                'error' => $e->getMessage(),
                'user_id' => $context['user_id'] ?? $context['log_user_id'] ?? null,
                'device_id' => $context['device_id'] ?? null,
            ]);
            return false;
        } catch (\Kreait\Firebase\Exception\MessagingException $e) {
            UserDevice::where('fcm_token', $token)->delete();
            Log::error('SendToDevice: Messaging Error', [
                'error' => $e->getMessage(),
                'user_id' => $context['user_id'] ?? $context['log_user_id'] ?? null,
                'device_id' => $context['device_id'] ?? null,
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('SendToDevice: Unexpected Error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}
