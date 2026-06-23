<div class="space-y-6" id="hospital-index-root" x-data 
     x-init="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })"
     @relode-hos.window="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })">

    <style>
        ui-modal#delete-org dialog {
            max-width: 420px !important;
        }

        #hospital-index-root .action-menu-wrapper {
            position: relative;
            overflow: visible;
        }

        #hospital-index-root table {
            overflow: visible;
        }

        #hospital-index-root tbody,
        #hospital-index-root tr,
        #hospital-index-root td {
            overflow: visible;
        }

        /* Force light mode on modal - override dark mode */
        [data-flux-modal="delete-hos"] dialog,
        [data-flux-modal="delete-hos"] dialog * {
            color-scheme: light !important;
            background-color: #ffffff !important;
            color: #111827 !important;
            border-color: #d1d5db !important;
        }

        [data-flux-modal="delete-hos"] dialog {
            background-color: #ffffff !important;
            border-color: #d1d5db !important;
        }

        [data-flux-modal="delete-hos"] dialog input,
        [data-flux-modal="delete-hos"] dialog textarea,
        [data-flux-modal="delete-hos"] dialog select,
        [data-flux-modal="delete-hos"] dialog button,
        [data-flux-modal="delete-hos"] dialog div,
        [data-flux-modal="delete-hos"] dialog .border,
        [data-flux-modal="delete-hos"] dialog [class*="border"] {
            border-color: #d1d5db !important;
        }

        /* ===== ACTION MENU ===== */
        .action-btn {
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .action-btn:hover {
            background: #f9fafb;
        }

        /* Fixed position menu - coordinates set via JS using viewport coords */
        .action-menu {
            position: fixed;
            width: 240px;
            max-height: min(70vh, 420px);
            overflow-y: auto;
            overflow-x: hidden;
            overscroll-behavior: contain;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-shadow: 0 12px 30px rgba(0,0,0,0.15);
            z-index: 9999;
        }

        .action-menu::-webkit-scrollbar { width: 7px; }
        .action-menu::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.15); border-radius: 6px; }

        .action-menu ul li a,
        .action-menu ul li button {
            display: inline-flex;
            align-items: center;
            width: 100%;
            padding: 8px 10px;
            border-radius: 6px;
            font-size: 14px;
            color: #374151;
            background: transparent;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.15s;
        }

        .action-menu ul li a:hover,
        .action-menu ul li button:hover {
            background: #f3f4f6;
        }

        .action-menu ul li button.text-red-600:hover {
            background: #fef2f2;
        }
    </style>

    <div>
        <div class="relative h-32 rounded-xl overflow-hidden">
            <div class="absolute inset-0 flex items-start justify-between p-6">
                <div class="backdrop-blur-md bg-gray-200 border border-gray-300 rounded-xl px-4 py-2 shadow-lg">
                    <h1 class="text-gray-900 text-2xl font-bold">
                        Hospital Overview
                    </h1>
                    <p class="text-sm text-gray-900/90 mt-1">
                        Manage Hospital Overview
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- OVERVIEW -->
    <div class="">
        <div class="grid grid-cols-2 gap-4 max-w-md">
            <div class="bg-white p-4 rounded shadow-md" style="border-radius: 10px">
                <p class="text-xs text-gray-500">Total Hospital</p>
                <p class="text-4xl font-bold mt-1">{{ $totalCount }}</p>
            </div>

            <div class="bg-white p-4 rounded shadow-md">
                <p class="text-xs text-gray-500">Active Hospital</p>
                <p class="text-4xl font-bold mt-1">{{ $activeCount }}</p>
            </div>
        </div>
    </div>

    <!-- TABLE CARD -->
    <div class="bg-white rounded-lg shadow-md p-6 overflow-visible">

        <!-- FILTER BAR -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-3">

                <!-- Search -->
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 w-4 text-gray-400"></i>
                    <input type="text"
                        placeholder="Search hospital name, address, admin..."
                        class="w-72 pl-10 pr-4 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                        wire:model.live.debounce.300ms="search" />
                </div>

                <!-- Location -->
                <div class="relative">
                    <button onclick="toggleFilter('locFilter')" class="filter-btn">
                        <i class="fas fa-map-marker-alt mr-2 text-gray-700"></i>
                        <span class="filter-label">
                            {{ $locationFilter === 'all' ? 'All Locations' : ucfirst($locationFilter) }}
                        </span>
                        <i class="fa-solid fa-angle-down w-4 ml-3"></i>
                    </button>
                    <div id="locFilter" class="filter-dropdown hidden">
                        <ul class="p-2 text-sm text-gray-700 font-medium">
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 hover:text-gray-900 rounded" onclick="selectFilter(this,'locFilter')" wire:click="$set('locationFilter','all')">
                                    <i class="fas fa-list mr-2 text-gray-700"></i>All Locations</button>
                            </li>
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-green-50 hover:text-green-700 rounded" onclick="selectFilter(this,'locFilter')" wire:click="$set('locationFilter','mumbai')">
                                    <i class="fas fa-map-marker-alt mr-2 text-gray-700"></i>Mumbai</button>
                            </li>
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-green-50 hover:text-green-700 rounded" onclick="selectFilter(this,'locFilter')" wire:click="$set('locationFilter','bengaluru')">
                                    <i class="fas fa-map-marker-alt mr-2 text-gray-700"></i>Bengaluru</button>
                            </li>
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-green-50 hover:text-green-700 rounded" onclick="selectFilter(this,'locFilter')" wire:click="$set('locationFilter','chennai')">
                                    <i class="fas fa-map-marker-alt mr-2 text-gray-700"></i>Chennai</button>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Status -->
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

            <!-- Add Organization -->
            <div class="ml-auto flex-shrink-0">
                <flux:modal.trigger name="add-hospital">
                    <button variant="primary" class="text-white px-6 py-2 rounded-lg shadow-md flex items-center" style="background:#0da2e7;">
                        <i class="fa-solid fa-plus w-4 mr-2 text-white"></i>
                        <span class="hidden sm:inline text-white">Add Hospital</span>
                    </button>
                </flux:modal.trigger>
            </div>

        </div>

        <!-- TABLE -->
        <table class="w-full border-collapse table-fixed shadow-md rounded-lg">

            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Hospital Name</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Location</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Organization</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">

                @forelse ($hospitals as $hos)
                    <tr class="hover:bg-gray-50">

                        <td class="px-6 py-4 text-sm">
                            <a href="{{ route('admin.organizations.hospital.show', $hos->id) }}"
                            class="text-gray-900 hover:underline">
                                {{ $hos->name }}
                            </a>
                        </td>

                        <td class="px-6 py-4 text-sm">
                            {{ $hos->address ?? '-' }}
                        </td>

                        <td class="px-6 py-4 text-sm">
                            {{ $hos->organization->name ?? '-' }}
                        </td>

                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                {{ $hos->status === 'active'
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-red-100 text-red-700' }}">
                                {{ ucfirst($hos->status) }}
                            </span>
                        </td>

                        <!-- ACTION MENU -->
                        <td class="px-6 py-4">
                            <div class="action-menu-wrapper">

                                <button
                                    class="action-btn"
                                    onclick="hosToggleMenu(event, 'menu-{{ $hos->id }}')">
                                    <i class="fa-solid fa-ellipsis-vertical w-4"></i>
                                </button>

                                <div id="menu-{{ $hos->id }}"
                                    class="action-menu hidden">

                                    <ul class="p-2 text-sm text-gray-700 font-medium space-y-0.5">

                                        <li>
                                            <a href="{{ route('admin.organizations.hospital.show', $hos->id) }}"
                                            onclick="hosCloseAllMenus()">
                                                <i class="fa-regular fa-eye w-4 mr-2"></i>
                                                View
                                            </a>
                                        </li>

                                        <li>
                                            <button
                                                type="button"
                                                onclick="hosCloseAllMenus()"
                                                wire:click="edit({{ $hos->id }})">
                                                <i class="fa-regular fa-pen-to-square w-4 mr-2"></i>
                                                Edit
                                            </button>
                                        </li>

                                        <li>
                                            <button
                                                type="button"
                                                onclick="hosCloseAllMenus()"
                                                wire:click="delete({{ $hos->id }})"
                                                class="text-red-600">
                                                <i class="fa-regular fa-trash-can w-4 mr-2"></i>
                                                Delete
                                            </button>
                                        </li>

                                        <li>
                                            <a href="{{ route('admin.hospital-admin.index', $hos->id) }}"
                                            onclick="hosCloseAllMenus()">
                                                <i class="fa-solid fa-user-plus w-4 mr-2"></i>
                                                Manage Cashier Credentials
                                            </a>
                                        </li>

                                        <li>
                                            <a href="{{ route('admin.hospital-pharmacist-credentials.index', $hos->id) }}"
                                            onclick="hosCloseAllMenus()">
                                                <i class="fa-solid fa-pills w-4 mr-2"></i>
                                                Manage Pharmacist Credentials
                                            </a>
                                        </li>

                                        <li>
                                            <a href="{{ route('admin.hospital-technician-credentials.index', $hos->id) }}"
                                            onclick="hosCloseAllMenus()">
                                                <i class="fa-solid fa-microscope w-4 mr-2"></i>
                                                Manage Technician Credentials
                                            </a>
                                        </li>

                                        <li>
                                            <a href="{{ route('admin.hospital-receptionist-credentials.index', $hos->id) }}"
                                            onclick="hosCloseAllMenus()">
                                                <i class="fa-solid fa-user-tie w-4 mr-2"></i>
                                                Manage Receptionist Credentials
                                            </a>
                                        </li>

                                        <li>
                                            <a href="{{ route('admin.view-specialities.index', $hos->id) }}"
                                            onclick="hosCloseAllMenus()">
                                                <i class="fa-regular fa-hospital w-4 mr-2"></i>
                                                Manage Specialities
                                            </a>
                                        </li>

                                        <li>
                                            <a href="{{ route('admin.procedure.index', $hos->id) }}"
                                            onclick="hosCloseAllMenus()">
                                                <i class="fa-solid fa-pills w-4 mr-2"></i>
                                                Manage Procedures
                                            </a>
                                        </li>

                                        <li>
                                            <a href="{{ route('admin.view-doctor.ind', $hos->id) }}"
                                            onclick="hosCloseAllMenus()">
                                                <i class="fa-solid fa-user-doctor w-4 mr-2"></i>
                                                Manage Doctors
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
                            <p class="text-lg font-medium text-gray-900">No hospitals found</p>
                            <p class="text-sm text-gray-600">Start by adding your first hospital</p>
                        </td>
                    </tr>
                @endforelse

            </tbody>
        </table>

        <div class="mt-4">
            {{ $hospitals->links() }}
        </div>

    </div>

    <flux:modal name="delete-hos" class="p-0" wire:close="closeModal" id="delete-org">
        <div x-data @click.outside="$wire.closeModal()">
            <div>

                <flux:modal.close
                    class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 cursor-pointer"
                    wire:click="closeModal" />

                <h2 class="text-lg font-semibold text-gray-900 mb-2">
                    Delete Hospital?
                </h2>

                <p class="text-sm text-gray-500 mb-6 leading-relaxed">
                    You're about to delete this Hospital.<br>
                    This action cannot be reversed.
                </p>

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
                        class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium shadow">
                        Delete Hospital
                    </button>
                </div>

            </div>
        </div>
    </flux:modal>

</div>

<script>
(function () {
    // Use unique function names to avoid conflicts with any global JS

    function hosCloseAllMenus() {
        document.querySelectorAll('.action-menu').forEach(function (m) {
            m.classList.add('hidden');
        });
    }

    function hosToggleMenu(event, menuId) {
        event.stopPropagation();

        var menu = document.getElementById(menuId);
        if (!menu) return;

        var isCurrentlyHidden = menu.classList.contains('hidden');

        // Close all menus first
        hosCloseAllMenus();

        // If it was already open, leave it closed (toggle off)
        if (!isCurrentlyHidden) return;

        // Position the menu using fixed coords from the button
        var btn = event.currentTarget;
        var rect = btn.getBoundingClientRect();

        var menuWidth = 240;
        var menuLeft  = rect.right - menuWidth;
        
        var menuTop   = rect.bottom + 4;

        // Clamp left edge so it doesn't go off-screen left
        if (menuLeft < 8) menuLeft = 8;

        menu.style.position = 'fixed';
        menu.style.left     = menuLeft + 'px';
        menu.style.top      = menuTop  + 'px';
        menu.style.bottom   = '';

        // Show it
        menu.classList.remove('hidden');

        // After render, check if it overflows the bottom of the viewport and flip upward
        requestAnimationFrame(function () {
            var mRect = menu.getBoundingClientRect();
            if (mRect.bottom > window.innerHeight - 8) {
                menu.style.top  = (rect.top - menu.offsetHeight - 4) + 'px';
                menu.style.bottom = '';
            }
        });
    }

    // Expose to inline onclick handlers
    window.hosToggleMenu    = hosToggleMenu;
    window.hosCloseAllMenus = hosCloseAllMenus;

    // Close on outside click
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.action-menu') && !e.target.closest('.action-btn')) {
            hosCloseAllMenus();
        }
    });

    // Close on Livewire page navigations
    document.addEventListener('livewire:navigating', hosCloseAllMenus);
})();
</script>