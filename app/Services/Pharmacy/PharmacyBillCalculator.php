<?php

namespace App\Services\Pharmacy;

/**
 * Same percent source as the pharmacist Create New Payment screen.
 *
 * config/services.php:109-111
 *   gst_percent                  = env('GST_PERCENT', 5)
 *   service_charges_percent      = env('SERVICE_CHARGES', 3)
 *   payment_gateway_charges_percent = env('PAYMENT_GATEWAY_CHARGES', 2)
 *
 * Applied the same way as PharmacistAdmin/Payments/CreatePayment.php:589-594:
 *   charge = round(base * (percent / 100), 2)
 *   total  = round(base + gst + service + gateway, 2)
 *
 * Here the base is max(0, subtotal - discount + delivery_fee).
 * CreatePayment folds item discounts into its subtotal before those three lines,
 * so a pharmacy subtotal with no order discount and no delivery fee matches it.
 */
class PharmacyBillCalculator
{
    public function gstPercent(): float
    {
        return (float) config('services.gst_percent', 5);
    }

    public function servicePercent(): float
    {
        return (float) config('services.service_charges_percent', 3);
    }

    public function gatewayPercent(): float
    {
        return (float) config('services.payment_gateway_charges_percent', 2);
    }

    /**
     * @return array{
     *     subtotal: float,
     *     discount: float,
     *     delivery_fee: float,
     *     base: float,
     *     tax: float,
     *     service_charge: float,
     *     gateway_charge: float,
     *     total: float
     * }
     */
    public function calculate(float $subtotal, float $discount = 0, float $deliveryFee = 0): array
    {
        $subtotal = round($subtotal, 2);
        $discount = round($discount, 2);
        $deliveryFee = round($deliveryFee, 2);
        $base = round(max(0, $subtotal - $discount + $deliveryFee), 2);
        $tax = round($base * ($this->gstPercent() / 100), 2);
        $serviceCharge = round($base * ($this->servicePercent() / 100), 2);
        $gatewayCharge = round($base * ($this->gatewayPercent() / 100), 2);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'delivery_fee' => $deliveryFee,
            'base' => $base,
            'tax' => $tax,
            'service_charge' => $serviceCharge,
            'gateway_charge' => $gatewayCharge,
            'total' => round($base + $tax + $serviceCharge + $gatewayCharge, 2),
        ];
    }
}
