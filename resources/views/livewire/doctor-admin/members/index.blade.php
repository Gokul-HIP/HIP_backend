<div class="min-h-screen bg-slate-100 p-6 space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-900">Member Directory</h2>
            <p class="mt-1 max-w-xl text-sm text-slate-500">View and manage patients connected to your referral activity.</p>
        </div>
        <a href="{{ route('doctor.referral.send.add') }}" class="inline-flex items-center gap-2 bg-[#0DA2E7] hover:bg-[#0b8ecf] text-white px-5 py-2.5 rounded-full font-semibold text-sm transition-all shadow-md flex-shrink-0">
            <span class="text-lg font-light leading-none">+</span>
            Add New Referral
        </a>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-slate-500">Pending</span>
                <div class="w-8 h-8 rounded-lg bg-orange-50 border border-orange-100 text-orange-500 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-black text-slate-900 mb-2">{{ str_pad((string) $statusCounts['pending'], 2, '0', STR_PAD_LEFT) }}</p>
            <p class="text-xs font-bold text-orange-500">Patients pending</p>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-slate-500">Accepted</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 border border-blue-100 text-[#0DA2E7] flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-black text-slate-900 mb-2">{{ str_pad((string) $statusCounts['accepted'], 2, '0', STR_PAD_LEFT) }}</p>
            <p class="text-xs font-bold text-[#0DA2E7]">Accepted patients</p>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-slate-500">Completed</span>
                <div class="w-8 h-8 rounded-lg bg-green-50 border border-green-100 text-green-600 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-black text-slate-900 mb-2">{{ str_pad((string) $statusCounts['completed'], 2, '0', STR_PAD_LEFT) }}</p>
            <p class="text-xs font-bold text-green-600">Completed patients</p>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-slate-500">Rejected</span>
                <div class="w-8 h-8 rounded-lg bg-red-50 border border-red-100 text-red-500 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-black text-slate-900 mb-2">{{ str_pad((string) $statusCounts['rejected'], 2, '0', STR_PAD_LEFT) }}</p>
            <p class="text-xs font-bold text-red-500">Rejected patients</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <h3 class="font-bold text-base text-slate-900">Patient List</h3>
            <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                <div class="relative min-w-[260px]">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search by name, HIP ID or phone..."
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 pl-10 pr-4 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#0DA2E7]/20"
                    >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                @php
                    $statusLabel = match($statusFilter) {
                        'pending' => 'Pending',
                        'accepted' => 'Accepted',
                        'completed' => 'Completed',
                        'rejected' => 'Rejected',
                        default => 'All Statuses',
                    };
                    $selectedHospital = $availableHospitals->firstWhere('id', (int) $hospitalFilter);
                    $hospitalLabel = $hospitalFilter !== 'all' && $selectedHospital ? $selectedHospital->name : 'All Hospitals';
                @endphp

                <div x-data="{ open: false }" class="relative">
                    <button
                        type="button"
                        @click="open = !open"
                        class="inline-flex min-w-[170px] items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                    >
                        <span>{{ $statusLabel }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="ms-3 h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" />
                        </svg>
                    </button>

                    <div
                        x-show="open"
                        x-transition
                        @click.outside="open = false"
                        class="absolute right-0 z-50 mt-2 w-44 rounded-xl border border-slate-200 bg-white p-2 shadow-xl"
                    >
                        <ul class="space-y-1 text-sm font-medium">
                            @foreach([
                                'all' => 'All Statuses',
                                'pending' => 'Pending',
                                'accepted' => 'Accepted',
                                'completed' => 'Completed',
                                'rejected' => 'Rejected',
                            ] as $value => $label)
                                <li>
                                    <button
                                        type="button"
                                        wire:click="$set('statusFilter', '{{ $value }}')"
                                        @click="open = false"
                                        class="w-full rounded-lg px-3 py-2 text-left transition hover:bg-slate-100 {{ $statusFilter === $value ? 'bg-blue-50 text-[#0DA2E7]' : 'text-slate-800' }}"
                                    >
                                        {{ $label }}
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div x-data="{ open: false }" class="relative">
                    <button
                        type="button"
                        @click="open = !open"
                        class="inline-flex min-w-[190px] items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                    >
                        <span class="truncate">{{ $hospitalLabel }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="ms-3 h-4 w-4 flex-shrink-0 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" />
                        </svg>
                    </button>

                    <div
                        x-show="open"
                        x-transition
                        @click.outside="open = false"
                        class="absolute right-0 z-50 mt-2 max-h-72 w-56 overflow-y-auto rounded-xl border border-slate-200 bg-white p-2 shadow-xl"
                    >
                        <ul class="space-y-1 text-sm font-medium">
                            <li>
                                <button
                                    type="button"
                                    wire:click="$set('hospitalFilter', 'all')"
                                    @click="open = false"
                                    class="w-full rounded-lg px-3 py-2 text-left transition hover:bg-slate-100 {{ $hospitalFilter === 'all' ? 'bg-blue-50 text-[#0DA2E7]' : 'text-slate-800' }}"
                                >
                                    All Hospitals
                                </button>
                            </li>
                            @foreach($availableHospitals as $hospital)
                                <li>
                                    <button
                                        type="button"
                                        wire:click="$set('hospitalFilter', '{{ $hospital->id }}')"
                                        @click="open = false"
                                        class="w-full rounded-lg px-3 py-2 text-left transition hover:bg-slate-100 {{ (string) $hospitalFilter === (string) $hospital->id ? 'bg-blue-50 text-[#0DA2E7]' : 'text-slate-800' }}"
                                    >
                                        {{ $hospital->name }}
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-slate-100">
                        <th class="px-6 py-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">Patient Details</th>
                        <th class="px-6 py-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">Mobile Number</th>
                        <th class="px-6 py-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">Latest Referral Date</th>
                        <th class="px-6 py-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">Referred By</th>
                        {{-- <th class="px-6 py-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">Hospital</th> --}}
                        <th class="px-6 py-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($patients as $patient)
                        @php
                            $statusClasses = match ($patient['status']) {
                                'accepted' => 'border-[#0DA2E7]/40 bg-blue-50 text-[#0DA2E7]',
                                'completed' => 'border-green-300 bg-green-50 text-green-600',
                                'rejected' => 'border-rose-300 bg-rose-50 text-rose-600',
                                default => 'border-orange-300 bg-orange-50 text-orange-600',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="px-6 py-4">
                                <p class="font-bold text-sm text-slate-900">{{ $patient['member_name'] }}</p>
                                <p class="text-xs text-slate-400 mt-0.5">{{ $patient['member_id'] }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ $patient['phone'] ?: '-' }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ $patient['referral_date'] }}</td>
                            <td class="px-6 py-4">
                                <p class="font-bold text-sm text-slate-900">{{ $patient['referred_by'] }}</p>
                            </td>
                            {{-- <td class="px-6 py-4 text-sm text-slate-600">{{ $patient['hospital_name'] }}</td> --}}
                            <td class="px-6 py-4">
                                <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold border {{ $statusClasses }}">{{ $patient['status_label'] }}</span>
                            </td>
                            <td class="px-4 py-4">
                                <a href="{{ route('doctor.referral.receive.show', $patient['id']) }}" class="text-sm font-semibold text-[#0DA2E7] hover:underline">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-sm text-slate-500">No patients found for the selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between flex-wrap gap-3">
            <span class="text-sm text-slate-500">
                Showing {{ $patients->firstItem() ?? 0 }} to {{ $patients->lastItem() ?? 0 }} of {{ $patients->total() }} entries
            </span>
            <div>{{ $patients->onEachSide(1)->links() }}</div>
        </div>
    </div>
</div>
