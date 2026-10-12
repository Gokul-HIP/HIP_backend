<?php

namespace App\Enums;

enum PharmacyPaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::Paid => 'Paid',
            self::Failed => 'Failed',
            self::Refunded => 'Refunded',
        };
    }

    public function colour(): string
    {
        return match ($this) {
            self::Unpaid => 'slate',
            self::Paid => 'green',
            self::Failed => 'red',
            self::Refunded => 'amber',
        };
    }
}
