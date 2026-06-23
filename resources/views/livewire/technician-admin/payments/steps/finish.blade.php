{{-- ============================================================
   Step 6: Finish — Payment Request Sent
   Path: resources/views/livewire/cashier/payment/steps/finish.blade.php
   Uses: Font Awesome + Tailwind only
   ============================================================ --}}

   <div class="flex-1 flex flex-col items-center justify-center py-10 px-4">

    <div class="w-full max-w-md text-center">

        {{-- ── Success checkmark circle ── --}}
        <div class="flex justify-center mb-6">
            <div class="w-20 h-20 rounded-full bg-emerald-100 flex items-center justify-center shadow-xl shadow-emerald-400/20">
                <i class="fas fa-check text-emerald-500 text-3xl"></i>
            </div>
        </div>

        {{-- ── Heading ── --}}
        <h3 class="text-2xl font-black text-slate-900 tracking-tight mb-2">
            Payment Request Sent Successfully!
        </h3>
        <p class="text-sm text-slate-500 mb-8 leading-relaxed">
            The payment link has been sent to
            <span class="font-semibold text-slate-700">{{ $selectedMember['name'] ?? 'Sarah Williams' }}</span>'s
            registered mobile number.
        </p>

        {{-- ── Info cards ── --}}
        <div class="space-y-3 text-left mb-8">

            {{-- Transaction Total (saved invoice total or preview) --}}
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-[10px] text-slate-400 font-extrabold uppercase tracking-widest mb-1">Transaction Total</p>
                    <p class="text-3xl font-black text-slate-900">
                        ₹{{ number_format($lastInvoiceTotal ?? $grandTotal ?? 0, 2) }}
                    </p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-400 flex-shrink-0">
                    <i class="fas fa-receipt text-lg"></i>
                </div>
            </div>

            {{-- Rewards Earned --}}
            <div class="bg-amber-50 border border-amber-200/60 rounded-2xl p-5 relative overflow-hidden group">
                {{-- Decorative bg icon --}}
                <div class="absolute -right-3 -top-3 text-amber-400/10 rotate-12 group-hover:scale-110 transition-transform pointer-events-none">
                    <i class="fas fa-piggy-bank text-8xl"></i>
                </div>

                <div class="flex items-center gap-4 relative z-10">
                    <div class="w-11 h-11 rounded-full bg-amber-400 flex items-center justify-center shadow-lg shadow-amber-400/30 flex-shrink-0">
                        <i class="fas fa-coins text-white text-base"></i>
                    </div>
                    <div>
                        <p class="text-[10px] text-amber-700 font-extrabold uppercase tracking-widest mb-0.5">Rewards Earned</p>
                        <p class="text-lg font-black text-amber-900">
                            Earned Coins: {{ $coinsEarned ?? 0 }} Coins
                        </p>
                    </div>
                </div>
            </div>

        </div>

        {{-- ── Action buttons ── --}}
        <div class="flex flex-col sm:flex-row items-center gap-3">
            <a href="{{ route('technician.payments.create') }}"
                class="w-full sm:w-auto flex-1 inline-flex items-center justify-center gap-2 bg-sky-500 hover:bg-sky-600 text-white font-bold text-sm px-6 py-3.5 rounded-xl shadow-lg shadow-sky-400/30 hover:-translate-y-0.5 transition-all"
            >
                <i class="fas fa-plus-circle"></i>
                Create Another Payment
            </a>
            <a href="{{ route('technician.dashboard.index') }}"
                href="{{ route('technician.dashboard.index') }}"
                class="w-full sm:w-auto flex-1 inline-flex items-center justify-center gap-2 bg-white border border-slate-200 text-slate-700 font-bold text-sm px-6 py-3.5 rounded-xl hover:bg-slate-50 hover:-translate-y-0.5 transition-all"
            >
                <i class="fas fa-th-large"></i>
                Go to Dashboard
            </a>
        </div>

        {{-- ── Transaction ID footer ── --}}
        <p class="mt-8 text-[10px] text-slate-400 font-bold uppercase tracking-widest">
            Invoice ID: #{{ $lastTransactionId ?? '—' }} • Health Clinic MS Payment System
        </p>

    </div>

</div>