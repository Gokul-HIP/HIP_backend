<?php

namespace App\Console\Commands;

use App\Models\HmsIntegration;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class RotateHmsIntegrationSecretCommand extends Command
{
    protected $signature = 'hms:integration:rotate-secret {id : Integration id}';

    protected $description = 'Replace an HMS integration signing secret and print the new secret once.';

    public function handle(): int
    {
        $integration = HmsIntegration::query()->find($this->argument('id'));
        if (! $integration) {
            $this->error('Integration was not found.');

            return self::FAILURE;
        }

        $secret = Str::random(48);
        $integration->signing_secret = $secret;
        $integration->save();

        $this->info('Signing secret rotated for integration #'.$integration->getKey().'.');
        $this->line('Signing secret: '.$secret);
        $this->warn('This secret is shown once. Store it now. Previous signatures are now invalid.');

        return self::SUCCESS;
    }
}
