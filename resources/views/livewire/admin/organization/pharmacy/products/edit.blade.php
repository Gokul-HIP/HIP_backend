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

    <flux:modal name="edit-pharmacy-product" class="p-0" wire:close="closeModal">
        <div x-data="modalHandler()" @click.outside="$wire.closeModal()">
            <div class="relative max-w-6xl mx-auto">

                <flux:modal.close
                    class="absolute top-2 right-2 sm:top-4 sm:right-4 text-gray-500 hover:text-gray-700 cursor-pointer z-10"
                    wire:click="closeModal" />

                <h1 class="text-xl sm:text-2xl font-semibold mb-4 sm:mb-6 pr-8">Edit Pharmacy Product</h1>

        <form wire:submit.prevent="editPharmacyProduct" enctype="multipart/form-data"
            x-data="{ imageUploading: false }"
            @image-uploading.window="imageUploading = $event.detail.uploading">

            <div class="grid grid-cols-3 gap-8">

                {{-- COLUMN 1 --}}
                <div class="space-y-4">
                    <h2 class="text-lg font-semibold mb-2">Basic Info</h2>

                    <div>
                        <label for="product_name" class="block text-sm font-medium mb-2">Product Name</label>
                        <input wire:model="product_name" type="text"
                            class="glass-input w-full px-4 py-2 rounded-lg"
                            placeholder="Product Name">
                        @error('product_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="product_code" class="block text-sm font-medium mb-2">Product Code</label>
                        <input wire:model="product_code" type="text"
                            class="glass-input w-full px-4 py-2 rounded-lg"
                            placeholder="Product Code">
                        @error('product_code') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="category" class="block text-sm font-medium mb-2">Category</label>
                        <input wire:model="category" type="text"
                            class="glass-input w-full px-4 py-2 rounded-lg"
                            placeholder="Category">
                        @error('category') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="brand_name" class="block text-sm font-medium mb-2">Brand Name</label>
                        <input wire:model="brand_name" type="text"
                            class="glass-input w-full px-4 py-2 rounded-lg"
                            placeholder="Brand Name">
                        @error('brand_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="product_description" class="block text-sm font-medium mb-2">Product Description</label>
                        <textarea wire:model="product_description"
                            class="glass-input w-full px-4 py-2 rounded-lg resize-none"
                            rows="4"
                            placeholder="Product Description"></textarea>
                        @error('product_description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- COLUMN 2 --}}
                <div class="space-y-4">
                    <h2 class="text-lg font-semibold mb-2">Medicine & Pricing</h2>

                    <div>
                        <label for="dosage_form" class="block text-sm font-medium mb-2">Dosage Form</label>
                        <input wire:model="dosage_form" type="text"
                            class="glass-input w-full px-4 py-2 rounded-lg"
                            placeholder="Dosage Form (Tablet / Syrup)">
                        @error('dosage_form') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="strength" class="block text-sm font-medium mb-2">Strength</label>
                        <input wire:model="strength" type="text"
                            class="glass-input w-full px-4 py-2 rounded-lg"
                            placeholder="Strength (500mg / 10ml)">
                        @error('strength') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="pack_size" class="block text-sm font-medium mb-2">Pack Size</label>
                        <input wire:model="pack_size" type="text"
                            class="glass-input w-full px-4 py-2 rounded-lg"
                            placeholder="Pack Size">
                        @error('pack_size') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="mrp" class="block text-sm font-medium mb-2">MRP</label>
                        <input wire:model="mrp" type="number" step="0.01"
                            class="glass-input w-full px-4 py-2 rounded-lg"
                            placeholder="MRP">
                        @error('mrp') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="selling_price" class="block text-sm font-medium mb-2">Selling Price</label>
                        <input wire:model="selling_price" type="number" step="0.01"
                            class="glass-input w-full px-4 py-2 rounded-lg"
                            placeholder="Selling Price">
                        @error('selling_price') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="discount" class="block text-sm font-medium mb-2">Discount</label>
                        <input wire:model="discount" type="number" step="0.01"
                            class="glass-input w-full px-4 py-2 rounded-lg"
                            placeholder="Discount">
                        @error('discount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- COLUMN 3 --}}
                <div class="space-y-4">
                    <h2 class="text-lg font-semibold mb-2">Stock & Compliance</h2>

                    <div>
                        <label for="stock_quantity" class="block text-sm font-medium mb-2">Stock Quantity</label>
                        <input wire:model="stock_quantity" type="number" step="1"
                            class="glass-input w-full px-4 py-2 rounded-lg"
                            placeholder="Stock Quantity">
                        @error('stock_quantity') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="expiry_date" class="block text-sm font-medium mb-2">Expiry Date</label>
                        <input wire:model="expiry_date" type="date"
                            class="glass-input w-full px-4 py-2 rounded-lg">
                        @error('expiry_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="batch_number" class="block text-sm font-medium mb-2">Batch Number</label>
                        <input wire:model="batch_number" type="text"
                            class="glass-input w-full px-4 py-2 rounded-lg"
                            placeholder="Batch Number">
                        @error('batch_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-sm font-medium">
                            <input type="checkbox" wire:model="prescription_required" class="rounded">
                            Prescription Required
                        </label>
                        @error('prescription_required') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
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
                                
                                @this.upload('product_image', input.files[0], 
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
                            this.showExisting = !!this.$wire.old_product_image && !this.$wire.remove_image;
                            const fileInput = document.getElementById('productImageEdit');
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
                            this.$watch('$wire.product_image', (value) => {
                                if (!value && this.previewUrl) {
                                    URL.revokeObjectURL(this.previewUrl);
                                    this.previewUrl = null;
                                    const fileInput = document.getElementById('productImageEdit');
                                    if (fileInput) fileInput.value = '';
                                }
                            });
                            
                            this.$watch('$wire.remove_image', (value) => {
                                if (!value && this.$wire.old_product_image && !this.previewUrl) {
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
                                this.showExisting = !!this.$wire.old_product_image;
                                const fileInput = document.getElementById('productImageEdit');
                                if (fileInput) fileInput.value = '';
                            });
                        }
                    }">
                        <label class="block text-sm font-medium mb-2">Product Image</label>
                    
                        <!-- Existing Image Display -->
                        <div x-show="showExisting && !previewUrl && $wire.old_product_image && !$wire.remove_image">
                            <div class="preview-box border border-gray-300 rounded-lg relative overflow-hidden mb-2">
                                <img src="{{ $old_product_image ? asset('storage/pharmacy/products/' . $old_product_image) : '' }}" 
                                     alt="Current product image" 
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
                                @click="document.getElementById('productImageEdit').click()"
                                class="mt-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm transition">
                                <i class="fas fa-edit mr-2"></i>Change Image
                            </button>
                        </div>
                    
                        <!-- Image Removal Message -->
                        <div x-show="$wire.remove_image && !$wire.product_image && !previewUrl" 
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
                        <div @click="document.getElementById('productImageEdit').click()"
                            class="image-box border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center 
                                   cursor-pointer hover:border-gray-400 transition-colors bg-gray-50"
                            x-show="!previewUrl && !$wire.product_image && !showExisting && !$wire.remove_image">
                    
                            <div class="text-center p-4">
                                <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                <p class="text-sm text-gray-700">Click to upload</p>
                                <p class="text-xs text-gray-500 mt-1">or drag and drop</p>
                                <p class="text-xs text-gray-400 mt-1">PNG, JPG up to 2MB</p>
                            </div>
                    
                            <input type="file" 
                                   id="productImageEdit" 
                                   class="hidden" 
                                   accept="image/*"
                                   @change="handleFileChange($event)">
                        </div>
                    
                        @error('product_image')
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
                    <div class="flex justify-end gap-4 pt-6 border-t mt-10">
                        <flux:button wire:click="closeModal" type="button" class="px-6 py-2.5 bg-red-500 text-white rounded-lg text-sm font-medium transition w-full sm:w-auto" style="background:#f14336">
                            <i class="fa-solid fa-times mr-2 text-white"></i>
                            <span class="hidden sm:inline text-white">Cancel</span>
                            <span class="sm:hidden text-white">Cancel</span>
                        </flux:button>
                        <flux:button wire:click="resetInput" type="button" class="px-6 py-2.5 bg-gray-300 rounded-lg text-sm font-medium transition w-full sm:w-auto" style="background:#6b7280">
                            <i class="fa-solid fa-rotate-right mr-2 text-white"></i>
                            <span class="hidden sm:inline text-white">Reset</span>
                            <span class="sm:hidden text-white">Reset</span>
                        </flux:button>

                        <flux:button type="submit"
                             class="flex items-center gap-2 text-white hover:opacity-90 transition w-full sm:w-auto" 
                                 style="background:#0da2e7"
                            x-bind:disabled="imageUploading"
                            x-bind:class="{ 'opacity-50 cursor-not-allowed': imageUploading }">
                            <i class="fa-solid fa-spinner fa-spin mr-2 text-white" x-show="imageUploading"></i>
                            <i class="fa-solid fa-check mr-2 text-white" x-show="!imageUploading"></i>
                            <span class="text-white" x-text="imageUploading ? 'Uploading...' : 'Update Product'"></span>
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
