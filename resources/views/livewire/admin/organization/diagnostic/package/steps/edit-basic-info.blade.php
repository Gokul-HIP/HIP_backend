<div class="bg-white rounded-lg p-6 shadow-sm">
    <h3 class="text-lg font-semibold mb-4">Basic Information</h3>

    <div class="space-y-6">
        <div>
            <label class="block text-sm font-medium mb-2">Package Name <span class="text-red-500">*</span></label>
            <input type="text" wire:model="name"
                class="w-full px-4 py-2 rounded-lg border glass-input"
                placeholder="Enter Package Name">
            @error('name')
                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-2">Package Code</label>
            <input type="text" wire:model="code"
                class="w-full px-4 py-2 rounded-lg border glass-input"
                placeholder="Package Code">
            @error('code')
                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
            @enderror
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
                    
                    @this.upload('image', input.files[0], 
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
                this.showExisting = !!this.$wire.old_image && !this.$wire.remove_image;
                const fileInput = document.getElementById('packageImageEdit');
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
                // Use $nextTick to ensure Livewire values are available
                this.$nextTick(() => {
                    this.showExisting = !!this.$wire.old_image && !this.$wire.remove_image && !this.previewUrl;
                });
                
                this.$watch('$wire.image', (value) => {
                    if (!value && this.previewUrl) {
                        URL.revokeObjectURL(this.previewUrl);
                        this.previewUrl = null;
                        const fileInput = document.getElementById('packageImageEdit');
                        if (fileInput) fileInput.value = '';
                    }
                });
                
                this.$watch('$wire.remove_image', (value) => {
                    if (!value && this.$wire.old_image && !this.previewUrl) {
                        this.showExisting = true;
                    }
                });
                
                this.$watch('$wire.old_image', () => {
                    this.$nextTick(() => {
                        this.showExisting = !!this.$wire.old_image && !this.$wire.remove_image && !this.previewUrl;
                    });
                });
                
                Livewire.on('reset-file-input', () => {
                    if (this.previewUrl) {
                        URL.revokeObjectURL(this.previewUrl);
                    }
                    this.previewUrl = null;
                    this.isUploading = false;
                    this.uploadProgress = 0;
                    this.showExisting = !!this.$wire.old_image;
                    const fileInput = document.getElementById('packageImageEdit');
                    if (fileInput) fileInput.value = '';
                });
            }
        }">
            <label class="block text-sm font-medium mb-2">Package Image</label>
        
            <!-- Existing Image Display -->
            <div x-show="showExisting && !previewUrl && $wire.old_image && !$wire.remove_image">
                <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden mb-2">
                    <img src="{{ $old_image ? asset('storage/diagnostic-packages/' . $old_image) : '' }}" 
                         alt="Current package image" 
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
                    @click="document.getElementById('packageImageEdit').click()"
                    class="mt-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm transition">
                    <i class="fas fa-edit mr-2"></i>Change Image
                </button>
            </div>
        
            <!-- Image Removal Message -->
            <div x-show="$wire.remove_image && !$wire.image && !previewUrl" 
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
            <div @click="document.getElementById('packageImageEdit').click()"
                class="image-box border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center 
                       cursor-pointer hover:border-gray-400 transition-colors bg-gray-50"
                x-show="!previewUrl && !$wire.image && !showExisting && !$wire.remove_image">
        
                <div class="text-center p-4">
                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                    <p class="text-sm text-gray-700">Click to upload</p>
                    <p class="text-xs text-gray-500 mt-1">or drag and drop</p>
                    <p class="text-xs text-gray-400 mt-1">PNG, JPG up to 2MB</p>
                </div>
        
                <input type="file" 
                       id="packageImageEdit" 
                       class="hidden" 
                       accept="image/*"
                       @change="handleFileChange($event)">
            </div>
        
            @error('image')
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
            <label class="block text-sm font-medium mb-2">Description</label>
            <textarea rows="3" wire:model="description"
                class="w-full px-4 py-2 rounded-lg border glass-input"
                placeholder="Enter Package Description"></textarea>
            @error('description')
                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

