<?php

namespace App\Services;

use App\Models\DiagnosticPackage;
use App\Models\DiseasePackage;
use App\Models\Hospital;
use App\Models\RewardTierPackageDiscount;
use App\Models\UserFamilySubscription;
use App\Services\Api\PaymentApiService;
use Illuminate\Support\Facades\Schema;

/** Read-only pricing previews that share the booking pricing services. */
class BookingPricingPreviewService
{
    public function __construct(
        private readonly MembershipEntitlementService $membership,
        private readonly RewardTierService $rewardTiers,
        private readonly InvoiceFeeCalculator $fees,
        private readonly PaymentApiService $payments,
    ) {}

    public function doctor(
        string $hipUserId,
        float $fee,
        int $requestedCoins = 0,
        int $availableCoins = 0,
        ?int $branchId = null,
        array $candidateBranchIds = [],
        bool $includeMembership = true,
    ): array {
        $fee = round(max(0, $fee), 2);
        $membershipState = $includeMembership
            ? $this->membership->previewMembershipState($hipUserId, 'doctor')
            : ['status' => false, 'free_consultations_left' => null, 'free_package' => false];
        $pricingBranch = $includeMembership
            ? $this->resolveBranch($hipUserId, $branchId, $candidateBranchIds)
            : null;

        if ($includeMembership) {
            $beforeCoins = $this->membership->priceDoctorConsultation($hipUserId, $fee, 0, $pricingBranch, true);
            $rewardDiscount = (float) $beforeCoins['reward_discount'];
            $tierLimit = (int) ($beforeCoins['reward_tier_limit'] ?? 0);
            $tierUsed = (int) ($beforeCoins['reward_tier_used'] ?? 0);
            $tierRemaining = $beforeCoins['reward_tier_remaining'] ?? null;
            $membershipPricing = $beforeCoins;
        } else {
            $reward = $this->rewardTiers->calculateBasePriceDiscount($hipUserId, $fee, null, true);
            $rewardDiscount = (float) ($reward['amount'] ?? 0);
            $tierLimit = (int) ($reward['limit'] ?? 0);
            $tierUsed = (int) ($reward['used'] ?? 0);
            $tierRemaining = $reward['remaining'] ?? null;
            $beforeCoins = $this->fees->bookingBreakdown($fee, $rewardDiscount);
            $membershipPricing = null;
        }

        $remainder = (float) ($includeMembership
            ? $beforeCoins['discounted_base_price']
            : $beforeCoins['discounted_amount']);
        $coinsUsed = $this->capCoins($requestedCoins, $availableCoins, $remainder);
        $coinValue = round($coinsUsed * $this->payments->amountForOneCoin(), 2);

        if ($includeMembership) {
            $membershipPricing = $this->membership->priceDoctorConsultation(
                $hipUserId,
                $fee,
                $coinValue,
                $pricingBranch,
                true
            );
            $rewardDiscount = (float) $membershipPricing['reward_discount'];
            $membershipDiscount = (float) $membershipPricing['membership_discount'];
            $breakdown = $membershipPricing['breakdown'];
            $membershipState = $this->membership->previewMembershipState($hipUserId, 'doctor');
        } else {
            $totalDiscount = round($rewardDiscount + min($coinValue, $remainder), 2);
            $breakdown = $this->fees->bookingBreakdown($fee, $totalDiscount);
            $membershipDiscount = 0.0;
        }

        $tier = $this->rewardTiers->getReadOnlyTierForUser($hipUserId);
        $percentage = $tier?->config?->total_discount_percentage !== null
            ? (float) $tier->config->total_discount_percentage
            : null;
        $rewardStatus = $tier !== null && ($percentage ?? 0) > 0;

        return [
            'pricing' => [
                'booking_type' => 'doctor',
                'original_fee' => $fee,
                'reward_tier' => [
                'status' => $rewardStatus,
                'name' => $tier?->name,
                'percentage' => $percentage,
                'applied' => $rewardDiscount > 0,
                'discount_amount' => round($rewardDiscount, 2),
                'package_promotion' => null,
                'limit' => $tierLimit,
                'used' => $tierUsed,
                'remaining' => $tierRemaining,
                ],
                'membership' => [
                'status' => $includeMembership && $membershipState['status'],
                'applied' => $includeMembership && (bool) ($membershipPricing['membership_benefit_applied'] ?? false),
                'discount_amount' => round($membershipDiscount, 2),
                'free_consultations_left' => $includeMembership ? $membershipState['free_consultations_left'] : null,
                'free_package' => false,
                ],
                'coin_discount' => round((float) ($membershipPricing['coins_discount'] ?? min($coinValue, $remainder)), 2),
                'service_charge' => round((float) $breakdown['service_charges'], 2),
                'total_payable' => round((float) $breakdown['invoice_total'], 2),
            ],
            'coins_used' => $coinsUsed,
            'remaining_coins' => max(0, $availableCoins - $coinsUsed),
        ];
    }

