<?php

namespace App\Services\Hms;

use App\Enums\HmsPaymentType;
use App\Enums\MembershipPaymentType;
use App\Exceptions\HmsPaymentSyncException;
use App\Models\Coins;
use App\Models\FamilyPackage;
use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\HmisPatientSaving;
use App\Models\HmisTransaction;
use App\Models\HmsIntegration;
use App\Models\Persons;
use App\Models\UserFamilySubscription;
use App\Modules\Automation\Support\RewardPointsContext;
use App\Services\Api\PaymentApiService;
use App\Services\CoinsWalletService;
use App\Services\FamilyPackageService;
use App\Services\MembershipEntitlementService;
use App\Services\RewardTierService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HmsPaymentReceivedService
{
    public function __construct(
        private readonly MembershipEntitlementService $membership,
        private readonly PaymentApiService $payments,
        private readonly CoinsWalletService $wallets,
        private readonly FamilyPackageService $familyPackages,
        private readonly RewardTierService $rewardTiers,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function receive(Request $request): array
    {
        $raw = $request->json()->all();
        if ($raw === []) {
            $raw = $request->all();
        }

        $input = $raw;
        unset($input['is_synced'], $input['is_synched']);

        $parsed = $this->parse($input, $request);
        $parsed['raw_payload'] = $raw;

        $outcome = DB::transaction(function () use ($parsed) {
            $existing = HmisTransaction::query()
                ->where('transaction_id', $parsed['transaction_id'])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $this->existingTransaction($existing, $parsed);
            }

            if ($parsed['is_refund']) {
                return $this->applyRefund($parsed);
            }

            return $this->applyPayment($parsed);
        });

        return $this->present($request, $outcome);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function parse(array $input, Request $request): array
    {
        $transactionId = trim((string) ($input['transaction_id'] ?? ''));
        $mrnId = trim((string) ($input['mrn_id'] ?? ''));
        $paymentTypeRaw = trim((string) ($input['payment_type'] ?? ''));

        if ($transactionId === '' || $mrnId === '' || $paymentTypeRaw === ''
            || ! array_key_exists('original_amount', $input) || ! array_key_exists('paid_amount', $input)) {
            throw new HmsPaymentSyncException('validation_error', 'transaction_id, mrn_id, payment_type, original_amount, and paid_amount are required.');
        }

        $paymentType = HmsPaymentType::tryFrom($paymentTypeRaw);
        if (! $paymentType) {
            throw new HmsPaymentSyncException('validation_error', 'Unknown payment_type.');
        }

        $this->assertHospitalCode($input['hospital_code'] ?? null, $request);

        $original = $this->money($input['original_amount'], 'original_amount');
        $paid = $this->money($input['paid_amount'], 'paid_amount');
        $membershipApplied = $this->boolFlag($input, 'is_membership_applied');
        $coinsUsedFlag = $this->boolFlag($input, 'is_coins_used');
        $isRefund = $this->boolFlag($input, 'is_refund');
        $isPaid = $this->nullableBool($input, 'is_paid');

        $membershipId = $input['membership_id'] ?? null;
        $percentage = $input['percentage_discount_applied'] ?? null;
        $discount = array_key_exists('discount_amount', $input)
            ? $this->money($input['discount_amount'], 'discount_amount')
            : 0.0;
        $coinsUsed = array_key_exists('coins_used', $input) ? $this->whole($input['coins_used'], 'coins_used') : 0;
        $coinDiscount = array_key_exists('coin_discount_amount', $input)
            ? $this->money($input['coin_discount_amount'], 'coin_discount_amount')
            : 0.0;

        if ($membershipApplied) {
            if ($membershipId === null || $membershipId === '' || $percentage === null || $percentage === '' || ! array_key_exists('discount_amount', $input)) {
                throw new HmsPaymentSyncException('validation_error', 'membership_id, percentage_discount_applied, and discount_amount are required when membership is applied.');
            }
            $percentage = $this->money($percentage, 'percentage_discount_applied');
            $membershipId = (int) $membershipId;
        } else {
            if ($this->presentNonZero($membershipId) || $this->presentNonZero($percentage) || abs($discount) > 0.0) {
                throw new HmsPaymentSyncException('validation_error', 'Membership fields must be null or zero when membership is not applied.');
            }
            $percentage = null;
            $membershipId = null;
            $discount = 0.0;
        }

        if ($coinsUsedFlag) {
            if (! array_key_exists('coins_used', $input) || ! array_key_exists('coin_discount_amount', $input)) {
                throw new HmsPaymentSyncException('validation_error', 'coins_used and coin_discount_amount are required when coins are used.');
            }
        } elseif ($coinsUsed !== 0 || abs($coinDiscount) > 0.0) {
            throw new HmsPaymentSyncException('validation_error', 'Coin fields must be null or zero when coins are not used.');
        }

        $refundOf = trim((string) ($input['refund_of_transaction_id'] ?? ''));
        if ($isRefund && $refundOf === '') {
            throw new HmsPaymentSyncException('validation_error', 'refund_of_transaction_id is required when is_refund is true.');
        }
        if (! $isRefund && $refundOf !== '') {
            throw new HmsPaymentSyncException('validation_error', 'refund_of_transaction_id must be empty unless this is a refund.');
        }

        $expectedPaid = round($original - $discount - $coinDiscount, 2);
        if (abs($expectedPaid - $paid) > 0.01) {
            throw new HmsPaymentSyncException('amount_check_failed', 'paid_amount must equal original_amount minus discount_amount minus coin_discount_amount.');
        }

        if ($coinsUsedFlag) {
            $expectedCoinValue = round($coinsUsed * $this->payments->amountForOneCoin(), 2);
            if (abs($expectedCoinValue - $coinDiscount) > 0.01) {
                throw new HmsPaymentSyncException('validation_error', 'coin_discount_amount must equal coins_used times the value of one coin.');
            }
        }

        $other = $input['other_data'] ?? null;
        if (is_string($other)) {
            $decoded = json_decode($other, true);
            if (! is_array($decoded)) {
                throw new HmsPaymentSyncException('validation_error', 'other_data must be JSON.');
            }
            $other = $decoded;
        }
        if ($other !== null && ! is_array($other)) {
            throw new HmsPaymentSyncException('validation_error', 'other_data must be JSON.');
        }
        if (is_array($other) && strlen((string) json_encode($other)) > 10240) {
            throw new HmsPaymentSyncException('validation_error', 'other_data must be 10 KB or smaller.');
        }

        $timeSlot = trim((string) ($input['time_slot'] ?? ''));
        if (strlen($timeSlot) > 50) {
            throw new HmsPaymentSyncException('validation_error', 'time_slot must be 50 characters or fewer.');
        }

        $packageId = null;
        $activePackage = null;
        $membershipStartDate = null;
        $membershipMembers = null;

        $integration = $request->attributes->get('hms_integration');
        $hospitalIds = $integration instanceof HmsIntegration
            ? array_values(array_filter(array_map('intval', $integration->hospital_ids ?? [])))
            : [];

        if ($paymentType === HmsPaymentType::Membership && ! $isRefund && $isPaid !== false) {
            if (! array_key_exists('package_id', $input)
                || $input['package_id'] === null
                || $input['package_id'] === ''
                || ! is_numeric($input['package_id'])
                || (int) $input['package_id'] != (float) $input['package_id']
                || (int) $input['package_id'] <= 0
            ) {
                throw new HmsPaymentSyncException('validation_error', 'package_id is required and must be an integer.', 422);
            }

            $packageId = (int) $input['package_id'];
            $activePackage = FamilyPackage::active()->find($packageId);
            if (! $activePackage) {
                throw new HmsPaymentSyncException('validation_error', 'package_id must be an active family package.', 422);
            }

            if (! empty($activePackage->branch_ids) && ! empty($hospitalIds)) {
                $branchIds = array_map('intval', $activePackage->branch_ids);
                if (array_intersect($branchIds, $hospitalIds) === []) {
                    throw new HmsPaymentSyncException('validation_error', 'package_id is not available for this hospital.', 422);
                }
            }

            if (array_key_exists('start_date', $input) && $input['start_date'] !== null && $input['start_date'] !== '') {
                try {
                    $membershipStartDate = Carbon::parse((string) $input['start_date']);
                } catch (\Throwable) {
                    throw new HmsPaymentSyncException('validation_error', 'start_date is not a valid date.', 422);
                }

                if ($membershipStartDate->copy()->startOfDay()->isAfter(now()->startOfDay())) {
                    throw new HmsPaymentSyncException('validation_error', 'start_date cannot be in the future.', 422);
                }
            }

            if (array_key_exists('members', $input) && $input['members'] !== null) {
                if (! is_array($input['members'])) {
                    throw new HmsPaymentSyncException('validation_error', 'members must be an array.', 422);
                }
                $membershipMembers = $input['members'];
            }
        } elseif ($paymentType === HmsPaymentType::Membership && ! $isRefund && $isPaid === false) {
            if (array_key_exists('package_id', $input) && $input['package_id'] !== null && $input['package_id'] !== '') {
                if (! is_numeric($input['package_id']) || (int) $input['package_id'] != (float) $input['package_id'] || (int) $input['package_id'] <= 0) {
                    throw new HmsPaymentSyncException('validation_error', 'package_id must be an integer.', 422);
                }
                $packageId = (int) $input['package_id'];
                $activePackage = FamilyPackage::active()->find($packageId);
                if (! $activePackage) {
                    throw new HmsPaymentSyncException('validation_error', 'package_id must be an active family package.', 422);
                }

                if (! empty($activePackage->branch_ids) && ! empty($hospitalIds)) {
                    $branchIds = array_map('intval', $activePackage->branch_ids);
                    if (array_intersect($branchIds, $hospitalIds) === []) {
                        throw new HmsPaymentSyncException('validation_error', 'package_id is not available for this hospital.', 422);
                    }
                }
            }

            if (array_key_exists('start_date', $input) && $input['start_date'] !== null && $input['start_date'] !== '') {
                try {
                    $membershipStartDate = Carbon::parse((string) $input['start_date']);
                } catch (\Throwable) {
                    throw new HmsPaymentSyncException('validation_error', 'start_date is not a valid date.', 422);
                }

                if ($membershipStartDate->copy()->startOfDay()->isAfter(now()->startOfDay())) {
                    throw new HmsPaymentSyncException('validation_error', 'start_date cannot be in the future.', 422);
                }
            }

            if (array_key_exists('members', $input) && $input['members'] !== null) {
                if (! is_array($input['members'])) {
                    throw new HmsPaymentSyncException('validation_error', 'members must be an array.', 422);
                }
                $membershipMembers = $input['members'];
            }
        }

        $paymentMethod = 'cash';
        if (array_key_exists('payment_method', $input) && $input['payment_method'] !== null && $input['payment_method'] !== '') {
            if (! is_string($input['payment_method']) || strlen(trim((string) $input['payment_method'])) > 30) {
                throw new HmsPaymentSyncException('validation_error', 'payment_method must be a string up to 30 characters.', 422);
            }
            $paymentMethod = trim((string) $input['payment_method']);
        }

        try {
            $paidAt = isset($input['paid_at']) && $input['paid_at'] !== '' && $input['paid_at'] !== null
                ? Carbon::parse((string) $input['paid_at'])
                : now();
        } catch (\Throwable) {
            throw new HmsPaymentSyncException('validation_error', 'paid_at is not a valid date.');
        }

        return [
            'transaction_id' => $transactionId,
            'hospital_code' => $this->blankToNull($input['hospital_code'] ?? null),
            'mrn_id' => $mrnId,
            'mobile' => trim((string) ($input['mobile'] ?? '')),
            'patient_name' => trim((string) ($input['patient_name'] ?? '')),
            'dob' => $this->blankToNull($input['dob'] ?? null),
            'gender' => $this->blankToNull($input['gender'] ?? null),
            'payment_type' => $paymentType,
            'paid_at' => $paidAt,
            'is_paid' => $isPaid,
            'time_slot' => $timeSlot !== '' ? $timeSlot : null,
            'doctor_id' => $this->blankToNull($input['doctor_id'] ?? null),
            'doctor_name' => $this->blankToNull($input['doctor_name'] ?? null),
            'lab_test_name' => $this->blankToNull($input['lab_test_name'] ?? null),
            'original_amount' => $original,
            'is_membership_applied' => $membershipApplied,
            'membership_id' => $membershipId,
            'percentage_discount_applied' => $percentage,
            'discount_amount' => $discount,
            'is_coins_used' => $coinsUsedFlag,
            'coins_used' => $coinsUsed,
            'coin_discount_amount' => $coinDiscount,
            'paid_amount' => $paid,
            'is_refund' => $isRefund,
            'refund_of_transaction_id' => $refundOf !== '' ? $refundOf : null,
            'other_data' => $other,
            'package_id' => $packageId,
            'package' => $activePackage,
            'membership_start_date' => $membershipStartDate,
            'membership_members' => $membershipMembers,
            'payment_method' => $paymentMethod,
        ];
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @return array<string, mixed>
     */
    private function existingTransaction(HmisTransaction $existing, array $parsed): array
    {
        $incomingPaid = $parsed['is_paid'] !== false;

        if ($existing->status === HmisTransaction::STATUS_PENDING && $incomingPaid) {
            if ($parsed['is_refund']) {
                return $this->completePendingRefund($existing, $parsed);
            }

            return $this->completePending($existing, $parsed);
        }

        return $this->duplicate($existing);
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @return array<string, mixed>
     */
    private function applyPayment(array $parsed): array
    {
        $match = $this->matchPatient($parsed);
        $flags = $match['flags'];
        $paid = $parsed['is_paid'] !== false;
        $type = $parsed['payment_type'];

        if (! $match['person'] instanceof Persons) {
            if ($type === HmsPaymentType::Membership) {
                $flags[] = 'membership_not_activated';
            }
            $row = $this->store($parsed, null, HmisTransaction::STATUS_UNMATCHED, $flags, 0, 0);

            return $this->outcome('recorded_unmatched', $row, 0, 0);
        }

        if (! $paid) {
            $row = $this->store($parsed, $match['person'], HmisTransaction::STATUS_PENDING, $flags, 0, 0);

            return $this->outcome('recorded_pending', $row, 0, 0);
        }

        if ($type === HmsPaymentType::Membership) {
            return $this->activateMembershipPayment($parsed, $match['person'], $flags);
        }

        if (! $type->isEligible()) {
            $row = $this->store($parsed, $match['person'], HmisTransaction::STATUS_IGNORED, $flags, 0, 0);

            return $this->outcome('ignored_ineligible', $row, 0, 0);
        }

        if ($parsed['is_membership_applied']) {
            $flags = array_values(array_unique(array_merge(
                $flags,
                $this->membershipFlags($match['person'], $parsed)
            )));
        }

        $earned = $this->earnAmount($parsed, true);
        $deducted = $parsed['is_coins_used'] ? (int) $parsed['coins_used'] : 0;
        $this->assertCoinsAvailable($match['person'], $deducted);

        $row = $this->store($parsed, $match['person'], HmisTransaction::STATUS_RECORDED, $flags, $earned, 0);
        $this->moveCoins($match['person'], $deducted, $earned, (string) $row->transaction_id);
        if (! $parsed['is_refund']) {
            $this->addTotals($match['person'], $parsed);
        }

        $upgraded = false;
        $hipUserId = $this->accountId($match['person']);
        if ($hipUserId && $earned > 0) {
            $upgradedPayload = $this->rewardTiers->addEarnedCoins((string) $hipUserId, $earned);
            $upgraded = $upgradedPayload !== null;
        }

        return $this->outcome('recorded', $row, $earned, $deducted, $upgraded);
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @return array<string, mixed>
     */
    private function completePending(HmisTransaction $existing, array $parsed): array
    {
        $match = $this->matchPatient($parsed);
        $flags = $match['flags'];
        $type = $parsed['payment_type'];

        if (! $match['person'] instanceof Persons) {
            if ($type === HmsPaymentType::Membership) {
                $flags[] = 'membership_not_activated';
            }
            $this->fill($existing, $parsed, null, HmisTransaction::STATUS_UNMATCHED, $flags, 0, 0);
            $existing->save();

            return $this->outcome('recorded_unmatched', $existing, 0, 0);
        }

        if ($type === HmsPaymentType::Membership) {
            if (! isset($parsed['package']) || ! $parsed['package']) {
                $pkgId = $parsed['package_id'] ?? ($existing->raw_payload['package_id'] ?? null);
                if ($pkgId && is_numeric($pkgId)) {
                    $parsed['package_id'] = (int) $pkgId;
                    $parsed['package'] = FamilyPackage::active()->find((int) $pkgId);
                }
            }
            if (! isset($parsed['package']) || ! $parsed['package']) {
                throw new HmsPaymentSyncException('validation_error', 'package_id must be an active family package.', 422);
            }

            return $this->activateMembershipPayment($parsed, $match['person'], $flags, $existing);
        }

        if (! $type->isEligible()) {
            $this->fill($existing, $parsed, $match['person'], HmisTransaction::STATUS_IGNORED, $flags, 0, 0);
            $existing->save();

            return $this->outcome('ignored_ineligible', $existing, 0, 0);
        }

        if ($parsed['is_membership_applied']) {
            $flags = array_values(array_unique(array_merge($flags, $this->membershipFlags($match['person'], $parsed))));
        }

        $earned = $this->earnAmount($parsed, true);
        $deducted = $parsed['is_coins_used'] ? (int) $parsed['coins_used'] : 0;
        $this->assertCoinsAvailable($match['person'], $deducted);

        $this->fill($existing, $parsed, $match['person'], HmisTransaction::STATUS_RECORDED, $flags, $earned, 0);
        $existing->save();
        $this->moveCoins($match['person'], $deducted, $earned, (string) $existing->transaction_id);
        $this->addTotals($match['person'], $parsed);

        $upgraded = false;
        $hipUserId = $this->accountId($match['person']);
        if ($hipUserId && $earned > 0) {
            $upgradedPayload = $this->rewardTiers->addEarnedCoins((string) $hipUserId, $earned);
            $upgraded = $upgradedPayload !== null;
        }

        return $this->outcome('recorded', $existing, $earned, $deducted, $upgraded);
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @return array<string, mixed>
     */
    private function applyRefund(array $parsed): array
    {
        $original = HmisTransaction::query()
            ->where('transaction_id', $parsed['refund_of_transaction_id'])
            ->lockForUpdate()
            ->first();

        if (! $original) {
            throw new HmsPaymentSyncException('original_not_found', 'refund_of_transaction_id was not found.', 409);
        }

        if ($original->status === HmisTransaction::STATUS_REFUNDED) {
            return $this->duplicate($original, $parsed['transaction_id'], 0, 0);
        }

        $already = HmisTransaction::query()
            ->where('refund_of_transaction_id', $original->transaction_id)
            ->where('is_refund', true)
            ->lockForUpdate()
            ->first();

        if ($already) {
            return $this->duplicate($already);
        }

        $paid = $parsed['is_paid'] !== false;
        $person = $original->person_id ? Persons::query()->find($original->person_id) : null;

        if ($original->payment_type === HmsPaymentType::Membership->value) {
            $flags = ['membership_refund_needs_review'];
            if (! $paid) {
                $row = $this->store($parsed, $person, HmisTransaction::STATUS_PENDING, $flags, 0, 0);

                return $this->outcome('recorded_pending', $row, 0, 0);
            }

            $original->status = HmisTransaction::STATUS_REFUNDED;
            $original->save();

            $row = $this->store($parsed, $person, HmisTransaction::STATUS_RECORDED, $flags, 0, 0);

            return $this->outcome('refund_applied', $row, 0, 0);
        }

        if (! $paid) {
            $row = $this->store($parsed, $person, HmisTransaction::STATUS_PENDING, [], 0, 0);

            return $this->outcome('recorded_pending', $row, 0, 0);
        }

        $reversed = $this->reverseOriginal($original, $person);

        $row = $this->store($parsed, $person, HmisTransaction::STATUS_RECORDED, [], 0, $reversed);

        return $this->outcome('refund_applied', $row, 0, $reversed);
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @return array<string, mixed>
     */
    private function completePendingRefund(HmisTransaction $existing, array $parsed): array
    {
        $original = HmisTransaction::query()
            ->where('transaction_id', $parsed['refund_of_transaction_id'])
            ->lockForUpdate()
            ->first();

        if (! $original) {
            throw new HmsPaymentSyncException('original_not_found', 'refund_of_transaction_id was not found.', 409);
        }

        if ($original->status === HmisTransaction::STATUS_REFUNDED) {
            return $this->duplicate($existing);
        }

        $person = $original->person_id ? Persons::query()->find($original->person_id) : null;

        if ($original->payment_type === HmsPaymentType::Membership->value) {
            $original->status = HmisTransaction::STATUS_REFUNDED;
            $original->save();

            $this->fill($existing, $parsed, $person, HmisTransaction::STATUS_RECORDED, ['membership_refund_needs_review'], 0, 0);
            $existing->save();

            return $this->outcome('refund_applied', $existing, 0, 0);
        }

        $reversed = $this->reverseOriginal($original, $person);
        $this->fill($existing, $parsed, $person, HmisTransaction::STATUS_RECORDED, [], 0, $reversed);
        $existing->save();

        return $this->outcome('refund_applied', $existing, 0, $reversed);
    }

    private function reverseOriginal(HmisTransaction $original, ?Persons $person): int
    {
        $reversed = 0;
        $applied = $original->status === HmisTransaction::STATUS_RECORDED && ! $original->is_refund;
        if ($applied && $person) {
            if ((int) $original->coins_used > 0) {
                $this->creditCoins($person, (int) $original->coins_used, (string) $original->transaction_id, 'hms_refund_return');
            }
            $balance = $this->lockedBalance($this->walletPersonId($person));
            $reversed = min($balance, (int) $original->coins_earned);
            if ($reversed > 0) {
                $this->debitCoins($person, $reversed, (string) $original->transaction_id, 'hms_refund_reversal');
            }
            $this->subtractTotals($person, $original);

            $hipUserId = $this->accountId($person);
            if ($hipUserId && (int) $original->coins_earned > 0) {
                $this->rewardTiers->reverseEarnedCoins((string) $hipUserId, (int) $original->coins_earned);
            }
        }

        $original->status = HmisTransaction::STATUS_REFUNDED;
        $original->coins_reversed = $reversed;
        $original->save();

        return $reversed;
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @return array{person: ?Persons, flags: list<string>, matched_by_mobile: bool}
     */
    private function matchPatient(array $parsed): array
    {
        $byMrn = Persons::query()->where('mrn', $parsed['mrn_id'])->first();
        if ($byMrn) {
            return ['person' => $byMrn, 'flags' => [], 'matched_by_mobile' => false];
        }

        $mobile = HmsMobile::normalize($parsed['mobile']);
        if ($mobile === '') {
            return ['person' => null, 'flags' => [], 'matched_by_mobile' => false];
        }

        $user = HIPUser::query()->where('mobile_num', $mobile)->first();
        if (! $user) {
            return ['person' => null, 'flags' => [], 'matched_by_mobile' => false];
        }

        $persons = Persons::query()->where('hip_user_id', $user->getKey())->get();
        if ($persons->count() === 1) {
            $person = $persons->first();
        } else {
            $person = $this->narrowPersons($persons, $parsed);
        }

        if (! $person instanceof Persons) {
            return ['person' => null, 'flags' => [], 'matched_by_mobile' => false];
        }

        $flags = [];
        $current = $person->mrn;
        if ($current === null || trim((string) $current) === '') {
            $person->mrn = $parsed['mrn_id'];
            $person->save();
        } elseif ((string) $current !== $parsed['mrn_id']) {
            $flags[] = 'mrn_conflict';
        }

        return ['person' => $person->fresh() ?? $person, 'flags' => $flags, 'matched_by_mobile' => true];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Persons>  $persons
     * @param  array<string, mixed>  $parsed
     */
    private function narrowPersons($persons, array $parsed): ?Persons
    {
        if ($parsed['patient_name'] === '') {
            return null;
        }

        $wanted = $this->normalizeName($parsed['patient_name']);
        $matches = $persons->filter(function (Persons $person) use ($wanted, $parsed): bool {
            if ($this->normalizeName(trim(($person->first_name ?? '').' '.($person->last_name ?? ''))) !== $wanted) {
                return false;
            }
            if ($parsed['dob'] !== null) {
                $dob = $person->dob ? Carbon::parse($person->dob)->toDateString() : null;
                if ($dob !== Carbon::parse((string) $parsed['dob'])->toDateString()) {
                    return false;
                }
            }
            if ($parsed['gender'] !== null && strtolower((string) $person->gender) !== strtolower((string) $parsed['gender'])) {
                return false;
            }

            return true;
        })->values();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @return list<string>
     */
    private function membershipFlags(Persons $person, array $parsed): array
    {
        $coverage = $this->coverage($person);
        $subscription = $coverage['subscription'];
        $package = $coverage['package'];

        if (! $coverage['covered'] || ! $subscription || (int) $subscription->id !== (int) $parsed['membership_id']) {
            throw new HmsPaymentSyncException('validation_error', 'membership_id is not an active membership covering this patient.');
        }

        $type = $parsed['payment_type']->value;
        $expected = 0.0;
        if ($package && MembershipPaymentType::tryFrom($type)) {
            $saved = $package->paymentDiscounts()->where('payment_type', $type)->value('percentage');
            $expected = $saved === null ? 0.0 : (float) $saved;
        }

        if (abs($expected - (float) $parsed['percentage_discount_applied']) > 0.01) {
            return ['membership_percentage_mismatch'];
        }

        return [];
    }

    /**
     * @return array{subscription: ?UserFamilySubscription, package: ?FamilyPackage, covered: bool}
     */
    private function coverage(Persons $person): array
    {
        $accountId = $this->accountId($person);
        if ($accountId === null) {
            return ['subscription' => null, 'package' => null, 'covered' => false];
        }

        $eligibility = $this->membership->evaluateConsultationEligibility($accountId, null, true);
        $subscription = $eligibility['subscription'] ?? null;
        $package = $eligibility['package'] ?? ($subscription?->familyPackage);
        $active = $subscription instanceof UserFamilySubscription && $package instanceof FamilyPackage;

        return [
            'subscription' => $active ? $subscription : null,
            'package' => $active ? $package : null,
            'covered' => $active && $this->personIsCovered($subscription, $person),
        ];
    }

    private function accountId(Persons $person): ?string
    {
        if (filled($person->hip_user_id)) {
            return (string) $person->hip_user_id;
        }

        if (filled($person->parent_id)) {
            $parent = Persons::query()->find($person->parent_id);
            if ($parent && filled($parent->hip_user_id)) {
                return (string) $parent->hip_user_id;
            }
        }

        return null;
    }

    private function personIsCovered(UserFamilySubscription $subscription, Persons $person): bool
    {
        $coveredIds = collect($subscription->covered_member_ids ?? [])
            ->map(fn ($id) => (string) $id)
            ->filter()
            ->values()
            ->all();

        if ($coveredIds === []) {
            return true;
        }

        return in_array((string) $person->getKey(), $coveredIds, true);
    }

    /**
     * @param  array<string, mixed>  $parsed
     */
    private function earnAmount(array $parsed, bool $eligiblePaid): int
    {
        if (! $eligiblePaid || $parsed['is_paid'] === false || $parsed['is_refund']) {
            return 0;
        }
        if ($parsed['is_membership_applied'] || $parsed['is_coins_used'] || ! $parsed['payment_type']->isEligible()) {
            return 0;
        }

        // Credit 1% of the amount actually paid, same as PaymentApiService (round, then int).
        return (int) round((float) $parsed['paid_amount'] * 0.01);
    }

    private function assertCoinsAvailable(Persons $person, int $coinsUsed): void
    {
        if ($coinsUsed <= 0) {
            return;
        }

        if ($this->lockedBalance($this->walletPersonId($person)) < $coinsUsed) {
            throw new HmsPaymentSyncException('coins_insufficient', 'Coin balance is too low.', 409);
        }
    }

    private function moveCoins(Persons $person, int $deducted, int $earned, string $transactionId): void
    {
        if ($deducted > 0) {
            $this->debitCoins($person, $deducted, $transactionId, 'hms_payment');
        }
        if ($earned > 0) {
            $this->creditCoins($person, $earned, $transactionId, 'hms_payment');
        }
    }

    private function debitCoins(Persons $person, int $amount, string $transactionId, string $source): void
    {
        $wallet = $this->lockedWallet($this->walletPersonId($person));
        if (! $wallet || (int) $wallet->coins < $amount) {
            throw new HmsPaymentSyncException('coins_insufficient', 'Coin balance is too low.', 409);
        }

        $this->wallets->debit($wallet, $amount, new RewardPointsContext(
            changeType: 'redeemed',
            source: $source,
            bookingSource: 'hmis_transaction',
            bookingId: $transactionId,
        ));
    }

    private function creditCoins(Persons $person, int $amount, string $transactionId, string $source): void
    {
        if ($amount <= 0) {
            return;
        }

        $context = new RewardPointsContext(
            changeType: 'earned',
            source: $source,
            bookingSource: 'hmis_transaction',
            bookingId: $transactionId,
            coinsEarned: $source === 'hms_payment' ? $amount : null,
        );

        $wallet = $this->lockedWallet($this->walletPersonId($person));
        if (! $wallet) {
            $this->wallets->createWalletAfterPayment([
                'person_id' => $this->walletPersonId($person),
                'coins' => $amount,
            ], $context);

            return;
        }

        if ($source === 'hms_payment') {
            $this->wallets->creditAfterPayment($wallet, $amount, $context);

            return;
        }

        $this->wallets->credit($wallet, $amount, $context);
    }

    /**
     * @param  array<string, mixed>  $parsed
     */
    private function addTotals(Persons $person, array $parsed): void
    {
        $saving = $this->lockedSaving($this->walletPersonId($person));
        $discount = round((float) $parsed['discount_amount'], 2);
        $coins = round((float) $parsed['coin_discount_amount'], 2);
        $saving->membership_discount_total = round((float) $saving->membership_discount_total + $discount, 2);
        $saving->coins_value_total = round((float) $saving->coins_value_total + $coins, 2);
        $saving->lifetime_discount_total = round((float) $saving->lifetime_discount_total + $discount + $coins, 2);
        $saving->lifetime_paid_total = round((float) $saving->lifetime_paid_total + (float) $parsed['paid_amount'], 2);
        $saving->transactions_count = (int) $saving->transactions_count + 1;
        $saving->save();
    }

    private function subtractTotals(Persons $person, HmisTransaction $original): void
    {
        $saving = HmisPatientSaving::query()
            ->where('person_id', $this->walletPersonId($person))
            ->lockForUpdate()
            ->first();

        if (! $saving) {
            return;
        }

        $discount = round((float) $original->discount_amount, 2);
        $coins = round((float) $original->coin_discount_amount, 2);
        $saving->membership_discount_total = max(0, round((float) $saving->membership_discount_total - $discount, 2));
        $saving->coins_value_total = max(0, round((float) $saving->coins_value_total - $coins, 2));
        $saving->lifetime_discount_total = max(0, round((float) $saving->lifetime_discount_total - $discount - $coins, 2));
        $saving->lifetime_paid_total = max(0, round((float) $saving->lifetime_paid_total - (float) $original->paid_amount, 2));
        $saving->transactions_count = max(0, (int) $saving->transactions_count - 1);
        $saving->save();
    }

    /**
     * @param  list<string>  $flags
     */
    private function store(array $parsed, ?Persons $person, string $status, array $flags, int $earned, int $reversed): HmisTransaction
    {
        $row = new HmisTransaction;
        $this->fill($row, $parsed, $person, $status, $flags, $earned, $reversed);
        $row->save();

        return $row;
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @param  list<string>  $flags
     * @return array<string, mixed>
     */
    private function activateMembershipPayment(
        array $parsed,
        Persons $person,
        array $flags,
        ?HmisTransaction $existing = null
    ): array {
        /** @var FamilyPackage $package */
        $package = $parsed['package'];

        if (abs((float) $package->price - (float) $parsed['paid_amount']) > 0.01) {
            $flags[] = 'plan_price_mismatch';
        }

        $hipUserId = $this->accountId($person);
        if ($hipUserId === null) {
            $mobile = HmsMobile::normalize($parsed['mobile'] ?: (string) $person->mobile);
            if ($mobile !== '') {
                $hipUserId = HIPUser::query()->where('mobile_num', $mobile)->value('id');
                if ($hipUserId) {
                    $person->hip_user_id = $hipUserId;
                    $person->save();
                }
            }
        }

        if ($hipUserId === null) {
            $flags[] = 'membership_not_activated';
            if ($existing) {
                $this->fill($existing, $parsed, $person, HmisTransaction::STATUS_UNMATCHED, $flags, 0, 0);
                $existing->save();
                $row = $existing;
            } else {
                $row = $this->store($parsed, $person, HmisTransaction::STATUS_UNMATCHED, $flags, 0, 0);
            }

            return $this->outcome('recorded_unmatched', $row, 0, 0);
        }

        $primaryPerson = filled($person->parent_id)
            ? (Persons::query()->find($person->parent_id) ?: $person)
            : $person;

        $coveredMemberIds = [(string) $primaryPerson->id];
        if ((string) $person->id !== (string) $primaryPerson->id) {
            $coveredMemberIds[] = (string) $person->id;
        }

        if (isset($parsed['membership_members']) && is_array($parsed['membership_members'])) {
            foreach ($parsed['membership_members'] as $m) {
                if (! is_array($m)) {
                    continue;
                }
                $name = trim((string) ($m['name'] ?? ''));
                if ($name === '') {
                    continue;
                }

                $parts = explode(' ', $name, 2);
                $firstName = $parts[0];
                $lastName = $parts[1] ?? '';
                $dob = isset($m['dob']) && $m['dob'] !== '' ? Carbon::parse($m['dob'])->toDateString() : null;
                $gender = isset($m['gender']) && $m['gender'] !== '' ? trim((string) $m['gender']) : null;
                $relation = isset($m['relation']) && $m['relation'] !== ''
                    ? trim((string) $m['relation'])
                    : (isset($m['relationship']) ? trim((string) $m['relationship']) : null);

                $dep = Persons::query()
                    ->where('parent_id', $primaryPerson->id)
                    ->where('first_name', $firstName)
                    ->where('last_name', $lastName)
                    ->first();

                if (! $dep) {
                    $dep = Persons::query()->create([
                        'parent_id' => $primaryPerson->id,
                        'hip_user_id' => $primaryPerson->hip_user_id,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'dob' => $dob,
                        'gender' => $gender,
                        'relationship' => $relation,
                        'is_primary' => false,
                        'is_active' => true,
                    ]);
                }

                $coveredMemberIds[] = (string) $dep->id;
            }
        }

        $coveredMemberIds = array_values(array_unique($coveredMemberIds));
        $maxMembers = max(1, (int) $package->max_members);
        $coveredMemberIds = array_slice($coveredMemberIds, 0, $maxMembers);

        $subscription = $this->familyPackages->createCashierSubscription(
            hipUserId: (string) $hipUserId,
            packageId: (int) $package->id,
            paymentMode: 'cash',
            amount: (float) $parsed['paid_amount'],
            createdBy: null,
            coveredMemberIds: $coveredMemberIds,
            paymentReference: (string) $parsed['transaction_id'],
            startDate: $parsed['membership_start_date'] ?? null,
            allowSamePlanRenewal: true,
            paymentMethod: $parsed['payment_method'] ?? 'cash',
        );

        $parsed['membership_id'] = $subscription->id;

        if ($existing) {
            $this->fill($existing, $parsed, $person, HmisTransaction::STATUS_RECORDED, $flags, 0, 0);
            $existing->membership_id = $subscription->id;
            $existing->save();
            $row = $existing;
        } else {
            $row = $this->store($parsed, $person, HmisTransaction::STATUS_RECORDED, $flags, 0, 0);
        }

        $outcome = $this->outcome('membership_created', $row, 0, 0);
        $outcome['membership'] = [
            'membership_id' => (int) $subscription->id,
            'plan_name' => (string) $package->name,
            'valid_from' => $subscription->start_date?->toDateString(),
            'valid_until' => $subscription->end_date?->toDateString(),
        ];

        return $outcome;
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @param  list<string>  $flags
     */
    private function fill(HmisTransaction $row, array $parsed, ?Persons $person, string $status, array $flags, int $earned, int $reversed): void
    {
        $row->fill([
            'transaction_id' => $parsed['transaction_id'],
            'hospital_code' => $parsed['hospital_code'],
            'mrn_id' => $parsed['mrn_id'],
            'person_id' => $person?->getKey(),
            'user_id' => $person ? $this->accountId($person) : null,
            'payment_type' => $parsed['payment_type']->value,
            'paid_at' => $parsed['paid_at'],
            'time_slot' => $parsed['time_slot'],
            'is_paid' => $parsed['is_paid'],
            'status' => $status,
            'doctor_id' => $parsed['doctor_id'],
            'doctor_name' => $parsed['doctor_name'],
            'lab_test_name' => $parsed['lab_test_name'],
            'original_amount' => $parsed['original_amount'],
            'is_membership_applied' => $parsed['is_membership_applied'],
            'membership_id' => $parsed['membership_id'],
            'percentage_discount_applied' => $parsed['percentage_discount_applied'],
            'discount_amount' => $parsed['discount_amount'],
            'is_coins_used' => $parsed['is_coins_used'],
            'coins_used' => $parsed['coins_used'],
            'coin_discount_amount' => $parsed['coin_discount_amount'],
            'paid_amount' => $parsed['paid_amount'],
            'coins_earned' => $earned,
            'coins_reversed' => $reversed,
            'is_refund' => $parsed['is_refund'],
            'refund_of_transaction_id' => $parsed['refund_of_transaction_id'],
            'flags' => array_values($flags),
            'other_data' => $parsed['other_data'],
            'raw_payload' => $parsed['raw_payload'] ?? [],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function duplicate(HmisTransaction $existing, ?string $transactionId = null, ?int $earned = null, ?int $deducted = null): array
    {
        $outcome = $this->outcome(
            'duplicate',
            $existing,
            $earned ?? (int) $existing->coins_earned,
            $deducted ?? ($existing->is_refund ? (int) $existing->coins_reversed : (int) $existing->coins_used),
        );
        if ($transactionId !== null) {
            $outcome['response_transaction_id'] = $transactionId;
            $outcome['flags'] = [];
        }

        if ($existing->payment_type === HmsPaymentType::Membership->value && $existing->membership_id) {
            $sub = UserFamilySubscription::with('familyPackage')->find($existing->membership_id);
            if ($sub) {
                $outcome['membership'] = [
                    'membership_id' => (int) $sub->id,
                    'plan_name' => (string) $sub->familyPackage?->name,
                    'valid_from' => $sub->start_date?->toDateString(),
                    'valid_until' => $sub->end_date?->toDateString(),
                ];
            }
        }

        return $outcome;
    }

    /**
     * @return array<string, mixed>
     */
    private function outcome(string $result, HmisTransaction $row, int $earned, int $deducted, bool $upgraded = false): array
    {
        return [
            'result' => $result,
            'transaction' => $row,
            'coins_earned' => $earned,
            'coins_deducted' => $deducted,
            'upgraded' => $upgraded,
        ];
    }

    /**
     * @param  array<string, mixed>  $outcome
     * @return array<string, mixed>
     */
    private function present(Request $request, array $outcome): array
    {
        /** @var HmisTransaction $row */
        $row = $outcome['transaction'];
        $person = $row->person_id ? Persons::query()->find($row->person_id) : null;
        $balance = $person ? $this->currentBalance($person) : 0;
        $lifetime = 0.0;
        if ($person) {
            $lifetime = (float) (HmisPatientSaving::query()
                ->where('person_id', $this->walletPersonId($person))
                ->value('lifetime_discount_total') ?? 0);
        }

        $presented = [
            'status' => true,
            'is_synched' => true,
            'transaction_id' => $outcome['response_transaction_id'] ?? $row->transaction_id,
            'result' => $outcome['result'],
            'coins_earned' => (int) $outcome['coins_earned'],
            'coins_deducted' => (int) $outcome['coins_deducted'],
            'coin_balance' => $balance,
            'lifetime_discount_total' => $lifetime,
            'flags' => $outcome['flags'] ?? array_values($row->flags ?? []),
            'request_id' => HmsResponse::requestId($request),
        ];

        $hipUserId = $person ? $this->accountId($person) : null;
        // if ($hipUserId) {
        //     $tier = $this->rewardTiers->getReadOnlyTierForUser((string) $hipUserId)
        //         ?? $this->rewardTiers->getTierForUser((string) $hipUserId);
        //     if ($tier) {
        //         $presented['reward_tier'] = [
        //             'name' => (string) $tier->name,
        //             'upgraded' => (bool) ($outcome['upgraded'] ?? false),
        //         ];
        //     }
        // }

        if (isset($outcome['membership'])) {
            $presented['membership'] = $outcome['membership'];
        } elseif ($row->payment_type === HmsPaymentType::Membership->value && $row->membership_id) {
            $sub = UserFamilySubscription::with('familyPackage')->find($row->membership_id);
            if ($sub) {
                $presented['membership'] = [
                    'membership_id' => (int) $sub->id,
                    'plan_name' => (string) $sub->familyPackage?->name,
                    'valid_from' => $sub->start_date?->toDateString(),
                    'valid_until' => $sub->end_date?->toDateString(),
                ];
            }
        }

        return $presented;
    }

    private function assertHospitalCode(mixed $code, Request $request): void
    {
        $code = trim((string) ($code ?? ''));
        if ($code === '') {
            return;
        }

        $integration = $request->attributes->get('hms_integration');
        $ids = $integration instanceof HmsIntegration ? array_values($integration->hospital_ids ?? []) : [];
        $hospitalId = count($ids) === 1 ? (int) $ids[0] : 0;
        $stored = $hospitalId > 0
            ? trim((string) (Hospital::query()->whereKey($hospitalId)->value('code') ?? ''))
            : '';

        if ($stored !== '' && strcasecmp($stored, $code) === 0) {
            return;
        }

        throw new HmsPaymentSyncException('hospital_code_mismatch', 'hospital_code does not match this hospital.', 422);
    }

    private function walletPersonId(Persons $person): string
    {
        return (string) ($person->parent_id ?? $person->id);
    }

    private function lockedWallet(string $personId): ?Coins
    {
        return Coins::query()
            ->where('person_id', $personId)
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();
    }

    private function lockedBalance(string $personId): int
    {
        return (int) ($this->lockedWallet($personId)?->coins ?? 0);
    }

    private function currentBalance(Persons $person): int
    {
        $wallet = Coins::query()
            ->where('person_id', $this->walletPersonId($person))
            ->orderByDesc('id')
            ->first();

        return (int) ($wallet?->coins ?? 0);
    }

    private function lockedSaving(string $personId): HmisPatientSaving
    {
        $saving = HmisPatientSaving::query()->where('person_id', $personId)->lockForUpdate()->first();
        if ($saving) {
            return $saving;
        }

        return HmisPatientSaving::query()->create([
            'person_id' => $personId,
            'lifetime_discount_total' => 0,
            'membership_discount_total' => 0,
            'coins_value_total' => 0,
            'lifetime_paid_total' => 0,
            'transactions_count' => 0,
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function boolFlag(array $input, string $key): bool
    {
        if (! array_key_exists($key, $input) || $input[$key] === null || $input[$key] === '') {
            return false;
        }

        $value = $this->nullableBool($input, $key);
        if ($value === null) {
            throw new HmsPaymentSyncException('validation_error', $key.' must be true or false.');
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function nullableBool(array $input, string $key): ?bool
    {
        if (! array_key_exists($key, $input) || $input[$key] === null || $input[$key] === '') {
            return null;
        }

        $value = $input[$key];
        if (is_bool($value)) {
            return $value;
        }
        if ($value === 1 || $value === '1' || $value === 'true') {
            return true;
        }
        if ($value === 0 || $value === '0' || $value === 'false') {
            return false;
        }

        throw new HmsPaymentSyncException('validation_error', $key.' must be true or false.');
    }

    private function money(mixed $value, string $field): float
    {
        if (! is_numeric($value)) {
            throw new HmsPaymentSyncException('validation_error', $field.' must be a number.');
        }

        return round((float) $value, 2);
    }

    private function whole(mixed $value, string $field): int
    {
        if (! is_numeric($value) || (int) $value != (float) $value) {
            throw new HmsPaymentSyncException('validation_error', $field.' must be a whole number.');
        }

        return (int) $value;
    }

    private function presentNonZero(mixed $value): bool
    {
        if ($value === null || $value === '' || $value === 0 || $value === 0.0 || $value === '0' || $value === '0.0' || $value === '0.00') {
            return false;
        }

        return true;
    }

    private function blankToNull(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));

        return $text === '' ? null : $text;
    }

    private function normalizeName(string $name): string
    {
        $name = strtolower(trim($name));

        return preg_replace('/\s+/', ' ', $name) ?? $name;
    }
}
