<div class="space-y-6" 
     x-data 
     x-init="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })"
     @relode-org.window="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })">

    <style>
        .action-menu {
            position: absolute;
            right: 0;
            top: 35px;
            width: 260px !important;
            max-width: 260px !important;
            min-width: 260px !important;
            z-index: 50;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        .action-cell {
            position: relative !important;
        }
        .action-btn {
            background: white;
            border: 1px solid #d1d5db;
            color: #374151;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        [x-cloak] { display: none !important; }
    </style>

    <!-- OVERVIEW -->
<div>
        <h1 class="text-2xl font-semibold mb-4 text-gray-900">Wellness Centre Overview</h1>

        <div class="flex gap-4 overflow-x-auto pb-2">
            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md min-w-[200px]">
                <p class="text-xs text-gray-500">Total Wellness Centres</p>
                <p class="text-4xl font-bold mt-1 text-gray-900">{{ $totalCenters }}</p>
            </div>
        
            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md min-w-[200px]">
                <p class="text-xs text-gray-500">Active Physical Health Centres</p>
                <p class="text-4xl font-bold mt-1 text-gray-900">{{ $activePhysicalHealth }}</p>
            </div>
        
            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md min-w-[200px]">
                <p class="text-xs text-gray-500">Active Mental Health Centres</p>
                <p class="text-4xl font-bold mt-1 text-gray-900">{{ $activeMentalHealth }}</p>
            </div>
        
            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md min-w-[200px]">
                <p class="text-xs text-gray-500">Active Employee Coaching</p>
                <p class="text-4xl font-bold mt-1 text-gray-900">{{ $activeEmployeeCoaching }}</p>
            </div>
        </div>        
    </div>

   <!-- TABLE CARD -->
    <div class="bg-white rounded-lg shadow-md p-6 border">

        <!-- FILTER BAR -->
        <div class="flex items-center justify-between mb-6">

            <div class="flex items-center space-x-3 flex-wrap gap-3">

                <!-- SEARCH -->
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 w-4 text-gray-400"></i>
                    <input
                        type="text"
                        placeholder="Search location, Wellness Centre.."
                        class="w-72 pl-10 pr-4 py-2 border rounded-lg bg-white"
                        wire:model.live.debounce.300ms="search"
                    />
                </div>

                <!-- LOCATION DROPDOWN -->
                <div class="relative">
                    <button onclick="toggleFilter('locationFilter')" class="filter-btn">
                        <i class="fas fa-map-marker-alt mr-2 text-gray-700"></i>
                        <span class="filter-label">
                            @if($locationFilter === 'all')
                                All Locations
                            @else
                                {{ $locationFilter }}
                            @endif
                        </span>
                        <i class="fa-solid fa-angle-down w-4 ml-3"></i>
                    </button>

                    <div id="locationFilter" class="filter-dropdown hidden max-h-96 overflow-y-auto">
                        <ul class="p-2 text-sm text-gray-700 font-medium">
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded"
                                    onclick="selectFilter(this,'locationFilter')"
                                    wire:click="$set('locationFilter','all')">
                                    <i class="fas fa-list mr-2 text-gray-700"></i> All Locations
                                </button>
                            </li>
                            @foreach($availableLocations as $location)
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded"
                                    onclick="selectFilter(this,'locationFilter')"
                                    wire:click="$set('locationFilter','{{ $location }}')">
                                    <i class="fas fa-map-marker-alt mr-2 text-gray-700"></i> {{ $location }}
                                </button>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <!-- TYPE DROPDOWN -->
                <div class="relative">
                    <button onclick="toggleFilter('typeFilter')" class="filter-btn">
                        <i class="fas fa-tags mr-2 text-gray-700"></i>
                        <span class="filter-label">
                            @if($typeFilter === 'all')
                                All Types
                            @else
                                {{ $typeFilter }}
                            @endif
                        </span>
                        <i class="fa-solid fa-angle-down w-4 ml-3"></i>
                    </button>

                    <div id="typeFilter" class="filter-dropdown hidden max-h-96 overflow-y-auto">
                        <ul class="p-2 text-sm text-gray-700 font-medium">
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded"
                                    onclick="selectFilter(this,'typeFilter')"
                                    wire:click="$set('typeFilter','all')">
                                    <i class="fas fa-list mr-2 text-gray-700"></i> All Types
                                </button>
                            </li>
                            @foreach($availableTypes as $type)
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded"
                                    onclick="selectFilter(this,'typeFilter')"
                                    wire:click="$set('typeFilter','{{ $type }}')">
                                    <i class="fas fa-tag mr-2 text-gray-700"></i> {{ $type }}
                                </button>
                            </li>
                            @endforeach
                        </ul>
                    </div>
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
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded"
                                    onclick="selectFilter(this,'statusFilter')"
                                    wire:click="$set('statusFilter','all')">
                                    <i class="fas fa-list mr-2 text-gray-700"></i> All Status
                                </button>
                            </li>
                            @foreach($availableStatuses as $status)
                            <li>
                                <button class="inline-flex items-center w-full p-2 
                                    {{ $status === 'active' ? 'hover:bg-green-50' : ($status === 'inactive' ? 'hover:bg-red-50' : 'hover:bg-yellow-50') }} rounded"
                                    onclick="selectFilter(this,'statusFilter')"
                                    wire:click="$set('statusFilter','{{ $status }}')">
                                    <i class="fas fa-{{ $status === 'active' ? 'check-circle' : ($status === 'inactive' ? 'times-circle' : 'clock') }} mr-2 text-gray-700"></i> {{ ucfirst($status) }}
                                </button>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

            </div>

            <!-- ADD BUTTON -->
            <div class="ml-auto flex-shrink-0">
                <a href="{{ route('admin.wellness-services.create') }}" 
                   class="text-white px-6 py-2 rounded-lg shadow-md flex items-center" style="background:#0da2e7;">
                    <i class="fas fa-plus mr-2 text-white"></i>
                    <span class="hidden sm:inline text-white">Add Wellness Centre</span>
                    <span class="sm:hidden text-white">Add</span>
                </a>
            </div>

        </div>

        <!-- TABLE -->
        <table class="w-full border-collapse table-fixed shadow-md rounded-lg">

            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Wellness Centre</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Location</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Type</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">

                @forelse ($wellnessCenters as $wellnessCenter)
                    <tr class="hover:bg-gray-50">

                        <td class="px-6 py-4 text-sm">
                            <div class="font-medium text-gray-900">{{ $wellnessCenter->centre_name ?? '-' }}</div>
                            @if($wellnessCenter->contact_person_name)
                            <div class="text-xs text-gray-500 mt-1">{{ $wellnessCenter->contact_person_name }}</div>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-sm">
                            @if($wellnessCenter->city)
                            <div class="font-medium text-gray-900">{{ $wellnessCenter->city }}</div>
                            @endif
                            @if($wellnessCenter->state)
                            <div class="text-xs text-gray-500 mt-1">{{ $wellnessCenter->state }}</div>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-sm">
                            {{ $wellnessCenter->centre_type ?? '-' }}
                        </td>

                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                {{ $wellnessCenter->status === 'active'
                                    ? 'bg-green-100 text-green-700'
                                    : ($wellnessCenter->status === 'inactive'
                                        ? 'bg-red-100 text-red-700'
                                        : 'bg-yellow-100 text-yellow-700') }}">
                                {{ ucfirst($wellnessCenter->status ?? 'pending') }}
                            </span>
                        </td>

                        <!-- ACTION MENU -->
                        <td class="px-6 py-4">
                            <div class="action-menu-wrapper">
                                <button
                                    class="action-btn"
                                    onclick="toggleActionMenu(event,'menu-{{ $wellnessCenter->id }}')">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>

                                <div id="menu-{{ $wellnessCenter->id }}" class="action-menu hidden">
                                    <ul class="p-2 text-sm text-gray-700 font-medium">
                                        <li>
                                            <a href="{{ route('admin.wellness-services.view', $wellnessCenter->id) }}"
                                                onclick="closeAllActionMenus()"
                                                class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                <i class="fa-regular fa-eye w-4 mr-2"></i> View
                                            </a>
                                        </li>

                                        <li>
                                            <a href="{{ route('admin.wellness-services.edit', $wellnessCenter->id) }}"
                                                onclick="closeAllActionMenus()"
                                                class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                <i class="fa-regular fa-pen-to-square w-4 mr-2"></i> Edit
                                            </a>
                                        </li>

                                        <li>
                                            <button
                                                wire:click="deleteWellnessCenter({{ $wellnessCenter->id }})"
                                                onclick="closeAllActionMenus()"
                                                class="inline-flex items-center w-full p-2 text-red-600 hover:bg-red-50 rounded">
                                                <i class="fa-regular fa-trash-can w-4 mr-2"></i> Delete
                                            </button>
                                        </li>

                                        <li>
                                            <a href="#"
                                                onclick="closeAllActionMenus()"
                                                class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                <i class="fas fa-calendar-plus w-4 mr-2"></i> Manage Appointment
                                            </a>
                                        </li>

                                        <li>
                                            <a href="#"
                                                onclick="closeAllActionMenus()"
                                                class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                <i class="fas fa-users w-4 mr-2"></i> Manage Users
                                            </a>
                                        </li>

                                    </ul>
                                </div>
                            </div>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                            <i class="fas fa-clipboard-list text-gray-400 mb-3 text-3xl"></i>
                            <p class="text-lg font-medium text-gray-900">No wellness centres found</p>
                            <p class="text-sm text-gray-600">No centres match your search criteria</p>
                        </td>
                    </tr>
                @endforelse

            </tbody>
        </table>

        <div class="mt-4">
            {{ $wellnessCenters->links() }}
        </div>
    </div>

    <flux:modal name="delete-wellness-center" class="p-0" wire:close="closeModal" id="delete-org">
        <div x-data @click.outside="$wire.closeModal()">
            <div class="p-6">

                <!-- Close Icon -->
                <flux:modal.close
                    class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 cursor-pointer"
                    wire:click="closeModal" />

                <!-- Title -->
                <h2 class="text-lg font-semibold text-gray-900 mb-2">
                    Delete Wellness Centre?
                </h2>

                <!-- Description -->
                <p class="text-sm text-gray-500 mb-6 leading-relaxed">
                    You're about to delete <strong>{{ $wellnessCenterName ?? 'this wellness centre' }}</strong>.<br>
                    This action cannot be reversed and all associated files will be permanently deleted.
                </p>

                <!-- Buttons -->
                <div class="flex justify-end gap-4">
                    <flux:button variant="ghost"
                        wire:click="closeModal"
                        class="text-sm font-medium text-black hover:text-gray-900">
                        <i class="fa-solid fa-times mr-2 text-black"></i>
                        <span class="hidden sm:inline text-black">Cancel</span>
                        <span class="sm:hidden text-black">Cancel</span>
                    </flux:button>

                    <button
                        type="button"
                        wire:click="destroy"
                        wire:loading.attr="disabled"
                        class="bg-red-500 hover:bg-red-600 disabled:bg-red-300 text-white px-4 py-2 rounded-lg text-sm font-medium shadow flex items-center gap-2">
                        <span wire:loading.remove wire:target="destroy">
                            Delete Wellness Centre
                        </span>
                        <span wire:loading wire:target="destroy" class="flex items-center gap-2">
                            <i class="fas fa-spinner fa-spin"></i>
                            Deleting...
                        </span>
                    </button>
                </div>

            </div>
        </div>
    </flux:modal>


</div>
