<div>
    <style>
        .image-box {
            min-height: 150px;
        }

        .preview-box {
            min-height: 200px;
        }

        .preview-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        [x-cloak] { display: none !important; }
    </style>

    <div class="bg-gray-50 min-h-screen">
        <div class="max-w-5xl mx-auto px-6 py-8">
            <!-- HEADER -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-semibold text-gray-900 mb-1">
                            {{ $isEdit ? 'Edit Wellness Centre' : 'Add New Wellness Centre' }}
                        </h2>
                        <p class="text-sm text-gray-500">Step {{ $step }} of {{ $totalSteps }}</p>
                    </div>
                    <a href="{{ route('admin.wellness-services.index') }}" 
                       class="text-gray-500 hover:text-gray-700 cursor-pointer w-10 h-10 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors">
                        <i class="fa-solid fa-times text-lg"></i>
                    </a>
                </div>
            </div>

            <!-- MAIN CARD -->
            <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden">
                <!-- STEPPER -->
                <div class="px-8 py-6 border-b border-gray-200 bg-gray-50">
                    <div class="flex justify-center">
                        <div class="w-full max-w-3xl ml-20">
                            <div class="flex items-center">
                                @foreach([1,2,3,4,5] as $s)
                                    <div class="flex-1 flex items-center">
                                        <div class="relative w-10 h-10 rounded-full flex items-center justify-center text-sm font-medium transition-all duration-300 shadow-sm
                                            {{ $step > $s ? 'bg-green-500 text-white' : ($step === $s ? 'bg-[#0da2e7] text-white ring-2 ring-[#0da2e7] ring-offset-2' : 'bg-gray-300 text-gray-700') }}">
                                            @if($step > $s)    
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            @else
                                                {{ $s }}
                                            @endif
                                        </div>

                                        @if($s < 5)
                                            <div class="flex-1 h-1 mx-3 transition-all duration-300 rounded-full
                                                {{ $step > $s ? 'bg-green-500' : 'bg-gray-300' }}">
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP CONTENT -->
                <div class="p-8">
                    @if($step === 1)
                        @include('livewire.admin.wellness-services.steps.centre-type')
                    @elseif($step === 2)
                        @include('livewire.admin.wellness-services.steps.basic-information')
                    @elseif($step === 3)
                        @include('livewire.admin.wellness-services.steps.location-address')
                    @elseif($step === 4)
                        @include('livewire.admin.wellness-services.steps.social-contact')
                    @elseif($step === 5)
                        @if($isEdit)
                            @include('livewire.admin.wellness-services.steps.legal-compliance-edit')
                        @else
                            @include('livewire.admin.wellness-services.steps.legal-compliance')
                        @endif
                    @endif
                </div>

                <!-- ACTION BUTTONS -->
                <div class="px-8 py-6 border-t border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <a href="{{ route('admin.wellness-services.index') }}" 
                           class="flex items-center gap-2 px-4 py-2 text-gray-600 hover:text-red-600 transition-colors rounded-lg hover:bg-red-50">
                            <i class="fa-solid fa-times"></i>
                            <span>Cancel</span>
                        </a>

                        <div class="flex items-center gap-3">
                            <button 
                                wire:click="back" 
                                :disabled="$step === 1"
                                class="flex items-center gap-2 px-5 py-2.5 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:border-gray-400 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm">
                                <i class="fa-solid fa-arrow-left-long"></i>
                                <span>Back</span>
                            </button>

                            @if($step < $totalSteps)
                                <button 
                                    wire:click="next"
                                    class="flex items-center gap-2 px-6 py-2.5 bg-[#0da2e7] text-white rounded-lg hover:bg-[#0b8dc7] transition-all shadow-sm font-medium">
                                    <span>Next</span>
                                    <i class="fa-solid fa-arrow-right-long"></i>
                                </button>
                            @else
                                <button 
                                    wire:click="save"
                                    wire:loading.attr="disabled"
                                    class="flex items-center gap-2 px-6 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 transition-all shadow-sm font-medium">
                                    <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                                        <i class="fa-solid fa-check"></i>
                                        <span>{{ $isEdit ? 'Update Wellness Centre' : 'Save Wellness Centre' }}</span>
                                    </span>
                                    <span wire:loading wire:target="save" class="flex items-center gap-2">
                                        <i class="fas fa-spinner fa-spin"></i>
                                        <span>{{ $isEdit ? 'Updating...' : 'Saving...' }}</span>
                                    </span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

