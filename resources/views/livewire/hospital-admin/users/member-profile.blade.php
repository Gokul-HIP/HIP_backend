<div class="space-y-6">
    <div class="mb-2">
        <h1 class="text-3xl font-black tracking-tight text-slate-900">Member Profile</h1>
        <p class="mt-1 text-sm font-medium text-slate-500">Members who completed payments for this hospital</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-visible">
        <div class="border-b border-slate-200 px-6 py-5 sm:px-8">
            <div class="relative w-full">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35"/>
                </svg>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Search by member name, HIP ID, mobile..."
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-10 pr-4 text-sm font-medium text-slate-700 outline-none transition focus:border-sky-400 focus:bg-white">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full table-fixed text-left">
                <thead>
                    <tr style="background:#EBF5FB;">
                        <th class="px-6 py-4 text-[11px] font-black uppercase tracking-[0.14em] whitespace-nowrap" style="color:#1A9FD4;">Member ID</th>
                        <th class="px-6 py-4 text-[11px] font-black uppercase tracking-[0.14em] whitespace-nowrap" style="color:#1A9FD4;">Member Name</th>
                        <th class="px-6 py-4 text-[11px] font-black uppercase tracking-[0.14em] whitespace-nowrap" style="color:#1A9FD4;">Mobile</th>
                        <th class="px-6 py-4 text-[11px] font-black uppercase tracking-[0.14em] whitespace-nowrap" style="color:#1A9FD4;">Email</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($members as $member)
                        <tr class="transition-colors hover:bg-slate-50">
                            <td class="px-6 py-4 text-sm font-bold text-slate-800">
                                {{ $member->hipUser?->hip_id ?: ('#MEM-' . str_pad((string) $member->id, 5, '0', STR_PAD_LEFT)) }}
                            </td>
                            <td class="px-6 py-4">
                                <p class="truncate text-sm font-semibold text-slate-800">
                                    {{ trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) ?: '-' }}
                                </p>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-500">{{ $member->mobile ?: '-' }}</td>
                            <td class="px-6 py-4 text-sm text-slate-500 break-all">{{ $member->email ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-16 text-center text-sm font-medium text-slate-500">
                                No paid members found for this hospital.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-500">
                Showing <span class="font-bold text-slate-900">{{ $members->firstItem() ?? 0 }} - {{ $members->lastItem() ?? 0 }}</span>
                of {{ $members->total() }} results
            </p>
            <div>{{ $members->links() }}</div>
        </div>
    </div>
</div>

