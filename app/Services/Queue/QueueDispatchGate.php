<?php

namespace App\Services\Queue;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class QueueDispatchGate
{
    public function enabled(): bool
    {
        if (! Schema::hasTable('settings')) {
            return true;
        }

        return filter_var(Setting::get('queue.dispatch_enabled', true), FILTER_VALIDATE_BOOLEAN);
    }

    public function assertEnabled(): void
    {
        if (! $this->enabled()) {
            throw new RuntimeException('Application queue dispatching is disabled in Queue Settings.');
        }
    }
}
