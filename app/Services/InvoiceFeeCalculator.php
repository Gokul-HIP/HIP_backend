<?php

namespace App\Services;

/**
 * Percentage fee math for invoices. Rates come from settings/config (SERVICE_CHARGES,
 * PAYMENT_GATEWAY_CHARGES, GST_PERCENT), never hardcoded in callers.
 *
 * Booking online pay (BookingApiService):
 *   discounted = original - discount
 *   service_charge = SERVICE_CHARGES% of discounted
 *   gateway = 0, GST = 0 (already stored that way on booking invoices)
 *   total = discounted + service_charge
 *
 * Hospital bills (cashier CreatePayment):
 *   GST, service, and gateway percents all apply to the item subtotal.
 */
class InvoiceFeeCalculator
{
    public function serviceChargesPercent(): float
    {
        return $this->percent('service_charges', 'services.service_charges_percent');
    }

    public function paymentGatewayChargesPercent(): float
    {
        return $this->percent('payment_gateway_charges', 'services.payment_gateway_charges_percent');
    }

    public function gstPercent(): float
    {
        return $this->percent('gst_percent', 'services.gst_percent');
    }

    public function percentOf(float $baseAmount, float $percent): float
    {
        if ($baseAmount <= 0 || $percent <= 0) {
            return 0.0;
        }

        return round($baseAmount * ($percent / 100), 2);
    }

    public function serviceCharges(float $baseAmount): float
    {
        return $this->percentOf($baseAmount, $this->serviceChargesPercent());
    }

    public function paymentGatewayCharges(float $baseAmount): float
    {
        return $this->percentOf($baseAmount, $this->paymentGatewayChargesPercent());
    }

    public function gst(float $baseAmount): float
    {
        return $this->percentOf($baseAmount, $this->gstPercent());
    }

    /**
     * Doctor / second-opinion / diagnostic booking totals (existing product order).
     *
     * @return array{
     *     original_amount: float,
     *     discount_amount: float,
     *     discounted_amount: float,
     *     service_charges: float,
     *     payment_gateway_charges: float,
     *     gst_amount: float,
     *     invoice_total: float
     * }
     */
    public function bookingBreakdown(float $originalAmount, float $discountAmount): array
    {
        $original = round(max(0, $originalAmount), 2);
        $discount = round(min(max(0, $discountAmount), $original), 2);
        $discounted = round(max(0, $original - $discount), 2);
        $service = $this->serviceCharges($discounted);

        return [
            'original_amount' => $original,
            'discount_amount' => $discount,
            'discounted_amount' => $discounted,
            'service_charges' => $service,
            'payment_gateway_charges' => 0.0,
            'gst_amount' => 0.0,
            'invoice_total' => round($discounted + $service, 2),
        ];
    }

    /**
     * Hospital bill totals (existing cashier CreatePayment order).
     *
     * @return array{
     *     original_amount: float,
     *     discount_amount: float,
     *     discounted_amount: float,
     *     service_charges: float,
     *     payment_gateway_charges: float,
     *     gst_amount: float,
     *     invoice_total: float
     * }
     */
    public function hospitalBillBreakdown(float $subtotal, float $discountSaved = 0.0): array
    {
        $discounted = round(max(0, $subtotal), 2);
        $discount = round(max(0, $discountSaved), 2);
        $service = $this->serviceCharges($discounted);
        $gateway = $this->paymentGatewayCharges($discounted);
        $gst = $this->gst($discounted);

        return [
            'original_amount' => round($discounted + $discount, 2),
            'discount_amount' => $discount,
            'discounted_amount' => $discounted,
            'service_charges' => $service,
            'payment_gateway_charges' => $gateway,
            'gst_amount' => $gst,
            'invoice_total' => round($discounted + $service + $gateway + $gst, 2),
        ];
    }

    protected function percent(string $settingKey, string $servicesConfigKey): float
    {
        $fromSettings = config('settings.fees.'.$settingKey);

        if (function_exists('app_setting')) {
            $fromSettings = app_setting($settingKey, $fromSettings);
        }

        return (float) ($fromSettings ?? config($servicesConfigKey, 0));
    }
}
