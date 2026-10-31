<?php

namespace App\Console\Commands;

use App\Models\HmsIntegration;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateHmsIntegrationCommand extends Command
{
    protected $signature = 'hms:integration:create
        {name : Display name}
        {--hospitals=1,2 : Comma-separated hospital ids}
        {--organization= : Organization id}
        {--ips= : Comma-separated IP allow list}
        {--rate=120 : Requests per minute}';

    protected $description = 'Create an HMS integration and print its API key and signing secret once.';

    public function handle(): int
    {
        $name = trim((string) $this->argument('name'));
        if ($name === '') {
            $this->error('A name is required.');

            return self::FAILURE;
        }

        $rate = (int) $this->option('rate');
        if ($rate < 1) {
            $this->error('Rate must be at least 1.');

            return self::FAILURE;
        }

        $apiKey = 'hms_'.Str::random(40);
        $secret = Str::random(48);
        $organization = trim((string) $this->option('organization'));

        $integration = HmsIntegration::query()->create([
            'name' => $name,
            'hospital_ids' => $this->integerList((string) $this->option('hospitals')),
            'organization_id' => $organization === '' ? null : (int) $organization,
            'api_key_hash' => HmsIntegration::hashKey($apiKey),
            'signing_secret' => $secret,
            'allowed_ips' => $this->stringList((string) $this->option('ips')),
            'is_enabled' => true,
            'rate_limit_per_minute' => $rate,
        ]);

        $this->info('Integration #'.$integration->getKey().' created.');
        $this->line('API key: '.$apiKey);
        $this->line('Signing secret: '.$secret);
        $this->warn('These credentials are shown once. Store them now.');

        return self::SUCCESS;
    }

    /**
     * @return list<int>
     */
    private function integerList(string $value): array
    {
        return collect(explode(',', $value))
            ->map(fn ($part) => (int) trim($part))
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<string>|null
     */
    private function stringList(string $value): ?array
    {
        $items = collect(explode(',', $value))
            ->map(fn ($part) => trim($part))
            ->filter(fn (string $part) => $part !== '')
            ->unique()
            ->values()
            ->all();

        return $items === [] ? null : $items;
    }
}
