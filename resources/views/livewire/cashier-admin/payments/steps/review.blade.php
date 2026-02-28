{{-- ============================================================
   Step 5: Review Details
   Path: resources/views/livewire/cashier/payment/steps/review.blade.php
   Uses: Font Awesome + Tailwind only
   ============================================================ --}}

   <div class="space-y-6 pb-10">

    {{-- ══════════════════════════════
         MAIN TWO-COLUMN LAYOUT
    ══════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

        {{-- ── LEFT (2/3): All sections ── --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- ─────────────────────────────
                 MEMBER DETAILS
            ───────────────────────────── --}}
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">

                {{-- Card header --}}
                <div class="bg-slate-50 px-5 py-3 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                        <i class="fas fa-user-circle text-sky-500 text-base"></i>
                        Member Details
                    </h3>
                    <button wire:click="goToStep(1)" class="text-sky-500 text-xs font-bold hover:underline">Edit</button>
                </div>

                {{-- Card body --}}
                <div class="p-5 flex items-center gap-5">

                    {{-- Avatar --}}
                    <div class="w-16 h-16 rounded-xl bg-slate-100 overflow-hidden border-2 border-white shadow-sm flex-shrink-0">
                        @if(!empty($selectedMember['photo']))
                            <img src="{{ $selectedMember['photo'] }}" alt="{{ $selectedMember['name'] }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-xl font-black text-slate-400">
                                {{ strtoupper(substr($selectedMember['name'] ?? 'S', 0, 1)) }}
                            </div>
                        @endif
                    </div>

                    {{-- Info grid --}}
                    <div class="grid grid-cols-2 gap-x-8 gap-y-3 flex-1">
                        <div>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Full Name</p>
                            <p class="text-sm font-bold text-slate-900">{{ $selectedMember['name'] ?? 'Sarah Williams' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Member ID</p>
                            <p class="text-sm font-bold text-slate-900">{{ $selectedMember['member_id'] ?? '#MEM-882910' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Contact</p>
                            <p class="text-sm font-medium text-slate-600">{{ $selectedMember['phone'] ?? '+1 (555) 012-3456' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Plan Status</p>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded text-[10px] font-bold uppercase">
                                Active
                            </span>
                        </div>
                    </div>

                </div>
            </div>


            {{-- ─────────────────────────────
                 SELECTED PROCEDURES
            ───────────────────────────── --}}
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">

                <div class="bg-slate-50 px-5 py-3 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                        <i class="fas fa-briefcase-medical text-sky-500 text-base"></i>
                        Selected Procedures
                    </h3>
                    <button wire:click="goToStep(2)" class="text-sky-500 text-xs font-bold hover:underline">Edit</button>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse($selectedProcedures ?? [] as $proc)
                    <div class="px-5 py-3.5 flex items-center justify-between">
                        <div>
                            <p class="font-semibold text-slate-900 text-sm">{{ $proc['name'] }}</p>
                            <p class="text-xs text-slate-400 italic mt-0.5">{{ $proc['category'] ?? '' }}</p>
                        </div>
                        <p class="font-bold text-slate-900 text-sm flex-shrink-0 ml-4">${{ number_format($proc['price'], 2) }}</p>
                    </div>
                    @empty
                    {{-- Static fallback --}}
                    <div class="px-5 py-3.5 flex items-center justify-between">
                        <div>
                            <p class="font-semibold text-slate-900 text-sm">General Consultation</p>
                            <p class="text-xs text-slate-400 italic mt-0.5">OPD • Standard Visit</p>
                        </div>
                        <p class="font-bold text-slate-900 text-sm">$50.00</p>
                    </div>
                    <div class="px-5 py-3.5 flex items-center justify-between">
                        <div>
                            <p class="font-semibold text-slate-900 text-sm">Routine Check-up</p>
                            <p class="text-xs text-slate-400 italic mt-0.5">Diagnostic • Physical</p>
                        </div>
                        <p class="font-bold text-slate-900 text-sm">$20.00</p>
                    </div>
                    @endforelse
                </div>
            </div>


            {{-- ─────────────────────────────
                 SELECTED LAB TESTS
            ───────────────────────────── --}}
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">

                <div class="bg-slate-50 px-5 py-3 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                        <i class="fas fa-flask text-sky-500 text-base"></i>
                        Selected Lab Tests &amp; Packages
                    </h3>
                    <button wire:click="goToStep(3)" class="text-sky-500 text-xs font-bold hover:underline">Edit</button>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse($selectedLabTests ?? [] as $test)
                    <div class="px-5 py-3.5 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-vial text-slate-400 text-sm"></i>
                            <div>
                                <p class="font-semibold text-slate-900 text-sm">{{ $test['name'] }}</p>
                                <p class="text-xs text-slate-400 mt-0.5">{{ $test['type'] ?? 'Individual Test' }}</p>
                            </div>
                        </div>
                        <p class="font-bold text-slate-900 text-sm flex-shrink-0 ml-4">${{ number_format($test['price'], 2) }}</p>
                    </div>
                    @empty
                    {{-- Static fallback --}}
                    <div class="px-5 py-3.5 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-vial text-slate-400 text-sm"></i>
                            <div>
                                <p class="font-semibold text-slate-900 text-sm">Lipid Profile</p>
                                <p class="text-xs text-slate-400 mt-0.5">Individual Test</p>
                            </div>
                        </div>
                        <p class="font-bold text-slate-900 text-sm">$40.00</p>
                    </div>
                    <div class="px-5 py-3.5 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-tint text-slate-400 text-sm"></i>
                            <div>
                                <p class="font-semibold text-slate-900 text-sm">HbA1c</p>
                                <p class="text-xs text-slate-400 mt-0.5">Individual Test</p>
                            </div>
                        </div>
                        <p class="font-bold text-slate-900 text-sm">$30.00</p>
                    </div>
                    @endforelse
                </div>
            </div>


            {{-- ─────────────────────────────
                 PHARMACY DETAILS
            ───────────────────────────── --}}
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">

                <div class="bg-slate-50 px-5 py-3 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                        <i class="fas fa-pills text-sky-500 text-base"></i>
                        Pharmacy Details
                    </h3>
                    <button wire:click="goToStep(4)" class="text-sky-500 text-xs font-bold hover:underline">Edit</button>
                </div>

                <div class="p-5 flex items-start gap-5">

                    {{-- Prescription thumbnail --}}
                    <div class="w-24 h-28 bg-slate-100 rounded-lg border border-slate-200 overflow-hidden flex-shrink-0 flex items-center justify-center relative group">
                        @if(!empty($prescriptionPreviewUrl))
                            <img src="{{ $prescriptionPreviewUrl }}" alt="Prescription" class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity rounded-lg cursor-pointer">
                                <i class="fas fa-search-plus text-white text-lg"></i>
                            </div>
                        @else
                            <i class="fas fa-file-prescription text-3xl text-slate-300"></i>
                        @endif
                    </div>

                    {{-- Details --}}
                    <div class="flex-1 py-1 space-y-4">
                        <div>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-1">Prescription Source</p>
                            <p class="text-sm font-semibold text-slate-900">
                                {{ $prescriptionSource ?? 'Internal Lab Referral (Ref: #RX-7721)' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-1">Total Estimated Amount</p>
                            <p class="text-2xl font-black text-slate-900">
                                ${{ number_format($pharmacyAmount !== null && $pharmacyAmount !== '' ? (float) $pharmacyAmount : 85.00, 2) }}
                            </p>
                        </div>
                    </div>

                </div>
            </div>

        </div>


        {{-- ── RIGHT (1/3): Payment Summary ── --}}
        <div class="lg:col-span-1">
            <div class="bg-white border-2 border-sky-100 rounded-xl overflow-hidden shadow-lg sticky top-6">

                {{-- Summary header --}}
                <div class="bg-sky-50/60 px-5 py-4 border-b border-sky-100">
                    <h3 class="font-black text-slate-900 text-sm uppercase tracking-wide">Payment Summary</h3>
                </div>

                {{-- Line items --}}
                <div class="p-5 space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-slate-500">Procedure Total</span>
                        <span class="text-sm font-semibold text-slate-900">₹{{ number_format($proceduresTotal ?? 70.00, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-slate-500">Lab &amp; Packages Total</span>
                        <span class="text-sm font-semibold text-slate-900">₹{{ number_format($labTotal ?? 70.00, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-slate-500">Pharmacy Total</span>
                        <span class="text-sm font-semibold text-slate-900">₹{{ number_format($pharmacyAmount !== null && $pharmacyAmount !== '' ? (float) $pharmacyAmount : 85.00, 2) }}</span>
                    </div>

                    <div class="h-px bg-slate-100 my-1"></div>

                    {{-- Grand Total --}}
                    <div class="flex justify-between items-end pt-1">
                        <div>
                            <p class="text-[10px] font-bold text-sky-500 uppercase tracking-wider mb-0.5">Grand Total</p>
                            <p class="text-3xl font-black text-slate-900 tracking-tight">
                                ₹{{ number_format((float)($proceduresTotal ?? 70) + (float)($labTotal ?? 70) + ($pharmacyAmount !== null && $pharmacyAmount !== '' ? (float) $pharmacyAmount : 85), 2) }}
                            </p>
                        </div>
                        <p class="text-[10px] text-slate-400 font-medium pb-1">Tax included (5%)</p>
                    </div>
                </div>

                {{-- Footer note --}}
                <div class="bg-slate-50 border-t border-slate-100 px-5 py-3">
                    <p class="text-[10px] text-center text-slate-400">
                        Final amount to be billed to Member {{ $selectedMember['name'] ?? 'Sarah Williams' }}
                    </p>
                </div>

            </div>
        </div>

    </div>

</div>