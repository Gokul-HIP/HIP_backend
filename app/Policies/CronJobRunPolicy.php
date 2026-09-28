<?php

namespace App\Policies;

use App\Models\CronJobRun;
use App\Models\HIPUser;
use App\Support\AdminAccess;
use Illuminate\Auth\Access\HandlesAuthorization;

class CronJobRunPolicy
{
    use HandlesAuthorization;

    public function viewAny(HIPUser $user): bool
    {
        return AdminAccess::canManageCron($user);
    }

    public function view(HIPUser $user, CronJobRun $cronJobRun): bool
    {
        return AdminAccess::canManageCron($user);
    }

    public function create(HIPUser $user): bool
    {
        return false;
    }

    public function update(HIPUser $user, CronJobRun $cronJobRun): bool
    {
        return false;
    }

    public function delete(HIPUser $user, CronJobRun $cronJobRun): bool
    {
        return false;
    }
}
