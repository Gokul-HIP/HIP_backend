<div class="flex-1 overflow-y-auto p-8 min-h-screen">
    <style>
        .action-wrap {
            position: relative;
            display: inline-block;
        }

        .action-trigger {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: none;
            background: transparent;
            color: #94a3b8;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .13s, color .13s;
            font-size: 16px;
        }

        .action-trigger:hover {
            background: #f1f5f9;
            color: #475569;
        }

        .action-menu {
            position: fixed;
            width: 196px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.13);
            z-index: 9999;
            overflow: hidden;
            display: none;
        }

        .action-menu.open {
            display: block;
        }

        .action-item {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 10px 14px;
            font-size: 13.5px;
            color: #374151;
            background: none;
            border: none;
            cursor: pointer;
            text-align: left;
            transition: background .11s;
        }

        .action-item:hover {
            background: #f8fafc;
        }

        .action-item.danger {
            color: #dc2626;
        }

        .action-item.danger:hover {
            background: #fef2f2;
        }

        .action-divider {
            border: none;
            border-top: 1px solid #f1f5f9;
            margin: 3px 0;
        }
    </style>

    <div class="flex flex-wrap items-end justify-between gap-4 mb-8">
        <div>
            <h2 class="text-3xl font-black tracking-tight text-slate-900">Sent Referrals</h2>
            <p class="text-slate-500 mt-1 max-w-xl text-sm">Manage and track all patient referrals sent to other specialists and partner hospitals within our network.</p>
        </div>
        <a href="{{ route('doctor.referral.send.add') }}" class="inline-flex items-center gap-2 bg-[#0DA2E7] hover:bg-[#2589c2] shadow-sm hover:shadow-md text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all active:scale-[0.98] shadow-md shadow-[#2D9CDB]/30 flex-shrink-0">
            <span class="text-lg leading-none font-light">+</span>
            Add New Referral
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm shadow-sm hover:shadow-md">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-semibold text-slate-500">Pending</span>
                <div class="w-8 h-8 rounded-lg bg-orange-100 text-orange-500 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-3xl font-black text-slate-900">{{ $statusCounts['pending'] }}</p>
            <p class="mt-2 text-xs font-bold text-orange-500">Total pending referrals</p>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm shadow-sm hover:shadow-md">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-semibold text-slate-500">Accepted</span>
                <div class="w-8 h-8 rounded-lg bg-[#2D9CDB]/10 text-[#2D9CDB] flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-3xl font-black text-slate-900">{{ $statusCounts['accepted'] }}</p>
            <p class="mt-2 text-xs font-bold text-[#2D9CDB]">Total accepted referrals</p>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm shadow-sm hover:shadow-md">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-semibold text-slate-500">Completed</span>
                <div class="w-8 h-8 rounded-lg bg-green-100 text-green-600 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </div>
            </div>
            <p class="text-3xl font-black text-slate-900">{{ $statusCounts['completed'] }}</p>
            <p class="mt-2 text-xs font-bold text-green-600">Total completed referrals</p>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm shadow-sm hover:shadow-md">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-semibold text-slate-500">Rejected</span>
                <div class="w-8 h-8 rounded-lg bg-red-100 text-red-500 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
            </div>
            <p class="text-3xl font-black text-slate-900">{{ $statusCounts['rejected'] }}</p>
            <p class="mt-2 text-xs font-bold text-red-500">Total rejected referrals</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm shadow-sm hover:shadow-md">
        <div class="p-6 border-b border-slate-200 flex items-center justify-between flex-wrap gap-3">
            <h3 class="font-bold text-lg text-slate-900">Referral List</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50">
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500">Member Details</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500">Mobile Number</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500">Referral Date</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500">Referred To</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500">Hospital</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($referrals as $referral)
                        @php
                            $status = strtolower($referral->status ?? 'pending');
                            $statusClasses = match ($status) {
                                'accepted' => 'bg-sky-50 text-[#2D9CDB] border-[#2D9CDB]/20',
                                'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'rejected' => 'bg-rose-50 text-rose-700 border-rose-200',
                                default => 'bg-orange-50 text-orange-700 border-orange-200',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <p class="font-bold text-slate-900">{{ $referral->member_name ?: '-' }}</p>
                                <p class="text-xs text-slate-500">HIP ID: {{ $referral->member?->hip_id ?? $referral->insurance_member_id ?? '-' }}</p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 font-medium">
                                {{ trim(($referral->country_code ?? '') . ' ' . ($referral->phone_number ?? '')) ?: '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 font-medium">
                                {{ optional($referral->referral_date)->format('M d, Y') ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <p class="font-bold text-slate-900">{{ $referral->referredToDoctor?->name ?? '-' }}</p>
                                <p class="text-xs text-slate-500">ID: #{{ $referral->referred_to_doctor_id ?? '-' }}</p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 font-medium">{{ $referral->hospital?->name ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border-2 {{ $statusClasses }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-slate-400 text-sm">
                                <div class="action-wrap">
                                    <button type="button" class="action-trigger" onclick="event.preventDefault(); event.stopPropagation(); toggleReferralMenu(this)">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <circle cx="12" cy="5" r="2"></circle>
                                            <circle cx="12" cy="12" r="2"></circle>
                                            <circle cx="12" cy="19" r="2"></circle>
                                        </svg>
                                    </button>
                                    <div class="action-menu">
                                        <a href="{{ route('doctor.referral.send.view', $referral->id) }}" class="action-item" onclick="closeReferralMenus()">
                                            View
                                        </a>
                                        <a href="{{ route('doctor.referral.send.edit', $referral->id) }}" class="action-item" onclick="closeReferralMenus()">
                                            Edit
                                        </a>
                                        <hr class="action-divider">
                                        <button type="button" class="action-item danger" wire:click="openDeleteModal({{ $referral->id }})" onclick="closeReferralMenus()">
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-sm text-slate-500">
                                No sent referrals found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-6 bg-slate-50/50 border-t border-slate-200 flex items-center justify-between flex-wrap gap-3">
            <span class="text-sm text-slate-500">Showing {{ $referrals->firstItem() ?? 0 }} to {{ $referrals->lastItem() ?? 0 }} of {{ $referrals->total() }} entries</span>
            <div>{{ $referrals->onEachSide(1)->links() }}</div>
        </div>
    </div>

    <flux:modal name="delete-referral" class="p-0" wire:close="closeDeleteModal" id="delete-org">
        <div x-data @click.outside="$wire.closeDeleteModal()">
            <div class="p-4 relative">
                <button
                    type="button"
                    wire:click="closeDeleteModal"
                    class="absolute top-3 right-3 z-10 h-8 w-8 rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-100 flex items-center justify-center text-xl leading-none"
                    aria-label="Close"
                >
                    &times;
                </button>

                <h2 class="text-lg font-semibold text-gray-900 mb-2">Delete Referral?</h2>
                <p class="text-sm text-gray-500 mb-6 leading-relaxed">
                    You are about to delete referral for
                    <span class="font-semibold text-gray-700">{{ $deleteReferralName ?: 'this member' }}</span>.
                    This action cannot be reversed.
                </p>

                <div class="flex justify-end gap-3">
                    <flux:button variant="ghost" wire:click="closeDeleteModal">Cancel</flux:button>
                    <button type="button" wire:click="deleteReferral" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium shadow">
                        Delete Referral
                    </button>
                </div>
            </div>
        </div>
    </flux:modal>
</div>

<script>
    function closeReferralMenus() {
        document.querySelectorAll('.action-menu.open').forEach((menu) => {
            menu.classList.remove('open');
            menu.style.cssText = '';
        });
    }

    function toggleReferralMenu(button) {
        const menu = button.nextElementSibling;
        const isOpen = menu.classList.contains('open');

        closeReferralMenus();

        if (isOpen) {
            return;
        }

        const buttonRect = button.getBoundingClientRect();
        const menuWidth = 196;

        menu.style.cssText = 'position:fixed;visibility:hidden;display:block;top:-9999px;left:-9999px;width:' + menuWidth + 'px;';
        const menuHeight = menu.offsetHeight;
        menu.style.cssText = '';

        const spaceBelow = window.innerHeight - buttonRect.bottom;
        const top = spaceBelow < menuHeight + 12 ? buttonRect.top - menuHeight - 4 : buttonRect.bottom + 4;
        let left = buttonRect.right - menuWidth;

        if (left < 8) {
            left = 8;
        }

        menu.style.position = 'fixed';
        menu.style.width = menuWidth + 'px';
        menu.style.zIndex = '9999';
        menu.style.top = top + 'px';
        menu.style.left = left + 'px';
        menu.style.right = 'auto';
        menu.style.bottom = 'auto';

        menu.classList.add('open');
    }

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.action-wrap')) {
            closeReferralMenus();
        }
    });

    document.addEventListener('scroll', () => {
        closeReferralMenus();
    }, true);
</script>
