<flux:modal name="bulk-add-specialitie" class="p-0" wire:close="closeModal()">
    <div 
        x-data="{ modalReady: false }"
        x-init="
            $el.closest('dialog').addEventListener('click', (e) => {
                if (e.target === e.currentTarget && modalReady) {
                    $wire.closeModal();
                }
            });
        "
        @modal-show.window="
            if ($event.detail.name === 'bulk-add-specialitie') {
                modalReady = false;
                setTimeout(() => modalReady = true, 300);
            }
        "
    >
<div class="p-4 sm:p-6 bg-gray-100 min-h-[600px]">

    <!-- HEADER -->
    <h2 class="text-xl font-semibold mb-2">Add Specialities in Bulk</h2>
    <p class="text-sm text-gray-500 mb-6">Step {{ $step }} of 2</p>

    <button type="button"
        wire:click="closeModal"
        class="absolute top-2 right-2 sm:top-4 sm:right-4 text-gray-500 hover:text-gray-700 cursor-pointer z-50 w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition">
        ✕
    </button>

    <!-- STEPPER -->
    <div class="flex justify-center mb-8" style="margin-left: 250px;">
        <div class="w-full max-w-3xl">
            <div class="flex items-center">
                @foreach([1,2] as $s)
                    <div class="flex-1 flex items-center">
                        
                        <div class="relative w-10 h-10 rounded-full flex items-center justify-center text-sm font-medium transition-all duration-300
                            {{ $step > $s ? 'bg-green-500 text-white' : ($step === $s ? 'bg-[#0da2e7] text-white' : 'bg-gray-300 text-gray-700') }}">
                            
                            @if($step > $s)    
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                </svg>
                            @else
                                {{ $s }}
                            @endif
                        </div>

                        @if($s < 2)
                            <div class="flex-1 h-1 mx-2 transition-all duration-300
                                {{ $step > $s ? 'bg-green-500' : 'bg-gray-300' }}">
                            </div>
                        @endif

                    </div>
                @endforeach
            </div>
        </div>
    </div>

    @if($step === 1)
        @include('livewire.admin.organization.hospital.specialitie.steps.select-specialities')
    @elseif($step === 2)
        @include('livewire.admin.organization.hospital.specialitie.steps.review-specialities')
    @endif

    {{-- <div class="flex items-center justify-between mt-6">

        <flux:button variant="ghost" wire:click="back" :disabled="$step === 1" class="flex items-center gap-2 hover:text-red-600">
            <i class="fa-solid fa-arrow-left-long"></i>
            Back
        </flux:button>

        @if($step < 2)
            <flux:button variant="ghost" wire:click="next" class="flex items-center gap-2 hover:text-green-600">
                Next
                <i class="fa-solid fa-arrow-right-long"></i>
            </flux:button>
        @else
            <flux:button variant="primary" wire:click="save" class="flex items-center gap-2 bg-green-600 text-white hover:bg-green-700">
                <i class="fa-solid fa-check mr-2 text-white"></i>
                <span class="hidden sm:inline text-white">Save Specialities</span>
                <span class="sm:hidden text-white">Save</span>
            </flux:button>
        @endif

    </div> --}}

    <div class="flex items-center justify-between mt-6">
        <flux:button variant="ghost" wire:click="closeModal" class="flex items-center gap-2 hover:text-red-600">
            <i class="fa-solid fa-times" ></i>
            Cancel
        </flux:button>

        <div class="flex items-center gap-4">
            <flux:button variant="ghost" wire:click="back" :disabled="$step === 1" class="flex items-center gap-2 hover:text-red-600">
                <i class="fa-solid fa-arrow-left-long"></i>
                Back
            </flux:button>

            @if($step < 2)
                <flux:button variant="ghost" wire:click="next" class="flex items-center gap-2 hover:text-green-600">
                    Next
                    <i class="fa-solid fa-arrow-right-long"></i>
                </flux:button>
            @else
                <flux:button variant="primary" wire:click="save" class="flex items-center gap-2 bg-green-600 text-white hover:bg-green-700">
                    <i class="fa-solid fa-check mr-2 text-white"></i>
                    <span class="hidden sm:inline text-white" style="color:#ffffff !important;">Save Specialities</span>
                    <span class="sm:hidden text-white" style="color:#ffffff !important;">Save</span>
                </flux:button>
            @endif
        </div>
    </div>

</div>
</flux:modal>

