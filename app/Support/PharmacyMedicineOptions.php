<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

/**
 * Option lists copied from the doctor prescription form
 * resources/views/livewire/doctor-admin/prescription/create-prescription.blade.php:195-255.
 */
class PharmacyMedicineOptions
{
    /**
     * @var list<string>
     */
    public const DOSAGES = [
        '250 mg',
        '500 mg',
        '750 mg',
        '1 g',
        '2.5 mg',
        '5 mg',
        '10 mg',
        '20 mg',
        '25 mg',
        '50 mg',
        '100 mg',
    ];

    /**
     * @var list<string>
     */
    public const FREQUENCIES = [
        'Once Daily',
        'Twice Daily',
        'Thrice Daily',
        'Every 4 Hours',
        'Every 6 Hours',
        'Every 8 Hours',
        'As Needed',
        'Weekly',
    ];

    /**
     * @var list<string>
     */
    public const DURATIONS = [
        '1 Day',
        '3 Days',
        '5 Days',
        '7 Days',
        '10 Days',
        '15 Days',
        '30 Days',
        '2 Months',
        '3 Months',
        'Ongoing',
    ];

    /**
     * @var list<string>
     */
    public const WHEN_TO_TAKE = [
        'Before Food',
        'After Food',
        'With Food',
        'Morning',
        'Afternoon',
        'Night',
        'Bedtime',
    ];

    /**
     * @param  list<string>  $allowed
     */
    public static function isAllowed(?string $value, array $allowed): bool
    {
        $value = self::blankToNull($value);

        return $value === null || in_array($value, $allowed, true);
    }

    /**
     * @param  list<string>  $values
     * @param  list<string>  $allowed
     */
    public static function isAllowedList(array $values, array $allowed): bool
    {
        foreach ($values as $value) {
            if (! in_array(trim((string) $value), $allowed, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string>  $allowed
     */
    public static function assertAllowed(?string $value, array $allowed, string $field): ?string
    {
        $value = self::blankToNull($value);

        if ($value === null) {
            return null;
        }

        if (! in_array($value, $allowed, true)) {
            throw ValidationException::withMessages([
                $field => [$value.' is not an allowed '.$field.'.'],
            ]);
        }

        return $value;
    }

    /**
     * Store when_to_take as one string joined with ", ", matching CreatePrescription::saveMedicine.
     *
     * @param  list<string>|string|null  $values
     */
    public static function joinWhenToTake(array|string|null $values): ?string
    {
        if ($values === null) {
            return null;
        }

        if (is_string($values)) {
            $values = $values === '' ? [] : array_map('trim', explode(',', $values));
        }

        $tokens = [];
        foreach ($values as $value) {
            $token = trim((string) $value);
            if ($token === '') {
                continue;
            }
            if (! in_array($token, self::WHEN_TO_TAKE, true)) {
                throw ValidationException::withMessages([
                    'when_to_take' => [$token.' is not an allowed when_to_take value.'],
                ]);
            }
            $tokens[] = $token;
        }

        return $tokens === [] ? null : implode(', ', $tokens);
    }

    private static function blankToNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
