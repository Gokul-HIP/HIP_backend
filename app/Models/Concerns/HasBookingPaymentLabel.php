<?php

namespace App\Models\Concerns;

trait HasBookingPaymentLabel
{
    public static function paymentModeLabel(bool $isOnlinePayment, ?string $paymentStatus): string
    {
        if ($isOnlinePayment) {
            return $paymentStatus === 'paid' ? 'Paid by online' : 'Pay by online';
        }

        return 'Pay by hospital';
    }

    public function getPaymentModeLabelAttribute(): string
    {
        return static::paymentModeLabel(
            (bool) $this->is_online_payment,
            $this->payment_status
        );
    }
}
