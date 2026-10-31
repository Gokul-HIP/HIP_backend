<?php

namespace App\Services\Hms;

/** Turns an HMS mobile into the 10-digit form stored on healthinpocket_users.mobile_num. */
class HmsMobile
{
    public static function normalize(string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', trim($mobile)) ?? '';
        $digits = ltrim($digits, '0');

        // Drop a country code only when extra digits follow it. A 10-digit
        // local number that itself starts with 91 is left unchanged.
        if (str_starts_with($digits, '91') && strlen($digits) > 10) {
            $digits = substr($digits, 2);
        }

        return ltrim($digits, '0');
    }
}
