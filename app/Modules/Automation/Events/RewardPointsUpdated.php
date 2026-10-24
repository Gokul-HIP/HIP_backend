<?php

namespace App\Modules\Automation\Events;

use App\Modules\Automation\Contracts\HospitalAutomationEvent;
use Illuminate\Foundation\Events\Dispatchable;

class RewardPointsUpdated implements HospitalAutomationEvent
{
    use Dispatchable;

    /** @param  array<string, mixed>  $context */
    public function __construct(public array $context = []) {}

    public static function occurrenceId(int|string $transitionId): string
    {
        return 'reward-points-updated:transition:'.$transitionId;
    }

    public function triggerType(): string
    {
        return 'rewardPointsUpdated';
    }

    public function payload(): array
    {
        $payload = $this->context;

        if (! filled($payload['member_id'] ?? null) && filled($payload['hip_user_id'] ?? null)) {
            $payload['member_id'] = $payload['hip_user_id'];
        }

        return $payload;
    }
}
