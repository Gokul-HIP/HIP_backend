<?php

namespace App\Console\Commands;

use App\Services\RewardTierService;
use Illuminate\Console\Command;

class ReleaseStaleRewardTierDiscountReservations extends Command
{
    protected $signature = 'reward-tiers:release-stale-discount-reservations';

    protected $description = 'Release expired pending reward-tier discount reservations.';

    public function handle(RewardTierService $rewardTierService): int
    {
        $count = $rewardTierService->releaseStaleDiscountReservations();
        $this->info("Released {$count} stale reward-tier discount reservation(s).");

        return self::SUCCESS;
    }
}
