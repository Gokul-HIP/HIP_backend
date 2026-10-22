<?php

namespace App\Services;

use App\Models\DiagnosticPackage;
use App\Models\FamilyPackage;
use App\Models\Hospital;
use App\Models\SubscriptionUsageLog;
use App\Models\UserFamilySubscription;
use Illuminate\Support\Facades\DB;

class MembershipEntitlementService
{
    public function __construct(
        protected FamilyPackageService $familyPackageService,
        protected InvoiceFeeCalculator $feeCalculator,
        protected RewardTierService $rewardTierService,
    ) {}

    /**
     * Evaluate consultation membership benefit and compute payable totals.
     *
     * Service charge is never waived by membership; InvoiceFeeCalculator keeps it
     * when the base fee is fully discounted (same behaviour as full coin coverage).
     *
     * @return array{
     *     membership_benefit_applied: bool,
     *     benefit_type: string|null,
     *     rejection_reason: string|null,
     *     subscription_id: int|null,
     *     original_base_price: float,
     *     membership_discount: float,
     *     coins_discount: float,
     *     total_discount: float,
     *     discounted_base_price: float,
     *     service_charges: float,
     *     final_payable_amount: float,
     *     remaining_consultations: int|null,
     *     breakdown: array<string, float>
     * }
     */
    public function priceDoctorConsultation(
        string $hipUserId,
        float $consultationFee,
        float $coinsValue = 0.0,
        ?int $branchId = null
    ): array {
        $consultationFee = round(max(0, $consultationFee), 2);
        $coinsValue = round(max(0, $coinsValue), 2);

        $eligibility = $this->evaluateConsultationEligibility($hipUserId, $branchId);

        $membershipDiscount = $eligibility['eligible'] ? $consultationFee : 0.0;
        $afterMembership = round(max(0, $consultationFee - $membershipDiscount), 2);
        $reward = $this->rewardTierService->calculateBasePriceDiscount($hipUserId, $afterMembership, null);
        $rewardDiscount = (float) ($reward['amount'] ?? 0);
        $afterReward = round(max(0, $afterMembership - $rewardDiscount), 2);
        $coinsDiscount = round(min($coinsValue, $afterReward), 2);
        $combinedDiscount = round($membershipDiscount + $rewardDiscount + $coinsDiscount, 2);
        $breakdown = $this->feeCalculator->bookingBreakdown($consultationFee, $combinedDiscount);

        $remaining = null;
        if ($eligibility['subscription'] && $eligibility['package']) {
            $limit = (int) ($eligibility['package']->max_consultations ?? 0);
            $used = $this->consultationUsageCount($eligibility['subscription']);
            $remaining = $limit === 0 ? null : max(0, $limit - $used);
        }

        return [
            'membership_benefit_applied' => (bool) $eligibility['eligible'],
            'benefit_type' => $eligibility['eligible'] ? 'free' : ($reward['benefit_type'] ?? null),
            'rejection_reason' => $eligibility['eligible'] ? null : $eligibility['reason'],
            'subscription_id' => $eligibility['subscription']?->id,
            'original_base_price' => $breakdown['original_amount'],
            'membership_discount' => $membershipDiscount,
            'reward_discount' => $rewardDiscount,
            'reward_discount_source' => $reward['source'] ?? null,
            'coins_discount' => $coinsDiscount,
            'total_discount' => $breakdown['discount_amount'],
            'discounted_base_price' => $breakdown['discounted_amount'],
            'service_charges' => $breakdown['service_charges'],
            'final_payable_amount' => $breakdown['invoice_total'],
            'remaining_consultations' => $remaining,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Evaluate diagnostic package membership benefit and compute payable totals.
     *
     * @return array{
     *     membership_benefit_applied: bool,
     *     benefit_type: string|null,
     *     rejection_reason: string|null,
     *     subscription_id: int|null,
     *     diagnostic_package_id: int|null,
     *     original_base_price: float,
     *     membership_discount: float,
     *     coins_discount: float,
     *     total_discount: float,
     *     discounted_base_price: float,
     *     service_charges: float,
     *     final_payable_amount: float,
     *     remaining_lab_tests: int|null,
     *     breakdown: array<string, float>
     * }
     */
    public function priceDiagnosticPackage(
        string $hipUserId,
        float $packageFee,
        int $diagnosticPackageId,
        int $branchId,
        string $packageType = 'diagnostic',
        float $coinsValue = 0.0
    ): array {
        $packageFee = round(max(0, $packageFee), 2);
        $coinsValue = round(max(0, $coinsValue), 2);

        $eligibility = $this->evaluateDiagnosticPackageEligibility(
            $hipUserId,
            $diagnosticPackageId,
            $branchId,
            $packageType
        );

        $membershipDiscount = $eligibility['eligible'] ? $packageFee : 0.0;
        $afterMembership = round(max(0, $packageFee - $membershipDiscount), 2);
        $packageIdForReward = $packageType === 'disease' ? null : $diagnosticPackageId;
        $reward = $this->rewardTierService->calculateBasePriceDiscount(
            $hipUserId,
            $afterMembership,
            $packageIdForReward
        );
        $rewardDiscount = (float) ($reward['amount'] ?? 0);
        $afterReward = round(max(0, $afterMembership - $rewardDiscount), 2);
        $coinsDiscount = round(min($coinsValue, $afterReward), 2);
        $combinedDiscount = round($membershipDiscount + $rewardDiscount + $coinsDiscount, 2);
        $breakdown = $this->feeCalculator->bookingBreakdown($packageFee, $combinedDiscount);

        return [
            'membership_benefit_applied' => (bool) $eligibility['eligible'],
            'benefit_type' => $eligibility['eligible']
                ? 'free'
                : ($reward['benefit_type'] ?? null),
            'rejection_reason' => $eligibility['eligible'] ? null : $eligibility['reason'],
            'subscription_id' => $eligibility['subscription']?->id,
            'diagnostic_package_id' => $diagnosticPackageId,
            'original_base_price' => $breakdown['original_amount'],
            'membership_discount' => $membershipDiscount,
            'reward_discount' => $rewardDiscount,
            'reward_discount_source' => $reward['source'] ?? null,
            'coins_discount' => $coinsDiscount,
            'total_discount' => $breakdown['discount_amount'],
            'discounted_base_price' => $breakdown['discounted_amount'],
            'service_charges' => $breakdown['service_charges'],
            'final_payable_amount' => $breakdown['invoice_total'],
            'remaining_lab_tests' => $eligibility['remaining_lab_tests'],
            'breakdown' => $breakdown,
        ];
    }

    /**
     * @return array{
     *     eligible: bool,
     *     reason: string|null,
     *     subscription: UserFamilySubscription|null,
     *     package: FamilyPackage|null
     * }
     */
    public function evaluateConsultationEligibility(string $hipUserId, ?int $branchId = null): array
    {
        $subscription = $this->lockedActiveSubscription($hipUserId);

        if (! $subscription) {
            return $this->ineligible('no_active_membership');
        }

        $package = $subscription->familyPackage;

        if (! $package) {
            return $this->ineligible('membership_package_missing', $subscription);
        }

        if ($branchId && ! $this->branchIsAllowed($package, $branchId)) {
            return $this->ineligible('branch_not_allowed', $subscription, $package);
        }

        $limit = (int) ($package->max_consultations ?? 0);
        $used = $this->consultationUsageCount($subscription);

        if ($limit > 0 && $used >= $limit) {
            return $this->ineligible('consultation_allowance_exhausted', $subscription, $package);
        }

        return [
            'eligible' => true,
            'reason' => null,
            'subscription' => $subscription,
            'package' => $package,
        ];
    }

    /**
     * @return array{
     *     eligible: bool,
     *     reason: string|null,
     *     subscription: UserFamilySubscription|null,
     *     package: FamilyPackage|null,
     *     remaining_lab_tests: int|null
     * }
     */
    public function evaluateDiagnosticPackageEligibility(
        string $hipUserId,
        int $diagnosticPackageId,
        int $branchId,
        string $packageType = 'diagnostic'
    ): array {
        if ($packageType === 'disease') {
            return array_merge($this->ineligible('disease_packages_not_covered'), [
                'remaining_lab_tests' => null,
            ]);
        }

        $subscription = $this->lockedActiveSubscription($hipUserId);

        if (! $subscription) {
            return array_merge($this->ineligible('no_active_membership'), [
                'remaining_lab_tests' => null,
            ]);
        }

        $package = $subscription->familyPackage;

        if (! $package) {
            return array_merge($this->ineligible('membership_package_missing', $subscription), [
                'remaining_lab_tests' => null,
            ]);
        }

        if (! $this->branchIsAllowed($package, $branchId)) {
            return array_merge($this->ineligible('branch_not_allowed', $subscription, $package), [
                'remaining_lab_tests' => null,
            ]);
        }

        if (! $this->diagnosticPackageBelongsToBranch($diagnosticPackageId, $branchId)) {
            return array_merge($this->ineligible('package_not_available_at_branch', $subscription, $package), [
                'remaining_lab_tests' => null,
            ]);
        }

        $benefit = $package->benefitForDiagnosticPackage($diagnosticPackageId);

        if (! $benefit) {
            return array_merge($this->ineligible('package_not_in_plan_benefits', $subscription, $package), [
                'remaining_lab_tests' => null,
            ]);
        }

        if (($benefit['benefit_type'] ?? '') !== 'free') {
            return array_merge($this->ineligible('benefit_type_not_free', $subscription, $package), [
                'remaining_lab_tests' => null,
            ]);
        }

        $limit = (int) ($benefit['max_lab_tests'] ?? 0);
        $used = $this->familyPackageService->countLabTestUsageForDiagnosticPackage(
            $subscription,
            $diagnosticPackageId
        );
        $remaining = $limit === 0 ? null : max(0, $limit - $used);

        if ($limit > 0 && $used >= $limit) {
            return array_merge($this->ineligible('lab_test_allowance_exhausted', $subscription, $package), [
                'remaining_lab_tests' => 0,
            ]);
        }

        return [
            'eligible' => true,
            'reason' => null,
            'subscription' => $subscription,
            'package' => $package,
            'remaining_lab_tests' => $remaining,
        ];
    }

    /**
     * Reserve membership usage for a booking (idempotent). Counts against allowance immediately
     * so concurrent bookings cannot oversell; release if payment fails/is abandoned.
     */
    public function reserveUsage(
        int $subscriptionId,
        string $usageType,
        int $bookingId,
        string $bookingType,
        ?int $diagnosticPackageId = null
    ): SubscriptionUsageLog {
        return DB::transaction(function () use ($subscriptionId, $usageType, $bookingId, $bookingType, $diagnosticPackageId) {
            $existing = SubscriptionUsageLog::query()
                ->where('booking_id', $bookingId)
                ->where('booking_type', $bookingType)
                ->where('usage_type', $usageType)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            return $this->familyPackageService->logUsage(
                $subscriptionId,
                $usageType,
                $bookingId,
                $bookingType,
                'membership_reserved',
                $diagnosticPackageId
            );
        });
    }

    public function confirmUsage(int $bookingId, string $bookingType, string $usageType): void
    {
        SubscriptionUsageLog::query()
            ->where('booking_id', $bookingId)
            ->where('booking_type', $bookingType)
            ->where('usage_type', $usageType)
            ->where('notes', 'membership_reserved')
            ->update(['notes' => 'membership_consumed']);
    }

    public function releaseUsage(int $bookingId, string $bookingType, string $usageType): int
    {
        return SubscriptionUsageLog::query()
            ->where('booking_id', $bookingId)
            ->where('booking_type', $bookingType)
            ->where('usage_type', $usageType)
            ->where('notes', 'membership_reserved')
            ->delete();
    }

    /**
     * API-friendly membership pricing payload.
     *
     * @param  array<string, mixed>  $pricing
     * @return array<string, mixed>
     */
    public function formatPricingForApi(array $pricing): array
    {
        return [
            'membership_benefit_applied' => (bool) ($pricing['membership_benefit_applied'] ?? false),
            'benefit_type' => $pricing['benefit_type'] ?? null,
            'rejection_reason' => $pricing['rejection_reason'] ?? null,
            'original_base_price' => (float) ($pricing['original_base_price'] ?? 0),
            'membership_discount' => (float) ($pricing['membership_discount'] ?? 0),
            'reward_discount' => (float) ($pricing['reward_discount'] ?? 0),
            'reward_discount_source' => $pricing['reward_discount_source'] ?? null,
            'discounted_base_price' => (float) ($pricing['discounted_base_price'] ?? 0),
            'service_charges' => (float) ($pricing['service_charges'] ?? 0),
            'final_payable_amount' => (float) ($pricing['final_payable_amount'] ?? 0),
            'remaining_consultations' => $pricing['remaining_consultations'] ?? null,
            'remaining_lab_tests' => $pricing['remaining_lab_tests'] ?? null,
        ];
    }

    private function lockedActiveSubscription(string $hipUserId): ?UserFamilySubscription
    {
        $this->familyPackageService->expireStaleSubscriptions();

        return UserFamilySubscription::query()
            ->where('hip_user_id', $hipUserId)
            ->where('status', 'active')
            ->whereDate('end_date', '>=', now()->toDateString())
            ->with('familyPackage')
            ->lockForUpdate()
            ->latest()
            ->first();
    }

    private function consultationUsageCount(UserFamilySubscription $subscription): int
    {
        return $subscription->usageLogs()
            ->where('usage_type', 'consultation')
            ->count();
    }

    private function branchIsAllowed(FamilyPackage $package, int $branchId): bool
    {
        $branchIds = array_map('intval', $package->branch_ids ?? []);

        // Empty branch_ids means all branches (legacy packages).
        if ($branchIds === []) {
            return true;
        }

        return in_array($branchId, $branchIds, true);
    }

    private function diagnosticPackageBelongsToBranch(int $diagnosticPackageId, int $branchId): bool
    {
        $hospital = Hospital::query()
            ->select('id', 'diagnostic_center_id')
            ->find($branchId);

        if (! $hospital?->diagnostic_center_id) {
            return false;
        }

        return DiagnosticPackage::query()
            ->where('id', $diagnosticPackageId)
            ->where('diagnostic_id', $hospital->diagnostic_center_id)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * @return array{
     *     eligible: false,
     *     reason: string,
     *     subscription: UserFamilySubscription|null,
     *     package: FamilyPackage|null
     * }
     */
    private function ineligible(
        string $reason,
        ?UserFamilySubscription $subscription = null,
        ?FamilyPackage $package = null
    ): array {
        return [
            'eligible' => false,
            'reason' => $reason,
            'subscription' => $subscription,
            'package' => $package,
        ];
    }
}
