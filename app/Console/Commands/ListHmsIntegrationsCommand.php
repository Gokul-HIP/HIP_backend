<?php

namespace App\Console\Commands;

use App\Models\HmsIntegration;
use Illuminate\Console\Command;

class ListHmsIntegrationsCommand extends Command
{
    protected $signature = 'hms:integration:list';

    protected $description = 'List HMS integrations without keys or secrets.';

    public function handle(): int
    {
        $integrations = HmsIntegration::query()
            ->orderBy('id')
            ->get([
                'id',
                'name',
                'is_enabled',
                'hospital_ids',
                'organization_id',
                'rate_limit_per_minute',
                'last_used_at',
                'allowed_ips',
            ]);

        if ($integrations->isEmpty()) {
            $this->info('No HMS integrations.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Enabled', 'Hospitals', 'Organization', 'Rate', 'Last used', 'IPs'],
            $integrations->map(fn (HmsIntegration $integration) => [
                $integration->getKey(),
                $integration->name,
                $integration->is_enabled ? 'yes' : 'no',
                implode(',', $integration->hospital_ids ?? []) ?: '—',
                $integration->organization_id ?? '—',
                $integration->rate_limit_per_minute,
                $integration->last_used_at?->toDateTimeString() ?? '—',
                implode(',', $integration->allowed_ips ?? []) ?: '—',
            ])->all()
        );

        return self::SUCCESS;
    }
}
