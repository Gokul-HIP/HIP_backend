<div class="flex-1 overflow-y-auto bg-slate-50 min-h-screen p-8">
    <div class="mb-8 flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div>
            <h2 class="mb-1 text-3xl font-bold text-slate-900">Doctor Dashboard</h2>
            <p class="text-sm text-slate-500">Comprehensive overview of your referral ecosystem and member activity.</p>
        </div>
        <a href="{{ route('doctor.referral.send.add') }}" class="flex w-fit items-center gap-2 rounded-lg bg-[#26ABE2] px-6 py-2.5 font-semibold text-white shadow-sm transition-shadow hover:bg-[#1e97cb]">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Add New Referral
        </a>
    </div>

    <div class="mb-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </div>
                <span class="text-sm font-medium text-slate-500">Total Outgoing</span>
            </div>
            <div class="flex items-end gap-2">
                <h3 class="text-2xl font-bold text-slate-900">{{ number_format($stats['outgoing']) }}</h3>
                <span class="mb-1 text-xs font-bold text-blue-500">Sent referrals</span>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M12 12V4m0 8l-3-3m3 3l3-3"/>
                    </svg>
                </div>
                <span class="text-sm font-medium text-slate-500">Total Received</span>
            </div>
            <div class="flex items-end gap-2">
                <h3 class="text-2xl font-bold text-slate-900">{{ number_format($stats['received']) }}</h3>
                <span class="mb-1 text-xs font-bold text-indigo-500">Incoming referrals</span>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-orange-100 text-orange-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-sm font-medium text-slate-500">Pending Actions</span>
            </div>
            <div class="flex items-end gap-2">
                <h3 class="text-2xl font-bold text-slate-900">{{ number_format($stats['pending_actions']) }}</h3>
                <span class="mb-1 text-xs font-bold text-orange-500">Requiring attention</span>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-5-3.87M9 20H4v-2a4 4 0 015-3.87m6 5.87H9m8-10a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <span class="text-sm font-medium text-slate-500">Active Members</span>
            </div>
            <div class="flex items-end gap-2">
                <h3 class="text-2xl font-bold text-slate-900">{{ number_format($stats['active_members']) }}</h3>
                <span class="mb-1 text-xs font-bold text-emerald-500">Unique members</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-1">
            <div class="mb-6 flex items-center justify-between">
                <h4 class="font-bold text-slate-800">Referral Trends</h4>
                <div class="flex items-center gap-2">
                    <span class="hidden rounded-lg border border-slate-200 bg-slate-50 px-2 py-1 text-xs text-slate-500 sm:inline">{{ $trendRangeLabel }}</span>
                    <select wire:model.live="trendRange"
                        class="rounded-lg border border-slate-200 bg-slate-50 px-2 py-1 text-xs text-slate-600 focus:border-[#26ABE2] focus:outline-none">
                        <option value="latest">Latest</option>
                        <option value="3m">Last 3 Months</option>
                        <option value="6m">Last 6 Months</option>
                    </select>
                </div>
            </div>

            <div class="flex h-48 items-end justify-between gap-3">
                @foreach ($trendBars as $bar)
                    <div class="flex h-full flex-1 flex-col items-center justify-end gap-2">
                        <div class="flex h-full w-full flex-col justify-end overflow-hidden rounded-t-md bg-slate-100">
                            <div class="w-full rounded-t-md bg-[#26ABE2] transition-all hover:bg-[#1e97cb]" style="height: {{ $bar['pct'] }}%"></div>
                        </div>
                        <span class="text-[10px] font-bold text-slate-400">{{ $bar['label'] }}</span>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="h-3 w-3 rounded-full bg-[#26ABE2]"></div>
                        <span class="text-xs text-slate-500">Outgoing</span>
                    </div>
                    <span class="text-xs font-bold">{{ $trendBars->sum('count') }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="h-3 w-3 rounded-full bg-slate-200"></div>
                        <span class="text-xs text-slate-500">Monthly Average</span>
                    </div>
                    <span class="text-xs font-bold">{{ $trendBars->count() ? round($trendBars->avg('count')) : 0 }}</span>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-100 p-6">
                <h4 class="font-bold text-slate-800">Recent Referral Activity</h4>
                <a href="{{ route('doctor.referral.send') }}" class="text-xs font-bold text-[#26ABE2] hover:underline">View All</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-wider text-slate-400">Member Details</th>
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-wider text-slate-400">Referral Date</th>
                            {{-- <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-wider text-slate-400">Hospital</th> --}}
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-wider text-slate-400">Direction</th>
                            <th class="px-6 py-4 text-center text-[10px] font-bold uppercase tracking-wider text-slate-400">Status</th>
                            <th class="px-6 py-4 text-right text-[10px] font-bold uppercase tracking-wider text-slate-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($recentReferrals as $referral)
                            @php
                                $statusClasses = match ($referral['status']) {
                                    'accepted' => 'bg-emerald-100 text-emerald-700',
                                    'completed' => 'bg-blue-100 text-blue-700',
                                    'rejected' => 'bg-red-100 text-red-700',
                                    default => 'bg-amber-100 text-amber-700',
                                };
                            @endphp
                            <tr class="transition-colors hover:bg-slate-50/50">
                                <td class="px-6 py-4">
                                    <p class="text-sm font-bold text-slate-800">{{ $referral['member_name'] }}</p>
                                    <p class="text-[10px] text-slate-400">{{ $referral['member_id'] }}</p>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-slate-600">{{ $referral['referral_date'] }}</td>
                                {{-- <td class="px-6 py-4 text-sm text-slate-600">{{ $referral['hospital_name'] }}</td> --}}
                                <td class="px-6 py-4">
                                    <span class="rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-wide {{ $referral['direction'] === 'sent' ? 'bg-sky-100 text-sky-700' : 'bg-indigo-100 text-indigo-700' }}">
                                        {{ $referral['direction'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-wide {{ $statusClasses }}">
                                        {{ $referral['status_label'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('doctor.referral.receive.show', $referral['id']) }}" class="text-xs font-semibold text-[#26ABE2] hover:underline">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-sm text-slate-500">No referral activity found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 bg-slate-50 p-4 text-center">
                <p class="text-[10px] font-medium uppercase tracking-widest text-slate-400">
                    Showing last {{ $recentReferrals->count() }} entries
                </p>
            </div>
        </div>
    </div>
</div>
