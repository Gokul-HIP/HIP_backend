<div class="space-y-6" 
     x-data 
     x-init="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })"
     @relode-org.window="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })">

    <div class="bg-white rounded-xl shadow-md border overflow-hidden">
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
                        Organization Overview
                    </h1>
                    <p class="text-sm text-white/90 mt-1">
                        Manage Organization Overview
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
    </div>

    <!-- OVERVIEW -->
    <div>
        <h2 class="text-lg font-semibold mb-4 text-gray-900">Organization Overview</h2>

        <div class="grid grid-cols-2 gap-4 max-w-md">
            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md">
                <p class="text-xs text-gray-500">Total Organizations</p>
                <p class="text-4xl font-bold mt-1 text-gray-900">{{ $organization->total() }}</p>
            </div>

            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md">
                <p class="text-xs text-gray-500">Active Organizations</p>
                <p class="text-4xl font-bold mt-1 text-gray-900">{{ $organization->where('status', 'active')->count() }}</p>
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

            </div>

            <!-- ADD ORGANIZATION -->
            <flux:modal.trigger name="add-organization">
                <button
                wire:click="$dispatch('openAddModal')"
                class="text-white px-6 py-2 rounded-lg shadow-md flex items-center"
                style="background:#0da2e7;">
                <i class="fa-solid fa-plus w-4 mr-2"></i>
                Add Organization
                </button>
            </flux:modal.trigger>

        </div>

        <!-- TABLE -->
        <table class="w-full border-collapse table-fixed shadow-md rounded-lg">

            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Organization Name</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">City</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">

                @forelse ($organization as $org)
                    <tr class="hover:bg-gray-50">

                        <td class="px-6 py-4 text-sm">
                            {{ $org->name }}
                        </td>

                        <td class="px-6 py-4 text-sm">
                            {{ $org->city }}
                        </td>

                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                {{ $org->status === 'active'
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-red-100 text-red-700' }}">
                                {{ ucfirst($org->status) }}
                            </span>
                        </td>

                        <!-- ACTION MENU (MEMBER STYLE) -->
                        <td class="px-6 py-4">
                            <div class="action-menu-wrapper">

                                <button
                                    class="action-btn"
                                    onclick="toggleActionMenu(event,'menu-{{ $org->id }}')">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>

                                <div id="menu-{{ $org->id }}" class="action-menu hidden">
                                    <ul class="p-2 text-sm text-gray-700 font-medium">

                                        <li>
                                            <a href="{{ route('admin.organizations.show', $org->id) }}"
                                                 onclick="closeAllActionMenus()"
                                            class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                <i class="fa-regular fa-eye w-4 mr-2"></i> View
                                            </a>
                                        </li>

                                        <li>
                                            <button
                                                 onclick="closeAllActionMenus(); Livewire.dispatch('editOrg',{id:{{ $org->id }}});"
                                                class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                <i class="fa-regular fa-pen-to-square w-4 mr-2"></i> Edit
                                            </button>
                                        </li>

                                        <li>
                                            <button
                                                onclick="closeAllActionMenus(); Livewire.dispatch('delete',{id:{{ $org->id }}});"
                                                class="inline-flex items-center w-full p-2 text-red-600 rounded">
                                                <i class="fa-regular fa-trash-can w-4 mr-2"></i> Delete
                                            </button>
                                        </li>

                                        <li>
                                            <a href="{{ route('admin.organizations.Add-hospital.index',$org->id) }}"
                                            onclick="closeAllActionMenus()"
                                            class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                <i class="fa-solid fa-hospital w-4 mr-2"></i> Manage Hospital
                                            </a>
                                        </li>

                                        <li>
                                            <a href="{{ route('admin.organizations.pharmacy.index',$org->id) }}"
                                            onclick="closeAllActionMenus()"
                                            class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                <i class="fa-solid fa-pills w-4 mr-2"></i> Manage Pharmacy
                                            </a>
                                        </li>

                                        <li>
                                            <a href="{{ route('admin.organizations.diagnostic.index',$org->id) }}"
                                            onclick="closeAllActionMenus()"
                                            class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                <i class="fa-solid fa-microscope w-4 mr-2"></i> Manage Diagnostic Lab
                                            </a>
                                        </li>

                                        <li>
                                            <a href="{{ route('admin.member-profile.member-index') }}"
                                            onclick="closeAllActionMenus()"
                                            class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                <i class="fa-solid fa-users w-4 mr-2"></i> Manage Users
                                            </a>
                                        </li>

                                    </ul>
                                </div>
                            </div>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-10 text-center text-gray-500">
                            <i class="fas fa-clipboard-list text-gray-400 mb-3 text-3xl"></i>
                            <p class="text-lg font-medium text-gray-900"></p>No organizations found</p>
                            <p class="text-sm text-gray-600">Start by adding your first organization</p>
                        </td>
                    </tr>
                @endforelse

            </tbody>
        </table>

        <div class="mt-4">
            {{ $organization->links() }}
        </div>
    </div>

</div>

