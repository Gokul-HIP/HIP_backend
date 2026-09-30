<?php

namespace App\Modules\MedicineReminder\Notifications;

class WhatsAppPhoneNormalizer
{
    /**
     * Digits only, no "+", no leading local 0. Prepend configured country code
     * when the number looks like a local subscriber number.
     */
    public function normalize(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if ($digits === '') {
            return null;
        }

        $digits = ltrim($digits, '0');

        if ($digits === '') {
            return null;
        }

        $country = preg_replace('/\D+/', '', (string) config('services.whatsapp.default_country_code', '')) ?? '';

        if ($country !== '' && ! str_starts_with($digits, $country)) {
            $localLength = strlen($country) === 2 ? 10 : 10;

            if (strlen($digits) <= $localLength) {
                $digits = $country.$digits;
            }
        }

        return strlen($digits) >= 8 ? $digits : null;
    }
}
