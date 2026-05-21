<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    public const CACHE_KEY = 'app.settings.all';

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = static::cached();

        if (!array_key_exists($key, $all)) {
            return $default;
        }

        return static::castValue(
            $all[$key]['value'],
            $all[$key]['type'] ?? 'string'
        );
    }

    public static function set(string $key, mixed $value, ?string $type = null, ?string $group = null): self
    {
        $record = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => static::serializeValue($value),
                'type' => $type ?? static::inferType($value),
                'group' => $group,
            ]
        );

        static::clearCache();

        return $record;
    }

    public static function clearCache(): void
    {
        Cache::forget(static::CACHE_KEY);
    }

    /**
     * @return array<string, array{value: ?string, type: ?string, group: ?string}>
     */
    protected static function cached(): array
    {
        return Cache::rememberForever(static::CACHE_KEY, function () {
            return static::query()
                ->get()
                ->keyBy('key')
                ->map(fn (self $setting) => [
                    'value' => $setting->value,
                    'type'  => $setting->type,
                    'group' => $setting->group,
                ])
                ->all();
        });
    }

    protected static function serializeValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }

    protected static function inferType(mixed $value): string
    {
        return match (true) {
            is_int($value) => 'integer',
            is_float($value) => 'float',
            is_bool($value) => 'boolean',
            is_numeric($value) && str_contains((string) $value, '.') => 'float',
            is_numeric($value) => 'integer',
            default => 'string',
        };
    }

    protected static function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'integer' => (int) $value,
            'float', 'numeric' => (float) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default => $value,
        };
    }
}
