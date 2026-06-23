<div id="subscriptions-index-root">
    <style>
        #subscriptions-index-root .action-wrap { position: relative; display: inline-block; }
        #subscriptions-index-root .action-trigger {
            width: 32px; height: 32px; border-radius: 50%;
            border: none; background: transparent; color: #94a3b8;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: background .13s, color .13s; font-size: 16px;
        }
        #subscriptions-index-root .action-trigger:hover { background: #f1f5f9; color: #475569; }
        #subscriptions-index-root .sub-action-menu {
            position: fixed;
            width: 210px; background: #fff;
            border: 1px solid #e2e8f0; border-radius: 10px;
            box-shadow: 0 8px 28px rgba(0,0,0,0.13);
            z-index: 9999; overflow: hidden; display: none;
        }
        #subscriptions-index-root .sub-action-menu.open { display: block; }
        #subscriptions-index-root .action-item {
            display: flex; align-items: center; gap: 10px;
            width: 100%; padding: 10px 14px;
            font-size: 13.5px; color: #374151;
            background: none; border: none;
            cursor: pointer; text-align: left;
        }
        #subscriptions-index-root .action-item:hover { background: #f8fafc; }
        #subscriptions-index-root .action-item.danger { color: #dc2626; }
        #subscriptions-index-root .action-divider { border: none; border-top: 1px solid #f1f5f9; margin: 3px 0; }
        #subscriptions-index-root .badge {
            display: inline-flex; align-items: center;
            padding: 2px 8px; border-radius: 20px;
            font-size: 10px; font-weight: 700;
            letter-spacing: .05em; text-transform: uppercase;
        }
        #subscriptions-index-root .badge-active { background:#d1fae5; color:#065f46; }
        #subscriptions-index-root .badge-pending { background:#fef3c7; color:#92400e; }
        #subscriptions-index-root .badge-expired { background:#e2e8f0; color:#475569; }
        #subscriptions-index-root .badge-cancelled { background:#fee2e2; color:#991b1b; }
        #subscriptions-index-root .badge-completed { background:#d1fae5; color:#065f46; }
        #subscriptions-index-root .badge-failed { background:#fee2e2; color:#991b1b; }
        #subscriptions-index-root .sub-tbl { width:100%; border-collapse:collapse; font-size:13.5px; }
        #subscriptions-index-root .sub-tbl thead tr { background:#f8fafc; border-bottom:1px solid #e2e8f0; }
        #subscriptions-index-root .sub-tbl thead th {
            padding:12px 16px; text-align:left;
            font-size:11px; font-weight:700; color:#94a3b8;
            letter-spacing:.08em; text-transform:uppercase; white-space:nowrap;
        }
        #subscriptions-index-root .sub-tbl tbody tr { border-bottom:1px solid #f1f5f9; }
        #subscriptions-index-root .sub-tbl tbody tr:hover { background:#f8fafc; }
        #subscriptions-index-root .sub-tbl td { padding:14px 16px; vertical-align:middle; color:#374151; }
    </style>

    <div class="flex items-center justify-between px-6 py-4 flex-wrap gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Manage Subscriptions</h2>
            <p class="text-sm text-slate-500 mt-0.5">Family package subscriptions activated by cashier</p>
        </div>
        <a href="{{ route('pharmacist.manage-subscriptions.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-sky-500 hover:bg-sky-600 text-white font-semibold text-sm rounded-lg shadow-md shadow-sky-400/30">
            <i class="fas fa-plus text-xs"></i> New Subscription
        </a>
    </div>

    <div class="px-6 pb-4">
        <div class="bg-white border border-slate-200 rounded-xl p-4 flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Search</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Mobile or name"
                    class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Payment Mode</label>
                <select wire:model.live="paymentModeFilter" class="border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    <option value="all">All</option>
                    <option value="cash">Cash</option>
                    <option value="online">Online</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Invoice Status</label>
                <select wire:model.live="invoiceStatusFilter" class="border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    <option value="all">All</option>
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Subscription Status</label>
                <select wire:model.live="subscriptionStatusFilter" class="border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    <option value="all">All</option>
                    <option value="pending">Pending</option>
                    <option value="active">Active</option>
                    <option value="expired">Expired</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">From</label>
                <input type="date" wire:model.live="dateFrom" class="border border-slate-200 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">To</label>
                <input type="date" wire:model.live="dateTo" class="border border-slate-200 rounded-lg px-3 py-2 text-sm">
            </div>
            <button type="button" wire:click="resetFilters"
                class="px-4 py-2 border border-slate-200 rounded-lg text-sm text-slate-600 hover:bg-slate-50">
                Reset
            </button>
        </div>
    </div>

    <div class="px-6 pb-6">
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm" style="overflow:visible;">
            <div style="overflow-x:auto; overflow-y:visible;">
                <table class="sub-tbl">
                    <thead>
                        <tr>
                            <th>User Name</th>
                            <th>Mobile</th>
                            <th>Package</th>
                            <th>Covered Members</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Payment Mode</th>
                            <th>Invoice Status</th>
                            <th>Subscription Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subscriptions as $subscription)
                            @php
                                $userName = trim(($subscription->member?->first_name ?? '').' '.($subscription->member?->last_name ?? '')) ?: 'N/A';
                                $invoiceStatus = $subscription->invoice?->status ?? '—';
                                $canRenew = (bool) ($subscription->can_renew ?? false);
                                $canMarkPaid = $subscription->status === 'pending'
                                    && $subscription->payment_mode === 'online'
                                    && ($subscription->invoice?->status === 'pending');
                                $subBadge = match($subscription->status) {
                                    'active' => 'badge-active',
                                    'pending' => 'badge-pending',
                                    'expired' => 'badge-expired',
                                    'cancelled' => 'badge-cancelled',
                                    default => 'badge-expired',
                                };
                                $invBadge = match(strtolower((string) $invoiceStatus)) {
                                    'completed' => 'badge-completed',
                                    'pending' => 'badge-pending',
                                    'cancelled', 'failed' => 'badge-failed',
                                    default => 'badge-expired',
                                };
                            @endphp
                            <tr wire:key="sub-row-{{ $subscription->id }}">
                                <td class="font-medium text-slate-900">{{ $userName }}</td>
                                <td>{{ $subscription->member?->mobile_num ?? '—' }}</td>
                                <td>{{ $subscription->familyPackage?->name ?? 'N/A' }}</td>
                                <td>
                                    @php $rowCovered = $subscription->covered_members_display ?? []; @endphp
                                    @if(!empty($rowCovered))
                                        <div class="space-y-1">
                                            @foreach($rowCovered as $covered)
                                                <div class="text-sm text-slate-700 leading-snug">
                                                    {{ $covered['name'] }}
                                                    <span class="text-slate-400 text-xs">({{ $covered['relationship'] }})</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-slate-400 text-sm">—</span>
                                    @endif
                                </td>
                                <td>{{ optional($subscription->start_date)->format('d M Y') }}</td>
                                <td>{{ optional($subscription->end_date)->format('d M Y') }}</td>
                                <td>{{ ucfirst($subscription->payment_mode ?? '—') }}</td>
                                <td><span class="badge {{ $invBadge }}">{{ ucfirst((string) $invoiceStatus) }}</span></td>
                                <td><span class="badge {{ $subBadge }}">{{ ucfirst($subscription->status) }}</span></td>
                                <td class="text-center">
                                    <div class="action-wrap" data-sub-action-wrap>
                                        <button type="button" class="action-trigger"
                                            onclick="event.preventDefault(); event.stopPropagation(); window.toggleSubMenu(this)">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div class="sub-action-menu">
                                            <button type="button" class="action-item"
                                                onclick="event.stopPropagation(); window.subIndexAction('viewDetails', {{ $subscription->id }})">
                                                <i class="fas fa-eye"></i> View Details
                                            </button>
                                            @if($canRenew)
                                                <button type="button" class="action-item"
                                                    onclick="event.stopPropagation(); window.subIndexAction('openRenew', {{ $subscription->id }})">
                                                    <i class="fas fa-redo"></i> Renew
                                                </button>
                                            @endif
                                            @if($canMarkPaid)
                                                <button type="button" class="action-item"
                                                    onclick="event.stopPropagation(); window.subIndexAction('markPaid', {{ $subscription->id }})">
                                                    <i class="fas fa-check-circle"></i> Mark Paid
                                                </button>
                                            @endif
                                            @if($subscription->status !== 'cancelled')
                                                <hr class="action-divider">
                                                <button type="button" class="action-item danger"
                                                    onclick="event.stopPropagation(); if (confirm('Cancel this subscription?')) { window.subIndexAction('cancelSubscription', {{ $subscription->id }}); }">
                                                    <i class="fas fa-ban"></i> Cancel
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-10 text-slate-500">No subscriptions found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $subscriptions->links() }}</div>
        </div>
    </div>

    @if($showDetailsModal && $selectedSubscription)
        <div class="fixed inset-0 z-[10000] flex items-center justify-center p-4"
            wire:key="details-modal-{{ $selectedSubscription->id }}">
            <div class="absolute inset-0 bg-slate-900/50" wire:click="closeDetails"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto z-10">
                <div class="flex items-center justify-between p-5 border-b border-slate-200">
                    <h3 class="text-lg font-bold text-slate-900">Subscription Details</h3>
                    <button type="button" wire:click="closeDetails" class="text-slate-400 hover:text-slate-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-6 space-y-4 text-sm text-slate-600">
                    <p><strong>User:</strong> {{ trim(($selectedSubscription->member?->first_name ?? '').' '.($selectedSubscription->member?->last_name ?? '')) }}</p>
                    <p><strong>Mobile:</strong> {{ $selectedSubscription->member?->mobile_num }}</p>
                    <p><strong>Package:</strong> {{ $selectedSubscription->familyPackage?->name }}</p>
                    <p><strong>Period:</strong> {{ optional($selectedSubscription->start_date)->format('d M Y') }} – {{ optional($selectedSubscription->end_date)->format('d M Y') }}</p>
                    <p><strong>Amount:</strong> ₹{{ number_format((float) $selectedSubscription->amount_paid, 2) }}</p>
                    <p><strong>Payment Mode:</strong> {{ ucfirst($selectedSubscription->payment_mode ?? '—') }}</p>
                    <p><strong>Status:</strong> {{ ucfirst($selectedSubscription->status) }}</p>
                    <p><strong>Invoice:</strong> #{{ $selectedSubscription->invoice_id ?? '—' }} ({{ ucfirst($selectedSubscription->invoice?->status ?? '—') }})</p>
                    @if($selectedSubscription->activated_at)
                        <p><strong>Activated:</strong> {{ $selectedSubscription->activated_at->format('d M Y, h:i A') }}</p>
                    @endif
                    @if(!empty($coveredMembers))
                        <div class="pt-2">
                            <p class="font-medium text-slate-800 mb-1">Covered Members</p>
                            <ul class="space-y-1">
                                @foreach($coveredMembers as $member)
                                    <li>{{ $member['name'] }} <span class="text-slate-400">({{ $member['relationship'] }})</span></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @if($selectedSubscription->usageLogs->isNotEmpty())
                        <div>
                            <p class="font-medium text-slate-800 mb-2">Usage</p>
                            <div class="space-y-2 max-h-48 overflow-y-auto">
                                @foreach($selectedSubscription->usageLogs as $log)
                                    <div class="text-xs border rounded-lg p-2">
                                        {{ ucfirst(str_replace('_', ' ', $log->usage_type)) }}
                                        · {{ optional($log->used_at)->format('d M Y') }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if($showRenewModal && $renewSubscriptionId)
        <div class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/50" wire:click="closeRenew"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto z-10">
                @livewire('pharmacist.subscriptions.renew', ['subscriptionId' => $renewSubscriptionId], key('renew-'.$renewSubscriptionId))
            </div>
        </div>
    @endif
</div>

@script
<script>
    window.closeAllSubMenus = function () {
        document.querySelectorAll('#subscriptions-index-root .sub-action-menu.open').forEach(function (menu) {
            menu.classList.remove('open');
            menu.style.cssText = '';
        });
    };

    window.toggleSubMenu = function (btn) {
        var menu = btn.nextElementSibling;
        if (!menu) return;

        var isOpen = menu.classList.contains('open');
        window.closeAllSubMenus();

        if (!isOpen) {
            var btnRect = btn.getBoundingClientRect();
            var menuW = 210;

            menu.style.cssText = 'position:fixed;visibility:hidden;display:block;top:-9999px;left:-9999px;width:' + menuW + 'px;';
            var menuH = menu.offsetHeight;
            menu.style.cssText = '';

            var spaceBelow = window.innerHeight - btnRect.bottom;
            var top = spaceBelow < menuH + 12 ? btnRect.top - menuH - 4 : btnRect.bottom + 4;
            var left = Math.max(8, btnRect.right - menuW);

            menu.style.position = 'fixed';
            menu.style.width = menuW + 'px';
            menu.style.zIndex = '9999';
            menu.style.top = top + 'px';
            menu.style.left = left + 'px';
            menu.classList.add('open');
        }
    };

    window.subIndexAction = function (method, param) {
        window.closeAllSubMenus();

        if (!window.Livewire || typeof window.Livewire.find !== 'function') {
            console.error('Livewire is not loaded');
            return;
        }

        var root = document.getElementById('subscriptions-index-root');
        var wireId = root ? (root.getAttribute('wire:id') || root.closest('[wire\\:id]')?.getAttribute('wire:id')) : null;

        if (!wireId) {
            console.error('Subscriptions Livewire wire:id not found on page');
            return;
        }

        var component = window.Livewire.find(wireId);
        if (!component) {
            console.error('Subscriptions Livewire component not found:', wireId);
            return;
        }

        component.call(method, param);
    };

    if (!window.__subIndexMenuListenersBound) {
        window.__subIndexMenuListenersBound = true;

        document.addEventListener('click', function (e) {
            if (!e.target.closest('[data-sub-action-wrap]')) {
                window.closeAllSubMenus();
            }
        });

        document.addEventListener('scroll', function () {
            window.closeAllSubMenus();
        }, true);
    }
</script>
@endscript
