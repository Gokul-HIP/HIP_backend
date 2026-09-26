<?php

namespace App\Modules\Workflow\Services\Runtime;

use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\Exceptions\InvalidDelayConfiguration;
use App\Modules\Workflow\Jobs\ContinueWorkflowExecutionJob;
use Carbon\Carbon;
use Illuminate\Support\Arr;

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
     * Resolve wait seconds from the saved node contract.
     *
     * Duration: waitType=duration + amount + unit (or legacy type/value).
     * Relative date: waitType=relative_date + relativeDateField + offset.
     *
     * The Wait node form often persists leftover relativeDateField defaults
     * (e.g. followup.date) even when waitType is duration. Duration wins in
     * that case — a 2-day wait must not require followup.date.
     *
     * Past target datetimes resolve to 0 seconds (resume immediately).
     * Invalid relative_date configuration throws — it is not converted to a default duration.
     *
     * @param  array<string, mixed>  $delayConfig
     *
     * @throws InvalidDelayConfiguration
     */
    public function resolveDelaySeconds(array $delayConfig, ?WorkflowContext $context = null): int
    {
        $waitType = strtolower(trim((string) ($delayConfig['waitType'] ?? '')));

        if ($waitType === 'relative_date') {
            return $this->resolveRelativeDateSeconds($delayConfig, $context);
        }

        if ($waitType === 'duration') {
            return $this->resolveDurationSeconds($delayConfig);
        }

        $relativeField = trim((string) ($delayConfig['relativeDateField'] ?? ''));
        $hasDurationValue = array_key_exists('amount', $delayConfig)
            || array_key_exists('value', $delayConfig);

        if ($relativeField !== '' && ! $hasDurationValue) {
            return $this->resolveRelativeDateSeconds($delayConfig, $context);
        }

        return $this->resolveDurationSeconds($delayConfig);
    }

    /**
     * @param  array<string, mixed>  $delayConfig
     */
    protected function resolveDurationSeconds(array $delayConfig): int
    {
        $type = (string) ($delayConfig['unit'] ?? $delayConfig['type'] ?? 'minutes');
        $value = (int) ($delayConfig['amount'] ?? $delayConfig['value'] ?? 0);

        return $this->unitToSeconds($type, $value);
    }

    /**
     * @param  array<string, mixed>  $delayConfig
     */
    protected function resolveRelativeDateSeconds(array $delayConfig, ?WorkflowContext $context): int
    {
        if ($context === null) {
            throw new InvalidDelayConfiguration('relative_date wait requires runtime context.');
        }

        $field = trim((string) ($delayConfig['relativeDateField'] ?? ''));

        if ($field === '') {
            throw new InvalidDelayConfiguration('relative_date wait requires relativeDateField.');
        }

        $raw = $this->resolveContextDate($field, $context);

        if ($raw === null) {
            throw new InvalidDelayConfiguration("relative_date field [{$field}] is missing or not a valid date/time.");
        }

        try {
            $base = $raw instanceof Carbon ? $raw->copy() : Carbon::parse($raw);
        } catch (\Throwable) {
            throw new InvalidDelayConfiguration("relative_date field [{$field}] is missing or not a valid date/time.");
        }

        $direction = strtolower(trim((string) ($delayConfig['relativeOffsetDirection'] ?? 'on')));
        $amount = (int) ($delayConfig['relativeOffsetAmount'] ?? 0);
        $unit = (string) ($delayConfig['relativeOffsetUnit'] ?? 'days');
        $offsetSeconds = $this->unitToSeconds($unit, $amount);

        $target = match ($direction) {
            'before' => $base->copy()->subSeconds($offsetSeconds),
            'after' => $base->copy()->addSeconds($offsetSeconds),
            'on', '', 'at' => $base->copy(),
            default => throw new InvalidDelayConfiguration("Unknown relativeOffsetDirection [{$direction}]."),
        };

        $seconds = now()->diffInSeconds($target, false);

        // Already in the past: resume immediately rather than failing or inventing another delay.
        return max(0, (int) $seconds);
    }

    protected function resolveContextDate(string $field, WorkflowContext $context): mixed
    {
        $facts = $context->payload['_facts'] ?? null;

        if (is_array($facts) && Arr::has($facts, $field)) {
            return data_get($facts, $field);
        }

        $fromPayload = data_get($context->payload, $field);

        if ($this->isPresentDate($fromPayload)) {
            return $fromPayload;
        }

        $underscore = str_replace('.', '_', $field);
        $fromFlat = $context->payload[$underscore] ?? $context->payload[$field] ?? null;

        if ($this->isPresentDate($fromFlat)) {
            return $fromFlat;
        }

        return data_get($context->variables, $field);
    }

    protected function isPresentDate(mixed $value): bool
    {
        if ($value instanceof \DateTimeInterface) {
            return true;
        }

        return is_string($value) && trim($value) !== '';
    }

    protected function unitToSeconds(string $unit, int $value): int
    {
        return match (strtolower($unit)) {
            'minutes', 'minute' => $value * 60,
            'hours', 'hour' => $value * 3600,
            'days', 'day' => $value * 86400,
            'weeks', 'week' => $value * 604800,
            'seconds', 'second' => $value,
            default => $value * 60,
        };
    }
}
