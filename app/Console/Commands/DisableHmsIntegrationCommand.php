<?php

namespace App\Console\Commands;

use App\Models\HmsIntegration;
use Illuminate\Console\Command;

class DisableHmsIntegrationCommand extends Command
{
    protected $signature = 'hms:integration:disable {id : Integration id}';

    protected $description = 'Disable an HMS integration.';

    public function handle(): int
    {
        return $this->setEnabled(false);
    }

    private function setEnabled(bool $enabled): int
    {
        $integration = HmsIntegration::query()->find($this->argument('id'));
        if (! $integration) {
            $this->error('Integration was not found.');

            return self::FAILURE;
        }

        $integration->is_enabled = $enabled;
        $integration->save();

        $this->info('Integration #'.$integration->getKey().' '.($enabled ? 'enabled' : 'disabled').'.');

        return self::SUCCESS;
    }
}
