<div class="space-y-6"  x-data 
x-init="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })"
@relod-doctor.window="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })">

    <!-- HERO BANNER -->
    
    {{-- <div class="bg-white rounded-xl shadow-md border overflow-hidden">
        <div class="relative h-48 md:h-52 rounded-xl overflow-hidden">

            <!-- IMAGE -->
            <img 
                src="{{ asset('assets/hospital.png') }}"
                class="w-full h-full object-cover rounded-xl object-center"
            >

            <!-- OVERLAY -->
            <div class="absolute inset-0 bg-black/30 rounded-xl"></div>

            <!-- CONTENT -->
            <div class="absolute inset-0 flex items-start justify-between p-6">

                <div class="backdrop-blur-md bg-white/20 border border-white/30 rounded-xl px-4 py-2 shadow-lg">
                    <h1 class="text-white text-2xl font-bold">
                        Doctor Profile List
                    </h1>
                    <p class="text-sm text-white/90 mt-1">
                        Manage Doctor Profile List
                    </p>
                </div>

                <!-- ACTION BUTTONS -->
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
                        Manage Doctor Profile List
                    </h1>
                    <p class="text-sm text-gray-900/90 mt-1">
                        Manage Doctor Overview
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div>
        {{-- <h2 class="text-lg font-semibold mb-4">Doctor Overview</h2> --}}

        <div class="grid grid-cols-3 gap-6 max-w-3xl">
            <div class="bg-white p-4 border rounded-lg shadow-md">
                <p class="text-xs text-gray-500">Total Doctors</p>
                <p class="text-4xl font-bold mt-1">{{ $doctors->total() }}</p>
            </div>

            <div class="bg-white p-4 border rounded-lg shadow-md">
                <p class="text-xs text-gray-500">Active Doctors</p>
                @php
                    $active = App\Models\Doctor::where('status','active')->count();
                @endphp
                <p class="text-4xl font-bold mt-1">{{ $active }}</p>
            </div>

            <div class="bg-white p-4 border rounded-lg shadow-md">
                <p class="text-xs text-gray-500">Inactive Doctors</p>
                @php
                    $inactive = App\Models\Doctor::where('status','inactive')->count();
                @endphp
                <p class="text-4xl font-bold mt-1">{{ $inactive }}</p>
            </div>

        </div>
    </div>

    <!-- MONTH FILTER -->
    <div class="flex items-center gap-4">
        <div class="relative shadow-md">
            <button onclick="toggleFilter('monthMenu')" class="filter-btn">
                <span id="monthLabel">November</span>
                <i class="fa-solid fa-chevron-down w-4"></i>
            </button>

            <div id="monthMenu" class="filter-dropdown hidden">
                <button class="filter-item" onclick="selectFilter(this,'monthMenu','monthLabel')">November</button>
                <button class="filter-item" onclick="selectFilter(this,'monthMenu','monthLabel')">October</button>
                <button class="filter-item" onclick="selectFilter(this,'monthMenu','monthLabel')">September</button>
            </div>
        </div>
    </div>

    <!-- TABLE CARD -->
    <div class="bg-white rounded-lg shadow-md border p-6 overflow-visible ">

        <!-- FILTER BAR -->
        <div class="flex items-center justify-between mb-6">

            <div class="flex items-center space-x-3">

                <!-- SEARCH -->
                <div class="relative">
                    <i  class="fa-solid fa-magnifying-glass absolute left-3 top-3 w-4 text-gray-400"></i>
                    <input type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search Doctor, status..."
                        class="w-72 pl-10 pr-4 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <!-- SORT -->
                <div class="relative">
                    <button onclick="toggleFilter('sortMenu')" class="filter-btn">
                        <span>
                            @switch($sort)
                                @case('name_asc') Name (A–Z) @break
                                @case('name_desc') Name (Z–A) @break
                                @case('newest') Newest First @break
                                @case('oldest') Oldest First @break
                            @endswitch
                        </span>
                        <i class="fa-solid fa-chevron-down w-4"></i>
                    </button>
                
                    <div id="sortMenu" class="filter-dropdown hidden">
                        <ul class="p-2 text-sm text-gray-700 font-medium">
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 hover:text-gray-900 rounded" onclick="selectFilter(this,'sortMenu')" wire:click="$set('sort','name_asc')">
                                    <i class="fas fa-sort-alpha-up mr-2 text-gray-700"></i>Name (A–Z)
                                </button>
                            </li>
                        </ul> 
                        <ul class="p-2 text-sm text-gray-700 font-medium">
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 hover:text-gray-900 rounded" onclick="selectFilter(this,'sortMenu')" wire:click="$set('sort','name_desc')">
                                    <i class="fas fa-sort-alpha-down mr-2 text-gray-700"></i>Name (Z–A)
                                </button>
                            </li>
                        </ul> 
                        
                        <ul class="p-2 text-sm text-gray-700 font-medium">
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 hover:text-gray-900 rounded" onclick="selectFilter(this,'sortMenu')" wire:click="$set('sort','newest')">
                                    <i class="fas fa-sort-numeric-up mr-2 text-gray-700"></i>Newest First
                                </button>
                            </li>
                        </ul> 
                        
                        <ul class="p-2 text-sm text-gray-700 font-medium">
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 hover:text-gray-900 rounded" onclick="selectFilter(this,'sortMenu')" wire:click="$set('sort','oldest')">
                                    <i class="fas fa-sort-numeric-down mr-2 text-gray-700"></i>Oldest First
                                </button>
                            </li>
                        </ul> 
                        
                    </div>
                </div>                

                <!-- STATUS -->
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
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 hover:text-gray-900 rounded" onclick="selectFilter(this,'statusFilter')" wire:click="$set('status','all')">
                                    <i class="fas fa-list mr-2 text-gray-700"></i>All Status</button>
                            </li>
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-green-50 hover:text-green-700 rounded" onclick="selectFilter(this,'statusFilter')" wire:click="$set('status','active')">
                                    <i class="fas fa-check-circle mr-2 text-gray-700"></i>Active</button>
                            </li>
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-red-50 hover:text-red-700 rounded" onclick="selectFilter(this,'statusFilter')" wire:click="$set('status','inactive')">
                                    <i class="fas fa-times-circle mr-2 text-gray-700"></i>Inactive</button>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>

            <!-- Add Doctor -->
            <div class="ml-auto flex-shrink-0">
                <flux:modal.trigger name="add-doctor">
                    <button variant="primary" class="text-white px-6 py-2 rounded-lg shadow-md flex items-center" style="background:#0da2e7;">
                        <i class="fa-solid fa-user-plus w-4 mr-2 text-white"></i>
                        <span class="hidden sm:inline text-white">Add Doctor</span>
                        <span class="sm:hidden text-white">Add</span>
                    </button>
                </flux:modal.trigger>
            </div>

        </div>

        <!-- TABLE -->
        <table class="w-full border-collapse table-fixed shadow-md rounded-lg">

            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Doctor Name</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Mobile Number</th>
                    {{-- <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Hospital</th> --}}
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Organization</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Qualification</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Speciality</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Working Since</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Created</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">

                @forelse ($doctors as $doctor)
                    <tr class="hover:bg-gray-50">

                        <td class="px-6 py-4 text-sm">
                            {{ $doctor->name ?? '-' }}
                        </td>

                        <td class="px-6 py-4 text-sm">
                            {{ $doctor->mobile_number ?? '-' }}
                        </td>

                        {{-- <td class="px-6 py-4 text-sm">
                            {{ $doctor->hospitals()->pluck('name')->isEmpty()
                                ? '-'
                                : $doctor->hospitals()->pluck('name')->join(', ') }}
                        </td> --}}

                        <td class="px-6 py-4 text-sm">
                            {{ $doctor->organization->name ?? '-' }}
                        </td>

                        <td class="px-6 py-4 text-sm">
                            {{ $doctor->qualification_names ?? '-' }}
                        </td>

                        <td class="px-6 py-4 text-sm">
                            {{ $doctor->speciality_names ?? '-' }}
                        </td>

                        <td class="px-6 py-4 text-sm">
                            {{ $doctor->working_since ?? '-' }}
                        </td>

                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                {{ $doctor->status === 'active'
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-red-100 text-red-700' }}">
                                {{ ucfirst($doctor->status) }}
                            </span>
                        </td>

                        <td class="px-6 py-4 text-sm">
                            {{ optional($doctor->created_at)->format('d-m-Y H:i:s') ?? '-' }}
                        </td>

                        <!-- ACTION MENU (SAME AS MEMBER TABLE) -->
                        <td class="px-6 py-4">
                            <div class="action-menu-wrapper">

                                <button
                                    class="action-btn"
                                    onclick="toggleActionMenu(event,'menu-{{ $doctor->id }}')">
                                    <i class="fa-solid fa-ellipsis-vertical w-4"></i>
                                </button>

                                <div id="menu-{{ $doctor->id }}"
                                    class="action-menu hidden bg-white border rounded-lg shadow-lg">

                                    <ul class="p-2 text-sm text-gray-700 font-medium">

                                        <li>
                                            <button
                                                onclick="closeAllActionMenus()"
                                                wire:click="viewDoctor('{{ $doctor->id }}')"
                                                class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                <i class="fa-regular fa-eye w-4 mr-2"></i>
                                                View Details
                                            </button>
                                        </li>

                                        <li>
                                            <button
                                                onclick="closeAllActionMenus()"
                                                wire:click="edit('{{ $doctor->id }}')"
                                                class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                <i class="fa-regular fa-pen-to-square w-4 mr-2"></i>
                                                Edit
                                            </button>
                                        </li>

                                        <li>
                                            <button
                                                onclick="closeAllActionMenus()"
                                                wire:click="delete('{{ $doctor->id }}')"
                                                class="inline-flex items-center w-full p-2 hover:bg-red-50 text-red-600 rounded">
                                                <i class="fa-regular fa-trash-can w-4 mr-2"></i>
                                                Delete
                                            </button>
                                        </li>

                                        <li>
                                            <button
                                            type="button"
                                            onclick="closeAllActionMenus()"
                                            wire:click="openCredentials('{{ $doctor->id }}')"
                                            class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                <i class="fa-solid fa-key w-4 mr-2"></i>
                                                Manage Credentials
                                            </button>
                                        </li>

                                        {{-- <li>
                                            <a href="#"
                                            onclick="closeAllActionMenus()"
                                            class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                <i class="fa-solid fa-capsules w-4 mr-2"></i>
                                                Manage Pharmacy
                                            </a>
                                        </li> --}}

                                        {{-- <li>
                                            <a href="#"
                                            onclick="closeAllActionMenus()"
                                            class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                <i class="fa-solid fa-clipboard-list w-4 mr-2"></i>
                                                Manage Diagnostic Lab
                                            </a>
                                        </li> --}}

                                        {{-- <li>
                                            <a href="#"
                                            onclick="closeAllActionMenus()"
                                            class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                <i class="fa-solid fa-users w-4 mr-2"></i>
                                                Manage Users
                                            </a>
                                        </li> --}}

                                    </ul>
                                </div>
                            </div>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-6 py-10 text-center text-gray-500">
                            No doctors found
                        </td>
                    </tr>
                @endforelse

            </tbody>
        </table>

        <div class="mt-4">
            {{ $doctors->links() }}
        </div>

    </div>

    <style>
    /* Force light mode on modal - override dark mode */
    [data-flux-modal="delete-doctor"] dialog,
    [data-flux-modal="delete-doctor"] dialog * {
        color-scheme: light !important;
        background-color: #ffffff !important;
        color: #111827 !important;
        border-color: #d1d5db !important;
    }
    
    [data-flux-modal="delete-doctor"] dialog {
        background-color: #ffffff !important;
        border-color: #d1d5db !important;
    }
    
    /* Force light borders on all elements */
    [data-flux-modal="delete-doctor"] dialog input,
    [data-flux-modal="delete-doctor"] dialog textarea,
    [data-flux-modal="delete-doctor"] dialog select,
    [data-flux-modal="delete-doctor"] dialog button,
    [data-flux-modal="delete-doctor"] dialog div,
    [data-flux-modal="delete-doctor"] dialog .border,
    [data-flux-modal="delete-doctor"] dialog [class*="border"] {
        border-color: #d1d5db !important;
    }
    </style>

    <flux:modal name="delete-doctor" class="p-0" wire:close="closeModal" id="delete-org">
        <div x-data @click.outside="$wire.closeModal()">
            <div>

            <!-- Close Icon -->
            <flux:modal.close
                class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 cursor-pointer"
                wire:click="closeModal" />

            <!-- Title -->
            <h2 class="text-lg font-semibold text-gray-900 mb-2">
                Delete Doctor?
            </h2>

            <!-- Description -->
            <p class="text-sm text-gray-500 mb-6 leading-relaxed">
                You're about to delete this Doctor.<br>
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
                    Delete Doctor
                </button>
            </div>

        </div>
    </div>
</flux:modal>

</div>  
