{{-- ============================================================
   Step 2: Select Procedures
   Include path: livewire/cashier/payment/steps/procedures.blade.php
   ============================================================ --}}

   <div class="space-y-8">

    {{-- ── Search ── --}}
    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
        <label class="block text-sm font-semibold text-slate-700 mb-3">Search Procedures</label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                <i class="fas fa-search text-slate-400 text-base"></i>
            </div>
            <input
                type="text"
                wire:model.live.debounce.300ms="procedureSearch"
                placeholder="Search by procedure name, department or doctor..."
                class="w-full pl-11 pr-4 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 placeholder:text-slate-400 outline-none focus:ring-2 focus:ring-sky-500/30 focus:border-sky-400 transition-all"
            >
        </div>
    </div>


    {{-- ── Selected Procedures ── --}}
    @if(count($selectedProcedures ?? []) > 0)
    <div>
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fas fa-briefcase-medical" style="color: var(--button-color);"></i>
                Selected Procedures
            </h3>
            <span class="text-xs font-bold text-slate-500 bg-slate-100 px-2.5 py-1 rounded-full">
                {{ count($selectedProcedures) }} {{ count($selectedProcedures) === 1 ? 'Item' : 'Items' }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($selectedProcedures as $proc)
            <div class="bg-white border border-slate-200 rounded-xl p-4 flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-stethoscope text-base"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm leading-tight">{{ $proc->procedure_name }}</h4>
                        <p class="text-sky-500 font-bold text-sm">₹{{ number_format((float) $proc->cost, 2) }}</p>
                    </div>
                </div>
                <button
                    type="button"
                    wire:click="removeProcedure({{ $proc->id }})"
                    class="w-8 h-8 rounded-full flex items-center justify-center text-slate-400 hover:text-red-500 hover:bg-red-50 transition-all flex-shrink-0"
                >
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
            @endforeach
        </div>
    </div>
    @endif


    {{-- ── Available Options (procedures for this hospital, filtered by search) ── --}}
    <div class="pb-10">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fas fa-list-alt" style="color: var(--button-color);"></i>
                Available Procedures
            </h3>
            @if(isset($availableProcedures) && method_exists($availableProcedures, 'total'))
                <span class="text-xs text-slate-500">
                    Showing {{ $availableProcedures->firstItem() }}–{{ $availableProcedures->lastItem() }}
                    of {{ $availableProcedures->total() }}
                </span>
            @endif
        </div>

        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm divide-y divide-slate-100">
            @forelse($availableProcedures ?? [] as $proc)
            <div class="p-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-notes-medical text-base"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold text-slate-900 text-sm">{{ $proc->procedure_name }}</h4>
                        <p class="text-xs text-slate-400">{{ $proc->speciality->speciality_name ?? '—' }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-5 flex-shrink-0">
                    <span class="text-base font-bold text-slate-900">₹{{ number_format((float) $proc->cost, 2) }}</span>
                    <button
                        type="button"
                        wire:click="addProcedure({{ $proc->id }})"
                        class="inline-flex items-center gap-1.5 border px-3 py-1.5 rounded-lg font-bold text-sm transition-all"
                    style="color: var(--button-color); border-color: var(--button-hover); hover:border-button-color;" >
                        <i class="fas fa-plus text-xs" style="color: var(--button-color);"></i> Add
                    </button>
                </div>
            </div>
            @empty
            <div class="p-8 text-center text-slate-500">
                @if(trim($procedureSearch ?? '') !== '')
                    <p class="text-sm">No procedures match your search for this hospital.</p>
                @else
                    <p class="text-sm">No procedures available for this hospital.</p>
                @endif
            </div>
            @endforelse
        </div>

        @if(isset($availableProcedures) && method_exists($availableProcedures, 'links'))
        <div class="mt-3 flex justify-end">
            {{ $availableProcedures->links() }}
        </div>
        @endif
    </div>

</div>


{{-- ── Footer override: show subtotal ── --}}
{{-- Add this block to your cnp-footer in the parent blade --}}
{{-- 
    @if(($step ?? 1) === 2)
        <div class="text-right mr-4 hidden sm:block">
            <p class="text-xs text-slate-500 font-medium">Subtotal (Procedures)</p>
            <p class="text-xl font-black text-slate-900">${{ number_format($proceduresSubtotal ?? 70.00, 2) }}</p>
        </div>
    @endif
--}}