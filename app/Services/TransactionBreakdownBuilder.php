<?php

namespace App\Services;

/** Builds an explainable payment breakdown without assigning unattributed discounts to a source. */
class TransactionBreakdownBuilder
{
    public function build(
        array $transaction,
        array $invoice,
        ?array $booking = null,
        ?array $membershipUsage = null,
        ?array $rewardUsage = null,
        ?array $pharmacyOrder = null,
        float $coinRate = 0.0,
    ): array {
        [$bookingType, $bookingId, $feeKey] = $this->bookingIdentity($invoice, $pharmacyOrder);
        $original = $this->firstNumeric(
            $invoice['original_amount'] ?? null,
            $booking[$feeKey] ?? null,
            $pharmacyOrder['subtotal'] ?? null,
            $transaction['transaction_amount'] ?? null,
        );
        $totalPaid = $this->firstNumeric($transaction['total_paid'] ?? null, $invoice['total_amount'] ?? null);
        $coinsUsed = (int) ($booking['coins_used'] ?? $pharmacyOrder['coins_applied'] ?? $invoice['coins_used'] ?? 0);
        $totalDiscount = $this->firstNumeric(
            $booking['total_discount'] ?? null,
            $invoice['discount_amount'] ?? null,
        );

        $membershipDiscount = $membershipUsage !== null
            ? round(min($original, $totalDiscount), 2)
            : 0.0;

        $storedCoinDiscount = max(0, (float) ($transaction['discount_amount'] ?? 0));
        $coinDiscountSource = $storedCoinDiscount > 0 ? 'transaction' : null;
        if ($storedCoinDiscount > 0) {
            $coinDiscount = $bookingType === 'pharmacy'
                ? min($storedCoinDiscount, $original)
                : min($storedCoinDiscount, max(0, $totalDiscount - $membershipDiscount));
        } elseif ($coinsUsed > 0) {
            $coinDiscount = min(
                round($coinsUsed * max(0, $coinRate), 2),
                max(0, $totalDiscount - $membershipDiscount),
                $original,
            );
            // Pharmacy coin redemptions store their discount in transactions.discount_amount;
            // booking coins are already reflected in booking total_discount/invoice.discount_price.
            if ($bookingType === 'pharmacy') {
                $coinDiscount = min(round($coinsUsed * max(0, $coinRate), 2), $original);
            }
            $coinDiscountSource = 'configured_rate';
        } else {
            $coinDiscount = 0.0;
        }

        $unallocatedDiscount = round(max(0, $totalDiscount - $membershipDiscount - $coinDiscount), 2);
        $rewardDiscount = $rewardUsage !== null ? $unallocatedDiscount : 0.0;
        $unexplainedDiscount = $rewardUsage === null ? $unallocatedDiscount : 0.0;

        $serviceCharge = $this->firstNumeric($transaction['service_charge'] ?? null, $invoice['service_charge'] ?? null);
        $gst = $this->firstNumeric($transaction['gst'] ?? null, $invoice['gst'] ?? null);
        $gatewayCharge = $this->firstNumeric($transaction['gateway_charge'] ?? null, $invoice['gateway_charge'] ?? null);

        return [
            'booking_type' => $bookingType,
            'booking_type_label' => $this->bookingTypeLabel($bookingType),
            'booking_id' => $bookingId,
            'original_amount' => $original,
            'membership_discount' => $membershipDiscount,
            'reward_discount' => $rewardDiscount,
            'reward_tier_name' => $rewardDiscount > 0 ? ($rewardUsage['tier_name'] ?? null) : null,
            'reward_tier_percentage' => $rewardDiscount > 0 && isset($rewardUsage['tier_percentage'])
                ? (float) $rewardUsage['tier_percentage']
                : null,
            'coin_discount' => round($coinDiscount, 2),
            'coin_discount_source' => $coinDiscountSource,
            'coins_used' => $coinsUsed,
            'unexplained_discount' => $unexplainedDiscount,
            'service_charge' => $serviceCharge,
            'gst' => $gst,
            'gateway_charge' => $gatewayCharge,
            'total_paid' => $totalPaid,
            'has_membership' => $membershipDiscount > 0,
            'has_reward_tier' => $rewardUsage !== null && $rewardDiscount > 0,
            'has_coins' => $coinsUsed > 0,
        ];
    }

    private function bookingIdentity(array $invoice, ?array $pharmacyOrder): array
    {
        foreach ([
            'doctor_booking_id' => ['doctor', 'consultation_fee'],
            'second_opinion_id' => ['second_opinion', 'consultation_fee'],
            'diagnostic_test_booking_id' => ['package', 'package_fee'],
        ] as $column => [$type, $feeKey]) {
            if (! empty($invoice[$column])) {
                return [$type, (int) $invoice[$column], $feeKey];
            }
        }

        if (! empty($invoice['pharmacy_order_id']) || $pharmacyOrder !== null) {
            return ['pharmacy', (int) ($invoice['pharmacy_order_id'] ?? $pharmacyOrder['id'] ?? 0), 'subtotal'];
        }

        return ['other', null, 'amount'];
    }

    private function bookingTypeLabel(string $type): string
    {
        return match ($type) {
            'doctor' => 'Doctor consultation',
            'second_opinion' => 'Second opinion',
            'package' => 'Diagnostic package',
            'pharmacy' => 'Pharmacy',
            default => 'Other',
        };
    }

    private function firstNumeric(mixed ...$values): float
    {
        foreach ($values as $value) {
            if ($value !== null && $value !== '' && is_numeric($value)) {
                return round((float) $value, 2);
            }
        }

        return 0.0;
    }
}
