<?php

namespace App\Services\Cron;

use Cron\CronExpression;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class CronSchedule
{
    public function presets(): array
    {
        $options = [];

        foreach (config('cron.schedule_presets', []) as $key => $meta) {
            $options[$key] = $meta['label'] ?? $key;
        }

        return $options;
    }

    public function expressionFor(string $scheduleType, ?string $customExpression = null): string
    {
        if ($scheduleType === 'custom') {
            $expression = trim((string) $customExpression);
            $this->assertValid($expression);

            return $expression;
        }

        $preset = config('cron.schedule_presets.'.$scheduleType);
        $expression = $preset['expression'] ?? null;

        if (! is_string($expression) || $expression === '') {
            throw new InvalidArgumentException('Unknown schedule type.');
        }

        $this->assertValid($expression);

        return $expression;
    }

    public function assertValid(string $expression): void
    {
        if (! $this->isValid($expression)) {
            throw new InvalidArgumentException('Invalid cron expression.');
        }
    }

    public function isValid(string $expression): bool
    {
        $expression = trim($expression);

        if ($expression === '' || substr_count($expression, ' ') < 4) {
            return false;
        }

        try {
            new CronExpression($expression);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function isDue(string $expression, DateTimeInterface $now, string $timezone): bool
    {
        $this->assertValid($expression);

        $at = Carbon::instance(\DateTime::createFromInterface($now))->timezone($timezone);

        return (new CronExpression($expression))->isDue($at->toDateTimeString(), $timezone);
    }

    public function nextRunAt(string $expression, DateTimeInterface $now, string $timezone): Carbon
    {
        $this->assertValid($expression);

        $at = Carbon::instance(\DateTime::createFromInterface($now))->timezone($timezone);
        $next = (new CronExpression($expression))->getNextRunDate($at->toDateTimeString(), 0, false, $timezone);

        return Carbon::instance($next)->timezone($timezone);
    }

    public function label(string $scheduleType, string $expression): string
    {
        if ($scheduleType !== 'custom') {
            return (string) (config('cron.schedule_presets.'.$scheduleType.'.label') ?? $expression);
        }

        return 'Custom: '.$expression;
    }
}
