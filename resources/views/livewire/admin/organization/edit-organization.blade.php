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
    [data-flux-modal="edit-organization"] dialog,
    [data-flux-modal="edit-organization"] dialog * {
        color-scheme: light !important;
        background-color: #ffffff !important;
        color: #111827 !important;
        border-color: #d1d5db !important;
    }
    
    [data-flux-modal="edit-organization"] dialog {
        background-color: #ffffff !important;
        border-color: #d1d5db !important;
    }
    
    /* Force light borders on all elements */
    [data-flux-modal="edit-organization"] dialog input,
    [data-flux-modal="edit-organization"] dialog textarea,
    [data-flux-modal="edit-organization"] dialog select,
    [data-flux-modal="edit-organization"] dialog button,
    [data-flux-modal="edit-organization"] dialog div,
    [data-flux-modal="edit-organization"] dialog .border,
    [data-flux-modal="edit-organization"] dialog [class*="border"] {
        border-color: #d1d5db !important;
    }
    </style>

    <flux:modal name="edit-organization" class="p-0" wire:close="closeModal">
    <div x-data="modalHandler()" @click.outside="$wire.closeModal()">
    <div class="relative max-w-6xl mx-auto">

        <flux:modal.close 
            class="absolute top-2 right-2 sm:top-4 sm:right-4 text-gray-500 hover:text-gray-700 cursor-pointer z-10" 
            wire:click="closeModal" />

        <h1 class="text-xl sm:text-2xl font-semibold mb-4 sm:mb-6 pr-8">Edit Organization</h1>

        <form wire:submit.prevent="updateOrg" method="POST" enctype="multipart/form-data" 
              x-data="{ imageUploading: false }" 
              @image-uploading.window="imageUploading = $event.detail.uploading">
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
                        showExisting: true,
                        isUploading: false,
                        uploadProgress: 0,
                        
                        handleFileChange(event) {
                            const file = event.target.files[0];
                            if (file) {
                                if (this.previewUrl) {
                                    URL.revokeObjectURL(this.previewUrl);
                                }
                                this.previewUrl = URL.createObjectURL(file);
                                this.showExisting = false;
                                this.isUploading = true;
                                this.uploadProgress = 0;
                                
                                // Dispatch event to parent form
                                window.dispatchEvent(new CustomEvent('image-uploading', { 
                                    detail: { uploading: true } 
                                }));
                                
                                // Manually trigger Livewire file upload with progress tracking
                                const input = event.target;
                                
                                @this.upload('org_logo', input.files[0], 
                                    (uploadedFilename) => {
                                        // Success callback
                                        this.isUploading = false;
                                        this.uploadProgress = 100;
                                        window.dispatchEvent(new CustomEvent('image-uploading', { 
                                            detail: { uploading: false } 
                                        }));
                                    },
                                    (error) => {
                                        // Error callback
                                        this.isUploading = false;
                                        this.uploadProgress = 0;
                                        window.dispatchEvent(new CustomEvent('image-uploading', { 
                                            detail: { uploading: false } 
                                        }));
                                        console.error('Upload error:', error);
                                        alert('File upload failed. Please check the file size and type.');
                                    },
                                    (event) => {
                                        // Progress callback
                                        this.uploadProgress = Math.round(event.detail.progress || 0);
                                    }
                                );
                            }
                        },
                        
                        clearPreview() {
                            if (this.previewUrl) {
                                URL.revokeObjectURL(this.previewUrl);
                            }
                            this.previewUrl = null;
                            this.isUploading = false;
                            this.uploadProgress = 0;
                            this.showExisting = !!this.$wire.old_logo_path && !this.$wire.remove_image;
                            const fileInput = document.getElementById('orgLogoEdit');
                            if (fileInput) fileInput.value = '';
                            @this.removeImage();
                        },
                        
                        removeExisting() {
                            this.showExisting = false;
                            @this.removeImage();
                        },
                        
                        restoreExisting() {
                            this.showExisting = true;
                            @this.restoreImage();
                        },
                        
                        init() {
                            // Initialize showExisting based on old_logo_path
                            // Use $nextTick to ensure Livewire values are available
                            this.$nextTick(() => {
                                this.showExisting = !!this.$wire.old_logo_path && !this.$wire.remove_image && !this.previewUrl;
                            });
                            
                            // Watch for changes to old_logo_path
                            this.$watch('$wire.old_logo_path', (value) => {
                                if (value && !this.$wire.remove_image && !this.previewUrl) {
                                    this.showExisting = true;
                                } else if (!value) {
                                    this.showExisting = false;
                                }
                            });
                            
                            // Watch for changes to org_logo from Livewire
                            this.$watch('$wire.org_logo', (value) => {
                                if (!value && this.previewUrl) {
                                    URL.revokeObjectURL(this.previewUrl);
                                    this.previewUrl = null;
                                    const fileInput = document.getElementById('orgLogoEdit');
                                    if (fileInput) fileInput.value = '';
                                }
                                // If org_logo is cleared and we have old image, show existing
                                if (!value && this.$wire.old_logo_path && !this.$wire.remove_image) {
                                    this.showExisting = true;
                                }
                            });
                            
                            // Watch for remove_image flag changes
                            this.$watch('$wire.remove_image', (value) => {
                                if (!value && this.$wire.old_logo_path && !this.previewUrl) {
                                    this.showExisting = true;
                                } else if (value) {
                                    this.showExisting = false;
                                }
                            });
                            
                            // Listen for reset event from Livewire
                            Livewire.on('reset-file-input', () => {
                                if (this.previewUrl) {
                                    URL.revokeObjectURL(this.previewUrl);
                                }
                                this.previewUrl = null;
                                this.isUploading = false;
                                this.uploadProgress = 0;
                                this.showExisting = !!this.$wire.old_logo_path && !this.$wire.remove_image;
                                const fileInput = document.getElementById('orgLogoEdit');
                                if (fileInput) fileInput.value = '';
                            });
                        }
                    }">
                        <label class="block text-sm font-medium mb-2">Organization Image</label>

                        <!-- Existing Image Display -->
                        <div x-show="showExisting && !previewUrl && $wire.old_logo_path && !$wire.remove_image">
                            <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden mb-2">
                                @if(!empty($old_logo_path))
                                    <img src="{{ asset('storage/organization/' . $old_logo_path) }}" 
                                    {{-- <img src="{{ Storage::disk('public')->url('organization/' . rawurlencode($old_logo_path)) }}"  --}}
                                         alt="Current organization image" 
                                         class="preview-img"
                                         onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="preview-img flex items-center justify-center bg-gray-100 text-gray-400" style="display: none;">
                                        <span>Image not found</span>
                                    </div>
                                @else
                                    <div class="preview-img flex items-center justify-center bg-gray-100 text-gray-400">
                                        <span>No image</span>
                                    </div>
                                @endif
                                <button type="button"
                                    @click.stop="removeExisting()"
                                    class="absolute top-2 right-2 bg-red-600 text-white w-8 h-8 flex items-center justify-center 
                                    rounded-full text-sm font-bold shadow hover:bg-red-700 transition"
                                    title="Remove image">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <button type="button"
                                @click="document.getElementById('orgLogoEdit').click()"
                                class="mt-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm transition">
                                <i class="fas fa-edit mr-2"></i>Change Image
                            </button>
                        </div>

                        <!-- Image Removal Message -->
                        <div x-show="$wire.remove_image && !$wire.org_logo && !previewUrl" 
                             class="p-4 border border-gray-300 rounded-lg bg-gray-50 mb-2">
                            <p class="text-sm text-gray-600 mb-2">
                                <i class="fas fa-info-circle mr-1"></i>Image will be removed
                            </p>
                            <button type="button"
                                @click="restoreExisting()"
                                class="px-3 py-1.5 bg-gray-500 text-white rounded-lg text-sm hover:bg-gray-600 transition">
                                <i class="fas fa-undo mr-1"></i>Cancel Removal
                            </button>
                        </div>

                        <!-- Upload Box (shown when no image) -->
                        <div @click="document.getElementById('orgLogoEdit').click()"
                            class="image-box border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center 
                                   cursor-pointer hover:border-gray-400 transition-colors bg-gray-50"
                            x-show="!previewUrl && !$wire.org_logo && !showExisting && !$wire.remove_image">

                            <div class="text-center p-4">
                                <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                <p class="text-sm text-gray-700">Click to upload</p>
                                <p class="text-xs text-gray-500 mt-1">or drag and drop</p>
                                <p class="text-xs text-gray-400 mt-1">PNG, JPG up to 2MB</p>
                            </div>

                            <input type="file" 
                                   id="orgLogoEdit" 
                                   class="hidden" 
                                   accept="image/*"
                                   @change="handleFileChange($event)">
                        </div>

                        @error('org_logo')
                            <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                        @enderror

                        <!-- New Image Preview -->
                        <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden" 
                             x-show="previewUrl"
                             x-cloak>
                            <img :src="previewUrl" class="preview-img" alt="New preview">
                            
                            <!-- Upload Progress Overlay -->
                            <div x-show="isUploading" 
                                 class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                                <div class="text-center text-white">
                                    <div class="mb-2">
                                        <i class="fas fa-spinner fa-spin text-3xl"></i>
                                    </div>
                                    <p class="text-sm font-medium">Uploading...</p>
                                    <p class="text-xs mt-1" x-text="uploadProgress + '%'"></p>
                                </div>
                            </div>
                            
                            <button type="button"
                                @click.stop="clearPreview()"
                                x-show="!isUploading"
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
                    <flux:button class="px-6 py-2.5 bg-red-500 text-white rounded-lg text-sm font-medium transition w-full sm:w-auto" style="background:#f14336" wire:click="closeModal" type="button">
                        <i class="fa-solid fa-times mr-2 text-white"></i>
                        <span class="hidden sm:inline text-white">Cancel</span>
                        <span class="sm:hidden text-white">Cancel</span>
                    </flux:button>

                    <flux:button class="px-6 py-2.5 bg-gray-300 rounded-lg text-sm font-medium transition w-full sm:w-auto" style="background:#6b7280" wire:click='resetInput' type="button">
                        <i class="fa-solid fa-rotate-right mr-2 text-white"></i>
                        <span class="hidden sm:inline text-white">Reset</span>
                        <span class="sm:hidden text-white">Reset</span>
                    </flux:button>

                    <flux:button variant="primary" type="submit" 
                                 class="flex items-center gap-2 text-white hover:opacity-90 transition w-full sm:w-auto" 
                                 style="background:#0da2e7"
                                 x-bind:disabled="imageUploading"
                                 x-bind:class="{ 'opacity-50 cursor-not-allowed': imageUploading }">
                        <i class="fa-solid fa-spinner fa-spin mr-2 text-white" x-show="imageUploading"></i>
                        <i class="fa-solid fa-check mr-2 text-white" x-show="!imageUploading"></i>
                        <span class="hidden sm:inline text-white" x-text="imageUploading ? 'Uploading...' : 'Update Organization'"></span>
                        <span class="sm:hidden text-white" x-text="imageUploading ? 'Uploading...' : 'Update'"></span>
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
        
        function modalHandler() {
            return {
                // Empty for now, might need later
            }
        }
    </script>
    @endscript
</div>