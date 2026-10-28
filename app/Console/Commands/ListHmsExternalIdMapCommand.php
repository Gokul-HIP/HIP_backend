<?php

namespace App\Console\Commands;

use App\Models\HmsExternalIdMap;
use App\Models\HmsIntegration;
use Illuminate\Console\Command;

class ListHmsExternalIdMapCommand extends Command
{
    protected $signature = 'hms:map:list {integration_id : Integration id}';

    protected $description = 'List external id mappings for an HMS integration.';

    public function handle(): int
    {
        $integration = HmsIntegration::query()->find($this->argument('integration_id'));
        if (! $integration) {
            $this->error('Integration was not found.');

            return self::FAILURE;
        }

        $maps = HmsExternalIdMap::query()
            ->where('integration_id', $integration->getKey())
            ->orderBy('entity_type')
            ->orderBy('external_id')
            ->get(['entity_type', 'external_id', 'local_id']);

        if ($maps->isEmpty()) {
            $this->info('No external ids for integration #'.$integration->getKey().'.');

            return self::SUCCESS;
        }

        $this->table(
            ['Entity', 'External id', 'Local id'],
            $maps->map(fn (HmsExternalIdMap $map) => [
                $map->entity_type,
                $map->external_id,
                $map->local_id,
            ])->all()
        );

        return self::SUCCESS;
    }
}
