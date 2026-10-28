<?php

namespace Database\Seeders;

use App\Models\HmsIntegration;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Local sandbox credentials only. Refuses to run when APP_ENV is production.
 *
 * php artisan db:seed --class=HmsSandboxIntegrationSeeder
 */
class HmsSandboxIntegrationSeeder extends Seeder
{
    public const NAME = 'HMS Sandbox';

    public const API_KEY = 'hms_sandbox_key';

    public const SIGNING_SECRET = 'hms_sandbox_secret';

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Refusing to seed the HMS sandbox integration in production.');
        }

        $organizationId = Schema::hasTable('organizations')
            ? DB::table('organizations')->orderBy('id')->value('id')
            : null;

        $hospitalIds = Schema::hasTable('hospitals')
            ? DB::table('hospitals')->orderBy('id')->limit(50)->pluck('id')->map(fn ($id) => (int) $id)->values()->all()
            : [];

        HmsIntegration::query()->updateOrCreate(
            ['name' => self::NAME],
            [
                'hospital_ids' => $hospitalIds,
                'organization_id' => $organizationId,
                'api_key_hash' => HmsIntegration::hashKey(self::API_KEY),
                'signing_secret' => self::SIGNING_SECRET,
                'allowed_ips' => null,
                'is_enabled' => true,
                'rate_limit_per_minute' => 120,
            ]
        );

        $this->command?->info('HMS sandbox integration is ready.');
        $this->command?->line('API key: '.self::API_KEY);
        $this->command?->line('Signing secret: '.self::SIGNING_SECRET);
        $this->command?->line('Hospitals in scope: '.($hospitalIds === [] ? '(none)' : implode(', ', $hospitalIds)));
    }
}
