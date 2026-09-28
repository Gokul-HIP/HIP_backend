<?php

namespace App\Services\Cron;

use InvalidArgumentException;

class CronCommandRegistry
{
    public function all(): array
    {
        return config('cron.commands', []);
    }

    public function options(): array
    {
        $options = [];

        foreach ($this->all() as $command => $meta) {
            $label = ($meta['name'] ?? $command).' ('.$command.')';
            $options[$command] = $label;
        }

        return $options;
    }

    public function isApproved(string $command): bool
    {
        return array_key_exists($command, $this->all()) && $this->isSafeIdentifier($command);
    }

    public function assertApproved(string $command): void
    {
        if (! $this->isApproved($command)) {
            throw new InvalidArgumentException('Command is not in the approved cron catalog.');
        }
    }

    public function label(string $command): string
    {
        return $this->all()[$command]['name'] ?? $command;
    }

    public function description(string $command): ?string
    {
        return $this->all()[$command]['description'] ?? null;
    }

    /**
     * Command names stored in the database must be a single Artisan identifier.
     */
    public function isSafeIdentifier(string $command): bool
    {
        return (bool) preg_match('/^[a-z0-9][a-z0-9:_-]*$/i', $command)
            && ! str_contains($command, ' ')
            && ! str_contains($command, ';')
            && ! str_contains($command, '|')
            && ! str_contains($command, '&')
            && ! str_contains($command, '`');
    }
}
