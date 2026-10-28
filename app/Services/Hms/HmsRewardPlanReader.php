<?php

namespace App\Services\Hms;

use App\Models\Coins;
use App\Models\HIPUser;
use App\Models\Persons;
use App\Models\RewardTier;
use App\Services\RewardTierService;
use Illuminate\Support\Facades\Schema;

/** Read-only reward view. Must not create user_reward_progress rows. */
class HmsRewardPlanReader
{
    public function __construct(private readonly RewardTierService $rewards) {}

    /**
     * @return array<string, mixed>
     */
    public function forPatient(HIPUser $account, ?Persons $person): array
    {
        $hipUserId = (string) $account->getKey();
        $payload = $this->rewards->getReadOnlyRewardProgressPayload($hipUserId);
        $summary = $this->rewards->getReadOnlyDiscountUsageSummary($hipUserId);
        $tier = $this->rewards->getReadOnlyTierForUser($hipUserId);

        if ($tier) {
            $tier->loadMissing(['config', 'packageDiscounts.diagnosticPackage']);
        }

        if ($summary === null && $tier) {
            $limit = max(0, (int) ($tier->config?->free_checkup_count ?? 0));
            $summary = [
                'limit' => $limit,
                'used' => 0,
                'remaining' => $limit === 0 ? null : $limit,
            ];
        }

        $percentage = $tier?->config?->total_discount_percentage;
        $name = $tier?->name !== null ? trim((string) $tier->name) : null;

        return [
            'tier_name' => $name !== '' ? $name : null,
            'percentage' => $percentage !== null ? (float) $percentage : null,
            'status' => $tier !== null && (bool) $tier->is_active,
            'discounted_bookings' => $this->discountedBookings($summary),
            'package_promotions' => $this->promotions($tier),
            'progress' => [
                'next_tier_name' => $this->trimmed($payload['next_tier_name'] ?? null),
                'earned_coins_in_tier' => (int) ($payload['earned_coins_in_tier'] ?? 0),
                'coins_to_next_tier' => (int) ($payload['coins_to_next_tier'] ?? 0),
                'progress_percentage' => (float) ($payload['progress_percentage'] ?? 0),
            ],
            'lifetime_coins' => (int) ($payload['total_lifetime_coins'] ?? 0),
            'wallet_coin_balance' => $this->walletBalance($person),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $summary
     * @return array{limit: int|null, used: int|null, remaining: int|null}
     */
    private function discountedBookings(?array $summary): array
    {
        if ($summary === null || (int) ($summary['limit'] ?? 0) === 0) {
            return ['limit' => null, 'used' => null, 'remaining' => null];
        }

        return [
            'limit' => (int) $summary['limit'],
            'used' => (int) $summary['used'],
            'remaining' => $summary['remaining'] === null ? null : (int) $summary['remaining'],
        ];
    }

    /**
     * @return list<array{package_id: int, package_code: string|null, type: string, value: float|null}>
     */
    private function promotions(?RewardTier $tier): array
    {
        if (! $tier || ! $tier->relationLoaded('packageDiscounts')) {
            return [];
        }

        return $tier->packageDiscounts->map(function ($promo): array {
            $free = $promo->promotion_type === 'free';

            return [
                'package_id' => (int) $promo->diagnostic_package_id,
                'package_code' => $promo->diagnosticPackage?->code,
                'type' => $free ? 'free' : (string) ($promo->discount_type ?? 'percentage'),
                'value' => $free || $promo->discount_value === null ? null : (float) $promo->discount_value,
            ];
        })->values()->all();
    }

    private function walletBalance(?Persons $person): int
    {
        if (! $person || ! Schema::hasTable('coins')) {
            return 0;
        }

        $walletPersonId = $person->parent_id ?: $person->getKey();

        return (int) (Coins::query()->where('person_id', $walletPersonId)->value('coins') ?? 0);
    }

    private function trimmed(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
