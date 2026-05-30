<div>
    <style>
        .image-box { min-height: 150px; }
        .preview-img { width: 100%; height: 200px; object-fit: cover; border-radius: 8px; }
    </style>

    <flux:modal name="edit-disease-package" class="p-0" wire:close="closeModal">
        <div class="p-4 sm:p-6 bg-gray-100 min-h-[600px]">
            <h2 class="text-xl font-semibold mb-2">Edit Disease Package</h2>
            <p class="text-sm text-gray-500 mb-6">Step {{ $step }} of 3</p>

            <button type="button" wire:click="closeModal"
                class="absolute top-2 right-2 sm:top-4 sm:right-4 text-gray-500 hover:text-gray-700 z-50 w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100">✕</button>

            <div class="flex justify-center mb-8" style="margin-left: 250px;">
                <div class="w-full max-w-3xl">
                    <div class="flex items-center">
                        @foreach([1,2,3] as $s)
                            <div class="flex-1 flex items-center">
                                <div class="relative w-10 h-10 rounded-full flex items-center justify-center text-sm font-medium
                                    {{ $step > $s ? 'bg-green-500 text-white' : ($step === $s ? 'bg-[#0da2e7] text-white' : 'bg-gray-300 text-gray-700') }}">
                                    @if($step > $s) ✓ @else {{ $s }} @endif
                                </div>
                                @if($s < 3)
                                    <div class="flex-1 h-1 mx-2 {{ $step > $s ? 'bg-green-500' : 'bg-gray-300' }}"></div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <form wire:submit.prevent="updateDiseasePackage">
                @if($step === 1)
                    @include('livewire.admin.organization.diagnostic.disease-package.steps.edit-basic-info')
                @elseif($step === 2)
                    @include('livewire.admin.organization.diagnostic.package.steps.edit-select-lab-tests')
                @elseif($step === 3)
                    @include('livewire.admin.organization.diagnostic.package.steps.edit-pricing-details')
                @endif

                <div class="flex items-center justify-between mt-6">
                    <flux:button variant="ghost" wire:click="closeModal" class="hover:text-red-600">Cancel</flux:button>
                    <div class="flex items-center gap-4">
                        <flux:button variant="ghost" wire:click="back" :disabled="$step === 1">Back</flux:button>
                        @if($step < 3)
                            <flux:button variant="ghost" wire:click="next">Next</flux:button>
                        @else
                            <flux:button variant="primary" wire:click="updateDiseasePackage" type="submit" class="bg-green-600 text-white">Update Package</flux:button>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
