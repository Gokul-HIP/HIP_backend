<?php

namespace App\Listeners;

use Illuminate\Queue\Events\WorkerStopping;
use Illuminate\Queue\Events\Looping;
use Illuminate\Support\Facades\Cache;

class RecordQueueWorkerHeartbeat
{
    public function handleLooping(Looping $event): void
    {
        $this->store(
            $event->connectionName ?? config('queue.default'),
            $event->queue ?? null,
        );
    }

    public function handleStopping(WorkerStopping $event): void
    {
        Cache::put('queue.worker.heartbeat', [
            'at' => now()->toIso8601String(),
            'connection' => config('queue.default'),
            'queue' => config('queue.connections.'.config('queue.default').'.queue', 'default'),
            'stopping' => true,
        ], now()->addMinutes(5));
    }

    protected function store(?string $connection, ?string $queue = null): void
    {
        Cache::put('queue.worker.heartbeat', [
            'at' => now()->toIso8601String(),
            'connection' => $connection ?: config('queue.default'),
            'queue' => $queue ?: config('queue.connections.'.config('queue.default').'.queue', 'default'),
        ], now()->addMinutes(5));
    }
}
