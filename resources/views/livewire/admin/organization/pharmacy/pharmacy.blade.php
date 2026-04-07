<div class="space-y-6"  x-data 
     x-init="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })"
     @relode-phar.window="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })" >

     <style>
        ui-modal#delete-org dialog {
            max-width: 420px !important;
        }
     </style>

    {{-- <div class="bg-white rounded-xl shadow-md border overflow-hidden">
        <div class="relative h-48 md:h-52 rounded-xl overflow-hidden">

            <!-- IMAGE -->
            <img 
                src="{{ asset('assets/pharmacy.png') }}"
                class="w-full h-full object-cover rounded-xl object-center"
            >

            <!-- OVERLAY -->
            <div class="absolute inset-0 bg-black/30 rounded-xl"></div>

            <!-- CONTENT -->
            <div class="absolute inset-0 flex items-start justify-between p-6">
                <div class="backdrop-blur-md bg-white/20 border border-white/30 rounded-xl px-4 py-2 shadow-lg">
                    <h1 class="text-white text-2xl font-bold">
                        {{ $organization->name }} - Organization
                    </h1>
                    <p class="text-sm text-white/90 mt-1">
                        Manage {{ $organization->name }} Pharmacy List
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
                        {{ ucfirst($organization->name) }} - Organization
                    </h1>
                    <p class="text-sm text-gray-900/90 mt-1">
                        Manage {{ ucfirst($organization->name) }} Pharmacy List
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- OVERVIEW -->
    <div>
        {{-- <h2 class="text-lg font-semibold mb-4">Pharmacy Overview List</h2> --}}

        <div class="grid grid-cols-2 gap-4 max-w-md">
            <div class="bg-white p-4 border rounded-lg shadow-md">
                <p class="text-xs text-gray-500">Total Pharmacy</p>
                <p class="text-4xl font-bold mt-1">{{ $pharmacys->total() }}</p>
            </div>

            <div class="bg-white p-4 border rounded-lg shadow-md">
                <p class="text-xs text-gray-500">Active Pharmacy</p>
                <p class="text-4xl font-bold mt-1">{{ $pharmacys->where('status','active')->count() }}</p>
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
                    <input type="text"
                        placeholder="Search pharmacy name, address, license..."
                        class="w-72 pl-10 pr-4 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                        wire:model.live.debounce.300ms="search" />
                </div>

                <!-- STATUS DROPDOWN -->
                <div class="relative">
                    <button onclick="toggleFilter('statusFilter')" class="filter-btn">
                        <i class="fas fa-toggle-on mr-2 text-gray-700"></i>
                        <span class="filter-label">
                            {{ $statusFilter === 'all' ? 'All Status' : ucfirst($statusFilter) }}
                        </span>
                        <i class="fa-solid fa-angle-down w-4 ml-3"></i>
                    </button>
                    <div id="statusFilter" class="filter-dropdown hidden">
                        <ul class="p-2 text-sm text-gray-700 font-medium">
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 hover:text-gray-900 rounded" onclick="selectFilter(this,'statusFilter')" wire:click="$set('statusFilter','all')">
                                    <i class="fas fa-list mr-2 text-gray-700"></i>All Status</button>
                            </li>
                            <li>    
                                <button class="inline-flex items-center w-full p-2 hover:bg-green-50 hover:text-green-700 rounded" onclick="selectFilter(this,'statusFilter')" wire:click="$set('statusFilter','active')">
                                    <i class="fas fa-check-circle mr-2 text-gray-700"></i>Active</button>
                            </li>
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-red-50 hover:text-red-700 rounded" onclick="selectFilter(this,'statusFilter')" wire:click="$set('statusFilter','inactive')">
                                    <i class="fas fa-times-circle mr-2 text-gray-700"></i>Inactive</button>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>

            <!-- ADD ORGANIZATION (KEEP COLOR) -->
            <div class="ml-auto flex-shrink-0">
                <flux:modal.trigger name="add-pharmacy">
                    <button variant="primary" class="text-white px-6 py-2 rounded-lg shadow-md flex items-center" style="background:#0da2e7;">
                        <i class="fa-solid fa-plus w-4 mr-2 text-white"></i>
                        <span class="hidden sm:inline text-white">Add Pharmacy</span>
                        <span class="sm:hidden text-white">Add</span>
                    </button>
                </flux:modal.trigger>
                
                {{-- <button type="button" class="text-white px-6 py-2 rounded-lg shadow flex items-center" style="background:#0da2e7;" wire:click='addPharmacy()'>
                    <i data-lucide="plus" class="w-4 mr-2"></i>
                        Add Pharmacy
                </button> --}}
            </div>
        </div>

        <!-- TABLE -->
        <table class="w-full border-collapse table-fixed shadow-md rounded-lg">
            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Pharmacy Name</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Location</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Mobile</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Email</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">

                @forelse($pharmacys as $pharmacy)
                <tr class="hover:bg-gray-50">

                    <td class="px-6 py-4 text-sm">{{ $pharmacy->name }}</td>
                    <td class="px-6 py-4 text-sm">{{ $pharmacy->address }}</td>

                    <td class="px-6 py-4 text-sm">{{ $pharmacy->contact_person_number }}</td>

                    <td class="px-6 py-4 text-sm">{{ $pharmacy->contact_person_email }}</td>

                    <td class="px-6 py-4">
                        <span class="px-3 py-1 rounded-full text-xs font-medium 
                            {{ $pharmacy->status === 'active'
                                ? 'bg-green-100 text-green-700'
                                : 'bg-red-100 text-red-700' }}">
                            {{ ucfirst($pharmacy->status) }}
                        </span>
                    </td>

                    <!-- ACTION MENU (KEEP ORIGINAL CSS + JS) -->
                    <td class="px-6 py-4 action-cell">

                        <button onclick="toggleActionMenu(event,'menu-{{ $pharmacy->id }}')" class="action-btn">
                            <i class="fa-solid fa-ellipsis-vertical w-4"></i>
                        </button>

                        <div id="menu-{{ $pharmacy->id }}" class="action-menu hidden">
                            <ul class="p-2 text-sm text-gray-700 font-medium">
                                <li>
                                    <a href="{{ route('admin.organizations.show', $pharmacy->id) }}">
                                        <i class="fa-regular fa-eye w-4"></i> View
                                    </a>
                                </li>
                                <li>
                                    <button 
                                        type="button" 
                                        wire:click='edit({{ $pharmacy->id }})'>
                                        <i class="fa-regular fa-edit"></i>
                                        Edit
                                    </button>
                                </li>

                                <li>
                                    <button class="delete" wire:click='delete({{ $pharmacy->id }})'>
                                        <i class="fa-regular fa-trash-can"></i> Delete
                                    </button>
                                </li>

                                <li>
                                    <a href="{{ route('admin.organizations.pharmacy.products.index', $pharmacy->id) }}">
                                        <i class="fa-solid fa-capsules text-gray-700"></i> Manage Pharmacy Products
                                    </a>
                                </li>

                                <li>
                                    <a><i class="fa-solid fa-shopping-basket text-gray-700"></i> Manage Orders</a>
                                </li>
                                <li>
                                    <a><i class="fa-solid fa-indian-rupee text-gray-700"></i> Manage Transaction</a>
                                </li>
                            </ul>
                        </div>

                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center justify-center items-center text-gray-500">
                        <i class="fas fa-clipboard-list text-gray-400 mb-3" style="font-size: 3rem;"></i>
                        <p class="text-lg font-medium text-gray-900">No pharmacies found</p>
                        <p class="text-sm text-gray-600">Start by adding your first pharmacy</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">
            {{ $pharmacys->links() }}
        </div>
    </div>
    <flux:modal name="delete-phar" class="p-0" wire:close="closeModal" id="delete-org">
    <div x-data @click.outside="$wire.closeModal()">
        <div>

            <!-- Close Icon -->
            <flux:modal.close
                class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 cursor-pointer"
                wire:click="closeModal" />

            <!-- Title -->
            <h2 class="text-lg font-semibold text-gray-900 mb-2">
                Delete Pharmacy?
            </h2>

            <!-- Description -->
            <p class="text-sm text-gray-500 mb-6 leading-relaxed">
                You're about to delete this Pharmacy.<br>
                This action cannot be reversed.
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
                    Delete Pharmacy
                </button>
            </div>

        </div>
    </div>
</flux:modal>
</div>