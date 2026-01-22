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

    <flux:modal name="add-pharmacy-product" class="p-0" wire:close="closeModal">
        <div x-data="{ modalReady: false }" 
             x-init="
                $el.closest('dialog').addEventListener('click', (e) => {
                    if (e.target === e.currentTarget && modalReady) {
                        $wire.closeModal();
                    }
                });
             "
             @modal-show.window="
                if ($event.detail.name === 'add-pharmacy-product') {
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

        <h1 class="text-xl sm:text-2xl font-semibold mb-4 sm:mb-6 pr-8">Add New Pharmacy Product</h1>

            <form wire:submit.prevent='addPharmacyProduct' method="POST" enctype="multipart/form-data">
                @csrf

                <div class="grid grid-cols-3 gap-8">

                    {{-- COLUMN 1 --}}
                    <div class="space-y-4">
                        <h2 class="text-lg font-semibold mb-2">Basic Info</h2>

                        <div>
                            <label for="product_name" class="block text-sm font-medium mb-2">Product Name</label>
                            <input wire:model="product_name" type="text"
                                class="glass-input w-full px-4 py-2 rounded-lg"
                                placeholder="Product Name">
                            @error('product_name')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="product_code" class="block text-sm font-medium mb-2">Product Code</label>
                            <input wire:model="product_code" type="text"
                                class="glass-input w-full px-4 py-2 rounded-lg"
                                placeholder="Product Code">
                            @error('product_code')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="category" class="block text-sm font-medium mb-2">Category</label>
                            <input wire:model="category" type="text"
                                class="glass-input w-full px-4 py-2 rounded-lg"
                                placeholder="Category">
                            @error('category')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="brand_name" class="block text-sm font-medium mb-2">Brand Name</label>
                            <input wire:model="brand_name" type="text"
                                class="glass-input w-full px-4 py-2 rounded-lg"
                                placeholder="Brand Name">
                            @error('brand_name')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="product_description" class="block text-sm font-medium mb-2">Product Description</label>
                            <textarea wire:model="product_description"
                                class="glass-input w-full px-4 py-2 rounded-lg resize-none"
                                rows="4"
                                placeholder="Product Description"></textarea>
                            @error('product_description')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
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
                            @error('dosage_form')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="strength" class="block text-sm font-medium mb-2">Strength</label>
                            <input wire:model="strength" type="text"
                                class="glass-input w-full px-4 py-2 rounded-lg"
                                placeholder="Strength (500mg / 10ml)">
                            @error('strength')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="pack_size" class="block text-sm font-medium mb-2">Pack Size</label>
                            <input wire:model="pack_size" type="text"
                                class="glass-input w-full px-4 py-2 rounded-lg"
                                placeholder="Pack Size">
                            @error('pack_size')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="mrp" class="block text-sm font-medium mb-2">MRP</label>
                            <input wire:model="mrp" type="number" step="0.01"
                                class="glass-input w-full px-4 py-2 rounded-lg"
                                placeholder="MRP">
                            @error('mrp')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="selling_price" class="block text-sm font-medium mb-2">Selling Price</label>
                            <input wire:model="selling_price" type="number" step="0.01"
                                class="glass-input w-full px-4 py-2 rounded-lg"
                                placeholder="Selling Price">
                            @error('selling_price')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="discount" class="block text-sm font-medium mb-2">Discount</label>
                            <input wire:model="discount" type="number" step="0.01"
                                class="glass-input w-full px-4 py-2 rounded-lg"
                                placeholder="Discount">
                            @error('discount')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
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
                            @error('stock_quantity')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="expiry_date" class="block text-sm font-medium mb-2">Expiry Date</label>
                            <input wire:model="expiry_date" type="date"
                                class="glass-input w-full px-4 py-2 rounded-lg">
                            @error('expiry_date')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="batch_number" class="block text-sm font-medium mb-2">Batch Number</label>
                            <input wire:model="batch_number" type="text"
                                class="glass-input w-full px-4 py-2 rounded-lg"
                                placeholder="Batch Number">
                            @error('batch_number')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="flex items-center gap-2 text-sm font-medium">
                                <input type="checkbox" wire:model="prescription_required" class="rounded">
                                Prescription Required
                            </label>
                            @error('prescription_required')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- IMAGE UPLOAD --}}
                        <div x-data="{ 
                            previewUrl: null,
                            handleFileChange(event) {
                                const file = event.target.files[0];
                                if (file) {
                                    if (this.previewUrl) {
                                        URL.revokeObjectURL(this.previewUrl);
                                    }
                                    this.previewUrl = URL.createObjectURL(file);
                                }
                            },
                            clearPreview() {
                                if (this.previewUrl) {
                                    URL.revokeObjectURL(this.previewUrl);
                                }
                                this.previewUrl = null;
                                const fileInput = document.getElementById('productImage');
                                if (fileInput) fileInput.value = '';
                                $wire.removeImage();
                            }
                        }"
                        @reset-file-input.window="
                            if (previewUrl) {
                                URL.revokeObjectURL(previewUrl);
                            }
                            previewUrl = null;
                            const fileInput = document.getElementById('productImage');
                            if (fileInput) fileInput.value = '';
                        ">
                            <label class="block text-sm font-medium mb-2">Upload Product Image</label>

                            <!-- Upload Box -->
                            <div onclick="document.getElementById('productImage').click()"
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
                                       id="productImage" 
                                       wire:model="product_image" 
                                       class="hidden"
                                       accept="image/*"
                                       @change="handleFileChange($event)">
                            </div>

                            @error('product_image')
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

                        {{-- STATUS --}}
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
                <div class="flex justify-end gap-4 mt-10">
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
                        <span class="hidden sm:inline text-white">Save Product</span>
                        <span class="sm:hidden text-white">Save</span>
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
