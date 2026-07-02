<div>
    <style>
        .image-box { min-height: 150px; }
        .preview-box { min-height: 200px; }
        .preview-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }
        [x-cloak] { display: none !important; }
    </style>

    <flux:modal name="edit-package" class="p-0" wire:close="closeModal">
        <div x-data="modalHandler()" @click.outside="$wire.closeModal()">
            <div class="relative max-w-6xl mx-auto p-4 sm:p-6 bg-gray-100 min-h-[600px]">

                <flux:modal.close
                    class="absolute top-2 right-2 sm:top-4 sm:right-4 text-gray-500 hover:text-gray-700 cursor-pointer z-10"
                    wire:click="closeModal" />

                <!-- HEADER -->
                <h2 class="text-xl font-semibold mb-2 pr-8">Edit Package</h2>
    <p class="text-sm text-gray-500 mb-6">Step {{ $step }} of 3</p>

    <!-- STEPPER -->
    <div class="flex justify-center mb-8" style="margin-left: 250px;">
        <div class="w-full max-w-3xl">
            <div class="flex items-center">
                @foreach([1,2,3] as $s)
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

                        @if($s < 3)
                            <div class="flex-1 h-1 mx-2 transition-all duration-300
                                {{ $step > $s ? 'bg-green-500' : 'bg-gray-300' }}">
                            </div>
                        @endif

                    </div>
                @endforeach
            </div>
        </div>
    </div>

    @if($packageId)
    <form wire:submit.prevent="updatePackage" method="POST" enctype="multipart/form-data"
        wire:key="edit-package-form-{{ $editSessionKey }}-{{ $packageId }}"
        x-data="{ imageUploading: false }"
        @image-uploading.window="imageUploading = $event.detail.uploading">
        @csrf

        <div @class(['hidden' => $step !== 1]) wire:key="edit-package-step-{{ $editSessionKey }}-1">
            @include('livewire.admin.organization.diagnostic.package.steps.edit-basic-info')
        </div>

        <div @class(['hidden' => $step !== 2]) wire:key="edit-package-step-{{ $editSessionKey }}-2">
            @include('livewire.admin.organization.diagnostic.package.steps.edit-select-lab-tests')
        </div>

        <div @class(['hidden' => $step !== 3]) wire:key="edit-package-step-{{ $editSessionKey }}-3">
            @include('livewire.admin.organization.diagnostic.package.steps.edit-pricing-details')
        </div>

        <div class="flex items-center justify-between mt-6">
            <flux:button variant="ghost" wire:click="closeModal" class="flex items-center gap-2 hover:text-red-600">
                <i class="fa-solid fa-times"></i>
                Cancel
            </flux:button>

            <div class="flex items-center gap-4">
                <flux:button variant="ghost" wire:click="back" :disabled="$step === 1" class="flex items-center gap-2 hover:text-red-600">
                    <i class="fa-solid fa-arrow-left-long"></i>
                    Back
                </flux:button>

                @if($step < 3)
                    <flux:button variant="ghost" wire:click="next" class="flex items-center gap-2 hover:text-green-600">
                        Next
                        <i class="fa-solid fa-arrow-right-long"></i>
                    </flux:button>
                @else
                    <flux:button variant="primary" wire:click="updatePackage" type="submit"
                         class="flex items-center gap-2 text-white hover:opacity-90 transition w-full sm:w-auto" 
                         style="background:var(--button-color); color:#ffffff !important;"
                        x-bind:disabled="imageUploading"
                        x-bind:class="{ 'opacity-50 cursor-not-allowed': imageUploading }">
                        <i class="fa-solid fa-spinner fa-spin mr-2 text-white" style="color:#ffffff !important;" x-show="imageUploading"></i>
                        <i class="fa-solid fa-check mr-2 text-white" style="color:#ffffff !important;" x-show="!imageUploading"></i>
                        <span class="text-white" style="color:#ffffff !important;" x-text="imageUploading ? 'Uploading...' : 'Update Package'"></span>
                    </flux:button>
                @endif
            </div>
        </div>
    </form>
    @endif
            </div>
        </div>
    </flux:modal>

    @script
    <script>
        function modalHandler() {
            return {}
        }
    </script>
    @endscript
</div>
