<?php

namespace App\Support;

use App\Events\NotificationReadStatus;
use App\Models\HIPUser;
use App\Models\Notification;

class NotificationReadStatusHelper
{
    public static function allRead(string $userId): bool
    {
        return ! Notification::query()
            ->where('user_id', $userId)
            ->where('is_read', false)
            ->exists();
    }

    public static function broadcastForUserId(string $userId): void
    {
        $user = HIPUser::query()->find($userId);

        if (! $user) {
            return;
        }

        event(new NotificationReadStatus($user, self::allRead($userId)));
    }
}
