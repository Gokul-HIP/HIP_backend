<?php

namespace App\Services\Hms;

class HmsPayloadHash
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public static function make(array $payload): string
    {
        $encoded = json_encode(self::canonicalize($payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return hash('sha256', $encoded === false ? '' : $encoded);
    }

    private static function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map([self::class, 'canonicalize'], $value);
        }

        ksort($value);
        foreach ($value as $key => $item) {
            if (in_array($key, ['original_fee', 'payable_amount', 'amount_paid'], true) && is_numeric($item)) {
                $value[$key] = number_format((float) $item, 2, '.', '');
                continue;
            }
            $value[$key] = self::canonicalize($item);
        }

        return $value;
    }
}
