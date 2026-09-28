<?php

namespace App\Policies;

use App\Models\CronJob;
use App\Models\HIPUser;
use App\Support\AdminAccess;
use Illuminate\Auth\Access\HandlesAuthorization;

class CronJobPolicy
{
    use HandlesAuthorization;

    public function viewAny(HIPUser $user): bool
    {
        return AdminAccess::canManageCron($user);
    }

    public function view(HIPUser $user, CronJob $cronJob): bool
    {
        return AdminAccess::canManageCron($user);
    }

    public function create(HIPUser $user): bool
    {
        return AdminAccess::canManageCron($user);
    }

    public function update(HIPUser $user, CronJob $cronJob): bool
    {
        return AdminAccess::canManageCron($user);
    }

    public function delete(HIPUser $user, CronJob $cronJob): bool
    {
        return AdminAccess::canManageCron($user);
    }

    public function deleteAny(HIPUser $user): bool
    {
        return false;
    }

    public function runNow(HIPUser $user, CronJob $cronJob): bool
    {
        return AdminAccess::canManageCron($user);
    }
}
