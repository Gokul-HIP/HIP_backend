<div>
    <style>
        .image-box { min-height: 150px; }
        .preview-box { min-height: 140px; }
        .preview-img-thumb {
            width: 100%;
            height: 140px;
            object-fit: cover;
            border-radius: 8px;
            display: block;
        }
        [x-cloak] { display: none !important; }
    </style>

    <flux:modal name="edit-catalog-product" class="p-0" wire:close="closeModal">
        <div class="relative max-w-5xl mx-auto p-1 sm:p-2" @click.stop>
            <flux:modal.close
                class="absolute top-2 right-2 sm:top-4 sm:right-4 text-gray-500 hover:text-gray-700 cursor-pointer z-10"
                wire:click="closeModal" />

            <h1 class="text-xl sm:text-2xl font-semibold mb-4 sm:mb-6 pr-10">Edit Pharmacy Product</h1>

            <form wire:submit.prevent="updateProduct"
                  x-data="{ imageUploading: false }"
                  @image-uploading.window="imageUploading = $event.detail.uploading">

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 max-h-[70vh] overflow-y-auto pr-1">
                    <div class="space-y-5">
                        <h2 class="text-lg font-semibold text-gray-900">Product Details</h2>

                        <div>
                            <label class="block text-sm font-medium mb-2">Product Name <span class="text-red-500">*</span></label>
                            <input wire:model="product_name" type="text" class="glass-input w-full px-4 py-2 rounded-lg" placeholder="Product name">
                            @error('product_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-sm font-medium mb-2">MRP (₹)</label>
                                <input wire:model="mrp" type="number" step="0.01" min="0" class="glass-input w-full px-4 py-2 rounded-lg">
                                @error('mrp') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Selling Price (₹)</label>
                                <input wire:model="selling_price" type="number" step="0.01" min="0" class="glass-input w-full px-4 py-2 rounded-lg">
                                @error('selling_price') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Discount (%)</label>
                                <input wire:model="discount" type="number" step="0.01" min="0" max="100" class="glass-input w-full px-4 py-2 rounded-lg">
                                @error('discount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">Description</label>
                            <textarea wire:model="description" rows="4" class="glass-input w-full px-4 py-2 rounded-lg resize-none"></textarea>
                            @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                            <div>
                                <label class="block text-sm font-medium mb-2">In Stock</label>
                                <div class="flex items-center space-x-4">
                                    <span class="text-sm text-gray-700">No</span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" wire:model.live="in_stock" class="sr-only">
                                        <span class="w-12 h-6 rounded-full flex items-center px-1 transition-all {{ $in_stock ? 'bg-[#0da2e7]' : 'bg-gray-400' }}">
                                            <span class="w-5 h-5 bg-white rounded-full transition-all shadow-sm {{ $in_stock ? 'translate-x-6' : 'translate-x-0' }}"></span>
                                        </span>
                                    </label>
                                    <span class="text-sm text-gray-800 font-medium">Yes</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Status</label>
                                <div class="flex items-center space-x-4">
                                    <span class="text-sm text-gray-700">Inactive</span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" wire:model.live="status" class="sr-only">
                                        <span class="w-12 h-6 rounded-full flex items-center px-1 transition-all {{ $status ? 'bg-[#0da2e7]' : 'bg-gray-400' }}">
                                            <span class="w-5 h-5 bg-white rounded-full transition-all shadow-sm {{ $status ? 'translate-x-6' : 'translate-x-0' }}"></span>
                                        </span>
                                    </label>
                                    <span class="text-sm text-gray-800 font-medium">Active</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <h2 class="text-lg font-semibold text-gray-900">Media & Benefits</h2>

                        @include('livewire.admin.organization.pharmacy.catalog-products.partials.product-images-upload', [
                            'inputId' => 'catalogProductImagesEdit',
                            'existingImages' => $existingImages ?? [],
                        ])

                        @include('livewire.admin.organization.pharmacy.catalog-products.partials.benefits-form')
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-end gap-3 sm:gap-4 pt-4 mt-6 border-t">
                    <flux:button type="button" wire:click="closeModal" style="background:#f14336; color:#ffffff !important;" class="px-6 py-2.5 rounded-lg text-sm font-medium w-full sm:w-auto">
                        <i class="fa-solid fa-times mr-2" style="color:#ffffff !important;"></i> <span style="color:#ffffff !important;">Cancel</span>
                    </flux:button>
                    <flux:button type="submit"
                                 wire:loading.attr="disabled"
                                 style="background: var(--primary-color); color:#ffffff !important;"
                                 class="px-6 py-2.5 rounded-lg text-sm font-medium w-full sm:w-auto"
                                 x-bind:disabled="imageUploading"
                                 x-bind:class="{ 'opacity-50 cursor-not-allowed': imageUploading }">
                        <span wire:loading.remove wire:target="updateProduct">
                            <i class="fa-solid fa-check mr-2" x-show="!imageUploading" style="color:#ffffff !important;"></i>
                            <span x-text="imageUploading ? 'Uploading...' : 'Update Product'" style="color:#ffffff !important;"></span>
                        </span>
                        <span wire:loading wire:target="updateProduct" style="color:#ffffff !important;"><i class="fa-solid fa-spinner fa-spin mr-2"></i> <span style="color:#ffffff !important;">Updating...</span></span>
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
