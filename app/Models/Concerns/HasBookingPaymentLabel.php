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

    public static function onlinePaymentStatusLabel(?string $paymentStatus): string
    {
        return match ($paymentStatus) {
            'paid' => 'Paid',
            'failed' => 'Failed',
            'created', 'pending', null, '' => 'Pending',
            default => ucfirst((string) $paymentStatus),
        };
    }

    public static function onlinePaymentStatusBadgeClass(?string $paymentStatus): string
    {
        return match ($paymentStatus) {
            'paid' => 'bg-green-100 text-green-700',
            'failed' => 'bg-red-100 text-red-700',
            default => 'bg-yellow-100 text-yellow-700',
        };
    }

    public function getPaymentModeLabelAttribute(): string
    {
        return static::paymentModeLabel(
            (bool) $this->is_online_payment,
            $this->payment_status
        );
    }

    public function getOnlinePaymentStatusLabelAttribute(): string
    {
        return static::onlinePaymentStatusLabel($this->payment_status);
    }

    public function getOnlinePaymentStatusBadgeClassAttribute(): string
    {
        return static::onlinePaymentStatusBadgeClass($this->payment_status);
    }
}
