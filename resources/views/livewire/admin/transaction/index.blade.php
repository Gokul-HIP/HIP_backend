<div class="space-y-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Transaction Report</h1>
            <p class="text-sm text-slate-500 mt-0.5">View and track transactions across all organizations</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <div class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg bg-white border border-slate-200 shadow-sm text-slate-700">
                <input type="date" wire:model.live="fromDate" class="outline-none bg-transparent text-slate-700">
                <span class="text-slate-400">to</span>
                <input type="date" wire:model.live="toDate" class="outline-none bg-transparent text-slate-700">
            </div>
            <a
                href="{{ action(\App\Http\Controllers\Admin\TransactionsExportController::class, ['search' => $search, 'service_type_filter' => $serviceTypeFilter, 'status_filter' => $statusFilter, 'hospital_filter' => $hospitalFilter, 'organization_filter' => $organizationFilter, 'from_date' => $fromDate, 'to_date' => $toDate]) }}"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg text-white shadow-sm transition-all hover:opacity-90"
                style="background:var(--button-color); hover:background:var(--button-hover);":
            >
                Download Report
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <p class="text-sm font-medium text-slate-500">Total Payments Received</p>
            <h3 class="text-2xl font-bold text-slate-900 mt-1 tracking-tight">Rs{{ number_format($stats['total_received'], 2) }}</h3>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <p class="text-sm font-medium text-slate-500">Total Transactions</p>
            <h3 class="text-2xl font-bold text-slate-900 mt-1 tracking-tight">{{ number_format($stats['total_transactions']) }}</h3>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <p class="text-sm font-medium text-slate-500">Successful Payments</p>
            <h3 class="text-2xl font-bold text-slate-900 mt-1 tracking-tight">{{ number_format($stats['successful']) }}</h3>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <p class="text-sm font-medium text-slate-500">Pending Payments</p>
            <h3 class="text-2xl font-bold text-slate-900 mt-1 tracking-tight">{{ number_format($stats['pending']) }}</h3>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-7 gap-4 items-end">
            <div class="lg:col-span-2">
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Search</label>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Organization / Hospital / Member / Transaction..."
                    class="w-full px-3 py-2 text-sm rounded-lg outline-none"
                    style="background:#f8fafc; border:1px solid #f1f5f9;"
                >
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Organization</label>
                <select wire:model.live="organizationFilter" class="w-full px-3 py-2 text-sm rounded-lg outline-none" style="background:#fff; border:1px solid #dbe3ef;">
                    <option value="all">All Organizations</option>
                    @foreach($availableOrganizations as $organization)
                        <option value="{{ $organization->id }}">{{ $organization->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Hospital</label>
                <select wire:model.live="hospitalFilter" class="w-full px-3 py-2 text-sm rounded-lg outline-none" style="background:#fff; border:1px solid #dbe3ef;">
                    <option value="all">All Hospitals</option>
                    @foreach($availableHospitals as $hospital)
                        <option value="{{ $hospital->id }}">{{ $hospital->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Service Type</label>
                <select wire:model.live="serviceTypeFilter" class="w-full px-3 py-2 text-sm rounded-lg outline-none" style="background:#fff; border:1px solid #dbe3ef;">
                    @foreach($serviceTypeOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Status</label>
                <select wire:model.live="statusFilter" class="w-full px-3 py-2 text-sm rounded-lg outline-none" style="background:#fff; border:1px solid #dbe3ef;">
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="button" wire:click="resetFilters" class="px-4 py-2 text-sm font-medium text-slate-600 rounded-lg transition-all hover:bg-slate-200" style="background:#f1f5f9;">Reset</button>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr style="background:#f8fafc; border-bottom:1px solid #f1f5f9;">
                        <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider whitespace-nowrap">Payment ID</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider whitespace-nowrap">Member</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider whitespace-nowrap">Service</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider whitespace-nowrap">Organization</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider whitespace-nowrap">Hospital</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider whitespace-nowrap">Amount</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider whitespace-nowrap">Method</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider whitespace-nowrap">Status</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider whitespace-nowrap">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($transactions as $transaction)
                        @php
                            $statusStyles = match($transaction['status']) {
                                'completed' => 'background:#f0fdf4; color:#16a34a;',
                                'pending' => 'background:#fffbeb; color:#d97706;',
                                'failed', 'cancelled' => 'background:#fff1f2; color:#e11d48;',
                                'refunded' => 'background:#eef2ff; color:#4f46e5;',
                                default => 'background:#f1f5f9; color:#475569;',
                            };
                            $dotColor = match($transaction['status']) {
                                'completed' => '#16a34a',
                                'pending' => '#d97706',
                                'failed', 'cancelled' => '#e11d48',
                                'refunded' => '#4f46e5',
                                default => '#475569',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 text-sm font-semibold text-slate-700">{{ $transaction['payment_id'] }}</td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-slate-800 whitespace-nowrap">{{ $transaction['member_name'] }}</div>
                                <div class="text-xs text-slate-400 whitespace-nowrap">{{ $transaction['member_id'] }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-500">{{ $transaction['service_summary'] }}</td>
                            <td class="px-6 py-4 text-sm text-slate-500">{{ $transaction['organization_name'] }}</td>
                            <td class="px-6 py-4 text-sm text-slate-500">{{ $transaction['hospital_name'] }}</td>
                            <td class="px-6 py-4 text-sm font-bold text-slate-800">Rs{{ number_format($transaction['amount'], 2) }}</td>
                            <td class="px-6 py-4 text-sm text-slate-500 whitespace-nowrap">{{ $transaction['payment_method'] }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wide" style="{{ $statusStyles }}">
                                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background:{{ $dotColor }};"></span>{{ $transaction['status_label'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-500 whitespace-nowrap">{{ $transaction['created_at'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-10 text-sm text-slate-500 text-center">No transactions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 px-6 py-4" style="border-top:1px solid #f1f5f9;">
            <p class="text-sm text-slate-500 font-medium">Showing {{ $transactions->firstItem() ?? 0 }}-{{ $transactions->lastItem() ?? 0 }} of {{ $transactions->total() }} entries</p>
            <div>{{ $transactions->links() }}</div>
        </div>
    </div>
</div>
