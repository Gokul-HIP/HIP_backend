<?php

namespace App\Support;

use App\Models\HIPUser;
use Illuminate\Contracts\Auth\Authenticatable;

class AdminAccess
{
    public static function canManageCron(?Authenticatable $user): bool
    {
        return self::isMasterAdmin($user)
            || self::hasPermission($user, 'manage cron jobs');
    }

    public static function canManageQueues(?Authenticatable $user): bool
    {
        return self::isMasterAdmin($user)
            || self::hasPermission($user, 'manage queues');
    }

    public static function isMasterAdmin(?Authenticatable $user): bool
    {
        return $user instanceof HIPUser && $user->isSuperAdmin();
    }

    protected static function hasPermission(?Authenticatable $user, string $permission): bool
    {
        if (! $user instanceof HIPUser) {
            return false;
        }

        try {
            return $user->can($permission);
        } catch (\Throwable) {
            return false;
        }
    }
}
