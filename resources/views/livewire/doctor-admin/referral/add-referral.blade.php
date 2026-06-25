{{-- livewire/doctor-admin/referral/add-referral.blade.php --}}
<div>

{{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     STEP 1 â€” Hospital & Doctor
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
@if($step === 1)
<div class="p-6">
    <div class="max-w-xl mx-auto bg-white rounded-2xl shadow-lg border border-slate-200 overflow-visible">

        {{-- Header --}}
        <div class="p-8 pb-6 border-b border-slate-100">
            <h1 class="text-2xl font-bold text-slate-900">Add New Referral</h1>
            <p class="text-slate-500 text-sm mt-1">Follow the steps to refer a patient to a specialized hospital.</p>

            {{-- Step Indicator --}}
            <div class="mt-7">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full flex-shrink-0 flex items-center justify-center text-xs font-bold text-white" style="background:#24a2e5;">1</span>
                        <span class="text-sm font-semibold text-slate-900">Hospital &amp; Doctor</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full flex-shrink-0 flex items-center justify-center text-xs font-bold text-slate-500" style="background:#e2e8f0;">2</span>
                        <span class="text-sm font-medium text-slate-400">Member Information</span>
                    </div>
                </div>
                <div class="w-full rounded-full overflow-hidden" style="height:6px;background:#f1f5f9;">
                    <div class="h-full rounded-full" style="width:50%;background:#24a2e5;"></div>
                </div>
                <div class="flex justify-between mt-2">
                    <span class="text-xs font-bold uppercase tracking-wider" style="color:#24a2e5;">Active</span>
                    <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Next Step</span>
                </div>
            </div>
        </div>

        {{-- Form --}}
        <div class="p-8 space-y-5">

            {{-- â”€â”€ Doctor Dropdown â”€â”€ --}}
            <div class="space-y-1.5 relative"
                x-data="{
                    open: false,
                    search: '',
                    selectedId: '{{ $doctor_id }}',
                    selectedLabel: '{{ $doctor_id ? $this->doctors->firstWhere('id', $doctor_id)?->name : '' }}',
                    items: {{ Js::from($this->doctors->map(fn($d) => ['id' => (string)$d->id, 'name' => $d->name])) }},
                    get filtered() {
                        if (!this.search) return this.items;
                        const q = this.search.toLowerCase();
                        return this.items.filter(i => i.name.toLowerCase().includes(q));
                    },
                    choose(id, name) {
                        this.selectedId = id;
                        this.selectedLabel = name;
                        this.open = false;
                        this.search = '';
                        $wire.set('doctor_id', id);
                        this.$nextTick(() => { this.open = false; });
                    }
                }"
                @click.outside="open = false"
                x-init="
                    $wire.$watch('doctor_id', (value) => {
                        if (value) {
                            const selected = items.find(i => String(i.id) === String(value));
                            if (selected) selectedLabel = selected.name;
                        }
                        open = false;
                    });
                "
                >

                <label class="text-sm font-semibold text-slate-700">Select Doctor</label>

                {{-- Trigger --}}
                <button type="button"
                    @click="if (items.length > 0) open = !open"
                    :disabled="items.length === 0"
                    class="w-full h-12 flex items-center justify-between px-4 rounded-xl text-sm transition-all focus:outline-none"
                    :style="open ? 'border-color:#24a2e5; box-shadow:0 0 0 3px rgba(36,162,229,0.15); background:#f8fafc; border:1px solid #24a2e5;' : 'background:#f8fafc; border:1px solid #e2e8f0;'">
                    <span :class="selectedLabel ? 'text-slate-800' : 'text-slate-400'"
                          x-text="selectedLabel || (items.length === 0 ? 'No doctors found' : 'Choose a doctor')"></span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                {{-- Panel --}}
                <div x-show="open && items.length >= 0" x-cloak
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    class="absolute left-0 right-0 z-50 mt-1 rounded-xl overflow-hidden"
                    style="background:white; border:1px solid #e2e8f0; box-shadow:0 10px 30px rgba(0,0,0,0.12);">

                    <div class="p-2.5" style="border-bottom:1px solid #f1f5f9;">
                        <div class="relative">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                            </svg>
                            <input x-model="search" @click.stop type="text" placeholder="Search doctor..."
                                x-effect="if(open) $nextTick(() => $el.focus())"
                                class="w-full pl-9 pr-4 py-2 text-sm rounded-lg focus:outline-none"
                                style="border:1px solid #e2e8f0; background:#f8fafc;">
                        </div>
                    </div>

                    <ul class="overflow-y-auto py-1" style="max-height:220px;">
                        <template x-if="filtered.length === 0">
                            <li class="px-4 py-3 text-sm text-center text-slate-400">No doctors found</li>
                        </template>
                        <template x-for="item in filtered" :key="item.id">
                            <li @click.stop="choose(item.id, item.name)"
                                class="flex items-center justify-between px-4 py-2.5 text-sm cursor-pointer transition-colors"
                                :style="selectedId == item.id ? 'background:#eff8ff; color:#24a2e5; font-weight:600;' : 'color:#374151;'"
                                @mouseenter="$el.style.background = selectedId == item.id ? '#eff8ff' : '#f8fafc'"
                                @mouseleave="$el.style.background = selectedId == item.id ? '#eff8ff' : 'transparent'">
                                <span x-text="item.name"></span>
                                <svg x-show="selectedId == item.id" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" style="color:#24a2e5;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            </li>
                        </template>
                    </ul>
                </div>

                <p x-show="items.length === 0" class="text-xs text-amber-600 mt-1">
                    No doctors found for this organization.
                </p>

                @error('doctor_id')
                    <p class="text-xs text-red-500 flex items-center gap-1 mt-0.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01"/></svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Info notice --}}
            <div class="flex gap-3 rounded-xl p-4" style="background:#eff8ff; border:1px solid #bfdbfe;">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0 mt-0.5" style="color:#24a2e5;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-xs text-slate-600 leading-relaxed">
                    The available doctors list is filtered to your organization.
                    Emergency referrals may require additional verification.
                </p>
            </div>

        </div>

        {{-- Footer --}}
        <div class="px-8 py-5 border-t border-slate-100 flex items-center justify-end gap-3" style="background:#f8fafc;">
            <button type="button" wire:click="cancel"
                class="px-6 py-2.5 text-sm font-semibold text-slate-600 rounded-xl hover:bg-slate-200 transition-colors">
                Cancel
            </button>
            <button type="button" wire:click="nextStep" wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 px-8 py-2.5 text-white text-sm font-bold rounded-full transition-all disabled:opacity-70"
                style="background:#24a2e5; box-shadow:0 4px 20px rgba(36,162,229,0.35);">
                <span wire:loading.remove wire:target="nextStep">Next Step</span>
                <span wire:loading wire:target="nextStep">Processing...</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>

    </div>
