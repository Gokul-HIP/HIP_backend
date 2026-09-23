<?php

namespace App\Modules\Automation\Support;

use Illuminate\Support\Facades\Cache;
use DateTimeInterface;

final class EventDispatchGuard
{
    /**
     * Returns true the first time $key is claimed before $expiresAt.
     * Used so scheduled commands do not emit the same domain event twice.
     */
    public static function claim(string $key, DateTimeInterface $expiresAt): bool
    {
        return Cache::add('ha:step2:'.$key, 1, $expiresAt);
    }
}
