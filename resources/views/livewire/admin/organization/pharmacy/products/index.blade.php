<div class="space-y-6" 
     x-data 
     x-init="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })"
     @relode-org.window="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })">

    {{-- <div class="bg-white rounded-xl shadow-md border overflow-hidden">
        <div class="relative h-48 md:h-52 rounded-xl overflow-hidden">

            <!-- IMAGE -->
            <img 
                src="{{ asset('assets/org.jpg') }}"
                class="w-full h-full object-cover rounded-xl object-center"
            >

            <!-- OVERLAY -->
            <div class="absolute inset-0 bg-black/30 rounded-xl"></div>

            <!-- CONTENT -->
            <div class="absolute inset-0 flex items-start justify-between p-6">
                <div class="backdrop-blur-md bg-white/20 border border-white/30 rounded-xl px-4 py-2 shadow-lg">
                    <h1 class="text-white text-2xl font-bold">
                        Pharmacy Products
                    </h1>
                    <p class="text-sm text-white/90 mt-1">
                        Manage Pharmacy Products
                    </p>
                </div>

                <!-- ACTION BUTTON -->
                <div class="flex space-x-1">
                    <button class="backdrop-blur-md bg-white/20 border border-white/30 rounded-xl px-3 py-2 shadow-lg">
                        <i class="fas fa-edit text-white"></i>
                    </button>
                </div>
            </div>

        </div>
    </div> --}}

    <div>
        <div class="relative h-32 rounded-xl overflow-hidden">
            <div class="absolute inset-0 flex items-start justify-between p-6">
                <div class="backdrop-blur-md bg-gray-200 border border-gray-300 rounded-xl px-4 py-2 shadow-lg">
                    <h1 class="text-gray-900 text-2xl font-bold">
                        Pharmacy Products
                    </h1>
                    <p class="text-sm text-gray-900/90 mt-1">
                        Manage Pharmacy Products
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- OVERVIEW -->
    <div>
        {{-- <h2 class="text-lg font-semibold mb-4 text-gray-900">Pharmacy Products</h2> --}}

        <div class="grid grid-cols-2 gap-4 max-w-md">
            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md">
                <p class="text-xs text-gray-500">Total Products</p>
                <p class="text-4xl font-bold mt-1 text-gray-900">{{ $totalProducts }}</p>
            </div>

            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md">
                <p class="text-xs text-gray-500">Active Products</p>
                @php
                    $activeProducts = $pharmacyProducts->where('product_status', 'active')->count();
                @endphp
                <p class="text-4xl font-bold mt-1 text-gray-900">{{ $activeProducts }}</p>
            </div>
        </div>
    </div>

   <!-- TABLE CARD -->
    <div class="bg-white rounded-lg shadow-md p-6 border">

        <!-- FILTER BAR -->
        <div class="flex items-center justify-between mb-6">

            <div class="flex items-center space-x-3">

                <!-- SEARCH -->
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 w-4 text-gray-400"></i>
                    <input
                        type="text"
                        placeholder="Search organizations, city, status..."
                        class="w-72 pl-10 pr-4 py-2 border rounded-lg bg-white"
                        wire:model.live.debounce.300ms="search"
                    />
                </div>

                <!-- LOCATION DROPDOWN -->
                <div class="relative">
                    <button onclick="toggleFilter('locFilter')" class="filter-btn">
                        <i class="fas fa-map-marker-alt mr-2 text-gray-700"></i>
                        <span class="filter-label">
                            {{ $location === 'all' ? 'All Locations' : ucfirst($location) }}
                        </span>
                        <i class="fa-solid fa-angle-down w-4 ml-3"></i>
                    </button>

                    <div id="locFilter" class="filter-dropdown hidden">
                        <ul class="p-2 text-sm text-gray-700 font-medium">
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded"
                                    onclick="selectFilter(this,'locFilter')"
                                    wire:click="$set('location','all')">
                                    All Locations
                                </button>
                            </li>
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded"
                                    onclick="selectFilter(this,'locFilter')"
                                    wire:click="$set('location','mumbai')">
                                    Mumbai
                                </button>
                            </li>
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded"
                                    onclick="selectFilter(this,'locFilter')"
                                    wire:click="$set('location','bengaluru')">
                                    Bengaluru
                                </button>
                            </li>
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded"
                                    onclick="selectFilter(this,'locFilter')"
                                    wire:click="$set('location','chennai')">
                                    Chennai
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- STATUS DROPDOWN -->
                <div class="relative">
                    <button onclick="toggleFilter('statusFilter')" class="filter-btn">
                        <i class="fas fa-toggle-on mr-2 text-gray-700"></i>
                        <span class="filter-label">
                            {{ $status === 'all' ? 'All Status' : ucfirst($status) }}
                        </span>
                        <i class="fa-solid fa-angle-down w-4 ml-3"></i>
                    </button>

                    <div id="statusFilter" class="filter-dropdown hidden">
                        <ul class="p-2 text-sm text-gray-700 font-medium">
                            <li>
                                
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded"
                                    onclick="selectFilter(this,'statusFilter')"
                                    wire:click="$set('status','all')">
                                    <i class="fas fa-list mr-2 text-gray-700"></i> All Status
                                </button>
                            </li>
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-green-50 rounded"
                                    onclick="selectFilter(this,'statusFilter')"
                                    wire:click="$set('status','active')">
                                    <i class="fas fa-check-circle mr-2 text-gray-700"></i> Active
                                </button>
                            </li>
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-red-50 rounded"
                                    onclick="selectFilter(this,'statusFilter')"
                                    wire:click="$set('status','inactive')">
                                    <i class="fas fa-times-circle mr-2 text-gray-700"></i> Inactive
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- BRAND NAME DROPDOWN -->
                <div class="relative">
                    <button onclick="toggleFilter('brandFilter')" class="filter-btn">
                        <i class="fas fa-tag mr-2 text-gray-700"></i>
                        <span class="filter-label">
                            @if($brandFilter === 'all')
                                All Brands
                            @else
                                {{ $brandFilter }}
                            @endif
                        </span>
                        <i class="fa-solid fa-angle-down w-4 ml-3"></i>
                    </button>

                    <div id="brandFilter" class="filter-dropdown hidden max-h-96 overflow-y-auto">
                        <ul class="p-2 text-sm text-gray-700 font-medium">
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded"
                                    onclick="selectFilter(this,'brandFilter')"
                                    wire:click="$set('brandFilter','all')">
                                    <i class="fas fa-list mr-2 text-gray-700"></i> All Brands
                                </button>
                            </li>
                            @foreach($availableBrands as $brand)
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded"
                                    onclick="selectFilter(this,'brandFilter')"
                                    wire:click="$set('brandFilter','{{ $brand }}')">
                                    <i class="fas fa-tag mr-2 text-gray-700"></i> {{ $brand }}
                                </button>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <!-- CATEGORY DROPDOWN -->
                <div class="relative">
                    <button onclick="toggleFilter('categoryFilter')" class="filter-btn">
                        <i class="fas fa-folder mr-2 text-gray-700"></i>
                        <span class="filter-label">
                            @if($categoryFilter === 'all')
                                All Categories
                            @else
                                {{ $categoryFilter }}
                            @endif
                        </span>
                        <i class="fa-solid fa-angle-down w-4 ml-3"></i>
                    </button>

                    <div id="categoryFilter" class="filter-dropdown hidden max-h-96 overflow-y-auto">
                        <ul class="p-2 text-sm text-gray-700 font-medium">
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded"
                                    onclick="selectFilter(this,'categoryFilter')"
                                    wire:click="$set('categoryFilter','all')">
                                    <i class="fas fa-list mr-2 text-gray-700"></i> All Categories
                                </button>
                            </li>
                            @foreach($availableCategories as $category)
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded"
                                    onclick="selectFilter(this,'categoryFilter')"
                                    wire:click="$set('categoryFilter','{{ $category }}')">
                                    <i class="fas fa-folder mr-2 text-gray-700"></i> {{ $category }}
                                </button>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <!-- EXPIRY DATE FILTER -->
                <div class="relative">
                    <div class="flex items-center border border-gray-300 rounded-lg bg-white px-4 py-2">
                        <i class="fas fa-calendar-alt mr-2 text-gray-700"></i>
                        <input
                            type="date"
                            class="border-none outline-none bg-transparent text-sm text-gray-700 cursor-pointer flex-1"
                            wire:model.live="expiryDateFilter"
                            placeholder="Select Date"
                        />
                        @if($expiryDateFilter)
                        <button 
                            type="button"
                            wire:click="clearExpiryDateFilter"
                            class="ml-2 text-gray-400 hover:text-gray-600 cursor-pointer"
                            title="Clear date filter">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                        @endif
                    </div>
                </div>

            </div>

            <!-- ADD BUTTONS -->
            <div class="ml-auto flex-shrink-0 flex gap-3">
                <flux:modal.trigger name="add-pharmacy-product">
                    <button variant="primary" class="text-white px-6 py-2 rounded-lg shadow-md flex items-center" style="background:#0da2e7;">
                        <i class="fas fa-plus mr-2 text-white"></i>
                        <span class="hidden sm:inline text-white">Add Product</span>
                        <span class="sm:hidden text-white">Add</span>
                    </button>
                </flux:modal.trigger>
 
                <button
                     wire:click="$dispatch('open-bulk-add-medicines', { pharmacyId: {{ $pharmacyId }} })"
                     type="button"
                     class="text-white px-6 py-2 rounded-lg shadow-md flex items-center" style="background:#0da2e7;">
                     <i class="fas fa-layer-group mr-2 text-white"></i>
                     Add Bulk Medicines
                 </button>
            </div>

        </div>

        <!-- TABLE -->
        <table class="w-full border-collapse table-fixed shadow-md rounded-lg">

            <thead class="bg-gray-100 border-b text-center">
                <tr>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Product Name</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Category</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Brand Name</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Dosage Form</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Selling Price</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Discount</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Stock Quantity</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Expiry Date</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Batch Number</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Product Status</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">

                @forelse ($pharmacyProducts as $pharmacyProduct)
                    <tr class="hover:bg-gray-50 text-gray-900 text-center">

                        <td class="px-6 py-4 text-sm text-center">
                            {{ $pharmacyProduct->product_name ?? '-'}}
                        </td>

                        <td class="px-6 py-4 text-sm text-center">
                            {{ $pharmacyProduct->category ?? '-'}}
                        </td>

                        <td class="px-6 py-4 text-sm text-center">
                            {{ $pharmacyProduct->brand_name ?? '-'}}
                        </td>

                        <td class="px-6 py-4 text-sm text-center">
                            {{ $pharmacyProduct->dosage_form ?? '-'}}
                        </td>

                        <td class="px-6 py-4 text-sm text-center">
                            {{ $pharmacyProduct->selling_price ?? '-'}}
                        </td>

                        <td class="px-6 py-4 text-sm text-center">
                            {{ $pharmacyProduct->discount ?? '-'}}
                        </td>

                        <td class="px-6 py-4 text-sm text-center">
                            {{ $pharmacyProduct->stock_quantity ?? '-'}}
                        </td>

                        <td class="px-6 py-4 text-sm text-center">
                            {{ $pharmacyProduct->expiry_date ?? '-'}}
                        </td>

                        <td class="px-6 py-4 text-sm text-center">
                            {{ $pharmacyProduct->batch_number ?? '-'}}
                        </td>

                        <td class="px-6 py-4 text-sm text-center">
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                {{ $pharmacyProduct->product_status === 'active'
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-red-100 text-red-700' }}">
                                {{ ucfirst($pharmacyProduct->product_status) }}
                            </span>
                        </td>

                        <!-- ACTION MENU (MEMBER STYLE) -->
                        <td class="px-6 py-4">
                            <div class="action-menu-wrapper">

                                <button
                                    class="action-btn"
                                    onclick="toggleActionMenu(event,'menu-{{ $pharmacyProduct->id }}')">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>

                                <div id="menu-{{ $pharmacyProduct->id }}" class="action-menu hidden">
                                    <ul class="p-2 text-sm text-gray-700 font-medium">

                                        <li>
                                            <a href="{{ route('admin.organizations.show', $pharmacyProduct->id) }}"
                                                 onclick="closeAllActionMenus()"
                                            class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                <i class="fa-regular fa-eye w-4 mr-2"></i> View
                                            </a>
                                        </li>

                                        <li>
                                            <button
                                                wire:click="editProduct({{ $pharmacyProduct->id }})"
                                                class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                <i class="fa-regular fa-pen-to-square w-4 mr-2"></i> Edit
                                            </button>
                                        </li>

                                        <li>
                                            <button
                                                wire:click="deleteProduct({{ $pharmacyProduct->id }})"
                                                class="inline-flex items-center w-full p-2 text-red-600 rounded">
                                                <i class="fa-regular fa-trash-can w-4 mr-2"></i> Delete
                                            </button>
                                        </li>

                                    </ul>
                                </div>
                            </div>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="px-6 py-10 text-center text-gray-500">
                            <i class="fas fa-clipboard-list text-gray-400 mb-3 text-3xl"></i>
                            <p class="text-lg font-medium text-gray-900"></p>No pharmacy products found</p>
                            <p class="text-sm text-gray-600">Start by adding your first pharmacy product</p>
                        </td>
                    </tr>
                @endforelse

            </tbody>
        </table>

        <div class="mt-4">
            {{ $pharmacyProducts->links() }}
        </div>
    </div>

    <flux:modal name="delete-pharmacy-product" class="p-0" wire:close="closeModal" id="delete-org">
        <div x-data @click.outside="$wire.closeModal()">
            <div>

                <!-- Close Icon -->
                <flux:modal.close
                    class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 cursor-pointer"
                    wire:click="closeModal" />

                <!-- Title -->
                <h2 class="text-lg font-semibold text-gray-900 mb-2">
                    Delete Pharmacy Product?
                </h2>

                <!-- Description -->
                <p class="text-sm text-gray-500 mb-6 leading-relaxed">
                    You're about to delete this Pharmacy Product.<br>
                    This action cannot be undone.
                </p>

                <!-- Buttons -->
                <div class="flex justify-end gap-4">
                    <flux:button  variant="ghost"
                        wire:click="closeModal"
                        class="text-sm font-medium text-black hover:text-gray-900">
                        <i class="fa-solid fa-times mr-2 text-black"></i>
                        <span class="hidden sm:inline text-black">Cancel</span>
                        <span class="sm:hidden text-black">Cancel</span>
                    </flux:button>

                    <button
                        type="button"
                        wire:click="destroy"
                        class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium shadow">
                        Delete Pharmacy Product
                    </button>
                </div>

            </div>
        </div>
    </flux:modal>

</div>

