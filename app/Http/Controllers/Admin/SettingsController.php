<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * @var array<string, array{group: string, type: string}>
     */
    protected array $definitions = [
        'amount_for_one_coin' => ['group' => 'payment', 'type' => 'float'],
        'reward_amount_per_coin' => ['group' => 'payment', 'type' => 'float'],
        'coins_expiry_months' => ['group' => 'payment', 'type' => 'integer'],
        'service_charges' => ['group' => 'fees', 'type' => 'float'],
        'payment_gateway_charges' => ['group' => 'fees', 'type' => 'float'],
        'gst_percent' => ['group' => 'fees', 'type' => 'float'],
        'openrouter_default_model' => ['group' => 'ai', 'type' => 'string'],
        'openrouter_fallback_model' => ['group' => 'ai', 'type' => 'string'],
        'pdf_render_density' => ['group' => 'pdf', 'type' => 'integer'],
    ];

    public function index(): View
    {
        return view('admin.settings.setting', [
            'values' => $this->formValues(),
        ]);
    }

    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'amount_for_one_coin' => 'required|numeric|min:0',
            'reward_amount_per_coin' => 'required|numeric|min:0',
            'coins_expiry_months' => 'required|integer|min:1|max:120',
            'service_charges' => 'required|numeric|min:0|max:100',
            'payment_gateway_charges' => 'required|numeric|min:0|max:100',
            'gst_percent' => 'required|numeric|min:0|max:100',
            'openrouter_default_model' => 'required|string|max:255',
            'openrouter_fallback_model' => 'required|string|max:255',
            'pdf_render_density' => 'required|integer|min:72|max:1200',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated as $key => $value) {
                $meta = $this->definitions[$key];

                Setting::set(
                    $key,
                    $value,
                    $meta['type'],
                    $meta['group']
                );
            }
        });

        Setting::clearCache();
        $this->refreshRuntimeConfig();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status'  => 200,
                'message' => 'Settings saved successfully',
                'data'    => $this->formValues(),
            ]);
        }

        return redirect()
            ->route('admin.settings.setting')
            ->with('success', 'Settings saved successfully');
    }

    /**
     * @return array<string, mixed>
     */
    protected function formValues(): array
    {
        $values = [];

        foreach (array_keys($this->definitions) as $key) {
            $configPath = $this->configPathForKey($key);
            $envDefault = $configPath ? config("settings.{$configPath}") : null;
            $values[$key] = Setting::get($key, $envDefault);
        }

        return $values;
    }

    protected function configPathForKey(string $key): ?string
    {
        return match ($key) {
            'amount_for_one_coin' => 'payment.amount_for_one_coin',
            'reward_amount_per_coin' => 'payment.reward_amount_per_coin',
            'coins_expiry_months' => 'payment.coins_expiry_months',
            'service_charges' => 'fees.service_charges',
            'payment_gateway_charges' => 'fees.payment_gateway_charges',
            'gst_percent' => 'fees.gst_percent',
            'openrouter_default_model' => 'ai.default_model',
            'openrouter_fallback_model' => 'ai.fallback_model',
            'pdf_render_density' => 'pdf.render_density',
            default => null,
        };
    }

    protected function refreshRuntimeConfig(): void
    {
        foreach ($this->definitions as $key => $meta) {
            unset($meta);
            $configPath = $this->configPathForKey($key);

            if ($configPath) {
                config(["settings.{$configPath}" => Setting::get($key, config("settings.{$configPath}"))]);
            }
        }
    }
}