</div>
@endif


{{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     STEP 2 â€” Member Information
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
@if($step === 2)
<div class="p-6">
    <div class="max-w-xl mx-auto space-y-5">

        {{-- Breadcrumb + Title --}}
        <div class="mb-2">
            <p class="text-sm text-slate-500 mb-1">
                <a href="{{ route('doctor.referral.send') }}" class="hover:underline" style="color:#24a2e5;">Referrals</a>
                <span class="mx-1 text-slate-300">/</span>
                <span class="text-slate-700 font-medium">Add New Referral</span>
            </p>
            <h1 class="text-2xl font-bold text-slate-900">Add New Referral</h1>
            <p class="text-sm text-slate-500 mt-0.5">Please provide the member information to complete the referral.</p>
        </div>

        {{-- Card --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-lg overflow-hidden">
            {{-- Step Indicator (same style as Step 1) --}}
            <div class="p-8 pb-6 border-b border-slate-100" style="background:#f8fafc;">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full flex-shrink-0 flex items-center justify-center text-xs font-bold text-white" style="background:#24a2e5;">1</span>
                            <span class="text-sm font-semibold text-slate-900">Hospital &amp; Doctor</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full flex-shrink-0 flex items-center justify-center text-xs font-bold text-white" style="background:#24a2e5;">2</span>
                            <span class="text-sm font-semibold text-slate-900">Member Information</span>
                        </div>
                    </div>
                    <div class="w-full rounded-full overflow-hidden" style="height:6px;background:#f1f5f9;">
                        <div class="h-full rounded-full" style="width:100%;background:#24a2e5;"></div>
                    </div>
                    <div class="flex justify-between mt-2">
                        <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Completed</span>
                        <span class="text-xs font-bold uppercase tracking-wider" style="color:#24a2e5;">Active</span>
                    </div>
                </div>
            </div>
            {{-- Form Body --}}
            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-slate-700">Member Name</label>
                        <div class="relative">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <input type="text" wire:model="member_name" placeholder="Enter patient's full name"
                                class="w-full h-12 pl-11 pr-4 rounded-full text-sm placeholder-slate-400 border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-[#24a2e5]/20 focus:border-[#24a2e5]">
                        </div>
                        @error('member_name') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-slate-700">Phone Number</label>
                        <div class="flex gap-3">
                            <div class="relative w-24 md:w-28">
                                <select wire:model="phone_code"
                                    class="w-full h-12 pl-3 pr-8 rounded-full text-sm border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-[#24a2e5]/20 focus:border-[#24a2e5]"
                                    style="-webkit-appearance:none;-moz-appearance:none;appearance:none;">
                                    <option value="+1">US +1</option>
                                    <option value="+91">IN +91</option>
                                    <option value="+44">UK +44</option>
                                    <option value="+61">AU +61</option>
                                </select>
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                            <input type="tel" wire:model="phone_number" placeholder="9876543210" maxlength="10"
                                class="flex-1 h-12 px-4 rounded-full text-sm placeholder-slate-400 border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-[#24a2e5]/20 focus:border-[#24a2e5]">
                        </div>
                        @error('phone_number') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-slate-700">Referral Date</label>
                        <div class="relative">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <input type="date" wire:model="referral_date"
                                class="w-full h-12 pl-11 pr-10 rounded-full text-sm border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-[#24a2e5]/20 focus:border-[#24a2e5]">
                        </div>
                        @error('referral_date') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-slate-700">Insurance / Member ID</label>
                        <div class="relative">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                            <input type="text" wire:model="member_id" placeholder="Optional ID number"
                                class="w-full h-12 pl-11 pr-4 rounded-full text-sm placeholder-slate-400 border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-[#24a2e5]/20 focus:border-[#24a2e5]">
                        </div>
                    </div>
                </div>

                <div class="mt-6 space-y-2">
                    <label class="text-sm font-semibold text-slate-700">Medical Notes / Reason for Referral</label>
                    <textarea wire:model="medical_notes" rows="5"
                        placeholder="Provide detailed medical history or reason for this specific referral..."
                        class="w-full p-4 rounded-2xl text-sm placeholder-slate-400 resize-none border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-[#24a2e5]/20 focus:border-[#24a2e5]"></textarea>
                    @error('medical_notes') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Footer Actions --}}
            <div class="px-8 py-5 border-t border-slate-200 flex items-center justify-between bg-slate-50">
                <button type="button" wire:click="prevStep"
                    class="inline-flex items-center gap-2 text-slate-600 text-sm font-semibold hover:text-slate-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Back
                </button>
                <div class="flex items-center gap-3">
                    <button type="button" wire:click="saveDraft" wire:loading.attr="disabled"
                        class="h-11 px-8 rounded-full text-slate-600 text-sm font-semibold border border-slate-300 bg-white hover:bg-slate-100 disabled:opacity-60">
                        Save Draft
                    </button>
                    <button type="button" wire:click="submit" wire:loading.attr="disabled"
                        class="primary-btn inline-flex items-center gap-2 h-11 px-9 rounded-full text-sm font-bold disabled:opacity-80">
                        <span wire:loading.remove wire:target="submit">Create Referral</span>
                        <span wire:loading wire:target="submit">Creating...</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/>
                        </svg>
                    </button>
                </div>
            </div>

        </div>

        {{-- HIPAA footer --}}
        <div class="flex items-center justify-center gap-2 text-slate-400 text-xs py-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            All medical information is encrypted
        </div>

    </div>
</div>
@endif

</div>
