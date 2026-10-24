<?php

namespace App\Services;

use App\Models\RewardTier;
use App\Models\RewardTierPackageDiscount;
use App\Models\RewardTierTransition;
use App\Models\UserRewardProgress;
use App\Modules\Automation\Events\RewardTierUpgraded;
use App\Modules\Automation\Support\AfterCommit;
use App\Modules\Automation\Support\PatientBookingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RewardTierService
{
    public function getAllTiers()
    {
        return RewardTier::with(['config', 'packageDiscounts.diagnosticPackage'])
            ->orderBy('sort_order')
            ->get();
    }

    public function createTier(array $data, array $config, array $packageDiscounts): RewardTier
    {
        return DB::transaction(function () use ($data, $config, $packageDiscounts) {
            $tier = RewardTier::create($data);
            $tier->config()->create($config);

            foreach ($packageDiscounts as $discount) {
                $tier->packageDiscounts()->create($discount);
            }

            return $tier->fresh(['config', 'packageDiscounts.diagnosticPackage']);
        });
    }

    public function updateTier(int $id, array $data, array $config, array $packageDiscounts): RewardTier
    {
        return DB::transaction(function () use ($id, $data, $config, $packageDiscounts) {
            $tier = RewardTier::findOrFail($id);
            $tier->update($data);

            $tier->config()->updateOrCreate(
                ['reward_tier_id' => $tier->id],
                $config
            );

            $tier->packageDiscounts()->delete();

            foreach ($packageDiscounts as $discount) {
                $tier->packageDiscounts()->create($discount);
            }

            return $tier->fresh(['config', 'packageDiscounts.diagnosticPackage']);
        });
    }

    public function deleteTier(int $id): void
    {
        RewardTier::findOrFail($id)->delete();
    }

    public function getUserProgress(string $hipUserId): UserRewardProgress
    {
        $progress = UserRewardProgress::where('hip_user_id', $hipUserId)->first();

        if ($progress) {
            return $progress;
        }

        $defaultTier = $this->resolveBronzeTier();

        if (! $defaultTier) {
            throw new \RuntimeException('No active reward tier configured.');
        }

        return UserRewardProgress::create([
            'hip_user_id' => $hipUserId,
            'reward_tier_id' => $defaultTier->id,
            'earned_coins_in_tier' => 0,
            'total_lifetime_coins' => 0,
        ]);
    }

    public function addEarnedCoins(string $hipUserId, int $coins): void
    {
        if ($coins <= 0) {
            return;
        }

        $payload = DB::transaction(function () use ($hipUserId, $coins) {
            $progress = UserRewardProgress::query()
                ->where('hip_user_id', $hipUserId)
                ->lockForUpdate()
                ->first();

            if (! $progress) {
                $progress = $this->getUserProgress($hipUserId);
                $progress = UserRewardProgress::query()
                    ->where('hip_user_id', $hipUserId)
                    ->lockForUpdate()
                    ->first() ?? $progress;
            }

            $progress->load('rewardTier');

            $previousTier = $progress->rewardTier;
            $previousTierId = (int) ($progress->reward_tier_id ?? 0);
            $previousTierName = $previousTier?->name;

            $progress->earned_coins_in_tier += $coins;
            $progress->total_lifetime_coins += $coins;

            while ($progress->rewardTier) {
                $currentMinCoins = (int) $progress->rewardTier->min_coins;

                $nextTier = RewardTier::active()
                    ->where('min_coins', '>', $currentMinCoins)
                    ->orderBy('sort_order')
                    ->first();

                if (! $nextTier || $progress->earned_coins_in_tier < (int) $nextTier->min_coins) {
                    break;
                }

                $progress->reward_tier_id = $nextTier->id;
                $progress->earned_coins_in_tier = 0;
                $progress->load('rewardTier');
            }

            $progress->save();

            $newTierId = (int) ($progress->reward_tier_id ?? 0);
            if ($newTierId <= 0 || $newTierId === $previousTierId) {
                return null;
            }

            $transition = $this->recordTierTransition($hipUserId, $previousTierId, $newTierId);

            return [
                'hip_user_id' => $hipUserId,
                // Push notifications read member_id, the same field payment workflows set
                // from the HIP user id. The occurrence id is not a recipient.
                'member_id' => $hipUserId,
                'previous_tier_id' => $previousTierId ?: null,
                'previous_tier_name' => $previousTierName,
                'new_tier_id' => $newTierId,
                'new_tier_name' => $progress->rewardTier?->name,
                'rewards' => [
                    'previous_tier' => $this->tierSnapshot($previousTier),
                    'current_tier' => $this->tierSnapshot($progress->rewardTier),
                ],
                'total_lifetime_coins' => (int) $progress->total_lifetime_coins,
                'reward_tier_transition_id' => $transition->id,
                'event_occurrence_id' => $transition->occurrence_id,
            ];
        });

        if ($payload === null) {
            return;
        }

        AfterCommit::run(function () use ($payload): void {
            $scope = PatientBookingScope::forHipUser((string) $payload['hip_user_id']);
            if ($scope === null) {
                Log::info('[reward-tier-upgraded] No booking hospital scope. Dispatching unscoped so only workflows with a null hospital_id can match.', [
                    'hip_user_id' => $payload['hip_user_id'],
                    'new_tier_id' => $payload['new_tier_id'],
                    'event_occurrence_id' => $payload['event_occurrence_id'],
                ]);
            } else {
                $payload['hospital_id'] = $scope['hospital_id'];
                $payload['organization_id'] = $scope['organization_id'];
                $payload['booking_source'] = $scope['source'];
                $payload['booking_id'] = $scope['booking_id'];
            }

            event(new RewardTierUpgraded($payload));
        });
    }

    /**
     * One row per actual upward transition. The occurrence id is written on
     * this row before the event is queued, so a listener retry cannot mint another.
     */
    private function recordTierTransition(string $hipUserId, int $previousTierId, int $newTierId): RewardTierTransition
    {
        $transition = RewardTierTransition::query()->create([
            'hip_user_id' => $hipUserId,
            'previous_tier_id' => $previousTierId ?: null,
            'new_tier_id' => $newTierId,
        ]);

        $transition->update([
            'occurrence_id' => RewardTierUpgraded::occurrenceId(
                $hipUserId,
                $previousTierId,
                $newTierId,
                $transition->id,
            ),
        ]);

        return $transition->fresh();
    }

    /**
     * Discount applied to the remaining eligible base price after membership waivers.
     * Service charges are never included in this amount.
     *
     * Priority for diagnostic packages:
     * 1) Package-specific free promotion
     * 2) Package-specific discount promotion
     * 3) Tier total_discount_percentage
     *
     * Doctor consultations use only total_discount_percentage.
     *
     * @return array{
     *     amount: float,
     *     source: string|null,
     *     benefit_type: string|null,
     *     package_promotion_type: string|null,
     *     total_discount_percentage: float|null
     * }
     */
    public function calculateBasePriceDiscount(
        string $hipUserId,
        float $remainingBasePrice,
        ?int $diagnosticPackageId = null
    ): array {
        $remaining = round(max(0, $remainingBasePrice), 2);
        $empty = [
            'amount' => 0.0,
            'source' => null,
            'benefit_type' => null,
            'package_promotion_type' => null,
            'total_discount_percentage' => null,
        ];

        if ($remaining <= 0) {
            return $empty;
        }

        $tier = $this->getTierForUser($hipUserId);
        if (! $tier || ! $tier->is_active) {
            return $empty;
        }

        $tier->loadMissing(['config', 'packageDiscounts']);

        if ($diagnosticPackageId) {
            /** @var RewardTierPackageDiscount|null $promo */
            $promo = $tier->packageDiscounts
                ->firstWhere('diagnostic_package_id', $diagnosticPackageId);

            if ($promo && $promo->promotion_type === 'free') {
                return [
                    'amount' => $remaining,
                    'source' => 'package_promotion',
                    'benefit_type' => 'free',
                    'package_promotion_type' => 'free',
                    'total_discount_percentage' => null,
                ];
            }

            if ($promo && $promo->promotion_type === 'discount') {
                $amount = $this->packageDiscountAmount($remaining, $promo);

                return [
                    'amount' => $amount,
                    'source' => 'package_promotion',
                    'benefit_type' => 'discount',
                    'package_promotion_type' => (string) ($promo->discount_type ?? 'percentage'),
                    'total_discount_percentage' => null,
                ];
            }
        }

        $percent = $tier->config?->total_discount_percentage;
        if ($percent === null || (float) $percent <= 0) {
            return $empty;
        }

        $amount = round(min($remaining, $remaining * ((float) $percent / 100)), 2);

        return [
            'amount' => $amount,
            'source' => 'tier_percentage',
            'benefit_type' => 'discount',
            'package_promotion_type' => null,
            'total_discount_percentage' => (float) $percent,
        ];
    }

    public function earnedCoinsPerBookingBonus(string $hipUserId): int
    {
        $tier = $this->getTierForUser($hipUserId);
        if (! $tier->is_active) {
            return 0;
        }

        $tier->loadMissing('config');

        return max(0, (int) ($tier->config?->earned_coins_per_booking ?? 0));
    }

    private function packageDiscountAmount(float $remaining, RewardTierPackageDiscount $promo): float
    {
        $value = (float) ($promo->discount_value ?? 0);
        if ($value <= 0) {
            return 0.0;
        }

        if (($promo->discount_type ?? 'percentage') === 'flat') {
            return round(min($remaining, $value), 2);
        }

        return round(min($remaining, $remaining * ($value / 100)), 2);
    }

    public function getTierForUser(string $hipUserId): RewardTier
    {
        $progress = $this->getUserProgress($hipUserId);

        return $progress->rewardTier ?? $this->resolveBronzeTier() ?? RewardTier::orderBy('sort_order')->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    public function getRewardProgressPayload(string $hipUserId): array
    {
        $progress = $this->getUserProgress($hipUserId);
        $progress->load(['rewardTier.config', 'rewardTier.packageDiscounts.diagnosticPackage']);

        $currentTier = $progress->rewardTier;

        $nextTier = $currentTier
            ? RewardTier::active()
                ->where('min_coins', '>', (int) $currentTier->min_coins)
                ->orderBy('sort_order')
                ->first()
            : null;

        $earnedInTier = (int) $progress->earned_coins_in_tier;
        $nextMinCoins = $nextTier ? (int) $nextTier->min_coins : 0;
        $coinsToNext = $nextTier ? max(0, $nextMinCoins - $earnedInTier) : 0;
        $progressPercentage = $nextTier && $nextMinCoins > 0
            ? round(($earnedInTier / $nextMinCoins) * 100, 1)
            : 0.0;

        $config = $currentTier?->config;

        return [
            'current_tier_name' => $currentTier ? $currentTier->name : null,
            'next_tier_name' => $nextTier ? $nextTier->name : null,
            'earned_coins_in_tier' => $earnedInTier,
            'coins_to_next_tier' => $coinsToNext,
            'progress_percentage' => $progressPercentage,
            'total_lifetime_coins' => (int) $progress->total_lifetime_coins,
            'total_discount_percentage' => $config?->total_discount_percentage !== null
                    ? (float) $config->total_discount_percentage
                    : null,
            // Stored for display only until checkup eligibility is defined business-side.
            'free_checkup_count' => (int) ($config?->free_checkup_count ?? 0),
            'earned_coins_per_booking' => (int) ($config?->earned_coins_per_booking ?? 0),
            'package_discounts' => $currentTier
                ? $currentTier->packageDiscounts->map(fn ($discount) => [
                    'diagnostic_package_id' => (int) $discount->diagnostic_package_id,
                    'package_name' => $discount->diagnosticPackage?->name,
                    'promotion_type' => $discount->promotion_type,
                    'discount_type' => $discount->discount_type,
                    'discount_value' => $discount->discount_value !== null
                        ? (float) $discount->discount_value
                        : null,
                ])->values()->all()
                : [],
        ];
    }

    public function enrollUserOnRegister(string $hipUserId): void
    {
        if (UserRewardProgress::where('hip_user_id', $hipUserId)->exists()) {
            return;
        }

        $bronzeTier = $this->resolveBronzeTier();

        if (! $bronzeTier) {
            return;
        }

        UserRewardProgress::create([
            'hip_user_id' => $hipUserId,
            'reward_tier_id' => $bronzeTier->id,
            'earned_coins_in_tier' => 0,
            'total_lifetime_coins' => 0,
        ]);
    }

    /**
     * Rank is the tier's sort_order. A missing tier stays null so templates
     * keep the placeholder and WhatsApp refuses to send it.
     *
     * @return array{id: int, name: string, rank: int}|null
     */
    private function tierSnapshot(?RewardTier $tier): ?array
    {
        if ($tier === null || ! filled($tier->name)) {
            return null;
        }

        return [
            'id' => (int) $tier->id,
            'name' => (string) $tier->name,
            'rank' => (int) $tier->sort_order,
        ];
    }

    private function resolveBronzeTier(): ?RewardTier
    {
        return RewardTier::active()
            ->where('sort_order', 1)
            ->first()
            ?? RewardTier::active()->orderBy('sort_order')->first();
    }
}
