{{-- ============================================================
   Step 4: Pharmacy Details
   Path: resources/views/livewire/cashier/payment/steps/pharmacy.blade.php
   Uses: Font Awesome + Tailwind + Alpine.js
   ============================================================ --}}
   <style>[x-cloak] { display: none !important; }</style>

   <div class="space-y-6 pb-10">
   
       {{-- ══════════════════════════════
            TWO COLUMN: Prescription + Payment Amount
       ══════════════════════════════ --}}
       <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
   
           {{-- ── LEFT: Prescription Upload ── --}}
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
                        if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
                        this.previewUrl = null;
                        this.fileName = null;
                        this.isPdf = false;
                        const fi = document.getElementById('prescription-upload');
                        if (fi) fi.value = '';
                        $wire.set('prescriptionFile', null);
                    }
                }"
                @reset-file-input.window="
                    if (previewUrl) URL.revokeObjectURL(previewUrl);
                    previewUrl = null; fileName = null; isPdf = false;
                    const fi = document.getElementById('prescription-upload');
                    if (fi) fi.value = '';
                ">
   
               <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                   <i class="fas fa-file-alt" style="color: var(--button-color);"></i>
                   Prescription
               </h3>
   
               {{-- ── Upload zone (shown when no file selected) ── --}}
               <div x-show="!previewUrl && !fileName"
                    @dragover.prevent="$el.classList.add('border-slate-200','bg-slate-50')"
                    @dragleave.prevent="$el.classList.remove('border-sky-400','bg-sky-50')"
                    @drop.prevent="$el.classList.remove('border-sky-400','bg-sky-50'); handleFileChange({target:{files:$event.dataTransfer.files}})"
                    onclick="document.getElementById('prescription-upload').click()"
                    class="group bg-white border-2 border-dashed border-slate-300 hover:border-sky-400 hover:bg-sky-50/40 rounded-xl cursor-pointer transition-all">
                   <div class="flex flex-col items-center justify-center text-center py-10 px-8">
                       <div class="w-14 h-14 rounded-full bg-slate-100 group-hover:bg-sky-100 flex items-center justify-center mb-3 transition-all group-hover:scale-110">
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
                       @change="handleFileChange($event)"
                   >
               </div>
   
               {{-- ── Image preview ── --}}
               <div x-show="previewUrl"
                    x-cloak
                    class="relative rounded-xl overflow-hidden border border-slate-200 shadow-sm bg-slate-100">
                   <img :src="previewUrl" class="w-full h-52 object-cover" alt="Prescription preview">
                   <div class="absolute inset-0 bg-black/0 hover:bg-black/10 transition-all flex items-start justify-end p-2">
                       <button type="button"
                               @click.stop="clearPreview()"
                               class="w-8 h-8 rounded-full bg-red-500 hover:bg-red-600 text-white flex items-center justify-center shadow-lg transition-all">
                           <i class="fas fa-times text-xs"></i>
                       </button>
                   </div>
                   <div class="bg-white border-t border-slate-100 px-4 py-2.5 flex items-center gap-2">
                       <i class="fas fa-image text-sky-500 text-sm flex-shrink-0"></i>
                       <span class="text-xs font-medium text-slate-700 truncate flex-1" x-text="fileName"></span>
                       <span class="text-[10px] font-medium text-emerald-600 flex-shrink-0 flex items-center gap-1">
                           <i class="fas fa-check-circle"></i> Ready
                       </span>
                   </div>
               </div>
   
               {{-- ── PDF / non-image file preview ── --}}
               <div x-show="fileName && !previewUrl"
                    x-cloak
                    class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                   <div class="flex items-center gap-4 p-5">
                       <div class="w-14 h-14 rounded-xl flex items-center justify-center flex-shrink-0"
                            :class="isPdf ? 'bg-red-50' : 'bg-sky-50'">
                           <i class="text-3xl"
                              :class="isPdf ? 'fas fa-file-pdf text-red-500' : 'fas fa-file-alt text-sky-500'"></i>
                       </div>
                       <div class="flex-1 min-w-0">
                           <p class="text-sm font-semibold text-slate-900 truncate" x-text="fileName"></p>
                           <p class="text-xs text-slate-400 mt-0.5" x-text="isPdf ? 'PDF Document' : 'Document'"></p>
                       </div>
                       <button type="button"
                               @click.stop="clearPreview()"
                               class="w-8 h-8 rounded-full bg-red-50 hover:bg-red-100 text-red-500 flex items-center justify-center flex-shrink-0 transition-all">
                           <i class="fas fa-times text-xs"></i>
                       </button>
                   </div>
                   <div class="bg-emerald-50 border-t border-emerald-100 px-5 py-2 flex items-center gap-2">
                       <i class="fas fa-check-circle text-emerald-500 text-xs"></i>
                       <span class="text-xs font-medium text-emerald-700">File ready to upload</span>
                   </div>
               </div>
   
               {{-- ── Livewire server-side fallback ── --}}
               @if($prescriptionFile ?? false)
               <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden"
                    x-show="!fileName && !previewUrl">
                   <div class="flex items-center gap-4 p-5">
                       <div class="w-14 h-14 rounded-xl bg-sky-50 flex items-center justify-center flex-shrink-0">
                           <i class="fas fa-file-alt text-sky-500 text-3xl"></i>
                       </div>
                       <div class="flex-1 min-w-0">
                           <p class="text-sm font-semibold text-slate-900 truncate">{{ $prescriptionFile->getClientOriginalName() }}</p>
                           <p class="text-xs text-slate-400 mt-0.5">Document</p>
                       </div>
                       <button type="button"
                               wire:click="$set('prescriptionFile', null)"
                               class="w-8 h-8 rounded-full bg-red-50 hover:bg-red-100 text-red-500 flex items-center justify-center flex-shrink-0 transition-all">
                           <i class="fas fa-times text-xs"></i>
                       </button>
                   </div>
                   <div class="bg-emerald-50 border-t border-emerald-100 px-5 py-2 flex items-center gap-2">
                       <i class="fas fa-check-circle text-emerald-500 text-xs"></i>
                       <span class="text-xs font-medium text-emerald-700">File ready to upload</span>
                   </div>
               </div>
               @endif
   
               @error('prescriptionFile')
                   <p class="text-xs text-red-500 flex items-center gap-1.5 mt-1">
                       <i class="fas fa-exclamation-circle"></i> {{ $message }}
                   </p>
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
                       <p class="text-sm font-semibold text-slate-700">Total Pharmacy Charges</p>
   
                       <div class="flex items-center gap-2">
                           <span class="text-xl font-bold text-slate-400 flex-shrink-0">₹</span>
                           <input
                               type="number"
                               step="0.01"
                               min="0"
                               wire:model.lazy="pharmacyAmount"
                               placeholder="0.00"
                               class="flex-1 bg-transparent border-none outline-none text-3xl font-black text-slate-900 placeholder:text-slate-300 min-w-0"
                           >
                       </div>
   
                       <p class="text-xs text-slate-400 italic">
                           Please enter the total value as per the physical pharmacy receipt.
                       </p>
                   </div>
   
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