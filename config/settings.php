<?php

/**
 * Static .env fallbacks (safe for config:cache).
 * At runtime, SettingsServiceProvider overlays DB values via app_setting().
 *
 * Usage: config('settings.fees.gst_percent') or app_setting('gst_percent')
 */

return [

    'payment' => [
        'amount_for_one_coin' => (float) env('AMOUNT_FOR_ONE_COIN', env('AMOUNT_For_ONE_COIN', 10)),
        'reward_amount_per_coin' => (float) env('REWARD_AMOUNT_PER_COIN', 10),
    ],

    'fees' => [
        'service_charges' => (float) env('SERVICE_CHARGES', 3),
        'payment_gateway_charges' => (float) env('PAYMENT_GATEWAY_CHARGES', 2),
        'gst_percent' => (float) env('GST_PERCENT', 5),
    ],

    'ai' => [
        'default_model' => env('OPENROUTER_DEFAULT_MODEL', 'openai/gpt-4o-mini'),
        'fallback_model' => env('OPENROUTER_FALLBACK_MODEL', 'openrouter/auto'),
    ],

    'pdf' => [
        'render_density' => (int) env('PDF_RENDER_DENSITY', 300),
    ],

];
