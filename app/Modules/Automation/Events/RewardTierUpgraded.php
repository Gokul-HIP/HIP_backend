<?php

namespace App\Modules\Automation\Events;

use App\Modules\Automation\Contracts\HospitalAutomationEvent;
use Illuminate\Foundation\Events\Dispatchable;

class RewardTierUpgraded implements HospitalAutomationEvent
{
    use Dispatchable;

    /** @param  array<string, mixed>  $context */
    public function __construct(public array $context = []) {}

    public static function occurrenceId(string $hipUserId, mixed $previousTierId, mixed $newTierId): string
    {
        return 'reward-tier-upgraded:'.$hipUserId
            .':from:'.($previousTierId ?: 0)
            .':to:'.($newTierId ?: 0);
    }

    public function triggerType(): string
    {
        return 'rewardTierUpgraded';
    }

    public function payload(): array
    {
        return $this->context;
    }
}
