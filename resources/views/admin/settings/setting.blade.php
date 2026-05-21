@extends('layouts.admin')

@section('title', 'Settings')
@section('breadcrumb', 'Dashboard / Settings')

@section('content')

@php
    $v = $values ?? [];
@endphp

<div class="py-6 px-4">

  <div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Global System Settings</h2>
    <p class="text-sm text-gray-500 mt-1">Manage core financial parameters, tax configurations, and system-wide AI models. Values are stored in the database with <code class="text-xs bg-gray-100 px-1 rounded">.env</code> fallbacks.</p>
  </div>

  @if ($errors->any())
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      <ul class="list-disc list-inside space-y-1">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form id="settings-form" method="POST" action="{{ route('admin.settings.update') }}" class="space-y-5">
    @csrf

    <!-- Coin & Rewards -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
      <div class="px-5 py-3.5 border-b border-gray-100 flex items-center gap-2.5">
        <div class="w-7 h-7 rounded-lg bg-[#e6f6fd] flex items-center justify-center shrink-0">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#0da2e7]" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm.31 14.71V18h-1.5v-1.33c-1.27-.26-2.31-1.06-2.38-2.42h1.46c.07.74.6 1.28 1.87 1.28 1.35 0 1.64-.67 1.64-1.08 0-.56-.29-1.08-1.87-1.48-1.77-.45-2.98-1.21-2.98-2.67 0-1.25.99-2.07 2.26-2.34V7h1.5v1.27c1.38.31 2.09 1.23 2.13 2.42H13c-.05-.78-.46-1.28-1.61-1.28-1.08 0-1.73.5-1.73 1.18 0 .58.45.95 1.87 1.32 1.41.38 2.98.99 2.98 2.83 0 1.32-.99 2.21-2.2 2.97z"/></svg>
        </div>
        <h3 class="text-sm font-semibold text-gray-800">Coin &amp; Rewards</h3>
      </div>
      <div class="px-5 py-5">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Amount for One Coin</label>
            <div class="flex border border-gray-300 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-[#0da2e7]/40 focus-within:border-[#0da2e7] transition-all">
              <span class="flex items-center px-3 bg-gray-50 border-r border-gray-300 text-gray-500 font-semibold text-sm select-none">₹</span>
              <input type="number" step="0.01" name="amount_for_one_coin" value="{{ old('amount_for_one_coin', $v['amount_for_one_coin'] ?? '') }}" class="flex-1 px-3 py-2.5 outline-none text-sm text-gray-800 bg-white" required />
            </div>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Reward Amount per Coin</label>
            <div class="flex border border-gray-300 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-[#0da2e7]/40 focus-within:border-[#0da2e7] transition-all">
              <span class="flex items-center px-3 bg-gray-50 border-r border-gray-300 text-gray-500 font-semibold text-sm select-none">₹</span>
              <input type="number" step="0.01" name="reward_amount_per_coin" value="{{ old('reward_amount_per_coin', $v['reward_amount_per_coin'] ?? '') }}" class="flex-1 px-3 py-2.5 outline-none text-sm text-gray-800 bg-white" required />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Fees & Taxes -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
      <div class="px-5 py-3.5 border-b border-gray-100 flex items-center gap-2.5">
        <div class="w-7 h-7 rounded-lg bg-[#e6f6fd] flex items-center justify-center shrink-0">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#0da2e7]" fill="currentColor" viewBox="0 0 24 24"><path d="M18 17H6v-2h12v2zm0-4H6v-2h12v2zm0-4H6V7h12v2zM3 22l1.5-1.5L6 22l1.5-1.5L9 22l1.5-1.5L12 22l1.5-1.5L15 22l1.5-1.5L18 22l1.5-1.5L21 22V2l-1.5 1.5L18 2l-1.5 1.5L15 2l-1.5 1.5L12 2l-1.5 1.5L9 2 7.5 3.5 6 2 4.5 3.5 3 2v20z"/></svg>
        </div>
        <h3 class="text-sm font-semibold text-gray-800">Fees &amp; Taxes</h3>
      </div>
      <div class="px-5 py-5">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Service Charges</label>
            <div class="flex border border-gray-300 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-[#0da2e7]/40 focus-within:border-[#0da2e7] transition-all">
              <input type="number" step="0.01" name="service_charges" value="{{ old('service_charges', $v['service_charges'] ?? '') }}" class="flex-1 px-3 py-2.5 outline-none text-sm text-gray-800 bg-white" required />
              <span class="flex items-center px-3 bg-gray-50 border-l border-gray-300 text-gray-500 font-semibold text-sm select-none">%</span>
            </div>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Payment Gateway Charges</label>
            <div class="flex border border-gray-300 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-[#0da2e7]/40 focus-within:border-[#0da2e7] transition-all">
              <input type="number" step="0.01" name="payment_gateway_charges" value="{{ old('payment_gateway_charges', $v['payment_gateway_charges'] ?? '') }}" class="flex-1 px-3 py-2.5 outline-none text-sm text-gray-800 bg-white" required />
              <span class="flex items-center px-3 bg-gray-50 border-l border-gray-300 text-gray-500 font-semibold text-sm select-none">%</span>
            </div>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">GST</label>
            <div class="flex border border-gray-300 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-[#0da2e7]/40 focus-within:border-[#0da2e7] transition-all">
              <input type="number" step="0.01" name="gst_percent" value="{{ old('gst_percent', $v['gst_percent'] ?? '') }}" class="flex-1 px-3 py-2.5 outline-none text-sm text-gray-800 bg-white" required />
              <span class="flex items-center px-3 bg-gray-50 border-l border-gray-300 text-gray-500 font-semibold text-sm select-none">%</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

      <!-- AI -->
      <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 border-b border-gray-100 flex items-center gap-2.5">
          <h3 class="text-sm font-semibold text-gray-800">AI &amp; Model Logic</h3>
        </div>
        <div class="px-5 py-5">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">OpenRouter Default Model</label>
              <select name="openrouter_default_model" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-[#0da2e7]/40 focus:border-[#0da2e7] bg-white text-sm" required>
                @php $defaultModel = old('openrouter_default_model', $v['openrouter_default_model'] ?? 'openai/gpt-4o-mini'); @endphp
                <optgroup label="OpenAI">
                  <option value="openai/gpt-4o-mini" @selected($defaultModel === 'openai/gpt-4o-mini')>GPT-4o Mini</option>
                  <option value="openai/gpt-4o" @selected($defaultModel === 'openai/gpt-4o')>GPT-4o</option>
                  <option value="openai/gpt-4-turbo" @selected($defaultModel === 'openai/gpt-4-turbo')>GPT-4 Turbo</option>
                </optgroup>
                <optgroup label="Anthropic">
                  <option value="anthropic/claude-3.5-sonnet" @selected($defaultModel === 'anthropic/claude-3.5-sonnet')>Claude 3.5 Sonnet</option>
                  <option value="anthropic/claude-3-haiku" @selected($defaultModel === 'anthropic/claude-3-haiku')>Claude 3 Haiku</option>
                </optgroup>
                <optgroup label="Google">
                  <option value="google/gemini-flash-1.5" @selected($defaultModel === 'google/gemini-flash-1.5')>Gemini Flash 1.5</option>
                </optgroup>
                <optgroup label="Meta">
                  <option value="meta-llama/llama-3.1-70b-instruct" @selected($defaultModel === 'meta-llama/llama-3.1-70b-instruct')>Llama 3.1 70B</option>
                </optgroup>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">OpenRouter Fallback Model</label>
              <select name="openrouter_fallback_model" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-[#0da2e7]/40 focus:border-[#0da2e7] bg-white text-sm" required>
                @php $fallbackModel = old('openrouter_fallback_model', $v['openrouter_fallback_model'] ?? 'openrouter/auto'); @endphp
                <option value="openrouter/auto" @selected($fallbackModel === 'openrouter/auto')>Auto Router</option>
                <option value="openai/gpt-4o-mini" @selected($fallbackModel === 'openai/gpt-4o-mini')>GPT-4o Mini</option>
                <option value="anthropic/claude-3-haiku" @selected($fallbackModel === 'anthropic/claude-3-haiku')>Claude 3 Haiku</option>
                <option value="google/gemini-flash-1.5" @selected($fallbackModel === 'google/gemini-flash-1.5')>Gemini Flash 1.5</option>
              </select>
            </div>
          </div>
          <p class="text-xs text-[#0369a1] mt-4 p-3 bg-[#f0f9ff] rounded-lg border border-[#bae6fd]">API keys stay in <code class="text-xs">.env</code> only — not stored in the database.</p>
        </div>
      </div>

      <!-- PDF -->
      <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
        <div class="px-5 py-3.5 border-b border-gray-100">
          <h3 class="text-sm font-semibold text-gray-800">PDF Rendering</h3>
        </div>
        <div class="px-5 py-5 flex-1">
          <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Render Density</label>
          <div class="flex border border-gray-300 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-[#0da2e7]/40 focus-within:border-[#0da2e7] transition-all">
            <input type="number" name="pdf_render_density" value="{{ old('pdf_render_density', $v['pdf_render_density'] ?? 300) }}" class="flex-1 px-3 py-2.5 outline-none text-sm text-gray-800 bg-white" required />
            <span class="flex items-center px-3 bg-gray-50 border-l border-gray-300 text-gray-500 font-semibold text-xs select-none">DPI</span>
          </div>
        </div>
      </div>

    </div>

    <div class="flex items-center justify-end gap-3 pt-1 pb-4">
      <button type="button" onclick="window.location.reload()" class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-600 text-sm font-semibold hover:bg-gray-50 transition-all">
        Reset Form
      </button>
      <button type="submit" id="settings-save-btn" class="px-6 py-2.5 rounded-lg bg-[#0da2e7] hover:bg-[#0b8fcf] text-white font-semibold text-sm shadow-sm flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        Save Changes
      </button>
    </div>
  </form>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('settings-form');
    const saveBtn = document.getElementById('settings-save-btn');

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        saveBtn.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new FormData(form),
            });

            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.message || 'Failed to save settings');
            }

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: payload.message || 'Settings saved',
                    showConfirmButton: false,
                    timer: 2500,
                });
            }
        } catch (err) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: err.message || 'Save failed',
                    showConfirmButton: false,
                    timer: 3000,
                });
            } else {
                alert(err.message || 'Save failed');
            }
        } finally {
            saveBtn.disabled = false;
        }
    });
});
</script>
@endpush
