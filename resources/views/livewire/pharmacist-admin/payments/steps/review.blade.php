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
                 MEMBER DETAILS (required)
            ───────────────────────────── --}}
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">

                <div class="bg-slate-50 px-5 py-3 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                        <i class="fas fa-user-circle text-base" style="color: var(--button-color);"></i>
                        Member Details
                        {{-- <span class="text-red-500 font-normal normal-case">(Required)</span> --}}
                    </h3>
                    <button type="button" wire:click="goToStep(1)" class="text-xs font-bold hover:underline" style="color: var(--button-color);">Edit</button>
                </div>

                @if(!empty($selectedMember))
                <div class="p-5 flex items-center gap-5">
                    <div class="w-16 h-16 rounded-xl bg-slate-100 overflow-hidden border-2 border-white shadow-sm flex-shrink-0">
                        @if(!empty($selectedMember['photo']))
                            <img src="{{ $selectedMember['photo'] }}" alt="{{ $selectedMember['name'] }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-xl font-black text-slate-400">
                                {{ strtoupper(mb_substr($selectedMember['name'], 0, 1)) }}
                            </div>
                        @endif
                    </div>
                    <div class="grid grid-cols-2 gap-x-8 gap-y-3 flex-1">
                        <div>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Full Name</p>
                            <p class="text-sm font-bold text-slate-900">{{ $selectedMember['name'] }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Member ID</p>
                            <p class="text-sm font-bold text-slate-900">{{ $selectedMember['member_id'] }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Contact</p>
                            <p class="text-sm font-medium text-slate-600">{{ $selectedMember['phone'] }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Plan Status</p>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded text-[10px] font-bold uppercase">Active</span>
                        </div>
                    </div>
                </div>
                @else
                <div class="p-5 bg-amber-50 border border-amber-200 rounded-lg flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-amber-600"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-bold text-amber-800">Member selection is required</p>
                        <p class="text-xs text-amber-700 mt-0.5">Please go to Step 1 to search and select the member for this payment.</p>
                    </div>
                    <button type="button" wire:click="goToStep(1)" class="px-4 py-2 bg-amber-600 text-white text-sm font-bold rounded-lg hover:bg-amber-700 transition">Select Member</button>
                </div>
                @endif
            </div>


            {{-- ─────────────────────────────
                 SELECTED PROCEDURES (only if procedures included)
            ───────────────────────────── --}}
            @if($includesProcedures ?? true)
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">

                <div class="bg-slate-50 px-5 py-3 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                        <i class="fas fa-briefcase-medical text-base" style="color: var(--button-color);"></i>
                        Selected Procedures
                    </h3>
                    <button type="button" wire:click="goToStep(2)" class="text-xs font-bold hover:underline" style="color: var(--button-color);">Edit</button>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse($selectedProcedures ?? [] as $proc)
                    @php
                        $procCost = (float) ($proc->cost ?? 0);
                        $procPay = \App\Support\DiscountPrice::payable($procCost, $proc->discount ?? null);
                        $procSave = \App\Support\DiscountPrice::saved($procCost, $proc->discount ?? null);
                        $procHasDiscount = \App\Support\DiscountPrice::hasDiscount($procCost, $proc->discount ?? null);
                    @endphp
                    <div class="px-5 py-3.5 flex items-center justify-between">
                        <div>
                            <p class="font-semibold text-slate-900 text-sm">{{ $proc->procedure_name }}</p>
                            <p class="text-xs text-slate-400 italic mt-0.5">{{ $proc->speciality?->speciality_name ?? '—' }}</p>
                        </div>
                        <div class="text-right flex-shrink-0 ml-4">
                            @if($procHasDiscount)
                                <p class="font-bold text-slate-400 text-sm line-through">₹{{ number_format($procCost, 2) }}</p>
                                <p class="font-bold text-emerald-600 text-sm">₹{{ number_format($procPay, 2) }}</p>
                                <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-wide mt-0.5">Save ₹{{ number_format($procSave, 2) }}</p>
                            @else
                                <p class="font-bold text-slate-900 text-sm">₹{{ number_format($procCost, 2) }}</p>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="px-5 py-4 text-center text-slate-500 text-sm">No procedures selected.</div>
                    @endforelse
                </div>
            </div>
            @endif


            {{-- ─────────────────────────────
                 SELECTED LAB TESTS & PACKAGES (only if diagnostics included)
            ───────────────────────────── --}}
            @if($includesDiagnostics ?? true)
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">

                <div class="bg-slate-50 px-5 py-3 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                        <i class="fas fa-flask text-base" style="color: var(--button-color);"></i>
                        Selected Lab Tests &amp; Packages
                    </h3>
                    <button type="button" wire:click="goToStep(3)" class="text-xs font-bold hover:underline" style="color: var(--button-color);">Edit</button>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse($selectedLabTests ?? [] as $item)
                    @php
                        $labName = $item->type === 'test' ? $item->model->test_name : $item->model->name;
                        $labType = $item->type === 'test' ? 'Test' : 'Package';
                        if ($item->type === 'test') {
                            $labPrice = (float) ($item->model->test_price ?? 0);
                            $labDiscountRaw = $item->model->test_discount ?? null;
                        } else {
                            $labPrice = (float) ($item->model->price ?? 0);
                            $labDiscountRaw = $item->model->discount ?? null;
                        }
                        $labPay = \App\Support\DiscountPrice::payable($labPrice, $labDiscountRaw);
                        $labSave = \App\Support\DiscountPrice::saved($labPrice, $labDiscountRaw);
                        $labHasDiscount = \App\Support\DiscountPrice::hasDiscount($labPrice, $labDiscountRaw);
                    @endphp
                    <div class="px-5 py-3.5 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-{{ $item->type === 'package' ? 'box-open' : 'vial' }} text-slate-400 text-sm"></i>
                            <div>
                                <p class="font-semibold text-slate-900 text-sm">{{ $labName }}</p>
                                <p class="text-xs text-slate-400 mt-0.5">{{ $labType }}</p>
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0 ml-4">
                            @if($labHasDiscount)
                                <p class="font-bold text-slate-400 text-sm line-through">₹{{ number_format($labPrice, 2) }}</p>
                                <p class="font-bold text-emerald-600 text-sm">₹{{ number_format($labPay, 2) }}</p>
                                <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-wide mt-0.5">Save ₹{{ number_format($labSave, 2) }}</p>
                            @else
                                <p class="font-bold text-slate-900 text-sm">₹{{ number_format($labPrice, 2) }}</p>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="px-5 py-4 text-center text-slate-500 text-sm">No lab tests or packages selected.</div>
                    @endforelse
                </div>
            </div>
            @endif


            {{-- ─────────────────────────────
                 PHARMACY DETAILS (only if pharmacy included)
            ───────────────────────────── --}}
            @if($includesPharmacy ?? true)
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">

                <div class="bg-slate-50 px-5 py-3 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                        <i class="fas fa-pills text-base" style="color: var(--button-color);"></i>
                        Pharmacy Details
                    </h3>
                    <button type="button" wire:click="goToStep(4)" class="text-xs font-bold hover:underline" style="color: var(--button-color);">Edit</button>
                </div>

                <div class="p-5 flex items-start gap-5">
                    <div class="w-24 h-28 bg-slate-100 rounded-lg border border-slate-200 overflow-hidden flex-shrink-0 flex items-center justify-center">
                        @if(!empty($prescriptionFileName))
                            <i class="fas fa-file-prescription text-3xl" style="color: var(--button-color);"></i>
                        @else
                            <i class="fas fa-file-prescription text-3xl text-slate-300"></i>
                        @endif
                    </div>
                    <div class="flex-1 py-1 space-y-4">
                        <div>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-1">Prescription</p>
                            <p class="text-sm font-semibold text-slate-900">{{ $prescriptionFileName ?? 'No file uploaded' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-1">Pharmacy Amount</p>
                            <p class="text-2xl font-black text-slate-900">
                                ₹{{ number_format($pharmacyAmount !== null && $pharmacyAmount !== '' ? (float) $pharmacyAmount : 0, 2) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @include('livewire.shared.payments.in-patient-option')

        </div>

        {{-- ── RIGHT (1/3): Payment Summary ── --}}
        <div class="lg:col-span-1">
            <div class="bg-white border-2 border-sky-100 rounded-xl overflow-hidden shadow-lg sticky top-6">

                {{-- Summary header --}}
                <div class="bg-sky-50/60 px-5 py-4 border-b border-sky-100">
                    <h3 class="font-black text-slate-900 text-sm uppercase tracking-wide">Payment Summary</h3>
                </div>

                {{-- Line items (only for included categories) --}}
                @php
                    $procTotal = (float) ($proceduresTotal ?? 0);
                    $labTotalVal = (float) ($labTotal ?? 0);
                    $pharmTotal = $pharmacyAmount !== null && $pharmacyAmount !== '' ? (float) $pharmacyAmount : 0;
                @endphp
                <div class="p-5 space-y-3">
                    @if($includesProcedures ?? true)
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-slate-500">Procedure Total</span>
                        <span class="text-sm font-semibold text-slate-900">₹{{ number_format($procTotal, 2) }}</span>
                    </div>
                    @endif
                    @if($includesDiagnostics ?? true)
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-slate-500">Lab &amp; Packages Total</span>
                        <span class="text-sm font-semibold text-slate-900">₹{{ number_format($labTotalVal, 2) }}</span>
                    </div>
                    @endif
                    @if($includesPharmacy ?? true)
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-slate-500">Pharmacy Total</span>
                        <span class="text-sm font-semibold text-slate-900">₹{{ number_format($pharmTotal, 2) }}</span>
                    </div>
                    @endif

                    @if(($totalSaved ?? 0) > 0)
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-emerald-600 font-medium">Total Discount</span>
                        <span class="text-sm font-bold text-emerald-600">− ₹{{ number_format($totalSaved, 2) }}</span>
                    </div>
                    @endif

                    <div class="h-px bg-slate-100 my-1"></div>

                    <div class="flex justify-between items-center">
                        <span class="text-sm text-slate-500">Subtotal</span>
                        <span class="text-sm font-semibold text-slate-900">₹{{ number_format($subtotal ?? ($procTotal + $labTotalVal + $pharmTotal), 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-slate-500">GST ({{ number_format($gstPercent ?? 5, 1) }}%)</span>
                        <span class="text-sm font-semibold text-slate-900">₹{{ number_format($totalGst ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-slate-500">Service Charges ({{ number_format($serviceChargesPercent ?? 3, 1) }}%)</span>
                        <span class="text-sm font-semibold text-slate-900">₹{{ number_format($serviceChargesAmount ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-slate-500">Payment Gateway ({{ number_format($paymentGatewayChargesPercent ?? 2, 1) }}%)</span>
                        <span class="text-sm font-semibold text-slate-900">₹{{ number_format($paymentGatewayChargesAmount ?? 0, 2) }}</span>
                    </div>

                    <div class="h-px bg-slate-200 my-1"></div>

                    <div class="flex justify-between items-end pt-1">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider mb-0.5" style="color: var(--button-color);">Grand Total</p>
                            <p class="text-3xl font-black text-slate-900 tracking-tight">₹{{ number_format($grandTotal ?? 0, 2) }}</p>
                            @if(($totalSaved ?? 0) > 0)
                                <p class="text-sm font-bold text-emerald-600 mt-1">You save: ₹{{ number_format($totalSaved, 2) }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="bg-slate-50 border-t border-slate-100 px-5 py-3">
                    <p class="text-[10px] text-center text-slate-400">
                        @if(!empty($selectedMember))
                            Final amount to be billed to {{ $selectedMember['name'] }}
                        @else
                            Select a member in Step 1 to see billing details.
                        @endif
                    </p>
                </div>

            </div>
        </div>

    </div>

</div>