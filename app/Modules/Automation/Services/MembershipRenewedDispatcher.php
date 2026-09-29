<?php

namespace App\Modules\Automation\Services;

use App\Models\UserFamilySubscription;
use App\Modules\Automation\Events\MembershipRenewed;
use App\Modules\Automation\Support\AfterCommit;
use Illuminate\Support\Facades\Schema;

class MembershipRenewedDispatcher
{
    public function dispatch(UserFamilySubscription $subscription): void
    {
        $user = null;
        try {
            if (Schema::hasTable('healthinpocket_users')) {
                $subscription->loadMissing('member');
                $user = $subscription->member;
            }
        } catch (\Throwable) {
            $user = null;
        }

        $occurrenceId = 'membership-renewed:subscription:'.$subscription->id;

        AfterCommit::run(function () use ($subscription, $user, $occurrenceId): void {
            MembershipRenewed::dispatch([
                'subscription_id' => $subscription->id,
                'member_id' => $subscription->hip_user_id,
                'hospital_id' => $user?->hospital_id,
                'organization_id' => $user?->organization_id,
                'event_occurrence_id' => $occurrenceId,
                'meta' => [
                    'booking_type' => 'family_package',
                    'booking_id' => (string) $subscription->id,
                ],
            ]);
        });
    }
}
