{{-- livewire/admin/payment/payment-report.blade.php --}}
<div class="space-y-8">

    <style>
        .hip-pay-menu {
            position: fixed;
            z-index: 9999;
            width: 11rem;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
            overflow: hidden;
        }
        .hip-pay-item {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.65rem 1rem;
            font-size: 0.875rem;
            color: #475569;
            text-decoration: none;
            background: transparent;
            border-bottom: 1px solid #f8fafc;
            transition: background .1s;
            cursor: pointer;
            width: 100%; text-align: left; border-left: none; border-right: none; border-top: none;
        }
        .hip-pay-item:last-child { border-bottom: none; }
        .hip-pay-item:hover { background: #f8fafc; }
        .hip-pay-btn { background: transparent; border: none; cursor: pointer; }
        .hip-select-wrap {
            position: relative;
        }
        .hip-filter-button {
            width: 100%;
            padding: 0.7rem 2.5rem 0.7rem 0.9rem;
            font-size: 0.875rem;
            color: #0f172a;
            background: #fff;
            border: 1px solid #dbe3ef;
            border-radius: 0.85rem;
            outline: none;
            appearance: none;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.05);
            transition: border-color .12s ease, box-shadow .12s ease, background .12s ease;
            text-align: left;
        }
        .hip-filter-button:hover {
            background: #f8fafc;
        }
        .hip-filter-button:focus {
            border-color: #7dd3fc;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.14);
            background: #fff;
        }
        .hip-select-icon {
            position: absolute;
            right: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
            font-size: 0.75rem;
        }
        .hip-filter-menu {
            position: absolute;
            top: calc(100% + 0.45rem);
            left: 0;
            z-index: 30;
            width: 100%;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 0.9rem;
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.14);
            padding: 0.45rem;
        }
        .hip-filter-item {
            display: block;
            width: 100%;
            padding: 0.75rem 0.9rem;
            border: 0;
            border-radius: 0.65rem;
            background: transparent;
            color: #0f172a;
            font-size: 0.9rem;
            text-align: left;
            transition: background .12s ease, color .12s ease;
        }
        .hip-filter-item:hover {
            background: #f8fafc;
        }
        .hip-filter-item.active {
            background: #e8f1fb;
            color: var(--button-color);
            font-weight: 600;
        }
    </style>

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Payment Report</h1>
            <p class="text-sm text-slate-500 mt-0.5">View and track all payments across hospital services</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <div class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg bg-white border border-slate-200 shadow-sm text-slate-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <input type="date" wire:model.live="fromDate" class="outline-none bg-transparent text-slate-700">
                <span class="text-slate-400">to</span>
                <input type="date" wire:model.live="toDate" class="outline-none bg-transparent text-slate-700">
            </div>
            <a
                href="{{ route('healthcare.transactions.export', ['search' => $search, 'service_type_filter' => $serviceTypeFilter, 'status_filter' => $statusFilter, 'hospital_filter' => $hospitalFilter, 'from_date' => $fromDate, 'to_date' => $toDate]) }}"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg text-white shadow-sm transition-all hover:opacity-90"
                style="background:var(--button-color);"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Download Report
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#eff6ff;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" style="color:#2563eb;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-sm font-medium text-slate-500">Total Payments Received</p>
            <h3 class="text-2xl font-bold text-slate-900 mt-1 tracking-tight">Rs{{ number_format($stats['total_received'], 2) }}</h3>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#eef2ff;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" style="color:#4f46e5;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                </div>
            </div>
            <p class="text-sm font-medium text-slate-500">Total Transactions</p>
            <h3 class="text-2xl font-bold text-slate-900 mt-1 tracking-tight">{{ number_format($stats['total_transactions']) }}</h3>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#f0fdf4;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" style="color:#16a34a;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-sm font-medium text-slate-500">Successful Payments</p>
            <h3 class="text-2xl font-bold text-slate-900 mt-1 tracking-tight">{{ number_format($stats['successful']) }}</h3>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#fffbeb;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" style="color:#d97706;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-sm font-medium text-slate-500">Pending Payments</p>
            <h3 class="text-2xl font-bold text-slate-900 mt-1 tracking-tight">{{ number_format($stats['pending']) }}</h3>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 items-end">
            <div class="lg:col-span-2">
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Search Member</label>
                <div class="relative">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35"/>
                    </svg>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Member Name / ID..."
                        class="w-full pl-9 pr-4 py-2 text-sm rounded-lg outline-none transition-all"
                        style="background:#f8fafc; border:1px solid #f1f5f9;"
                        onfocus="this.style.background='#fff'; this.style.borderColor='#bae6fd'; this.style.boxShadow='0 0 0 2px rgba(14,165,233,0.15)';"
                        onblur="this.style.background='#f8fafc'; this.style.borderColor='#f1f5f9'; this.style.boxShadow='none';"
                    >
                </div>
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
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Hospital</label>
                <select wire:model.live="hospitalFilter" class="w-full px-3 py-2 text-sm rounded-lg outline-none" style="background:#fff; border:1px solid #dbe3ef;">
                    <option value="all">All Hospitals</option>
                    @foreach($availableHospitals as $hospital)
                        <option value="{{ $hospital->id }}">{{ $hospital->name }}</option>
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
                        <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider whitespace-nowrap">Member Name</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider whitespace-nowrap">Service Type</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider whitespace-nowrap">Hospital</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider whitespace-nowrap">Amount</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider whitespace-nowrap">Method</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider whitespace-nowrap">Status</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider whitespace-nowrap">Date</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-right whitespace-nowrap">Actions</th>
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
                                <div class="flex items-center gap-3">
                                    @if(!empty($transaction['member_image']))
                                        <img src="{{ $transaction['member_image'] }}"
                                            alt="{{ $transaction['member_name'] }}"
                                            class="w-8 h-8 rounded-full object-cover flex-shrink-0 border border-slate-200">
                                    @else
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs flex-shrink-0" style="background:#dbeafe; color:#2563eb;">{{ $transaction['initials'] ?: 'NA' }}</div>
                                    @endif
                                    <div>
                                        <div class="text-sm font-medium text-slate-800 whitespace-nowrap">{{ $transaction['member_name'] }}</div>
                                        <div class="text-xs text-slate-400 whitespace-nowrap">{{ $transaction['member_id'] }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-500">{{ $transaction['service_summary'] }}</td>
                            <td class="px-6 py-4 text-sm text-slate-500">{{ $transaction['hospital_name'] }}</td>
                            <td class="px-6 py-4 text-sm font-bold text-slate-800">Rs{{ number_format($transaction['amount'], 2) }}</td>
                            <td class="px-6 py-4 text-sm text-slate-500 whitespace-nowrap">{{ $transaction['payment_method'] }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wide" style="{{ $statusStyles }}">
                                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background:{{ $dotColor }};"></span>{{ $transaction['status_label'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-500 whitespace-nowrap">{{ $transaction['created_at'] }}</td>
                            <td class="px-6 py-4 text-right">
                                <div class="inline-block">
                                    <button type="button" class="hip-pay-btn w-8 h-8 inline-flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 transition-colors" onclick="hipPayToggle(event,'payMenu{{ $transaction['id'] }}')">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/></svg>
                                    </button>
                                    <div id="payMenu{{ $transaction['id'] }}" class="hip-pay-menu" style="display:none;">
                                        <button type="button" onclick="hipPayCloseAll()" class="hip-pay-item">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            View Details
                                        </button>
                                        <button type="button" onclick="hipPayCloseAll()" class="hip-pay-item">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                            Download Receipt
                                        </button>
                                    </div>
                                </div>
                            </td>
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
            <div class="flex items-center gap-1">
                @if ($transactions->lastPage() > 1)
                    <a href="{{ $transactions->previousPageUrl() ?: '#' }}" class="px-3 py-1.5 text-sm font-semibold rounded-lg transition-colors {{ $transactions->onFirstPage() ? 'pointer-events-none opacity-50 text-slate-400' : 'text-slate-600 hover:bg-slate-100' }}">Previous</a>
                    @for($page = 1; $page <= $transactions->lastPage(); $page++)
                        <a href="{{ $transactions->url($page) }}" class="w-8 h-8 flex items-center justify-center text-sm rounded-lg {{ $transactions->currentPage() === $page ? 'font-bold text-white' : 'font-semibold text-slate-600 hover:bg-slate-100' }}" style="{{ $transactions->currentPage() === $page ? 'background:#1a73e8;' : '' }}">{{ $page }}</a>
                    @endfor
                    <a href="{{ $transactions->nextPageUrl() ?: '#' }}" class="px-3 py-1.5 text-sm font-semibold rounded-lg transition-colors {{ $transactions->currentPage() === $transactions->lastPage() ? 'pointer-events-none opacity-50 text-slate-400' : 'text-slate-600 hover:bg-slate-100' }}">Next</a>
                @endif
            </div>
        </div>
    </div>

    <script>
        function hipPayToggle(e, id) {
            e.stopPropagation();
            var menu = document.getElementById(id);
            var btn = e.currentTarget;
            var isOpen = menu.style.display === 'block';
            hipPayCloseAll();
            if (!isOpen) {
                var rect = btn.getBoundingClientRect();
                var menuW = 176;
                var menuH = 90;
                var spaceBelow = window.innerHeight - rect.bottom;
                menu.style.left = Math.max(8, rect.right - menuW) + 'px';
                menu.style.top = (spaceBelow < menuH ? rect.top - menuH - 4 : rect.bottom + 4) + 'px';
                menu.style.display = 'block';
            }
        }
        function hipPayCloseAll() {
            document.querySelectorAll('.hip-pay-menu').forEach(function(m) { m.style.display = 'none'; });
        }
        document.addEventListener('click', hipPayCloseAll);
        window.addEventListener('scroll', hipPayCloseAll, true);
        window.addEventListener('resize', hipPayCloseAll);
    </script>

</div>
