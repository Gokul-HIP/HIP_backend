<?php

namespace App\Console\Commands;

use App\Models\HmsIntegration;
use Illuminate\Console\Command;

class EnableHmsIntegrationCommand extends Command
{
    protected $signature = 'hms:integration:enable {id : Integration id}';

    protected $description = 'Enable an HMS integration.';

    public function handle(): int
    {
        $integration = HmsIntegration::query()->find($this->argument('id'));
        if (! $integration) {
            $this->error('Integration was not found.');

            return self::FAILURE;
        }

        $integration->is_enabled = true;
        $integration->save();

        $this->info('Integration #'.$integration->getKey().' enabled.');

        return self::SUCCESS;
    }
}
