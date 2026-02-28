{{-- ============================================================
   Step 4: Pharmacy Details
   Path: resources/views/livewire/cashier/payment/steps/pharmacy.blade.php
   Uses: Font Awesome + Tailwind only
   ============================================================ --}}

   <div class="space-y-6 pb-10">

    {{-- ══════════════════════════════
         TWO COLUMN: Prescription + Payment Amount
    ══════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

        {{-- ── LEFT: Prescription Upload ── --}}
        <div class="space-y-3">
            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <i class="fas fa-file-alt text-sky-500 text-base"></i>
                Prescription
            </h3>

            {{-- Upload zone --}}
            <label
                for="prescription-upload"
                class="group block bg-white border-2 border-dashed border-slate-300 hover:border-sky-400 rounded-xl cursor-pointer transition-all"
                x-data="{ dragging: false }"
                @dragover.prevent="dragging = true"
                @dragleave.prevent="dragging = false"
                @drop.prevent="dragging = false"
                :class="dragging ? 'border-sky-400 bg-sky-50' : ''"
            >
                <div class="flex flex-col items-center justify-center text-center py-10 px-8">
                    <div class="w-14 h-14 rounded-full bg-slate-100 group-hover:bg-sky-50 flex items-center justify-center mb-3 transition-all group-hover:scale-110">
                        <i class="fas fa-cloud-upload-alt text-2xl text-slate-400 group-hover:text-sky-500 transition-colors"></i>
                    </div>
                    <p class="font-bold text-slate-900 text-sm mb-1">Upload Prescription</p>
                    <p class="text-sm text-slate-500">Drag and drop or click to browse</p>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-4">
                        Supports PDF, JPG, PNG
                    </p>
                </div>

                <input
                    id="prescription-upload"
                    type="file"
                    wire:model="prescriptionFile"
                    accept=".pdf,.jpg,.jpeg,.png"
                    class="hidden"
                >
            </label>

            {{-- Uploaded file indicator --}}
            @if($prescriptionFile ?? false)
            <div class="flex items-center gap-3 bg-sky-50 border border-sky-200 rounded-lg px-4 py-3">
                <i class="fas fa-file text-sky-500 flex-shrink-0"></i>
                <span class="text-sm text-sky-700 font-medium truncate flex-1">
                    {{ $prescriptionFile->getClientOriginalName() }}
                </span>
                <button
                    wire:click="$set('prescriptionFile', null)"
                    class="text-slate-400 hover:text-red-500 transition-colors flex-shrink-0"
                >
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>
            @endif

            @error('prescriptionFile')
                <p class="text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>


        {{-- ── RIGHT: Payment Amount ── --}}
        <div class="space-y-3">
            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <i class="fas fa-wallet text-sky-500 text-base"></i>
                Payment Amount
            </h3>

            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">

                <div class="p-6 space-y-5">

                    {{-- Label --}}
                    <p class="text-sm font-semibold text-slate-700">Total Pharmacy Charges</p>

                    {{-- Big $ input — flat, no inner border --}}
                    <div class="flex items-center gap-2">
                        <span class="text-xl font-bold text-slate-400 flex-shrink-0">₹</span>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            wire:model.lazy="pharmacyAmount"
                            placeholder="0.00"
                            class="flex-1 bg-transparent border-none outline-none text-3xl font-black text-slate-900 placeholder:text-slate-300 min-w-0"
                            style="background-color: transparent;" >
                    </div>

                    {{-- Helper text --}}
                    <p class="text-xs text-slate-400 italic">
                        Please enter the total value as per the physical pharmacy receipt.
                    </p>

                </div>

                {{-- Divider row --}}
                <div class="border-t border-slate-100 px-6 py-4 flex justify-between items-center">
                    <span class="text-sm font-medium text-slate-500">Other Services Total</span>
                    <span class="text-sm font-bold text-slate-900">
                        ₹{{ number_format($otherServicesTotal ?? 140.00, 2) }}
                    </span>
                </div>

            </div>
        </div>

    </div>


    {{-- ══════════════════════════════
         PHARMACY POLICY NOTICE
    ══════════════════════════════ --}}
    <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 flex items-start gap-3">
        <div class="flex-shrink-0 w-7 h-7 rounded-full bg-blue-500 flex items-center justify-center mt-0.5">
            <i class="fas fa-info text-white text-[10px]"></i>
        </div>
        <div>
            <p class="text-sm font-bold text-blue-700 mb-0.5">Pharmacy Policy</p>
            <p class="text-sm text-blue-600 leading-relaxed">
                Pharmacy charges are added to the final invoice. Ensure the prescription is uploaded
                for auditing purposes before proceeding to the final review step.
            </p>
        </div>
    </div>

</div>