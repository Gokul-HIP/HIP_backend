<?php

namespace App\Modules\Automation\Events;

use App\Modules\Automation\Contracts\HospitalAutomationEvent;
use Illuminate\Foundation\Events\Dispatchable;

class RewardTierUpgraded implements HospitalAutomationEvent
{
    use Dispatchable;

    /** @param  array<string, mixed>  $context */
    public function __construct(public array $context = []) {}

    /**
     * Identity of one upward transition. The transition id is the persisted
     * reward_tier_transitions row created when the tier change is detected.
     * Queue redelivery must pass that same id; it is not minted here again.
     * Calls without a transition id keep the historical pair-only form so an
     * already stored production occurrence can still be retried.
     */
    public static function occurrenceId(
        string $hipUserId,
        mixed $previousTierId,
        mixed $newTierId,
        int|string|null $transitionId = null,
    ): string {
        $occurrenceId = 'reward-tier-upgraded:'.$hipUserId
            .':from:'.($previousTierId ?: 0)
            .':to:'.($newTierId ?: 0);

        if ($transitionId !== null && $transitionId !== '') {
            $occurrenceId .= ':transition:'.$transitionId;
        }

        return $occurrenceId;
    }

    public function triggerType(): string
    {
        return 'rewardTierUpgraded';
    }

    public function payload(): array
    {
        $payload = $this->context;

        if (! filled($payload['member_id'] ?? null)) {
            $hipUserId = $payload['hip_user_id'] ?? null;
            if (is_string($hipUserId) && $hipUserId !== '') {
                $payload['member_id'] = $hipUserId;
            }
        }

        return $payload;
    }
}
