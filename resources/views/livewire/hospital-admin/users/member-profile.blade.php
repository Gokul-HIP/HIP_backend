<div class="space-y-6">

    {{-- PAGE HEADER --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Member Profiles</h1>
            <p class="mt-1 text-sm text-slate-500">All members from HIP users for this organization</p>
        </div>
        <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 shadow-sm">
            <span class="text-xs font-semibold uppercase tracking-widest text-slate-400">Total</span>
            <span class="text-lg font-black text-sky-500">{{ $members->total() }}</span>
        </div>
    </div>

    {{-- MAIN CARD --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

        {{-- SEARCH BAR --}}
        <div class="px-6 py-4 sm:px-8">
            <div class="relative w-72">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35"/>
                </svg>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Search by name, HIP ID, mobile..."
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-4 text-sm text-slate-700 outline-none transition focus:border-sky-400 focus:bg-white focus:ring-2 focus:ring-sky-100">
            </div>
        </div>

        {{-- TABLE --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-y border-slate-100 bg-slate-50">
                        {{-- Avatar + Name merged as one column header --}}
                        <th class="py-3 px-6 text-[11px] font-bold uppercase tracking-widest text-slate-400" style="width:300px">Member</th>
                        <th class="py-3 px-6 text-[11px] font-bold uppercase tracking-widest text-slate-400" style="width:160px">HIP ID</th>
                        <th class="py-3 px-6 text-[11px] font-bold uppercase tracking-widest text-slate-400" style="width:160px">Mobile</th>
                        <th class="py-3 px-6 text-[11px] font-bold uppercase tracking-widest text-slate-400">Email</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($members as $member)
                        @php
                            $firstName = $member->first_name ?? '';
                            $lastName  = $member->last_name  ?? '';
                            $fullName  = trim("$firstName $lastName") ?: 'Unknown';
                            $initials  = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1)) ?: '?';
                            $memberImage = $member->profile_image ? asset('storage/users/' . $member->profile_image) : null;
                            $hipId     = $member->hip_id ?: ('#MEM-' . str_pad((string) $member->id, 5, '0', STR_PAD_LEFT));

                            $colours = [
                                ['#DBEAFE', '#1D4ED8'],
                                ['#EDE9FE', '#6D28D9'],
                                ['#D1FAE5', '#065F46'],
                                ['#FEF3C7', '#92400E'],
                                ['#FFE4E6', '#BE123C'],
                                ['#E0E7FF', '#3730A3'],
                            ];
                            [$avatarBg, $avatarText] = $colours[abs(crc32($fullName)) % count($colours)];
                        @endphp
                        <tr class="transition-colors hover:bg-slate-50">

                            {{-- AVATAR + NAME in one cell --}}
                            <td class="py-3 px-6">
                                <div class="flex items-center gap-3">
                                    @if($memberImage)
                                        <img src="{{ $memberImage }}"
                                            alt="{{ $fullName }}"
                                            class="h-9 w-9 rounded-full object-cover border border-slate-200 flex-shrink-0">
                                    @else
                                        <div style="background:{{ $avatarBg }}; color:{{ $avatarText }}; min-width:2.25rem;"
                                            class="flex h-9 w-9 items-center justify-center rounded-full text-xs font-bold">
                                            {{ $initials }}
                                        </div>
                                    @endif
                                    <p class="truncate text-sm font-semibold text-slate-800">{{ $fullName }}</p>
                                </div>
                            </td>

                            {{-- HIP ID --}}
                            <td class="py-3 px-6">
                                <span style="background:#EFF6FF; color:#1D6FB8; border:1px solid #BFDBFE;"
                                    class="inline-block rounded-md px-2.5 py-0.5 text-xs font-bold">
                                    {{ $hipId }}
                                </span>
                            </td>

                            {{-- MOBILE --}}
                            <td class="py-3 px-6 text-sm text-slate-600">
                                {{ $member->mobile_num ?: '-' }}
                            </td>

                            {{-- EMAIL --}}
                            <td class="py-3 px-6 text-sm text-slate-500">
                                {{ $member->email ?: '-' }}
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-20 text-center">
                                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
                                    <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0Zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0Z"/>
                                    </svg>
                                </div>
                                <p class="text-sm font-semibold text-slate-700">No members found</p>
                                <p class="mt-1 text-xs text-slate-400">No HIP users match your search for this organization.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        <div class="flex flex-col gap-3 border-t border-slate-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-500">
                Showing
                <span class="font-bold text-slate-800">{{ $members->firstItem() ?? 0 }}–{{ $members->lastItem() ?? 0 }}</span>
                of <span class="font-bold text-slate-800">{{ $members->total() }}</span> members
            </p>
            <div>{{ $members->links() }}</div>
        </div>

    </div>
</div>