<div class="p-6 bg-gray-100 min-h-screen">

    <div class="mb-6">
        <h2 class="text-3xl font-extrabold tracking-tight text-slate-900">Received Referrals</h2>
        <p class="text-slate-500 text-sm mt-1">Manage and coordinate incoming patient transfers from partner clinics.</p>
    </div>

    <div class="flex gap-4 mb-6 flex-wrap">
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm shadow-sm hover:shadow-md flex-1 min-w-[220px]">
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Total Received</p>
            <div class="flex items-baseline gap-2 flex-wrap">
                <span class="text-3xl font-bold text-slate-900">{{ $statusCounts['total'] }}</span>
                <span class="text-xs font-semibold text-slate-500">All referrals</span>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm hover:shadow-md flex-1 min-w-[220px]">
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Pending Review</p>
            <div class="flex items-baseline gap-2 flex-wrap">
                <span class="text-3xl font-bold text-amber-500">{{ $statusCounts['pending'] }}</span>
                <span class="text-xs font-semibold text-amber-500">Action Required</span>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm shadow-sm hover:shadow-md flex-1 min-w-[220px]">
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Accepted</p>
            <div class="flex items-baseline gap-2 flex-wrap">
                <span class="text-3xl font-bold text-slate-900">{{ $statusCounts['accepted'] }}</span>
                <span class="text-xs font-semibold text-emerald-500">Active</span>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm shadow-sm hover:shadow-md flex-1 min-w-[220px]">
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Completed</p>
            <div class="flex items-baseline gap-2 flex-wrap">
                <span class="text-3xl font-bold text-[#24a2e5]">{{ $statusCounts['completed'] }}</span>
                <span class="text-xs font-semibold text-slate-400">Total completed</span>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm shadow-sm hover:shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-6 py-4 text-sm font-bold text-slate-600">Patient Name</th>
                        <th class="px-6 py-4 text-sm font-bold text-slate-600">Referred By</th>
                        <th class="px-6 py-4 text-sm font-bold text-slate-600">Originating Clinic</th>
                        <th class="px-6 py-4 text-sm font-bold text-slate-600">Date Received</th>
                        <th class="px-6 py-4 text-sm font-bold text-slate-600">Status</th>
                        <th class="px-6 py-4 text-sm font-bold text-slate-600 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($referrals as $referral)
                        @php
                            $status = strtolower($referral->status ?? 'pending');
                            $statusStyles = match ($status) {
                                'accepted' => 'bg-emerald-100 text-emerald-700 bg-emerald-500',
                                'completed' => 'bg-slate-100 text-slate-600 bg-slate-400',
                                'rejected' => 'bg-red-100 text-red-700 bg-red-500',
                                'progress' => 'bg-blue-100 text-blue-700 bg-blue-500',
                                default => 'bg-amber-100 text-amber-700 bg-amber-500',
                            };
                            [$pillBg, $pillText, $dotBg] = explode(' ', $statusStyles);
                        @endphp
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <p class="font-semibold text-sm text-slate-900">{{ $referral->member_name ?: '-' }}</p>
                                <p class="text-xs text-slate-500 mt-0.5">ID: #{{ $referral->id }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-medium text-sm text-slate-900">{{ $referral->referredByDoctor?->name ?? '-' }}</p>
                                <p class="text-xs text-slate-500 mt-0.5">ID: #{{ $referral->referred_by_doctor_id ?? '-' }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ $referral->hospital?->name ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ optional($referral->created_at)->format('M d, Y') ?? '-' }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold {{ $pillBg }} {{ $pillText }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $dotBg }} flex-shrink-0"></span>
                                    {{ ucfirst($status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button class="px-4 py-2 text-slate-600 text-xs font-bold rounded-lg border border-slate-200 hover:bg-slate-100 transition-all">View Details</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-sm text-slate-500">No received referrals found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between">
            <p class="text-sm text-slate-500 italic">
                Showing {{ $referrals->firstItem() ?? 0 }} to {{ $referrals->lastItem() ?? 0 }} of {{ $referrals->total() }} total referrals
            </p>
            <div>
                {{ $referrals->onEachSide(1)->links() }}
            </div>
        </div>
    </div>

</div>
