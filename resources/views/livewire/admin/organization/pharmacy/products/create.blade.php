<flux:modal name="add-pharmacy-product" class="p-0" x-on:close="$wire.resetInput();">
<div class="p-6">

    <div class="max-w-6xl mx-auto">
        <h1 class="text-2xl font-semibold mb-6">Add New Pharmacy Product</h1>

        <form wire:submit.prevent="addPharmacyProduct" enctype="multipart/form-data">

            <div class="grid grid-cols-3 gap-8">

                {{-- COLUMN 1 --}}
                <div class="space-y-4">
                    <h2 class="text-lg font-semibold mb-2">Basic Info</h2>

                    <label for="product_name" class="block text-sm font-medium mb-2">Product Name</label>
                    <input wire:model.defer="product_name" type="text"
                        class="w-full h-11 px-4 rounded-lg border"
                        placeholder="Product Name">
                    @error('product_name')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror

                    <label for="product_code" class="block text-sm font-medium mb-2">Product Code</label>
                    <input wire:model.defer="product_code" type="text"
                        class="w-full h-11 px-4 rounded-lg border"
                        placeholder="Product Code">
                    @error('product_code')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror

                    <label for="category" class="block text-sm font-medium mb-2">Category</label>
                    <input wire:model.defer="category" type="text"
                        class="w-full h-11 px-4 rounded-lg border"
                        placeholder="Category">
                    @error('category')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror

                    <label for="brand_name" class="block text-sm font-medium mb-2">Brand Name</label>
                    <input wire:model.defer="brand_name" type="text"
                        class="w-full h-11 px-4 rounded-lg border"
                        placeholder="Brand Name">
                    @error('brand_name')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror

                    <label for="product_description" class="block text-sm font-medium mb-2">Product Description</label>
                    <textarea wire:model.defer="product_description"
                        class="w-full h-24 px-4 py-2 rounded-lg border resize-none"
                        placeholder="Product Description"></textarea>
                    @error('product_description')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror

                </div>

                {{-- COLUMN 2 --}}
                <div class="space-y-4">
                    <h2 class="text-lg font-semibold mb-2">Medicine & Pricing</h2>

                    <label for="dosage_form" class="block text-sm font-medium mb-2">Dosage Form</label>
                    <input wire:model.defer="dosage_form" type="text"
                        class="w-full h-11 px-4 rounded-lg border"
                        placeholder="Dosage Form (Tablet / Syrup)">
                    @error('dosage_form')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror

                    <label for="strength" class="block text-sm font-medium mb-2">Strength</label>
                    <input wire:model.defer="strength" type="text"
                        class="w-full h-11 px-4 rounded-lg border"
                        placeholder="Strength (500mg / 10ml)">
                    @error('strength')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror

                    <label for="pack_size" class="block text-sm font-medium mb-2">Pack Size</label>
                    <input wire:model.defer="pack_size" type="text"
                        class="w-full h-11 px-4 rounded-lg border"
                        placeholder="Pack Size">
                    @error('pack_size')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror

                    <label for="mrp" class="block text-sm font-medium mb-2">MRP</label>
                    <input wire:model.defer="mrp" type="number" step="0.01"
                        class="w-full h-11 px-4 rounded-lg border"
                        placeholder="MRP">
                    @error('mrp')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror

                    <label for="selling_price" class="block text-sm font-medium mb-2">Selling Price</label>
                    <input wire:model.defer="selling_price" type="number" step="0.01"
                        class="w-full h-11 px-4 rounded-lg border"
                        placeholder="Selling Price">
                    @error('selling_price')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror

                    <label for="discount" class="block text-sm font-medium mb-2">Discount</label>
                    <input wire:model.defer="discount" type="number" step="0.01"
                        class="w-full h-11 px-4 rounded-lg border"
                        placeholder="Discount">
                    @error('discount')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                {{-- COLUMN 3 --}}
                <div class="space-y-4">
                    <h2 class="text-lg font-semibold mb-2">Stock & Compliance</h2>

                    <label for="stock_quantity" class="block text-sm font-medium mb-2">Stock Quantity</label>
                    <input wire:model.defer="stock_quantity" type="number" step="1"
                        class="w-full h-11 px-4 rounded-lg border"
                        placeholder="Stock Quantity">
                    @error('stock_quantity')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror

                    <label for="expiry_date" class="block text-sm font-medium mb-2">Expiry Date</label>
                    <input wire:model.defer="expiry_date" type="date"
                        class="w-full h-11 px-4 rounded-lg border">
                    @error('expiry_date')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror

                    <label for="batch_number" class="block text-sm font-medium mb-1">Batch Number</label>
                    <input wire:model.defer="batch_number" type="text"
                        class="w-full h-11 px-4 rounded-lg border"
                        placeholder="Batch Number">
                    @error('batch_number')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror   

                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model="prescription_required" class="rounded">
                        Prescription Required
                    </label>
                    @error('prescription_required')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror

                    {{-- IMAGE --}}
                    <div>
                        <label class="text-sm font-medium mb-2 block">Upload Product Image</label>

                        <div onclick="document.getElementById('productImage').click()"
                            class="h-32 border-2 border-dashed rounded-lg flex items-center justify-center 
                                   cursor-pointer bg-gray-50 hover:border-gray-400"
                            x-show="!$wire.product_image">

                            <div class="text-center text-gray-500">
                                <i class="fas fa-cloud-upload-alt text-2xl mb-1"></i>
                                <p class="text-sm">Click to upload</p>
                                <p class="text-xs">PNG / JPG up to 2MB</p>
                            </div>

                            <input type="file" id="productImage"
                                   wire:model="product_image"
                                   class="hidden" accept="image/*">
                        </div>
                        @error('product_image')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror

                        @if ($product_image)
                            <div class="relative h-32 border rounded-lg overflow-hidden">
                                <img src="{{ $product_image->temporaryUrl() }}"
                                     class="w-full h-full object-cover">
                                <button type="button"
                                    wire:click="removeImage"
                                    class="absolute top-2 right-2 bg-red-600 text-white w-7 h-7 rounded-full">
                                    ×
                                </button>
                            </div>
                        @endif
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

            {{-- FOOTER --}}
            <div class="flex justify-end gap-4 mt-8 pt-4 border-t">
                    {{-- <button class="px-6 py-2.5 bg-red-500 text-white rounded-lg text-sm font-medium hover:bg-red-600 transition w-full sm:w-auto" 
                        type="button" wire:click="closeModal">
                        Cancel
                    </button> --}}

                    <flux:button class="px-6 py-2.5 bg-red-500 text-white rounded-lg text-sm font-medium  transition w-full sm:w-auto" style="background:#f14336" wire:click="closeModal" type="button">
                        <i class="fa-solid fa-times mr-2 text-white"></i>
                        <span class="hidden sm:inline text-white">Cancel</span>
                        <span class="sm:hidden text-white">Cancel</span>
                    </flux:button>
                
                    {{-- <button type="button" wire:click='resetInput'
                        class="px-6 py-2.5 bg-gray-300 rounded-lg text-sm font-medium hover:bg-gray-400 transition w-full sm:w-auto">
                        Reset
                    </button> --}}

                    <flux:button class="px-6 py-2.5 bg-gray-300 rounded-lg text-sm font-medium transition w-full sm:w-auto" style="background:#6b7280" wire:click='resetInput' type="button">
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
