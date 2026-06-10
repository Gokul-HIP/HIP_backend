<div>
    <style>
        .image-box        { min-height: 150px; }
        .preview-img      { width: 100%; height: 200px; object-fit: cover; border-radius: 8px; }
        .step-label       { font-size: 11px; font-weight: 500; margin-top: 6px; white-space: nowrap; }
        .step-connector   { flex: 1; height: 2px; margin: 0 4px; margin-bottom: 14px; }
    </style>

    <flux:modal name="edit-disease-package" class="p-0 !max-w-2xl" wire:close="closeModal">
        <div class="flex flex-col bg-gray-50 min-h-[600px] rounded-xl overflow-hidden">

            {{-- ── Header ── --}}
            <div class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between flex-shrink-0">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Edit Disease Package</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Step {{ $step }} of 3 — fill in the details below</p>
                </div>
                <button type="button" wire:click="closeModal"
                    class="text-gray-400 hover:text-gray-600 w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            {{-- ── Step Progress ── --}}
            <div class="bg-white border-b border-gray-100 px-6 py-4 flex-shrink-0">
                <div class="flex items-center justify-between max-w-sm mx-auto">

                    {{-- Step 1 --}}
                    <div class="flex flex-col items-center">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-semibold
                            {{ $step > 1 ? 'bg-green-500 text-white' : ($step === 1 ? 'bg-[#0da2e7] text-white ring-4 ring-blue-100' : 'bg-gray-200 text-gray-500') }}">
                            @if($step > 1) <i class="fas fa-check text-xs"></i> @else 1 @endif
                        </div>
                        <span class="step-label {{ $step === 1 ? 'text-[#0da2e7]' : ($step > 1 ? 'text-green-600' : 'text-gray-400') }}">Basic Info</span>
                    </div>

                    <div class="step-connector {{ $step > 1 ? 'bg-green-400' : 'bg-gray-200' }}"></div>

                    {{-- Step 2 --}}
                    <div class="flex flex-col items-center">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-semibold
                            {{ $step > 2 ? 'bg-green-500 text-white' : ($step === 2 ? 'bg-[#0da2e7] text-white ring-4 ring-blue-100' : 'bg-gray-200 text-gray-500') }}">
                            @if($step > 2) <i class="fas fa-check text-xs"></i> @else 2 @endif
                        </div>
                        <span class="step-label {{ $step === 2 ? 'text-[#0da2e7]' : ($step > 2 ? 'text-green-600' : 'text-gray-400') }}">Lab Tests</span>
                    </div>

                    <div class="step-connector {{ $step > 2 ? 'bg-green-400' : 'bg-gray-200' }}"></div>

                    {{-- Step 3 --}}
                    <div class="flex flex-col items-center">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-semibold
                            {{ $step === 3 ? 'bg-[#0da2e7] text-white ring-4 ring-blue-100' : 'bg-gray-200 text-gray-500' }}">
                            3
                        </div>
                        <span class="step-label {{ $step === 3 ? 'text-[#0da2e7]' : 'text-gray-400' }}">Pricing</span>
                    </div>

                </div>
            </div>

            {{-- ── Step Content ── --}}
            <div class="flex-1 overflow-y-auto px-6 py-5">
                <form wire:submit.prevent="updateDiseasePackage">

                    @if($step === 1)
                        @include('livewire.admin.organization.diagnostic.disease-package.steps.edit-basic-info')
                    @elseif($step === 2)
                        @include('livewire.admin.organization.diagnostic.package.steps.edit-select-lab-tests')
                    @elseif($step === 3)
                        @include('livewire.admin.organization.diagnostic.package.steps.edit-pricing-details')
                    @endif

                    {{-- ── Footer Actions ── --}}
                    <div class="flex items-center justify-between pt-5 mt-2 border-t border-gray-200">
                        <button type="button" wire:click="closeModal"
                            class="px-4 py-2 text-sm font-medium text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition">
                            Cancel
                        </button>

                        <div class="flex items-center gap-3">
                            <button type="button" wire:click="back"
                                @if($step === 1) disabled @endif
                                class="px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 text-gray-600
                                       hover:bg-gray-100 transition disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-1.5">
                                <i class="fas fa-arrow-left text-xs"></i> Back
                            </button>

                            @if($step < 3)
                                <button type="button" wire:click="next"
                                    class="px-5 py-2 text-sm font-semibold rounded-lg bg-[#0da2e7] text-white
                                           hover:bg-[#0b8fd0] transition flex items-center gap-1.5">
                                    Next <i class="fas fa-arrow-right text-xs"></i>
                                </button>
                            @else
                                <button type="submit" wire:click="updateDiseasePackage"
                                    class="px-5 py-2 text-sm font-semibold rounded-lg bg-green-600 text-white
                                           hover:bg-green-700 transition flex items-center gap-1.5">
                                    <i class="fas fa-check text-xs"></i> Update Package
                                </button>
                            @endif
                        </div>
                    </div>

                </form>
            </div>

        </div>
    </flux:modal>
</div>