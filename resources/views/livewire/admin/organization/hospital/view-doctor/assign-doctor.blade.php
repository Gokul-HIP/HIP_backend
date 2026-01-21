<flux:modal name="assign-doctor" class="p-0" x-on:close="$wire.resetInput()">
<div class="p-6 bg-gray-100 min-h-[600px]">

    <!-- HEADER -->
    <h2 class="text-xl font-semibold mb-2">Assign Doctor to Procedure</h2>
    <p class="text-sm text-gray-500 mb-6">Step {{ $step }} of 4</p>

    <!-- STEPPER -->
    <div class="flex justify-center mb-8" style="margin-left: 180px;">
        <div class="w-full max-w-3xl">
            <div class="flex items-center">
                @foreach([1,2,3,4] as $s)
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

                        @if($s < 4)
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
        @include('livewire.admin.organization.hospital.view-doctor.steps.select-doctor')
    @elseif($step === 2)
        @include('livewire.admin.organization.hospital.view-doctor.steps.schedule')
    @elseif($step === 3)
        @include('livewire.admin.organization.hospital.view-doctor.steps.select-procedures')
    @elseif($step === 4)
        @include('livewire.admin.organization.hospital.view-doctor.steps.confirm')
    @endif

    <div class="flex items-center justify-between mt-6">

        <flux:button variant="ghost" wire:click="back" :disabled="$step === 1" class="flex items-center gap-2 hover:text-red-600">
            <i class="fa-solid fa-arrow-left-long"></i>
            Back
        </flux:button>

        @if($step < 4)
            <flux:button variant="ghost" wire:click="next" class="flex items-center gap-2 hover:text-green-600">
                Next
                <i class="fa-solid fa-arrow-right-long"></i>
            </flux:button>
        @else
            <flux:button variant="primary" wire:click="save" class="flex items-center gap-2 bg-green-600 text-white hover:bg-green-700">
                <i class="fa-solid fa-check text-white mr-2"></i>
                <span class="text-white">Confirm Assignment</span>
            </flux:button>
        @endif

    </div>

</div>
</flux:modal>