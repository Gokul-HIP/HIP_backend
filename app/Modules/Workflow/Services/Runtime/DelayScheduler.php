<?php

namespace App\Modules\Workflow\Services\Runtime;

use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\Jobs\ContinueWorkflowExecutionJob;

class DelayScheduler
{
    /**
     * Schedule continuation after a delay.
     *
     * @param  string  $resumeNodeId  The next node to execute after the delay (never the Delay node itself).
     */
    public function scheduleResume(
        int $executionId,
        string $resumeNodeId,
        int $delaySeconds,
        WorkflowContext $context
    ): void {
        ContinueWorkflowExecutionJob::dispatch($executionId, $resumeNodeId)
            ->delay(now()->addSeconds(max(0, $delaySeconds)));
    }

    /**
     * @param  array<string, mixed>  $delayConfig
     */
    public function resolveDelaySeconds(array $delayConfig): int
    {
        $type = (string) ($delayConfig['type'] ?? $delayConfig['unit'] ?? 'minutes');
        $value = (int) ($delayConfig['value'] ?? $delayConfig['amount'] ?? 0);

        return match ($type) {
            'minutes', 'minute' => $value * 60,
            'hours', 'hour' => $value * 3600,
            'days', 'day' => $value * 86400,
            'weeks', 'week' => $value * 604800,
            'seconds', 'second' => $value,
            default => $value * 60,
        };
    }
}
