<div class="space-y-6"
     x-data
     x-init="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })">

    <div>
        <div class="relative h-32 rounded-xl overflow-hidden">
            <div class="absolute inset-0 flex items-start justify-between p-6">
                <div class="backdrop-blur-md bg-gray-200 border border-gray-300 rounded-xl px-4 py-2 shadow-lg">
                    <h1 class="text-gray-900 text-2xl font-bold">Pharmacy Products</h1>
                    <p class="text-sm text-gray-900/90 mt-1">
                        {{ $pharmacy?->name ?? 'Pharmacy' }} — catalog products
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 max-w-md">
        <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md">
            <p class="text-xs text-gray-500">Total Products</p>
            <p class="text-4xl font-bold mt-1 text-gray-900">{{ $totalProducts }}</p>
        </div>
        <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md">
            <p class="text-xs text-gray-500">Active Products</p>
            <p class="text-4xl font-bold mt-1 text-gray-900">{{ $activeProducts }}</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 border">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 w-4 text-gray-400"></i>
                    <input type="text"
                           placeholder="Search products..."
                           class="w-72 pl-10 pr-4 py-2 border rounded-lg bg-white"
                           wire:model.live.debounce.300ms="search">
                </div>

                <select wire:model.live="status" class="border rounded-lg px-3 py-2 text-sm bg-white">
                    <option value="all">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>

                <select wire:model.live="inStock" class="border rounded-lg px-3 py-2 text-sm bg-white">
                    <option value="all">All Stock</option>
                    <option value="yes">In Stock</option>
                    <option value="no">Out of Stock</option>
                </select>
            </div>

            <flux:modal.trigger name="add-catalog-product">
                <button type="button" class="text-white px-6 py-2 rounded-lg shadow-md flex items-center" style="background: var(--primary-color);">
                    <i class="fas fa-plus mr-2 text-white"></i>
                    Add Product
                </button>
            </flux:modal.trigger>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse shadow-md rounded-lg min-w-[900px]">
                <thead class="bg-gray-100 border-b text-center">
                    <tr>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-700">Image</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-700">Product Name</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-700">MRP</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-700">Selling Price</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-700">Discount %</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-700">In Stock</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-700">Status</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-700">Benefits</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-700">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($products as $product)
                        @php
                            $firstImage = collect($product->images ?? [])->first();
                        @endphp
                        <tr class="hover:bg-gray-50 text-gray-900 text-center">
                            <td class="px-4 py-4">
                                @if($firstImage)
                                    <img src="{{ \App\Services\CatalogProductService::imageUrl($firstImage) }}"
                                         alt="{{ $product->product_name }}"
                                         class="w-12 h-12 rounded-lg object-cover mx-auto border">
                                @else
                                    <div class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center mx-auto text-gray-400">
                                        <i class="fa-solid fa-box"></i>
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-sm font-medium">{{ $product->product_name }}</td>
                            <td class="px-4 py-4 text-sm">{{ $product->mrp !== null ? '₹'.number_format((float) $product->mrp, 2) : '—' }}</td>
                            <td class="px-4 py-4 text-sm">{{ $product->selling_price !== null ? '₹'.number_format((float) $product->selling_price, 2) : '—' }}</td>
                            <td class="px-4 py-4 text-sm">{{ $product->discount !== null ? rtrim(rtrim(number_format((float) $product->discount, 2), '0'), '.').'%' : '0%' }}</td>
                            <td class="px-4 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-medium {{ $product->in_stock ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $product->in_stock ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-medium {{ $product->status ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $product->status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-sm">{{ $product->product_benefits_count ?? 0 }}</td>
                            <td class="px-4 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <button type="button"
                                            wire:click="editProduct({{ $product->id }})"
                                            class="inline-flex items-center px-3 py-1.5 text-sm rounded-lg border border-gray-300 hover:bg-gray-50">
                                        <i class="fa-regular fa-pen-to-square mr-1"></i> Edit
                                    </button>
                                    <button type="button"
                                            wire:click="deleteProduct({{ $product->id }})"
                                            class="inline-flex items-center px-3 py-1.5 text-sm rounded-lg text-red-600 border border-red-200 hover:bg-red-50">
                                        <i class="fa-regular fa-trash-can mr-1"></i> Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-10 text-center text-gray-500">
                                <i class="fas fa-box-open text-gray-400 mb-3 text-3xl"></i>
                                <p class="text-lg font-medium text-gray-900">No products found</p>
                                <p class="text-sm text-gray-600">Start by adding your first catalog product</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $products->links() }}
        </div>
    </div>

    <flux:modal name="delete-catalog-product" class="p-0" wire:close="closeModal" id="delete-org">
        <div x-data @click.outside="$wire.closeModal()">
            <flux:modal.close class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 cursor-pointer" wire:click="closeModal" />
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Delete Product?</h2>
            <p class="text-sm text-gray-500 mb-6">This will permanently delete the product and its benefits.</p>
            <div class="flex justify-end gap-4">
                <flux:button variant="ghost" wire:click="closeModal">Cancel</flux:button>
                <button type="button" wire:click="destroy" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium shadow">
                    Delete Product
                </button>
            </div>
        </div>
    </flux:modal>
</div>
