{{-- ============================================================
   Step 4: Pharmacy Details
   Path: resources/views/livewire/cashier/payment/steps/pharmacy.blade.php
   Uses: Font Awesome + Tailwind only (file upload style like add-organization)
   ============================================================ --}}
<style>[x-cloak] { display: none !important; }</style>

   <div class="space-y-6 pb-10">

    {{-- ══════════════════════════════
         TWO COLUMN: Prescription + Payment Amount
    ══════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

        {{-- ── LEFT: Prescription Upload (same style as add-organization) ── --}}
        <div class="space-y-3"
             x-data="{
                 previewUrl: null,
                 fileName: null,
                 isPdf: false,
                 handleFileChange(event) {
                     const file = event.target.files[0];
                     if (file) {
                         this.fileName = file.name;
                         this.isPdf = (file.type === 'application/pdf');
                         if (file.type.startsWith('image/')) {
                             this.previewUrl = URL.createObjectURL(file);
                         } else {
                             this.previewUrl = null;
                         }
                     }
                 },
                 clearPreview() {
                     if (this.previewUrl) {
                         URL.revokeObjectURL(this.previewUrl);
                     }
                     this.previewUrl = null;
                     this.fileName = null;
                     this.isPdf = false;
                     const fileInput = document.getElementById('prescription-upload');
                     if (fileInput) fileInput.value = '';
                     $wire.set('prescriptionFile', null);
                 }
             }"
             @reset-file-input.window="
                 if (previewUrl) URL.revokeObjectURL(previewUrl);
                 previewUrl = null;
                 fileName = null;
                 isPdf = false;
                 const fileInput = document.getElementById('prescription-upload');
                 if (fileInput) fileInput.value = '';
             ">
            <label class="block text-sm font-bold text-slate-900 mb-2 flex items-center gap-2">
                <i class="fas fa-file-alt text-sky-500 text-base"></i>
                Prescription
            </label>

            {{-- Upload box (same style as add-organization) --}}
            <div onclick="document.getElementById('prescription-upload').click()"
                 class="min-h-[150px] border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center cursor-pointer hover:border-gray-400 transition-colors bg-gray-50"
                 x-show="!previewUrl && !fileName">

                <div class="text-center p-4">
                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                    <p class="text-sm text-gray-700">Click to upload</p>
                    <p class="text-xs text-gray-500 mt-1">or drag and drop</p>
                    <p class="text-xs text-gray-400 mt-1">PDF, JPG, PNG up to 2MB</p>
                </div>

                <input type="file"
                       id="prescription-upload"
                       wire:model="prescriptionFile"
                       class="hidden"
                       accept=".pdf,.jpg,.jpeg,.png"
                       @change="handleFileChange($event)">
            </div>

            @error('prescriptionFile')
                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror

            {{-- Preview: image --}}
            <div class="min-h-[200px] border border-gray-300 rounded-lg relative overflow-hidden bg-gray-50"
                 x-show="previewUrl"
                 x-cloak
                 style="display: none;">
                <img :src="previewUrl" class="w-full h-[200px] object-cover rounded-lg" alt="Prescription preview">
                <button type="button"
                        @click.stop="clearPreview()"
                        class="absolute top-2 right-2 bg-red-600 text-white w-8 h-8 flex items-center justify-center rounded-full text-sm font-bold shadow hover:bg-red-700 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Preview: PDF or fallback (file name only when no image preview) --}}
            <div class="min-h-[120px] border border-gray-300 rounded-lg relative flex items-center justify-center p-4 bg-gray-50"
                 x-show="fileName && !previewUrl"
                 x-cloak
                 style="display: none;">
                <div class="text-center flex-1">
                    <i class="fas fa-file-pdf text-4xl text-red-500 mb-2" x-show="isPdf"></i>
                    <i class="fas fa-file-alt text-4xl text-gray-500 mb-2" x-show="!isPdf"></i>
                    <p class="text-sm text-gray-700 font-medium truncate px-4" x-text="fileName"></p>
                </div>
                <button type="button"
                        @click.stop="clearPreview()"
                        class="absolute top-2 right-2 bg-red-600 text-white w-8 h-8 flex items-center justify-center rounded-full text-sm font-bold shadow hover:bg-red-700 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Fallback when Livewire has file (e.g. after re-render) and Alpine state not yet set --}}
            @if($prescriptionFile ?? false)
            <div class="min-h-[120px] border border-gray-300 rounded-lg relative flex items-center justify-center p-4 bg-gray-50"
                 x-show="!fileName && !previewUrl">
                <div class="text-center flex-1">
                    <i class="fas fa-file-alt text-4xl text-gray-500 mb-2"></i>
                    <p class="text-sm text-gray-700 font-medium truncate px-4">{{ $prescriptionFile->getClientOriginalName() }}</p>
                </div>
                <button type="button"
                        wire:click="$set('prescriptionFile', null)"
                        class="absolute top-2 right-2 bg-red-600 text-white w-8 h-8 flex items-center justify-center rounded-full text-sm font-bold shadow hover:bg-red-700 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            @endif
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