<?php

namespace App\Services\Queue;

use App\Models\DatabaseQueueJob;
use App\Models\FailedQueueJob;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class QueueMetrics
{
    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $pending = DatabaseQueueJob::query()->whereNull('reserved_at')->count();
        $processing = DatabaseQueueJob::query()->whereNotNull('reserved_at')->count();
        $failed = FailedQueueJob::query()->count();

        $oldestPending = DatabaseQueueJob::query()
            ->whereNull('reserved_at')
            ->orderBy('available_at')
            ->first();

        $latestFailure = FailedQueueJob::query()
            ->orderByDesc('failed_at')
            ->orderByDesc('id')
            ->first();

        $sanitizer = app(QueuePayloadSanitizer::class);
        $latestSummary = $latestFailure
            ? $sanitizer->summarize($latestFailure->payload)
            : null;

        return [
            'connection' => config('queue.default'),
            'queue' => config('queue.connections.'.config('queue.default').'.queue', 'default'),
            'dispatch_enabled' => $this->dispatchEnabled(),
            'pending' => $pending,
            'processing' => $processing,
            'failed' => $failed,
            'oldest_pending_at' => $oldestPending?->availableAt(),
            'oldest_pending_age' => $oldestPending?->availableAt()?->diffForHumans(),
            'latest_failure_at' => $latestFailure?->failed_at,
            'latest_failure_job' => $latestSummary['display_name'] ?? $latestSummary['command_name'] ?? null,
            'worker' => $this->workerStatus(),
            'successful_jobs_note' => 'Laravel database queues do not persist successful jobs. Completed work is not stored in the jobs table.',
        ];
    }

    public function dispatchEnabled(): bool
    {
        if (! Schema::hasTable('settings')) {
            return true;
        }

        $value = Setting::get('queue.dispatch_enabled', true);

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return array<string, mixed>
     */
    public function workerStatus(): array
    {
        $heartbeat = Cache::get('queue.worker.heartbeat');

        if (! is_array($heartbeat) || empty($heartbeat['at'])) {
            return [
                'status' => 'unknown',
                'label' => 'Unknown',
                'detail' => 'No application-level worker heartbeat yet. Supervisor is the source of truth; this panel does not run supervisorctl.',
                'last_heartbeat' => null,
            ];
        }

        $at = Carbon::parse($heartbeat['at']);
        $seconds = $at->diffInSeconds(now());

        if ($seconds <= 90) {
            return [
                'status' => 'active',
                'label' => 'Worker loop recently active',
                'detail' => 'Heartbeat from the Laravel queue worker process (not Supervisor).',
                'last_heartbeat' => $at,
                'queue' => $heartbeat['queue'] ?? null,
                'connection' => $heartbeat['connection'] ?? null,
            ];
        }

        return [
            'status' => 'stale',
            'label' => 'Heartbeat stale',
            'detail' => 'Last worker loop signal was '.$at->diffForHumans().'. This does not prove Supervisor is down.',
            'last_heartbeat' => $at,
            'queue' => $heartbeat['queue'] ?? null,
            'connection' => $heartbeat['connection'] ?? null,
        ];
    }
}
