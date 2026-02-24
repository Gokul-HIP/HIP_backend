<div class="p-6 space-y-6">
    <div>
        <h2 class="text-lg font-semibold text-slate-800">Ad Engagement</h2>
        <p class="text-sm text-slate-500 mt-1">Impressions, clicks, and CTR.</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg border p-4">
            <div class="text-sm text-slate-500">Impressions</div>
            <div class="text-2xl font-bold text-slate-800">{{ number_format($totals['impressions']) }}</div>
        </div>
        <div class="bg-white rounded-lg border p-4">
            <div class="text-sm text-slate-500">Clicks</div>
            <div class="text-2xl font-bold text-slate-800">{{ number_format($totals['clicks']) }}</div>
        </div>
        <div class="bg-white rounded-lg border p-4">
            <div class="text-sm text-slate-500">CTR %</div>
            <div class="text-2xl font-bold text-slate-800">{{ $totals['ctr'] }}%</div>
        </div>
    </div>
    <a href="{{ route('admin.ads.ad-management.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-white" style="background:#0DA2E7;">Back to Ad Management</a>
</div>
