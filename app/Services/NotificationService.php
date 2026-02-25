<?php

namespace App\Services;

use App\Models\UserDevice;
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

    public function sendToDevice($userId, $deviceId, $title, $body, $data = [])
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

            $result = $this->messaging->send($message);

            Log::info('SendToDevice Success', [
                'user_id' => $userId,
                'device_id' => $deviceId,
                'message_id' => $result,
            ]);

            return true;

        } catch (\Kreait\Firebase\Exception\Messaging\InvalidArgument $e) {
            Log::error('SendToDevice: Invalid Argument', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'device_id' => $deviceId,
            ]);
            return false;
        } catch (\Kreait\Firebase\Exception\MessagingException $e) {
            UserDevice::where('fcm_token', $token)->delete();
            Log::error('SendToDevice: Messaging Error', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'device_id' => $deviceId,
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
