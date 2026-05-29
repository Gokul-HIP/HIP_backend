<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * Map config dot paths to setting keys.
     *
     * @var array<string, string>
     */
    protected array $configMap = [
        'payment.amount_for_one_coin' => 'amount_for_one_coin',
        'payment.reward_amount_per_coin' => 'reward_amount_per_coin',
        'payment.coins_expiry_months' => 'coins_expiry_months',
        'fees.service_charges' => 'service_charges',
        'fees.payment_gateway_charges' => 'payment_gateway_charges',
        'fees.gst_percent' => 'gst_percent',
        'ai.default_model' => 'openrouter_default_model',
        'ai.fallback_model' => 'openrouter_fallback_model',
        'pdf.render_density' => 'pdf_render_density',
    ];

    public function boot(): void
    {
        try {
            if (!Schema::hasTable('settings')) {
                return;
            }
        } catch (\Throwable) {
            return;
        }

        foreach ($this->configMap as $configPath => $settingKey) {
            $envDefault = config("settings.{$configPath}");
            config(["settings.{$configPath}" => Setting::get($settingKey, $envDefault)]);
        }
    }
}
