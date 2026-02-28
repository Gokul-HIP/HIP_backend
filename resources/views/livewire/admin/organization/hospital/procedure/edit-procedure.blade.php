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

    <flux:modal name="edit-procedure" class="p-0" wire:close="closeModal">
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
                if ($event.detail.name === 'edit-procedure') {
                    modalReady = false;
                    setTimeout(() => modalReady = true, 300);
                }
            "
        >

            <!-- CLOSE BUTTON -->
            <button type="button"
                wire:click="closeModal"
                class="absolute top-2 right-2 sm:top-4 sm:right-4 text-gray-500 hover:text-gray-700 cursor-pointer z-50 w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition">
                ✕
            </button>

            <div class="relative max-w-5xl mx-auto" @click.stop>

                <h1 class="text-xl sm:text-2xl font-semibold mb-4 sm:mb-6 pr-8">
                    Edit Procedure
                </h1>

                <form wire:submit.prevent="updateProcedure" enctype="multipart/form-data"
                      x-data="{ imageUploading: false }" 
                      @image-uploading.window="imageUploading = $event.detail.uploading">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 sm:gap-10 mt-4">

                        <!-- LEFT -->
                        <div class="space-y-6">

                            <h2 class="text-lg font-semibold">Basic Information</h2>

                            <!-- Procedure Name -->
                            <div>
                                <label class="block text-sm font-medium mb-2">
                                    Procedure Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" wire:model="procedure_name"
                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                                    placeholder="Enter procedure name">
                                @error('procedure_name')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Speciality -->
                            <div class="relative" x-data="{ open: false }">
                                <label class="block text-sm font-medium mb-2">
                                    Speciality <span class="text-red-500">*</span>
                                </label>
                            
                                <button type="button"
                                    @click="open = !open"
                                    class="inline-flex items-center justify-between w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5">
                                    <span>
                                        {{ $speciality_id
                                            ? $specialities->firstWhere('id', $speciality_id)?->speciality_name
                                            : 'Select speciality'
                                        }}
                                    </span>
                                    <i class="fas fa-chevron-down ml-2"></i>
                                </button>
                            
                                <div x-show="open" @click.away="open=false" x-transition
                                    class="absolute z-50 mt-2 w-full bg-white border rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                    <ul class="p-2 text-sm">
                                        @foreach($specialities as $speciality)
                                            <li>
                                                <button type="button"
                                                    wire:click="$set('speciality_id', {{ $speciality->id }})"
                                                    @click="open=false"
                                                    class="w-full text-left p-2 hover:bg-gray-100 rounded">
                                                    {{ $speciality->speciality_name }}
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            
                                @error('speciality_id')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Estimated Time -->
                            <div>
                                <label class="block text-sm font-medium mb-2">
                                    Estimated Time <span class="text-red-500">*</span>
                                </label>
                                <input type="text" wire:model="estimated_time"
                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                                    placeholder="e.g., 2-3 hours">
                                @error('estimated_time')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Recovery Time -->
                            <div class="relative" 
                                x-data="{ open: false }">

                                <label class="block text-sm font-medium mb-2">
                                    Recovery Time <span class="text-red-500">*</span>
                                </label>

                                <!-- INPUT ROW -->
                                <div class="flex gap-2">

                                    <!-- FROM -->
                                    <input type="number"
                                        min="0"
                                        wire:model.defer="recovery_from"
                                        placeholder="From"
                                        class="w-1/3 border border-gray-300 rounded-lg px-3 py-2.5
                                                focus:ring-2 focus:ring-[#0da2e7]/40 focus:border-[#0da2e7]">

                                    <!-- TO -->
                                    <input type="number"
                                        min="0"
                                        wire:model.defer="recovery_to"
                                        placeholder="To"
                                        class="w-1/3 border border-gray-300 rounded-lg px-3 py-2.5
                                                focus:ring-2 focus:ring-[#0da2e7]/40 focus:border-[#0da2e7]">

                                    <!-- UNIT DROPDOWN -->
                                    <div class="relative w-1/3">

                                        <button type="button"
                                            @click="open = !open"
                                            class="inline-flex items-center justify-between w-full bg-white 
                                                border border-gray-300 rounded-lg px-3 py-2.5
                                                focus:ring-2 focus:ring-[#0da2e7]/40 focus:border-[#0da2e7]">

                                            <span>
                                                {{ $recovery_unit ? ucfirst($recovery_unit) : 'Unit' }}
                                            </span>

                                            <i class="fas fa-chevron-down ml-2 text-gray-500"></i>
                                        </button>

                                        <!-- DROPDOWN -->
                                        <div x-show="open"
                                            x-cloak
                                            x-transition
                                            @click.away="open = false"
                                            class="absolute z-50 mt-2 w-full bg-white border rounded-lg shadow-lg">

                                            <ul class="p-1 text-sm">
                                                @foreach(['days', 'weeks', 'months'] as $unit)
                                                    <li>
                                                        <button type="button"
                                                            wire:click="$set('recovery_unit', '{{ $unit }}')"
                                                            @click="open = false"
                                                            class="w-full text-left px-3 py-2 rounded hover:bg-gray-100">
                                                            {{ ucfirst($unit) }}
                                                        </button>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>

                                    </div>
                                </div>

                                <!-- ERRORS -->
                                @error('recovery_from')
                                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                                @enderror
                                @error('recovery_to')
                                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                                @enderror
                                @error('recovery_unit')
                                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                                @enderror

                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2">
                                    Success Rate (%) <span class="text-red-500">*</span>
                                </label>
                                <input type="number" wire:model="success_rate" step="0.01" min="0" max="100"
                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                                    placeholder="e.g., 90">
                                @error('success_rate')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2">
                                    Hospitalization Days <span class="text-red-500">*</span>
                                </label>
                                <input type="number" wire:model="hospitalization_days" min="0"
                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                                    placeholder="e.g., 2">
                                @error('hospitalization_days')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                        <!-- RIGHT -->
                        <div class="space-y-6">

                            <h2 class="text-lg font-semibold">Additional Details</h2>

                            <!-- Procedure Code -->
                            <div>
                                <label class="block text-sm font-medium mb-2">Procedure Code</label>
                                <input type="text" wire:model="procedure_code"
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-50"
                                    placeholder="Procedure Code" readonly>
                            </div>

                            <!-- Cost -->
                            <div>
                                <label class="block text-sm font-medium mb-2">
                                    Cost (₹) <span class="text-red-500">*</span>
                                </label>
                                <input type="number" wire:model="cost" step="0.01" min="0"
                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                                    placeholder="Enter cost in rupees">
                                @error('cost')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Discount -->
                            <div>
                                <label class="block text-sm font-medium mb-2">
                                    Discount Price (₹) <span class="text-red-500">*</span>
                                </label>
                                <input type="number" wire:model="discount" step="0.01" min="0"
                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                                    placeholder="Enter discount in rupees">
                                @error('discount')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Upload Procedure Image -->
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
                                        
                                        window.dispatchEvent(new CustomEvent('image-uploading', { 
                                            detail: { uploading: true } 
                                        }));
                                        
                                        const input = event.target;
                                        
                                        @this.upload('procedure_image', input.files[0], 
                                            (uploadedFilename) => {
                                                this.isUploading = false;
                                                this.uploadProgress = 100;
                                                window.dispatchEvent(new CustomEvent('image-uploading', { 
                                                    detail: { uploading: false } 
                                                }));
                                            },
                                            (error) => {
                                                this.isUploading = false;
                                                this.uploadProgress = 0;
                                                window.dispatchEvent(new CustomEvent('image-uploading', { 
                                                    detail: { uploading: false } 
                                                }));
                                                console.error('Upload error:', error);
                                                alert('File upload failed. Please check the file size and type.');
                                            },
                                            (event) => {
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
                                    this.showExisting = !!this.$wire.old_image_path && !this.$wire.remove_image;
                                    const fileInput = document.getElementById('procedureImageEdit');
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
                                    this.$nextTick(() => {
                                        this.showExisting = !!this.$wire.old_image_path && !this.$wire.remove_image && !this.previewUrl;
                                    });
                                    
                                    this.$watch('$wire.old_image_path', (value) => {
                                        if (value && !this.$wire.remove_image && !this.previewUrl) {
                                            this.showExisting = true;
                                        } else if (!value) {
                                            this.showExisting = false;
                                        }
                                    });
                                    
                                    this.$watch('$wire.procedure_image', (value) => {
                                        if (!value && this.previewUrl) {
                                            URL.revokeObjectURL(this.previewUrl);
                                            this.previewUrl = null;
                                            const fileInput = document.getElementById('procedureImageEdit');
                                            if (fileInput) fileInput.value = '';
                                        }
                                        if (!value && this.$wire.old_image_path && !this.$wire.remove_image) {
                                            this.showExisting = true;
                                        }
                                    });
                                    
                                    this.$watch('$wire.remove_image', (value) => {
                                        if (!value && this.$wire.old_image_path && !this.previewUrl) {
                                            this.showExisting = true;
                                        } else if (value) {
                                            this.showExisting = false;
                                        }
                                    });
                                    
                                    Livewire.on('reset-file-input', () => {
                                        if (this.previewUrl) {
                                            URL.revokeObjectURL(this.previewUrl);
                                        }
                                        this.previewUrl = null;
                                        this.isUploading = false;
                                        this.uploadProgress = 0;
                                        this.showExisting = !!this.$wire.old_image_path && !this.$wire.remove_image;
                                        const fileInput = document.getElementById('procedureImageEdit');
                                        if (fileInput) fileInput.value = '';
                                    });
                                }
                            }">
                                <label class="block text-sm font-medium mb-2">Procedure Image</label>

                                <!-- Existing Image Display -->
                                <div x-show="showExisting && !previewUrl && $wire.old_image_path && !$wire.remove_image">
                                    <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden mb-2">
                                        @if(!empty($old_image_path))
                                            <img src="{{ asset('storage/procedures/' . $old_image_path) }}" 
                                                 alt="Current procedure image" 
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
                                        @click="document.getElementById('procedureImageEdit').click()"
                                        class="mt-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm transition">
                                        <i class="fas fa-edit mr-2"></i>Change Image
                                    </button>
                                </div>

                                <!-- Image Removal Message -->
                                <div x-show="$wire.remove_image && !$wire.procedure_image && !previewUrl" 
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
                                <div @click="document.getElementById('procedureImageEdit').click()"
                                    class="image-box border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center 
                                           cursor-pointer hover:border-gray-400 transition-colors bg-gray-50"
                                    x-show="!previewUrl && !$wire.procedure_image && !showExisting && !$wire.remove_image">

                                    <div class="text-center p-4">
                                        <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                        <p class="text-sm text-gray-700">Click to upload</p>
                                        <p class="text-xs text-gray-500 mt-1">or drag and drop</p>
                                        <p class="text-xs text-gray-400 mt-1">PNG, JPG up to 2MB</p>
                                    </div>

                                    <input type="file" 
                                           id="procedureImageEdit" 
                                           class="hidden" 
                                           accept="image/*"
                                           @change="handleFileChange($event)">
                                </div>

                                @error('procedure_image')
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

                            <!-- Status -->
                            <div class="pt-2">
                                <label class="block text-sm font-medium mb-2">Status</label>
                                <div class="flex items-center space-x-4">
                                    <span class="text-sm text-gray-700">Inactive</span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" wire:model.live="status" class="sr-only">
                                        <span class="w-12 h-6 rounded-full flex items-center px-1 transition-all duration-300
                                            {{ $status ? 'bg-[#0da2e7]' : 'bg-gray-400' }}">
                                            <span class="dot w-5 h-5 bg-white rounded-full transition-all duration-300
                                                {{ $status ? 'translate-x-6' : 'translate-x-0' }}"></span>
                                        </span>
                                    </label>
                                    <span class="text-sm text-gray-800 font-medium">Active</span>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mt-6">
                        <label class="block text-sm font-medium mb-2">
                            Description <span class="text-red-500">*</span>
                        </label>
                        <textarea wire:model="description" rows="4"
                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition resize-none"
                            placeholder="Describe the procedure in detail..."></textarea>
                        @error('description')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- ACTION BUTTONS -->
                    <div class="flex flex-col sm:flex-row justify-end gap-3 sm:gap-4 pt-4 border-t">
                        <flux:button class="px-6 py-2.5 bg-red-500 text-white rounded-lg text-sm font-medium transition w-full sm:w-auto" 
                        style="background:#f14336; color:#ffffff !important;" wire:click="closeModal" type="button">
                            <i class="fa-solid fa-times mr-2 text-white"></i>
                            <span class="hidden sm:inline" style="color:#ffffff !important;">Cancel</span>
                            <span class="sm:hidden" style="color:#ffffff !important;">Cancel</span>
                        </flux:button>

                        <flux:button class="px-6 py-2.5 bg-gray-300 rounded-lg text-sm font-medium transition w-full sm:w-auto" style="background:#6b7280" wire:click='resetInput' type="button">
                            <i class="fa-solid fa-rotate-right mr-2" style="color:#ffffff !important;"></i>
                            <span class="hidden sm:inline" style="color:#ffffff !important;">Reset</span>
                            <span class="sm:hidden" style="color:#ffffff !important;">Reset</span>
                        </flux:button>

                        <flux:button variant="primary" type="submit" 
                                     class="flex items-center gap-2 text-white hover:opacity-90 transition w-full sm:w-auto" 
                                     style="background:#0da2e7; color:#ffffff !important;"
                                     x-bind:disabled="imageUploading"
                                     x-bind:class="{ 'opacity-50 cursor-not-allowed': imageUploading }">
                            <i class="fa-solid fa-spinner fa-spin mr-2" style="color:#ffffff !important;" x-show="imageUploading"></i>
                            <i class="fa-solid fa-check mr-2" style="color:#ffffff !important;" x-show="!imageUploading"></i>
                            <span class="hidden sm:inline" style="color:#ffffff !important;" x-text="imageUploading ? 'Uploading...' : 'Update Procedure'"></span>
                            <span class="sm:hidden" style="color:#ffffff !important;" x-text="imageUploading ? 'Uploading...' : 'Update'"></span>
                        </flux:button>
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