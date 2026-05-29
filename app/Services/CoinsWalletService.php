<?php

namespace App\Services;

use App\Models\Coins;
use Carbon\Carbon;

class CoinsWalletService
{
    /**
     * Coin validity period in months (DB setting with .env fallback).
     */
    public function expiryMonths(): int
    {
        $months = (int) app_setting(
            'coins_expiry_months',
            (int) config('settings.payment.coins_expiry_months', 2)
        );

        return max(1, min(120, $months));
    }

    public function calculateExpiryDate(?Carbon $from = null): Carbon
    {
        return ($from ?? now())->copy()->addMonths($this->expiryMonths());
    }

    /**
     * Credit coins without touching expiry (non-payment rewards).
     */
    public function credit(Coins $wallet, int $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $wallet->coins = (int) ($wallet->coins ?? 0) + $amount;
        $wallet->save();
    }

    /**
     * Credit coins earned after a successful payment.
     * Sets expires_at when the wallet row is new / empty (first paid coins for this user).
     */
    public function creditAfterPayment(Coins $wallet, int $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $currentBalance = (int) ($wallet->coins ?? 0);
        $wallet->coins = $currentBalance + $amount;

        if ($currentBalance === 0 || $wallet->expires_at === null) {
            $wallet->expires_at = $this->calculateExpiryDate();
        }

        $wallet->save();
    }

    /**
     * Create a new coins wallet row after payment with balance and expiry.
     *
     * @param  array{person_id: string, organization_id?: int|null, coins: int}  $attributes
     */
    public function createWalletAfterPayment(array $attributes): Coins
    {
        return Coins::create([
            'person_id' => $attributes['person_id'],
            'organization_id' => $attributes['organization_id'] ?? null,
            'coins' => (int) ($attributes['coins'] ?? 0),
            'expires_at' => $this->calculateExpiryDate(),
        ]);
    }

    /**
     * Debit coins; clear expiry when balance reaches zero.
     */
    public function debit(Coins $wallet, int $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $newBalance = max(0, (int) ($wallet->coins ?? 0) - $amount);

        $wallet->coins = $newBalance;
        $wallet->expires_at = $newBalance > 0 ? $wallet->expires_at : null;
        $wallet->save();
    }

    /**
     * Apply non-payment credit while syncing optional legacy HIP user balance column.
     */
    public function creditWithHipUserSync(Coins $wallet, int $amount, ?object $hipUser, bool $hasCoinsBalanceColumn): int
    {
        $this->credit($wallet, $amount);

        $finalBalance = (int) ($wallet->coins ?? 0);

        if ($hipUser && $hasCoinsBalanceColumn && property_exists($hipUser, 'coins_balance')) {
            $hipUser->coins_balance = $finalBalance;
            $hipUser->save();
        }

        return $finalBalance;
    }
}
