<?php

namespace App\Enums;

enum PharmacyOrderStatus: string
{
    case Requested = 'requested';
    case InReview = 'in_review';
    case PendingPayment = 'pending_payment';
    case Processing = 'processing';
    case ReadyForDelivery = 'ready_for_delivery';
    case Dispatched = 'dispatched';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Requested',
            self::InReview => 'In review',
            self::PendingPayment => 'Pending payment',
            self::Processing => 'Processing',
            self::ReadyForDelivery => 'Ready for delivery',
            self::Dispatched => 'Dispatched',
            self::OutForDelivery => 'Out for delivery',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
            self::Rejected => 'Rejected',
        };
    }

    public function colour(): string
    {
        return match ($this) {
            self::Requested => 'amber',
            self::InReview => 'violet',
            self::PendingPayment => 'orange',
            self::Processing => 'blue',
            self::ReadyForDelivery => 'indigo',
            self::Dispatched => 'cyan',
            self::OutForDelivery => 'sky',
            self::Delivered => 'green',
            self::Cancelled => 'slate',
            self::Rejected => 'red',
        };
    }
}
