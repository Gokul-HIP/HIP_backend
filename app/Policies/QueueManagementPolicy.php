<?php

namespace App\Policies;

use App\Models\HIPUser;
use App\Support\AdminAccess;
use Illuminate\Auth\Access\HandlesAuthorization;

class QueueManagementPolicy
{
    use HandlesAuthorization;

    public function viewAny(HIPUser $user): bool
    {
        return AdminAccess::canManageQueues($user);
    }

    public function update(HIPUser $user): bool
    {
        return AdminAccess::canManageQueues($user);
    }

    public function retry(HIPUser $user): bool
    {
        return AdminAccess::canManageQueues($user);
    }

    public function delete(HIPUser $user): bool
    {
        return AdminAccess::canManageQueues($user);
    }
}
