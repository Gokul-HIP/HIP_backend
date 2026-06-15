<?php

namespace App\Services;

use App\Models\RewardTier;
use App\Models\UserRewardProgress;
use Illuminate\Support\Facades\DB;

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

        $progress = $this->getUserProgress($hipUserId);
        $progress->load('rewardTier');

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
            // 'current_tier' => $currentTier ? [
            //     'name' => $currentTier->name,
            //     'min_coins' => (int) $currentTier->min_coins,
            // ] : null,
            // 'next_tier' => $nextTier ? [
            //     'name' => $nextTier->name,
            //     'min_coins' => $nextMinCoins,
            // ] : null,
            'current_tier_name' => $currentTier ? $currentTier->name : null,
            'next_tier_name' => $nextTier ? $nextTier->name : null,
            'earned_coins_in_tier' => $earnedInTier,
            'coins_to_next_tier' => $coinsToNext,
            'progress_percentage' => $progressPercentage,
            // 'total_lifetime_coins' => (int) $progress->total_lifetime_coins,
            'total_discount_percentage' => $config?->total_discount_percentage !== null
                    ? (float) $config->total_discount_percentage
                    : null,
            // 'config' => [
            //     'total_discount_percentage' => $config?->total_discount_percentage !== null
            //         ? (float) $config->total_discount_percentage
            //         : null,
            //     'free_checkup_count' => (int) ($config?->free_checkup_count ?? 0),
            //     'earned_coins_per_booking' => (int) ($config?->earned_coins_per_booking ?? 0),
            // ],
            // 'package_discounts' => $currentTier
            //     ? $currentTier->packageDiscounts->map(fn ($discount) => [
            //         'package_name' => $discount->diagnosticPackage?->name,
            //         'promotion_type' => $discount->promotion_type,
            //         'discount_type' => $discount->discount_type,
            //         'discount_value' => $discount->discount_value !== null
            //             ? (float) $discount->discount_value
            //             : null,
            //     ])->values()->all()
            //     : [],
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

    private function resolveBronzeTier(): ?RewardTier
    {
        return RewardTier::active()
            ->where('sort_order', 1)
            ->first()
            ?? RewardTier::active()->orderBy('sort_order')->first();
    }
}