    public function secondOpinion(
        string $hipUserId,
        float $fee,
        int $requestedCoins = 0,
        int $availableCoins = 0,
    ): array {
        $pricing = $this->doctor($hipUserId, $fee, $requestedCoins, $availableCoins, null, [], false);
        $pricing['pricing']['booking_type'] = 'second_opinion';

        return $pricing;
    }

    public function package(
        string $hipUserId,
        DiagnosticPackage|DiseasePackage $package,
        string $packageType,
        int $requestedCoins = 0,
        int $availableCoins = 0,
    ): array {
        $fee = \App\Support\DiscountPrice::payable((float) ($package->price ?? 0), $package->discount ?? null);
        $candidateBranches = Hospital::query()
            ->where('diagnostic_center_id', $package->diagnostic_id)
            ->pluck('id')->map(fn ($id) => (int) $id)->all();
        $branchId = $this->resolveBranch($hipUserId, null, $candidateBranches);
        $branchId ??= 0;

        $beforeCoins = $this->membership->priceDiagnosticPackage(
            $hipUserId, $fee, (int) $package->id, $branchId, $packageType, 0, true
        );
        $remainder = (float) $beforeCoins['discounted_base_price'];
        $coinsUsed = $this->capCoins($requestedCoins, $availableCoins, $remainder);
        $coinValue = round($coinsUsed * $this->payments->amountForOneCoin(), 2);
        $final = $this->membership->priceDiagnosticPackage(
            $hipUserId, $fee, (int) $package->id, $branchId, $packageType, $coinValue, true
        );

        $tier = $this->rewardTiers->getReadOnlyTierForUser($hipUserId);
        $percentage = $tier?->config?->total_discount_percentage !== null
            ? (float) $tier->config->total_discount_percentage
            : null;
        $promotion = $packageType === 'diagnostic'
            ? $tier?->packageDiscounts?->firstWhere('diagnostic_package_id', $package->id)
            : null;
        $promotionData = $promotion instanceof RewardTierPackageDiscount
            ? [
                'type' => (string) $promotion->promotion_type,
                'applied' => ($final['reward_discount_source'] ?? null) === 'package_promotion'
                    && (float) $final['reward_discount'] > 0,
            ]
            : null;
        $rewardStatus = $tier !== null
            && (($percentage ?? 0) > 0 || $promotion instanceof RewardTierPackageDiscount);
        $membershipState = $this->membership->previewMembershipState($hipUserId, 'package', (int) $package->id);

        $membership = [
            'status' => (bool) $membershipState['status'],
            'applied' => (bool) $final['membership_benefit_applied'],
            'discount_amount' => round((float) $final['membership_discount'], 2),
            'free_consultations_left' => null,
            'free_package' => (bool) $membershipState['free_package'],
        ];
        if (is_numeric($membershipState['remaining_lab_tests'] ?? null)) {
            $membership['remaining_lab_tests'] = (int) $membershipState['remaining_lab_tests'];
        }

        return [
            'pricing' => [
                'booking_type' => 'package',
                'original_fee' => round($fee, 2),
                'reward_tier' => [
                'status' => $rewardStatus,
                'name' => $tier?->name,
                'percentage' => $percentage,
                'applied' => (float) $final['reward_discount'] > 0,
                'discount_amount' => round((float) $final['reward_discount'], 2),
                'package_promotion' => $promotionData,
                'limit' => (int) ($final['reward_tier_limit'] ?? 0),
                'used' => (int) ($final['reward_tier_used'] ?? 0),
                'remaining' => $final['reward_tier_remaining'] ?? null,
                ],
                'membership' => $membership,
                'coin_discount' => round((float) $final['coins_discount'], 2),
                'service_charge' => round((float) $final['service_charges'], 2),
                'total_payable' => round((float) $final['final_payable_amount'], 2),
            ],
            'coins_used' => $coinsUsed,
            'remaining_coins' => max(0, $availableCoins - $coinsUsed),
        ];
    }

    private function capCoins(int $requested, int $available, float $remainingBase): int
    {
        $value = $this->payments->amountForOneCoin();
        if ($requested <= 0 || $available <= 0 || $remainingBase <= 0 || $value <= 0) return 0;

        return min($requested, $available, (int) floor($remainingBase / $value));
    }

    private function resolveBranch(string $hipUserId, ?int $requestedBranch, array $candidates): ?int
    {
        if ($requestedBranch) return $requestedBranch;

        $subscription = Schema::hasTable('user_family_subscriptions')
            ? UserFamilySubscription::query()
            ->where('hip_user_id', $hipUserId)
            ->where('status', 'active')
            ->whereDate('end_date', '>=', now()->toDateString())
            ->with('familyPackage')
            ->latest()->first()
            : null;
        $allowed = array_map('intval', $subscription?->familyPackage?->branch_ids ?? []);
        $candidates = array_values(array_unique(array_map('intval', $candidates)));

        if ($allowed === []) return $candidates[0] ?? null;
        $matching = array_values(array_intersect($candidates, $allowed));
        if ($matching !== []) return $matching[0];

        return $candidates[0] ?? $allowed[0] ?? null;
    }
}
