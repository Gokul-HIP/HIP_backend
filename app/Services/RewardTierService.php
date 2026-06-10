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
