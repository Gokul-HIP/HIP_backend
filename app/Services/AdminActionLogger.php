<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AdminActionLogger
{
    /**
     * Structured admin audit trail using the existing Laravel log stack.
     *
     * @param  array<string, mixed>  $context
     */
    public function record(string $action, array $context = []): void
    {
        Log::info('admin.action', array_filter([
            'action' => $action,
            'actor_id' => auth('filament')->id() ?? auth()->id(),
            ...$context,
        ], fn ($value) => $value !== null));
    }
}
