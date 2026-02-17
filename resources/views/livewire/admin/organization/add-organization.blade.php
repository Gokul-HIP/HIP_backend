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
    
    /* Force light mode on modal - override dark mode */
    [data-flux-modal="add-organization"] dialog,
    [data-flux-modal="add-organization"] dialog * {
        color-scheme: light !important;
        background-color: #ffffff !important;
        color: #111827 !important;
        border-color: #d1d5db !important;
    }
    
    [data-flux-modal="add-organization"] dialog {
        background-color: #ffffff !important;
        border-color: #d1d5db !important;
    }
    
    /* Force light borders on all elements */
    [data-flux-modal="add-organization"] dialog input,
    [data-flux-modal="add-organization"] dialog textarea,
    [data-flux-modal="add-organization"] dialog select,
    [data-flux-modal="add-organization"] dialog button,
    [data-flux-modal="add-organization"] dialog div,
    [data-flux-modal="add-organization"] dialog .border,
    [data-flux-modal="add-organization"] dialog [class*="border"] {
        border-color: #d1d5db !important;
    }
    </style>

    <flux:modal name="add-organization" class="p-0" wire:close="closeModal">
        <div x-data="{ modalReady: false }" 
             x-init="
                $el.closest('dialog').addEventListener('click', (e) => {
                    if (e.target === e.currentTarget && modalReady) {
                        $wire.closeModal();
                    }
                });
             "
             @modal-show.window="
                if ($event.detail.name === 'add-organization') {
                    modalReady = false;
                    $wire.resetInput().then(() => {
                        setTimeout(() => modalReady = true, 300);
                    });
                }
             ">
            
        <button type="button"
            wire:click="closeModal"
            class="absolute top-2 right-2 sm:top-4 sm:right-4 text-gray-500 hover:text-gray-700 cursor-pointer z-50 w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

            <div class="relative max-w-6xl mx-auto" @click.stop>

        <h1 class="text-xl sm:text-2xl font-semibold mb-4 sm:mb-6 pr-8">Add New Organization</h1>

        <form wire:submit.prevent="addNewOrg" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="space-y-6">
                <!-- First Row: Organization Name and Logo -->  
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Organization Name -->
                    <div>
                        <label class="block text-sm font-medium mb-2">Organization Name <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="org_name"
                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                            placeholder="Enter Organization Name">
                        @error('org_name')
                            <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                        @enderror 
                    </div>

                    <!-- Upload Logo -->
                    <div x-data="{ 
                        previewUrl: null,
                        handleFileChange(event) {
                            const file = event.target.files[0];
                            if (file) {
                                this.previewUrl = URL.createObjectURL(file);
                            }
                        },
                        clearPreview() {
                            if (this.previewUrl) {
                                URL.revokeObjectURL(this.previewUrl);
                            }
                            this.previewUrl = null;
                            const fileInput = document.getElementById('orgLogo');
                            if (fileInput) fileInput.value = '';
                            $wire.removeImage();
                        }
                    }"
                    @reset-file-input.window="
                        if (previewUrl) {
                            URL.revokeObjectURL(previewUrl);
                        }
                        previewUrl = null;
                        const fileInput = document.getElementById('orgLogo');
                        if (fileInput) fileInput.value = '';
                    ">
                        <label class="block text-sm font-medium mb-2">Upload Organization Logo/Image</label>

                        <!-- Upload Box -->
                        <div onclick="document.getElementById('orgLogo').click()"
                            class="image-box border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center 
                                   cursor-pointer hover:border-gray-400 transition-colors bg-gray-50"
                            x-show="!previewUrl">

                            <div class="text-center p-4">
                                <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                <p class="text-sm text-gray-700">Click to upload</p>
                                <p class="text-xs text-gray-500 mt-1">or drag and drop</p>
                                <p class="text-xs text-gray-400 mt-1">PNG, JPG up to 2MB</p>
                            </div>

                            <input type="file" 
                                   id="orgLogo" 
                                   wire:model="org_logo" 
                                   class="hidden"
                                   accept="image/*"
                                   @change="handleFileChange($event)">
                        </div>

                        @error('org_logo')
                            <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                        @enderror

                        <!-- Preview Box -->
                        <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden" 
                             x-show="previewUrl"
                             x-cloak>
                            <img :src="previewUrl" class="preview-img" alt="Preview">
                            <button type="button"
                                @click.stop="clearPreview()"
                                class="absolute top-2 right-2 bg-red-600 text-white w-8 h-8 flex items-center justify-center 
                                    rounded-full text-sm font-bold shadow hover:bg-red-700 transition">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Second Row: City and Address -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- City -->
                    <div>
                        <label class="block text-sm font-medium mb-2">City <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="org_city"
                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                            placeholder="Enter City">
                        @error('org_city')
                            <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Address -->
                    <div>
                        <label class="block text-sm font-medium mb-2">Address <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="org_address"
                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                            placeholder="Enter Address">
                        @error('org_address')
                            <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- Status Toggle -->
                <div>
                    <label class="block text-sm font-medium mb-3">Status</label>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-700">Inactive</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model.live="status" class="sr-only">
                            <span class="w-12 h-6 rounded-full flex items-center px-1 transition-all duration-300 
                                {{ $status ? 'bg-[#0da2e7]' : 'bg-gray-400' }}">
                                <span class="dot w-5 h-5 bg-white rounded-full transition-all duration-300 shadow-sm
                                    {{ $status ? 'translate-x-6' : 'translate-x-0' }}">
                                </span>
                            </span>
                        </label>
                        <span class="text-sm text-gray-800 font-medium">Active</span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row justify-end gap-3 sm:gap-4 pt-4 border-t">  
                    <flux:button class="px-6 py-2.5 bg-red-500 text-white rounded-lg text-sm font-medium transition w-full sm:w-auto" 
                                 style="background:#f14336" 
                                 wire:click="closeModal" 
                                 type="button">
                        <i class="fa-solid fa-times mr-2 text-white"></i>
                        <span class="hidden sm:inline text-white">Cancel</span>
                        <span class="sm:hidden text-white">Cancel</span>
                    </flux:button>

                    <flux:button class="px-6 py-2.5 bg-gray-300 rounded-lg text-sm font-medium transition w-full sm:w-auto" 
                                 style="background:#6b7280" 
                                 wire:click='resetInput' 
                                 type="button">
                        <i class="fa-solid fa-rotate-right mr-2 text-white"></i>
                        <span class="hidden sm:inline text-white">Reset</span>
                        <span class="sm:hidden text-white">Reset</span>
                    </flux:button>

                    <flux:button variant="primary" type="submit" class="flex items-center gap-2 text-white hover:opacity-90 transition w-full sm:w-auto" style="background:#0da2e7">
                        <i class="fa-solid fa-check mr-2 text-white"></i>
                        <span class="hidden sm:inline text-white">Save Organization</span>
                        <span class="sm:hidden text-white">Save</span>
                    </flux:button>
                </div>
                
            </div>

        </form>
            </div>
        </div>
    </flux:modal>

    @script
    <script>
        lucide.createIcons();
    </script>
    @endscript
</div>