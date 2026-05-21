<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * @var array<string, array{value: mixed, type: string, group: string, env: array<int, string>}>
     */
    protected array $defaults = [
        'amount_for_one_coin' => [
            'value' => 100,
            'type' => 'float',
            'group' => 'payment',
            'env' => ['AMOUNT_FOR_ONE_COIN', 'AMOUNT_For_ONE_COIN'],
        ],
        'reward_amount_per_coin' => [
            'value' => 100,
            'type' => 'float',
            'group' => 'payment',
            'env' => ['REWARD_AMOUNT_PER_COIN'],
        ],
        'service_charges' => [
            'value' => 3,
            'type' => 'float',
            'group' => 'fees',
            'env' => ['SERVICE_CHARGES'],
        ],
        'payment_gateway_charges' => [
            'value' => 2,
            'type' => 'float',
            'group' => 'fees',
            'env' => ['PAYMENT_GATEWAY_CHARGES'],
        ],
        'gst_percent' => [
            'value' => 5,
            'type' => 'float',
            'group' => 'fees',
            'env' => ['GST_PERCENT'],
        ],
        'openrouter_default_model' => [
            'value' => 'openai/gpt-4o-mini',
            'type' => 'string',
            'group' => 'ai',
            'env' => ['OPENROUTER_DEFAULT_MODEL'],
        ],
        'openrouter_fallback_model' => [
            'value' => 'openrouter/auto',
            'type' => 'string',
            'group' => 'ai',
            'env' => ['OPENROUTER_FALLBACK_MODEL'],
        ],
        'pdf_render_density' => [
            'value' => 300,
            'type' => 'integer',
            'group' => 'pdf',
            'env' => ['PDF_RENDER_DENSITY'],
        ],
    ];

    public function run(): void
    {
        foreach ($this->defaults as $key => $meta) {
            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => (string) $this->resolveEnvValue($meta['env'], $meta['value']),
                    'type' => $meta['type'],
                    'group' => $meta['group'],
                ]
            );
        }

        Setting::clearCache();

        $this->command?->info('Settings seeded from .env defaults.');
    }

    protected function resolveEnvValue(array $envKeys, mixed $fallback): mixed
    {
        foreach ($envKeys as $envKey) {
            $value = env($envKey);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return $fallback;
    }
}
