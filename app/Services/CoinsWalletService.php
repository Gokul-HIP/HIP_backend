<?php

namespace App\Services;

use App\Models\Coins;
use App\Models\Persons;
use App\Models\RewardPointsTransition;
use App\Modules\Automation\Events\RewardPointsUpdated;
use App\Modules\Automation\Support\AfterCommit;
use App\Modules\Automation\Support\RewardPointsContext;
use App\Modules\Automation\Support\RewardPointsScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

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
    public function credit(Coins $wallet, int $amount, ?RewardPointsContext $context = null): void
    {
        if ($amount <= 0) {
            return;
        }

        $this->applyChange($wallet, $context ?? new RewardPointsContext('earned', 'wallet'), function (Coins $wallet, int $previous) use ($amount): int {
            $wallet->coins = $previous + $amount;
            $wallet->save();

            return (int) $wallet->coins;
        });
    }

    /**
     * Credit coins earned after a successful payment.
     * Sets expires_at when the wallet row is new / empty (first paid coins for this user).
     */
    public function creditAfterPayment(Coins $wallet, int $amount, ?RewardPointsContext $context = null): void
    {
        if ($amount <= 0) {
            return;
        }

        $this->applyChange($wallet, $context ?? new RewardPointsContext('earned', 'payment'), function (Coins $wallet, int $previous) use ($amount): int {
            $wallet->coins = $previous + $amount;

            if ($previous === 0 || $wallet->expires_at === null) {
                $wallet->expires_at = $this->calculateExpiryDate();
            }

            $wallet->save();

            return (int) $wallet->coins;
        });
    }

    /**
     * Create a new coins wallet row after payment with balance and expiry.
     *
     * @param  array{person_id: string, organization_id?: int|null, coins: int}  $attributes
     */
    public function createWalletAfterPayment(array $attributes, ?RewardPointsContext $context = null): Coins
    {
        $amount = (int) ($attributes['coins'] ?? 0);
        $wallet = Coins::create([
            'person_id' => $attributes['person_id'],
            'organization_id' => $attributes['organization_id'] ?? null,
            'coins' => 0,
            'expires_at' => $amount > 0 ? $this->calculateExpiryDate() : null,
        ]);

        if ($amount > 0) {
            $this->creditAfterPayment($wallet, $amount, $context);
        }

        return $wallet->fresh() ?? $wallet;
    }

    /**
     * Debit coins; clear expiry when balance reaches zero.
     */
    public function debit(Coins $wallet, int $amount, ?RewardPointsContext $context = null): void
    {
        if ($amount <= 0) {
            return;
        }

        $this->applyChange($wallet, $context ?? new RewardPointsContext('redeemed', 'redemption'), function (Coins $wallet, int $previous) use ($amount): int {
            $newBalance = max(0, $previous - $amount);
            $wallet->coins = $newBalance;
            $wallet->expires_at = $newBalance > 0 ? $wallet->expires_at : null;
            $wallet->save();

            return $newBalance;
        });
    }

    /**
     * Apply non-payment credit while syncing optional legacy HIP user balance column.
     */
    public function creditWithHipUserSync(Coins $wallet, int $amount, ?object $hipUser, bool $hasCoinsBalanceColumn, ?RewardPointsContext $context = null): int
    {
        $this->credit($wallet, $amount, $context);

        $finalBalance = (int) ($wallet->fresh()->coins ?? $wallet->coins ?? 0);

        if ($hipUser && $hasCoinsBalanceColumn && property_exists($hipUser, 'coins_balance')) {
            $hipUser->coins_balance = $finalBalance;
            $hipUser->save();
        }

        return $finalBalance;
    }

    /**
     * @param  callable(Coins, int): int  $apply
     */
    private function applyChange(Coins $wallet, RewardPointsContext $context, callable $apply): void
    {
        $payload = DB::transaction(function () use ($wallet, $context, $apply) {
            $previous = (int) ($wallet->coins ?? 0);
            $current = (int) $apply($wallet, $previous);

            if ($current === $previous) {
                return null;
            }

            return $this->transitionPayload($wallet, $previous, $current, $context);
        });

        if ($payload === null) {
            return;
        }

        AfterCommit::run(function () use ($payload): void {
            try {
                event(new RewardPointsUpdated($payload));
            } catch (Throwable $e) {
                report($e);
                Log::error('RewardPointsUpdated dispatch failed after the wallet change was committed', [
                    'event_occurrence_id' => $payload['event_occurrence_id'] ?? null,
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    private function transitionPayload(Coins $wallet, int $previous, int $current, RewardPointsContext $context): ?array
    {
        $person = $this->walletPerson($wallet);
        $hipUserId = $this->hipUserId($person);

        if (! is_string($hipUserId) || $hipUserId === '') {
            Log::info('Reward points balance changed without a HIP user. No workflow event was dispatched.', [
                'person_id' => $wallet->person_id,
                'previous_balance' => $previous,
                'current_balance' => $current,
            ]);

            return null;
        }

        if (! Schema::hasTable('reward_points_transitions')) {
            Log::warning('Reward points balance changed but reward_points_transitions is missing. No workflow event was dispatched.');

            return null;
        }

        $delta = $current - $previous;
        $hospitalId = $context->hospitalId;
        $organizationId = RewardPointsScope::organizationIdForHospital($hospitalId);
        $changeType = $context->changeType !== '' ? $context->changeType : ($delta > 0 ? 'earned' : 'redeemed');
        $coinsEarned = $this->persistedCoinsEarned($changeType, $context);

        $transition = RewardPointsTransition::query()->create([
            'hip_user_id' => $hipUserId,
            'previous_balance' => $previous,
            'current_balance' => $current,
            'points_changed' => $delta,
            'coins_earned' => $coinsEarned,
            'change_type' => $changeType,
            'reward_source' => $context->source,
            'hospital_id' => $hospitalId,
            'organization_id' => $organizationId,
            'invoice_id' => $context->invoiceId,
            'transaction_id' => $context->transactionId,
            'booking_source' => $context->bookingSource,
            'booking_id' => $context->bookingId !== null ? (string) $context->bookingId : null,
        ]);

        $occurrenceId = RewardPointsUpdated::occurrenceId($transition->id);
        $transition->update(['occurrence_id' => $occurrenceId]);

        return [
            'hip_user_id' => $hipUserId,
            'member_id' => $hipUserId,
            'previous_balance' => $previous,
            'current_balance' => $current,
            'points_changed' => $delta,
            'coins_earned' => $coinsEarned,
            'change_type' => $changeType,
            'reward_source' => $context->source,
            'update_type' => $this->updateTypeLabel($changeType),
            'rewards' => [
                'previous_balance' => $previous,
                'current_balance' => $current,
                'points_changed' => $delta,
                'coins_earned' => $coinsEarned,
                'change_type' => $changeType,
                'source' => $context->source,
                'update_type' => $this->updateTypeLabel($changeType),
            ],
            'patient' => [
                'id' => $person?->id,
                'first_name' => $person?->first_name,
                'last_name' => $person?->last_name,
                'mobile' => $person?->mobile,
                'email' => $person?->email,
            ],
            'patient_mobile' => $person?->mobile,
            'patient_email' => $person?->email,
            'invoice_id' => $context->invoiceId,
            'transaction_id' => $context->transactionId,
            'booking_source' => $context->bookingSource,
            'booking_id' => $context->bookingId,
            'hospital_id' => $hospitalId,
            'organization_id' => $organizationId,
            'reward_points_transition_id' => $transition->id,
            'event_occurrence_id' => $occurrenceId,
        ];
    }

    /**
     * Payment coins awarded for this earning, already decided by the caller
     * from the persisted invoice or earning record. Redemption, adjustment,
     * and earnings without that record stay null. The wallet delta is not used.
     */
    private function persistedCoinsEarned(string $changeType, RewardPointsContext $context): ?int
    {
        if ($changeType !== 'earned' || $context->coinsEarned === null || $context->coinsEarned < 0) {
            return null;
        }

        return $context->coinsEarned;
    }

    private function hipUserId(?Persons $person): ?string
    {
        $id = $person?->hip_user_id;
        if (is_string($id) && $id !== '') {
            return $id;
        }

        if (! $person?->parent_id || ! Schema::hasTable('persons')) {
            return null;
        }

        $parentId = Persons::query()->whereKey($person->parent_id)->value('hip_user_id');

        return is_string($parentId) && $parentId !== '' ? $parentId : null;
    }

    private function updateTypeLabel(string $changeType): string
    {
        return match ($changeType) {
            'redeemed' => 'Points Redeemed',
            'adjusted' => 'Points Adjusted',
            default => 'Points Added',
        };
    }

    private function walletPerson(Coins $wallet): ?Persons
    {
        if (! Schema::hasTable('persons') || ! filled($wallet->person_id)) {
            return null;
        }

        return Persons::query()->find($wallet->person_id);
    }
}
