<?php

namespace App\Services;

use App\Models\UserDevice;
use Kreait\Firebase\Contract\Messaging;
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
            return false;
        }

        $message = CloudMessage::new()
        ->toToken($token)
        ->withNotification(Notification::create($title, $body))
        ->withData($data);

        try {
            $this->messaging->send($message);
            Log::info('SendToDevice Debug', [
                'user_id' => $userId,
                'device_id' => $deviceId,
                'token_found' => $token ? true : false,
            ]);
            return true;
        } catch (\Throwable $e) {
            // Token is invalid → remove it
            UserDevice::where('fcm_token', $token)->delete();
            Log::error('SendToDevice Error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}
