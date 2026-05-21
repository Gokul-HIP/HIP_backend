<?php

namespace App\Services;

/**
 * Example usage of app settings (DB + .env fallback).
 *
 *   $gst = app_setting('gst_percent', config('settings.fees.gst_percent'));
 *   $coinValue = config('settings.payment.amount_for_one_coin');
 *   $pdfDpi = (int) app_setting('pdf_render_density', 300);
 */
class SettingsService
{
    public function coinValue(): float
    {
        return (float) app_setting(
            'amount_for_one_coin',
            config('settings.payment.amount_for_one_coin', 10)
        );
    }

    public function rewardPerCoin(): float
    {
        return (float) app_setting(
            'reward_amount_per_coin',
            config('settings.payment.reward_amount_per_coin', 10)
        );
    }

    public function gstPercent(): float
    {
        return (float) app_setting(
            'gst_percent',
            config('settings.fees.gst_percent', 5)
        );
    }

    public function openRouterDefaultModel(): string
    {
        return (string) app_setting(
            'openrouter_default_model',
            config('settings.ai.default_model', 'openai/gpt-4o-mini')
        );
    }

    public function pdfRenderDensity(): int
    {
        return (int) app_setting(
            'pdf_render_density',
            config('settings.pdf.render_density', 300)
        );
    }
}
