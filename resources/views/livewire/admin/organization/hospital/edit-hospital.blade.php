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

    <flux:modal name="edit-hospital" class="p-0" wire:close="closeModal">
        <div x-data="modalHandler()" @click.outside="$wire.closeModal()">
            <div class="relative max-w-5xl mx-auto">

                <flux:modal.close
                    class="absolute top-2 right-2 sm:top-4 sm:right-4 text-gray-500 hover:text-gray-700 cursor-pointer z-10"
                    wire:click="closeModal" />

                <h1 class="text-xl sm:text-2xl font-semibold mb-4 sm:mb-6 pr-8">Edit Hospital</h1>
            <form wire:submit.prevent="updateHospital" method="POST" enctype="multipart/form-data"
                x-data="{ imageUploading: false }"
                @image-uploading.window="imageUploading = $event.detail.uploading">
                @csrf

                <div class="grid grid-cols-2 gap-10 mt-4">

                    <div class="space-y-6">

                        <h2 class="text-lg font-semibold">Basic Information</h2>

                        <div>
                            <label class="block text-sm font-medium mb-2">Hospital Name</label>
                            <input type="text" wire:model="hospital_name"
                                class="w-full px-4 py-2 rounded-lg border glass-input"
                                placeholder="Enter Hospital Name">
                            @error('hospital_name')
                                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">Subtitle</label>
                            <input type="text" wire:model="hospital_subtitle"
                                class="w-full px-4 py-2 rounded-lg border glass-input"
                                placeholder="Enter Subtitle">
                            @error('hospital_subtitle')
                                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                            @enderror
                        </div>

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
                                        
                                        const input = event.target;
                                        
                                        @this.upload('hospital_logo', input.files[0], 
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
                                    this.showExisting = !!this.$wire.old_hospital_logo && !this.$wire.remove_image;
                                    const fileInput = document.getElementById('hospitalLogoEdit');
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
                                    // Initialize showExisting based on old_hospital_logo
                                    // Use $nextTick to ensure Livewire values are available
                                    this.$nextTick(() => {
                                        this.showExisting = !!this.$wire.old_hospital_logo && !this.$wire.remove_image && !this.previewUrl;
                                    });
                                    
                                    // Watch for changes to old_hospital_logo
                                    this.$watch('$wire.old_hospital_logo', (value) => {
                                        if (value && !this.$wire.remove_image && !this.previewUrl) {
                                            this.showExisting = true;
                                        } else if (!value) {
                                            this.showExisting = false;
                                        }
                                    });
                                    
                                    // Watch for changes to hospital_logo from Livewire
                                    this.$watch('$wire.hospital_logo', (value) => {
                                        if (!value && this.previewUrl) {
                                            URL.revokeObjectURL(this.previewUrl);
                                            this.previewUrl = null;
                                            const fileInput = document.getElementById('hospitalLogoEdit');
                                            if (fileInput) fileInput.value = '';
                                        }
                                        // If hospital_logo is cleared and we have old image, show existing
                                        if (!value && this.$wire.old_hospital_logo && !this.$wire.remove_image) {
                                            this.showExisting = true;
                                        }
                                    });
                                    
                                    // Watch for remove_image flag changes
                                    this.$watch('$wire.remove_image', (value) => {
                                        if (!value && this.$wire.old_hospital_logo && !this.previewUrl) {
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
                                        this.showExisting = !!this.$wire.old_hospital_logo && !this.$wire.remove_image;
                                        const fileInput = document.getElementById('hospitalLogoEdit');
                                        if (fileInput) fileInput.value = '';
                                    });
                                }
                            }">
                                <label class="block text-sm font-medium mb-2">Hospital Logo/Image</label>
                            
                                <!-- Existing Image Display -->
                                <div x-show="showExisting && !previewUrl && $wire.old_hospital_logo && !$wire.remove_image">
                                    <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden mb-2">
                                        <img src="{{ $old_hospital_logo ? asset('storage/hospital/' . $old_hospital_logo) : '' }}" 
                                             alt="Current hospital image" 
                                             class="preview-img">
                                        <button type="button"
                                            @click.stop="removeExisting()"
                                            class="absolute top-2 right-2 bg-red-600 text-white w-8 h-8 flex items-center justify-center 
                                            rounded-full text-sm font-bold shadow hover:bg-red-700 transition"
                                            title="Remove image">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <button type="button"
                                        @click="document.getElementById('hospitalLogoEdit').click()"
                                        class="mt-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm transition">
                                        <i class="fas fa-edit mr-2"></i>Change Image
                                    </button>
                                </div>
                            
                                <!-- Image Removal Message -->
                                <div x-show="$wire.remove_image && !$wire.hospital_logo && !previewUrl" 
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
                            
                                <!-- Upload Box -->
                                <div @click="document.getElementById('hospitalLogoEdit').click()"
                                    class="image-box border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center 
                                           cursor-pointer hover:border-gray-400 transition-colors bg-gray-50"
                                    x-show="!previewUrl && !$wire.hospital_logo && !showExisting && !$wire.remove_image">
                            
                                    <div class="text-center p-4">
                                        <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                        <p class="text-sm text-gray-700">Click to upload</p>
                                        <p class="text-xs text-gray-500 mt-1">or drag and drop</p>
                                        <p class="text-xs text-gray-400 mt-1">PNG, JPG up to 2MB</p>
                                    </div>
                            
                                    <input type="file" 
                                           id="hospitalLogoEdit" 
                                           class="hidden" 
                                           accept="image/*"
                                           @change="handleFileChange($event)">
                                </div>
                            
                                @error('hospital_logo')
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

                        <div>
                            <label class="block text-sm font-medium mb-2">About Hospital</label>
                            <textarea rows="3" wire:model="hospital_about"
                                class="w-full px-4 py-2 rounded-lg border glass-input"
                                placeholder="Enter About Hospital"></textarea>
                            @error('hospital_about')
                                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <div
                            class="relative"
                            wire:key="pharmacy-dropdown-{{ implode('-', $selected_pharmacy_ids) }}"
                            x-data="{ open: false }">
                            <label class="block text-sm font-medium mb-2">
                                Pharmacy <span class="text-red-500">*</span>
                            </label>

                            <button
                                type="button"
                                @click="open = !open"
                                class="w-full border rounded-lg px-4 py-2.5 bg-white flex justify-between items-center"
                            >
                                <span class="truncate">
                                    @if(count($selected_pharmacy_ids))
                                        {{
                                            collect($pharmacies)
                                                ->whereIn('id', $selected_pharmacy_ids)
                                                ->pluck('name')
                                                ->implode(', ')
                                        }}
                                    @else
                                        Select Pharmacy
                                    @endif
                                </span>
                                <i class="fas fa-chevron-down"></i>
                            </button>

                            <div
                                x-show="open"
                                @click.away="open = false"
                                class="absolute z-50 mt-2 w-full bg-white border rounded-lg shadow max-h-60 overflow-y-auto">
                                <ul class="p-2 text-sm">
                                    @foreach($pharmacies as $pharmacy)
                                        <li>
                                            <label class="flex items-center gap-2 p-2 hover:bg-gray-100 rounded cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    wire:model="selected_pharmacy_ids"
                                                    value="{{ $pharmacy->id }}"
                                                >
                                                {{ $pharmacy->name }}
                                            </label>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            @error('selected_pharmacy_ids')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        

                        <div class="relative" wire:key="diagnostic-dropdown-{{ $selected_diagnostic_id }}" x-data="{ open: false }">
                            <label class="block text-sm font-medium mb-2">
                                Diagnostic Center <span class="text-red-500">*</span>
                            </label>
                        
                            <button type="button"
                                @click="open = !open"
                                class="w-full border rounded-lg px-4 py-2.5 bg-white flex justify-between items-center">
                                <span class="truncate">
                                    {{
                                        optional(
                                            collect($diagnosticCenters)
                                                ->firstWhere('id', $selected_diagnostic_id)
                                        )->name ?? 'Select Diagnostic Center'
                                    }}
                                </span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        
                            <div x-show="open" @click.away="open=false"
                                 class="absolute z-50 mt-2 w-full bg-white border rounded-lg shadow max-h-60 overflow-y-auto">
                                <ul class="p-2 text-sm space-y-1">
                                    @foreach($diagnosticCenters as $center)
                                        <li>
                                            <button type="button"
                                                wire:click="$set('selected_diagnostic_id', {{ $center->id }})"
                                                @click="open=false"
                                                class="w-full text-left p-2 hover:bg-gray-100 rounded">
                                                {{ $center->name }}
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        
                            @error('selected_diagnostic_id')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        

                    </div>
                    
                    <div class="space-y-6">

                        <div>
                            <label class="block text-sm font-medium mb-2">Address</label>
                            <textarea rows="3" wire:model="hospital_address"
                                class="w-full px-4 py-2 rounded-lg border glass-input"
                                placeholder="Enter Address"></textarea>
                            @error('hospital_address')
                                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <h2 class="text-lg font-semibold">Contact Person</h2>
                        
                        <input type="text" wire:model="hospital_admin_name"
                            class="w-full px-4 py-2 rounded-lg border glass-input"
                            placeholder="Admin Name">
                             @error('hospital_admin_name')
                                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                            @enderror

                        <input type="text" wire:model="hospital_admin_contact"
                            class="w-full px-4 py-2 rounded-lg border glass-input"
                            placeholder="Contact Details">
                             @error('hospital_admin_contact')
                                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                            @enderror

                        <input type="email" wire:model="hospital_admin_email"
                            class="w-full px-4 py-2 rounded-lg border glass-input"
                            placeholder="Email">
                             @error('hospital_admin_email')
                                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                            @enderror

                        <input type="text" wire:model="hospital_admin_longitude"
                            class="w-full px-4 py-2 rounded-lg border glass-input"
                            placeholder="Longitude">
                             @error('hospital_admin_longitude')
                                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                            @enderror

                        <input type="text" wire:model="hospital_admin_latitude"
                            class="w-full px-4 py-2 rounded-lg border glass-input"
                            placeholder="Latitude">
                             @error('hospital_admin_latitude')
                                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                            @enderror

                        <textarea rows="2" wire:model="hospital_admin_address"
                            class="w-full px-4 py-2 rounded-lg border glass-input"
                            placeholder="Enter Your Address..."></textarea>
                             @error('hospital_admin_address')
                                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                            @enderror

                        <!-- Is 24 Hours Available -->
                        <div class="pt-2">
                            <label class="block text-sm font-medium mb-2">Is 24 Hours Available</label>
                            <input type="checkbox" wire:model.live="is_24_hours_available" class="form-checkbox h-5 w-5 text-blue-600">
                        </div>

                        <!-- Status -->
                        <div class="pt-2">
                            <label class="block text-sm font-medium mb-2">Status</label>

                            <div class="flex items-center space-x-4">
                                <span class="text-sm text-gray-700">Inactive</span>

                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" wire:model.live="status" class="sr-only">
                                    <span class="w-12 h-6 rounded-full flex items-center px-1 transition-all
                                        {{ $status ? 'bg-[#0da2e7]' : 'bg-gray-400' }}">
                                        <span class="dot w-5 h-5 bg-white rounded-full transition-all
                                            {{ $status ? 'translate-x-6' : 'translate-x-0' }}"></span>
                                    </span>
                                </label>

                                <span class="text-sm text-gray-800">Active</span>
                            </div>
                        </div>

                    </div>

                </div>

                    <!-- BUTTONS -->
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
                            <span class="hidden sm:inline" style="color:#ffffff !important;" x-text="imageUploading ? 'Uploading...' : 'Update Hospital'"></span>
                            <span class="sm:hidden" style="color:#ffffff !important;" x-text="imageUploading ? 'Uploading...' : 'Update'"></span>
                        </flux:button>
                    </div>

            </form>
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