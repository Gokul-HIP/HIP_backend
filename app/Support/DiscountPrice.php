<?php

namespace App\Support;

class DiscountPrice
{
    /**
     * Payable amount after optional discounted price (null/0/>= actual = no discount).
     */
    public static function payable(float $actualPrice, mixed $discountPrice): float
    {
        $discounted = self::normalized($discountPrice);

        if ($discounted === null || $discounted >= $actualPrice) {
            return round($actualPrice, 2);
        }

        return round($discounted, 2);
    }

    public static function hasDiscount(float $actualPrice, mixed $discountPrice): bool
    {
        return self::payable($actualPrice, $discountPrice) < round($actualPrice, 2);
    }

    public static function saved(float $actualPrice, mixed $discountPrice): float
    {
        return round($actualPrice - self::payable($actualPrice, $discountPrice), 2);
    }

    /**
     * Normalize stored discount price: null when empty or zero.
     */
    public static function normalized(mixed $discountPrice): ?float
    {
        if ($discountPrice === null || $discountPrice === '') {
            return null;
        }

        $value = round((float) $discountPrice, 2);

        return $value > 0 ? $value : null;
    }

    /**
     * Value to persist: null when no valid discount (empty, zero, or >= actual price).
     */
    public static function forStorage(float $actualPrice, mixed $discountPrice): ?float
    {
        $value = self::normalized($discountPrice);

        if ($value === null || $value >= $actualPrice) {
            return null;
        }

        return $value;
    }

    /**
     * Convert legacy percentage values (e.g. 15 meaning 15%) to discounted price.
     */
    public static function migrateLegacyPercentage(float $actualPrice, float $storedValue): float
    {
        if ($storedValue <= 0) {
            return 0;
        }

        if ($storedValue <= 100
            && $storedValue < $actualPrice
            && ($actualPrice - $storedValue) > ($actualPrice * 0.5)
        ) {
            return round($actualPrice - ($actualPrice * $storedValue / 100), 2);
        }

        return round($storedValue, 2);
    }
}
