@extends('layouts.admin')

@section('title', 'Settings')
@section('breadcrumb', 'Dashboard / Settings')

@section('content')

<div class="py-6 px-4">

  <!-- Page Header -->
  <div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Global System Settings</h2>
    <p class="text-sm text-gray-500 mt-1">Manage core financial parameters, tax configurations, and system-wide AI models.</p>
  </div>

  <div class="space-y-5">

    <!-- Section 1: Coin & Rewards -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
      <div class="px-5 py-3.5 border-b border-gray-100 flex items-center gap-2.5">
        <div class="w-7 h-7 rounded-lg bg-[#e6f6fd] flex items-center justify-center shrink-0">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#0da2e7]" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm.31 14.71V18h-1.5v-1.33c-1.27-.26-2.31-1.06-2.38-2.42h1.46c.07.74.6 1.28 1.87 1.28 1.35 0 1.64-.67 1.64-1.08 0-.56-.29-1.08-1.87-1.48-1.77-.45-2.98-1.21-2.98-2.67 0-1.25.99-2.07 2.26-2.34V7h1.5v1.27c1.38.31 2.09 1.23 2.13 2.42H13c-.05-.78-.46-1.28-1.61-1.28-1.08 0-1.73.5-1.73 1.18 0 .58.45.95 1.87 1.32 1.41.38 2.98.99 2.98 2.83 0 1.32-.99 2.21-2.2 2.97z"/>
          </svg>
        </div>
        <h3 class="text-sm font-semibold text-gray-800">Coin &amp; Rewards</h3>
      </div>
      <div class="px-5 py-5">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Amount for One Coin</label>
            <div class="flex border border-gray-300 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-[#0da2e7]/40 focus-within:border-[#0da2e7] transition-all">
              <span class="flex items-center px-3 bg-gray-50 border-r border-gray-300 text-gray-500 font-semibold text-sm select-none">₹</span>
              <input type="number" name="coin_amount" placeholder="1.00" value="{{ old('coin_amount', $settings->coin_amount ?? '') }}" class="flex-1 px-3 py-2.5 outline-none text-sm text-gray-800 bg-white placeholder-gray-400" />
            </div>
            <p class="text-xs text-gray-400 mt-1.5">The rupee value assigned to a single loyalty coin.</p>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Reward Amount per Coin</label>
            <div class="flex border border-gray-300 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-[#0da2e7]/40 focus-within:border-[#0da2e7] transition-all">
              <span class="flex items-center px-3 bg-gray-50 border-r border-gray-300 text-gray-500 font-semibold text-sm select-none">₹</span>
              <input type="number" name="reward_amount" placeholder="0.10" value="{{ old('reward_amount', $settings->reward_amount ?? '') }}" class="flex-1 px-3 py-2.5 outline-none text-sm text-gray-800 bg-white placeholder-gray-400" />
            </div>
            <p class="text-xs text-gray-400 mt-1.5">Amount credited to user wallet when redeeming one coin.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Section 2: Fees & Taxes -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
      <div class="px-5 py-3.5 border-b border-gray-100 flex items-center gap-2.5">
        <div class="w-7 h-7 rounded-lg bg-[#e6f6fd] flex items-center justify-center shrink-0">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#0da2e7]" fill="currentColor" viewBox="0 0 24 24">
            <path d="M18 17H6v-2h12v2zm0-4H6v-2h12v2zm0-4H6V7h12v2zM3 22l1.5-1.5L6 22l1.5-1.5L9 22l1.5-1.5L12 22l1.5-1.5L15 22l1.5-1.5L18 22l1.5-1.5L21 22V2l-1.5 1.5L18 2l-1.5 1.5L15 2l-1.5 1.5L12 2l-1.5 1.5L9 2 7.5 3.5 6 2 4.5 3.5 3 2v20z"/>
          </svg>
        </div>
        <h3 class="text-sm font-semibold text-gray-800">Fees &amp; Taxes</h3>
      </div>
      <div class="px-5 py-5">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Service Charges</label>
            <div class="flex border border-gray-300 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-[#0da2e7]/40 focus-within:border-[#0da2e7] transition-all">
              <input type="number" name="service_charge" placeholder="10.0" value="{{ old('service_charge', $settings->service_charge ?? '') }}" class="flex-1 px-3 py-2.5 outline-none text-sm text-gray-800 bg-white placeholder-gray-400" />
              <span class="flex items-center px-3 bg-gray-50 border-l border-gray-300 text-gray-500 font-semibold text-sm select-none">%</span>
            </div>
            <p class="text-xs text-gray-400 mt-1.5">Internal platform fee per booking.</p>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Payment Gateway Charges</label>
            <div class="flex border border-gray-300 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-[#0da2e7]/40 focus-within:border-[#0da2e7] transition-all">
              <input type="number" name="gateway_charge" placeholder="2.5" value="{{ old('gateway_charge', $settings->gateway_charge ?? '') }}" class="flex-1 px-3 py-2.5 outline-none text-sm text-gray-800 bg-white placeholder-gray-400" />
              <span class="flex items-center px-3 bg-gray-50 border-l border-gray-300 text-gray-500 font-semibold text-sm select-none">%</span>
            </div>
            <p class="text-xs text-gray-400 mt-1.5">External processing fees (Stripe/Razorpay).</p>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">GST</label>
            <div class="flex border border-gray-300 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-[#0da2e7]/40 focus-within:border-[#0da2e7] transition-all">
              <input type="number" name="gst" placeholder="18.0" value="{{ old('gst', $settings->gst ?? '') }}" class="flex-1 px-3 py-2.5 outline-none text-sm text-gray-800 bg-white placeholder-gray-400" />
              <span class="flex items-center px-3 bg-gray-50 border-l border-gray-300 text-gray-500 font-semibold text-sm select-none">%</span>
            </div>
            <p class="text-xs text-gray-400 mt-1.5">Standard Goods and Services Tax applicable.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Section 3: Bento Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

      <!-- AI & Model Logic -->
      <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 border-b border-gray-100 flex items-center gap-2.5">
          <div class="w-7 h-7 rounded-lg bg-[#e6f6fd] flex items-center justify-center shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#0da2e7]" fill="currentColor" viewBox="0 0 24 24">
              <path d="M21 10.5h-1.5V9a.5.5 0 00-.5-.5h-1V7a3 3 0 00-3-3h-1V2.5a.5.5 0 00-1 0V4h-2V2.5a.5.5 0 00-1 0V4h-1a3 3 0 00-3 3v1.5H5.5A.5.5 0 005 9v1.5H3.5a.5.5 0 000 1H5v2H3.5a.5.5 0 000 1H5V16a3 3 0 003 3h1v1.5a.5.5 0 001 0V19h2v1.5a.5.5 0 001 0V19h1a3 3 0 003-3v-1.5h1.5a.5.5 0 00.5-.5v-3h1.5a.5.5 0 000-1zM9.5 14a1 1 0 110-2 1 1 0 010 2zm5 0a1 1 0 110-2 1 1 0 010 2z"/>
            </svg>
          </div>
          <h3 class="text-sm font-semibold text-gray-800">AI &amp; Model Logic</h3>
        </div>
        <div class="px-5 py-5">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            <!-- Default Model -->
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">OpenRouter Default Model</label>
              <div class="relative">
                <select name="default_model" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-[#0da2e7]/40 focus:border-[#0da2e7] bg-white text-sm text-gray-800 appearance-none transition-all pr-9">
                  <optgroup label="— OpenAI">
                    <option value="openai/gpt-4o-mini" {{ (old('default_model', $settings->default_model ?? 'openai/gpt-4o-mini') == 'openai/gpt-4o-mini') ? 'selected' : '' }}>GPT-4o Mini ⭐ (Current)</option>
                    <option value="openai/gpt-4o" {{ (old('default_model', $settings->default_model ?? '') == 'openai/gpt-4o') ? 'selected' : '' }}>GPT-4o</option>
                    <option value="openai/gpt-4-turbo" {{ (old('default_model', $settings->default_model ?? '') == 'openai/gpt-4-turbo') ? 'selected' : '' }}>GPT-4 Turbo</option>
                    <option value="openai/gpt-3.5-turbo" {{ (old('default_model', $settings->default_model ?? '') == 'openai/gpt-3.5-turbo') ? 'selected' : '' }}>GPT-3.5 Turbo</option>
                  </optgroup>
                  <optgroup label="— Anthropic">
                    <option value="anthropic/claude-3.5-sonnet" {{ (old('default_model', $settings->default_model ?? '') == 'anthropic/claude-3.5-sonnet') ? 'selected' : '' }}>Claude 3.5 Sonnet</option>
                    <option value="anthropic/claude-3-haiku" {{ (old('default_model', $settings->default_model ?? '') == 'anthropic/claude-3-haiku') ? 'selected' : '' }}>Claude 3 Haiku</option>
                    <option value="anthropic/claude-3-opus" {{ (old('default_model', $settings->default_model ?? '') == 'anthropic/claude-3-opus') ? 'selected' : '' }}>Claude 3 Opus</option>
                  </optgroup>
                  <optgroup label="— Google">
                    <option value="google/gemini-flash-1.5" {{ (old('default_model', $settings->default_model ?? '') == 'google/gemini-flash-1.5') ? 'selected' : '' }}>Gemini Flash 1.5</option>
                    <option value="google/gemini-pro-1.5" {{ (old('default_model', $settings->default_model ?? '') == 'google/gemini-pro-1.5') ? 'selected' : '' }}>Gemini Pro 1.5</option>
                  </optgroup>
                  <optgroup label="— Meta">
                    <option value="meta-llama/llama-3.1-70b-instruct" {{ (old('default_model', $settings->default_model ?? '') == 'meta-llama/llama-3.1-70b-instruct') ? 'selected' : '' }}>Llama 3.1 70B Instruct</option>
                    <option value="meta-llama/llama-3.1-8b-instruct" {{ (old('default_model', $settings->default_model ?? '') == 'meta-llama/llama-3.1-8b-instruct') ? 'selected' : '' }}>Llama 3.1 8B Instruct</option>
                  </optgroup>
                  <optgroup label="— Mistral">
                    <option value="mistralai/mistral-7b-instruct" {{ (old('default_model', $settings->default_model ?? '') == 'mistralai/mistral-7b-instruct') ? 'selected' : '' }}>Mistral 7B Instruct</option>
                    <option value="mistralai/mixtral-8x7b-instruct" {{ (old('default_model', $settings->default_model ?? '') == 'mistralai/mixtral-8x7b-instruct') ? 'selected' : '' }}>Mixtral 8x7B Instruct</option>
                  </optgroup>
                </select>
                <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </span>
              </div>
              <p class="text-xs text-gray-400 mt-1.5">Primary model for all AI tasks.</p>
            </div>

            <!-- Fallback Model -->
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">OpenRouter Fallback Model</label>
              <div class="relative">
                <select name="fallback_model" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-[#0da2e7]/40 focus:border-[#0da2e7] bg-white text-sm text-gray-800 appearance-none transition-all pr-9">
                  <optgroup label="— OpenRouter Smart">
                    <option value="openrouter/auto" {{ (old('fallback_model', $settings->fallback_model ?? 'openrouter/auto') == 'openrouter/auto') ? 'selected' : '' }}>Auto Router ⭐ (Current)</option>
                  </optgroup>
                  <optgroup label="— OpenAI">
                    <option value="openai/gpt-4o-mini" {{ (old('fallback_model', $settings->fallback_model ?? '') == 'openai/gpt-4o-mini') ? 'selected' : '' }}>GPT-4o Mini</option>
                    <option value="openai/gpt-3.5-turbo" {{ (old('fallback_model', $settings->fallback_model ?? '') == 'openai/gpt-3.5-turbo') ? 'selected' : '' }}>GPT-3.5 Turbo</option>
                  </optgroup>
                  <optgroup label="— Anthropic">
                    <option value="anthropic/claude-3-haiku" {{ (old('fallback_model', $settings->fallback_model ?? '') == 'anthropic/claude-3-haiku') ? 'selected' : '' }}>Claude 3 Haiku</option>
                    <option value="anthropic/claude-3.5-sonnet" {{ (old('fallback_model', $settings->fallback_model ?? '') == 'anthropic/claude-3.5-sonnet') ? 'selected' : '' }}>Claude 3.5 Sonnet</option>
                  </optgroup>
                  <optgroup label="— Google">
                    <option value="google/gemini-flash-1.5" {{ (old('fallback_model', $settings->fallback_model ?? '') == 'google/gemini-flash-1.5') ? 'selected' : '' }}>Gemini Flash 1.5</option>
                  </optgroup>
                  <optgroup label="— Meta">
                    <option value="meta-llama/llama-3.1-8b-instruct" {{ (old('fallback_model', $settings->fallback_model ?? '') == 'meta-llama/llama-3.1-8b-instruct') ? 'selected' : '' }}>Llama 3.1 8B Instruct</option>
                  </optgroup>
                  <optgroup label="— Mistral">
                    <option value="mistralai/mistral-7b-instruct" {{ (old('fallback_model', $settings->fallback_model ?? '') == 'mistralai/mistral-7b-instruct') ? 'selected' : '' }}>Mistral 7B Instruct</option>
                  </optgroup>
                </select>
                <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </span>
              </div>
              <p class="text-xs text-gray-400 mt-1.5">Used when default exceeds rate limits.</p>
            </div>

          </div>

          <!-- Info box -->
          <div class="mt-4 p-3.5 bg-[#f0f9ff] rounded-lg flex items-start gap-2.5 border border-[#bae6fd]">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#0da2e7] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-xs text-[#0369a1] leading-relaxed">The system will automatically switch to the Fallback Model if the Default Model exceeds latency thresholds or rate limits. <span class="font-semibold">openrouter/auto</span> intelligently routes to the best available model.</p>
          </div>
        </div>
      </div>

      <!-- PDF Rendering -->
      <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
        <div class="px-5 py-3.5 border-b border-gray-100 flex items-center gap-2.5">
          <div class="w-7 h-7 rounded-lg bg-[#e6f6fd] flex items-center justify-center shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#0da2e7]" fill="currentColor" viewBox="0 0 24 24">
              <path d="M20 2H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-8.5 7.5c0 .83-.67 1.5-1.5 1.5H9v2H7.5V7H10c.83 0 1.5.67 1.5 1.5v1zm5 2c0 .83-.67 1.5-1.5 1.5h-2.5V7H15c.83 0 1.5.67 1.5 1.5v3zm4-3H19v1h1.5V11H19v2h-1.5V7h3v1.5zM9 9.5h1v-1H9v1zM4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm10 5.5h1v-3h-1v3z"/>
            </svg>
          </div>
          <h3 class="text-sm font-semibold text-gray-800">PDF Rendering</h3>
        </div>
        <div class="px-5 py-5 flex flex-col flex-1 justify-between">
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Render Density</label>
            <div class="flex border border-gray-300 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-[#0da2e7]/40 focus-within:border-[#0da2e7] transition-all">
              <input type="number" name="pdf_dpi" value="{{ old('pdf_dpi', $settings->pdf_dpi ?? 300) }}" class="flex-1 px-3 py-2.5 outline-none text-sm text-gray-800 bg-white" />
              <span class="flex items-center px-3 bg-gray-50 border-l border-gray-300 text-gray-500 font-semibold text-xs select-none">DPI</span>
            </div>
            <p class="text-xs text-gray-400 mt-1.5">DPI setting for document generation.</p>
          </div>
          <div class="mt-5 flex items-center gap-2 p-3 bg-emerald-50 rounded-lg border border-emerald-100">
            <span class="relative flex h-2 w-2 shrink-0">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
            </span>
            <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Optimization: Active</span>
          </div>
        </div>
      </div>

    </div>

    <!-- Footer Actions -->
    <div class="flex items-center justify-end gap-3 pt-1 pb-4">
      <button type="button" onclick="window.location.reload()" class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-600 text-sm font-semibold hover:bg-gray-50 active:scale-95 transition-all">
        Reset to Defaults
      </button>
      <button type="submit" class="px-6 py-2.5 rounded-lg bg-[#0da2e7] hover:bg-[#0b8fcf] text-white font-semibold text-sm shadow-sm hover:shadow-md active:scale-95 transition-all flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
        </svg>
        Save Changes
      </button>
    </div>

  </div>
</div>

@endsection