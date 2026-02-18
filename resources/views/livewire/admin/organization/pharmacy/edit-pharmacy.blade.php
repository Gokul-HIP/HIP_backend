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

    <flux:modal name="edit-pharmacy" class="p-0" wire:close="closeModal">
        <div x-data="modalHandler()" @click.outside="$wire.closeModal()">
            <div class="relative max-w-5xl mx-auto">

                <flux:modal.close
                    class="absolute top-2 right-2 sm:top-4 sm:right-4 text-gray-500 hover:text-gray-700 cursor-pointer z-10"
                    wire:click="closeModal" />

                <h1 class="text-xl sm:text-2xl font-semibold mb-4 sm:mb-6 pr-8">Edit Pharmacy</h1>

                <form wire:submit.prevent="updatePharmacy" method="POST" enctype="multipart/form-data"
                    x-data="{ imageUploading: false }"
                    @image-uploading.window="imageUploading = $event.detail.uploading">

                    @csrf

                    <div class="grid grid-cols-2 gap-10">

                        <!-- LEFT COLUMN -->
                        <div class="space-y-6">

                            <h2 class="text-lg font-semibold">Pharmacy Details</h2>

                            <div>
                                <input type="text" wire:model="pharmacy_name"
                                    class="glass-input w-full px-4 py-2 rounded-lg"
                                    placeholder="Enter Pharmacy Name">
                                @error('pharmacy_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <input type="text" readonly wire:model="pharmacy_id"
                                    class="glass-input w-full px-4 py-2 rounded-lg"
                                    placeholder="Pharmacy ID">
                            </div>

                            <div>
                                <input type="text" wire:model="pharmacy_address"
                                    class="glass-input w-full px-4 py-2 rounded-lg"
                                    placeholder="Enter Full Address">
                                @error('pharmacy_address') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <input type="text" wire:model="pharmacy_license_number"
                                    class="glass-input w-full px-4 py-2 rounded-lg"
                                    placeholder="Enter License Number">
                                @error('pharmacy_license_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <input type="text" wire:model="pharmacy_gst_num"
                                    class="glass-input w-full px-4 py-2 rounded-lg"
                                    placeholder="Enter GST Number (Optional)">
                            </div>
                        </div>

                        <!-- RIGHT COLUMN -->
                        <div class="space-y-6">

                            <h2 class="text-lg font-semibold">Contact Details</h2>

                            <div>
                                <input type="text" wire:model="pharmacy_contact_person_name"
                                    class="glass-input w-full px-4 py-2 rounded-lg"
                                    placeholder="Contact Person Name">
                                @error('pharmacy_contact_person_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <input type="text" wire:model="pharmacy_contact_person_number"
                                    class="glass-input w-full px-4 py-2 rounded-lg"
                                    placeholder="Contact Number">
                                @error('pharmacy_contact_person_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <input type="email" wire:model="pharmacy_contact_person_email"
                                    class="w-full px-4 py-2 rounded-lg border glass-input"
                                    placeholder="Email Address">
                                @error('pharmacy_contact_person_email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- TIME -->
                            <div>
                                <label class="block text-sm font-medium mb-1 text-gray-600">Opening Hours</label>

                                <div class="flex items-start gap-3">
                                    <input type="time" wire:model="pharmacy_opening_time"
                                        class="w-full px-4 py-2 rounded-lg border glass-input">

                                    <span class="pt-2">to</span>

                                    <input type="time" wire:model="pharmacy_closing_time"
                                        class="w-full px-4 py-2 rounded-lg border glass-input">
                                </div>
                            </div>

                            <!-- IMAGE UPLOAD (ADVANCED) -->
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
                                        
                                        @this.upload('pharmacy_logo', input.files[0], 
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
                                    this.showExisting = !!this.$wire.old_pharmacy_logo && !this.$wire.remove_image;
                                    const fileInput = document.getElementById('pharmacyLogoEdit');
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
                                    this.$watch('$wire.pharmacy_logo', (value) => {
                                        if (!value && this.previewUrl) {
                                            URL.revokeObjectURL(this.previewUrl);
                                            this.previewUrl = null;
                                            const fileInput = document.getElementById('pharmacyLogoEdit');
                                            if (fileInput) fileInput.value = '';
                                        }
                                    });
                                    
                                    this.$watch('$wire.remove_image', (value) => {
                                        if (!value && this.$wire.old_pharmacy_logo && !this.previewUrl) {
                                            this.showExisting = true;
                                        }
                                    });
                                    
                                    Livewire.on('reset-file-input', () => {
                                        if (this.previewUrl) {
                                            URL.revokeObjectURL(this.previewUrl);
                                        }
                                        this.previewUrl = null;
                                        this.isUploading = false;
                                        this.uploadProgress = 0;
                                        this.showExisting = !!this.$wire.old_pharmacy_logo;
                                        const fileInput = document.getElementById('pharmacyLogoEdit');
                                        if (fileInput) fileInput.value = '';
                                    });
                                }
                            }">
                                <label class="block text-sm font-medium mb-2">Pharmacy Image</label>
                            
                                <!-- Existing Image Display -->
                                <div x-show="showExisting && !previewUrl && $wire.old_pharmacy_logo && !$wire.remove_image">
                                    <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden mb-2">
                                        <img src="{{ $old_pharmacy_logo ? asset('storage/pharmacy/' . $old_pharmacy_logo) : '' }}" 
                                             alt="Current pharmacy image" 
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
                                        @click="document.getElementById('pharmacyLogoEdit').click()"
                                        class="mt-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm transition">
                                        <i class="fas fa-edit mr-2"></i>Change Image
                                    </button>
                                </div>
                            
                                <!-- Image Removal Message -->
                                <div x-show="$wire.remove_image && !$wire.pharmacy_logo && !previewUrl" 
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
                                <div @click="document.getElementById('pharmacyLogoEdit').click()"
                                    class="image-box border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center 
                                           cursor-pointer hover:border-gray-400 transition-colors bg-gray-50"
                                    x-show="!previewUrl && !$wire.pharmacy_logo && !showExisting && !$wire.remove_image">
                            
                                    <div class="text-center p-4">
                                        <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                        <p class="text-sm text-gray-700">Click to upload</p>
                                        <p class="text-xs text-gray-500 mt-1">or drag and drop</p>
                                        <p class="text-xs text-gray-400 mt-1">PNG, JPG up to 2MB</p>
                                    </div>
                            
                                    <input type="file" 
                                           id="pharmacyLogoEdit" 
                                           class="hidden" 
                                           accept="image/*"
                                           @change="handleFileChange($event)">
                                </div>
                            
                                @error('pharmacy_logo')
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

                            <!-- STATUS -->
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
                            <span class="hidden sm:inline" style="color:#ffffff !important;" x-text="imageUploading ? 'Uploading...' : 'Update Pharmacy'"></span>
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
