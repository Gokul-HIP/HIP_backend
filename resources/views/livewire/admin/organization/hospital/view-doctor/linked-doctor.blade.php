<div class="space-y-6">

    <!-- HERO BANNER -->
    
    <div class="bg-white rounded-xl shadow-md border overflow-hidden">
        <div class="relative h-48 md:h-52 rounded-xl overflow-hidden">

            <!-- IMAGE -->
            <img 
                src="https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?auto=format&fit=crop&w=1600&q=80"
                class="w-full h-full object-cover rounded-xl"
            >

            <!-- OVERLAY -->
            <div class="absolute inset-0 bg-black/30 rounded-xl"></div>

            <!-- CONTENT -->
            <div class="absolute inset-0 flex items-start justify-between p-6">
                <div class="backdrop-blur-md bg-white/20 border border-white/30 rounded-xl px-4 py-2 shadow-lg">
                    <h1 class="text-white text-2xl font-bold">
                        {{ $hospital->hospital_name }} - Hospital
                    </h1>
                    <p class="text-sm text-white/90 mt-1">
                        Manage {{ $hospital->hospital_name }} Doctor List
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

    <h2 class="text-lg font-semibold mb-3">Manage Doctor List</h2>
    <!-- MONTH FILTER -->
    <div class="flex items-center gap-4">
        <div class="relative">
            <button onclick="toggleFilter('monthMenu')" class="filter-btn">
                <span id="monthLabel">November</span>
                <i class="fa-solid fa-angle-down w-4"></i>
            </button>

            <div id="monthMenu" class="filter-dropdown hidden">
                <button class="filter-item" onclick="selectFilter(this,'monthMenu','monthLabel')">November</button>
                <button class="filter-item" onclick="selectFilter(this,'monthMenu','monthLabel')">October</button>
                <button class="filter-item" onclick="selectFilter(this,'monthMenu','monthLabel')">September</button>
            </div>
        </div>
    </div>

    <!-- TABLE CARD -->
    <div class="bg-white rounded-lg shadow p-6 overflow-visible">

        <!-- FILTER BAR -->
        <div class="flex items-center justify-between mb-6">

            <div class="flex items-center space-x-3">

                <!-- SEARCH -->
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-3 w-4 text-gray-400"></i>
                    <input type="text"
                        wire:model.debounce.500ms="search"
                        placeholder="Search doctor, mobile..."
                        class="w-72 pl-10 pr-4 py-2 border rounded-lg">
                </div>

                <!-- SORT -->
                <div class="relative">
                    <button onclick="toggleFilter('sortMenu')" class="filter-btn">
                        <span id="sortLabel">Sort by: Name (A-Z)</span>
                        <i class="fa-solid fa-angle-down w-4"></i>
                    </button>
                    <div id="sortMenu" class="filter-dropdown hidden">
                        <button class="filter-item"
                            onclick="selectFilter(this,'sortMenu','sortLabel')"
                            wire:click="$set('sort','name_asc')">
                            Name (A-Z)
                        </button>
                        <button class="filter-item"
                            onclick="selectFilter(this,'sortMenu','sortLabel')"
                            wire:click="$set('sort','latest')">
                            Latest
                        </button>
                        <button class="filter-item"
                            onclick="selectFilter(this,'sortMenu','sortLabel')"
                            wire:click="$set('sort','hospital')">
                            Assigned Hospitals
                        </button>
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

            <!-- Assign Doctor -->
            {{-- <div class="ml-auto flex-shrink-0">
                <flux:modal.trigger name="assign-doctor">
                    <flux:button variant="primary" class="text-white px-6 py-2 rounded-lg shadow flex items-center" style="background:#0da2e7;">
                        <i data-lucide="plus" class="w-4 mr-2"></i>
                        Assign Doctor
                    </flux:button>
                </flux:modal.trigger>
            </div> --}}

            <div class="ml-auto flex-shrink-0">
               <flux:modal.trigger
                    name="assign-doctor"
                    wire:click="$dispatch('open-assign-doctor', { hospitalId: {{ request()->id }} })">
                    <button class="text-white px-6 py-2 rounded-lg shadow-md flex items-center" style="background:#0da2e7;">
                        <i class="fas fa-plus w-4 mr-2 text-white"></i>
                        <span class="hidden sm:inline text-white">Assign Doctor</span>
                        <span class="sm:hidden text-white">Assign</span>
                    </button>
                </flux:modal.trigger>
            </div>

        </div>

        <!-- TABLE -->
        <table class="w-full table-fixed shadow-md rounded-lg">
            <thead class="bg-gray-100">
                <tr class="text-left text-sm font-semibold text-gray-700">
                    <th class="px-4 py-3 w-48">Doctor Name</th>
                    <th class="px-4 py-3 w-36">Mobile</th>
                    <th class="px-4 py-3 w-40">Qualification</th>
                    {{-- <th class="px-4 py-3 w-32">Assigned Hospitals</th> --}}
                    <th class="px-4 py-3 w-32">Assigned Specialities</th>
                    <th class="px-4 py-3 w-40">Assigned Procedures</th>
                    <th class="px-4 py-3 w-28">Status</th>
                    <th class="px-4 py-3 w-28">Created</th>
                    <th class="px-4 py-3 w-20 text-center">Action</th>
                </tr>
            </thead>
        
            <tbody class="divide-y divide-gray-200">
        
                @forelse ($doctors as $doctor)
                    <tr class="hover:bg-gray-50 align-top">
        
                        <!-- Doctor Name -->
                        <td class="px-4 py-3 text-sm font-medium">
                            {{ ucfirst($doctor->doctor_name) }}
                        </td>
        
                        <!-- Mobile -->
                        <td class="px-4 py-3 text-sm">
                            {{ $doctor->mobile_number ?? '-' }}
                        </td>
        
                        <!-- Qualification -->
                        <td class="px-4 py-3 text-sm">
                            {{ $doctor->qualification_names ?? '-' }}
                        </td>
        
                        <!-- Hospitals -->
                        {{-- <td class="px-4 py-3 text-sm">
                            @forelse ($doctor->assigned_hospital_name ?? [] as $hospital)
                                <span class="inline-block bg-green-100 text-green-800 text-xs px-2 py-1 rounded mr-1 mb-1">
                                    {{ $hospital }}
                                </span>
                            @empty
                                <span class="text-gray-400">-</span>
                            @endforelse
                        </td> --}}
        
                        <!-- Specialities -->
                        <td class="px-4 py-3 text-sm">
                            @forelse ($doctor->assigned_speciality_names ?? [] as $spec)
                                <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded mr-1 mb-1">
                                    {{ $spec }}
                                </span>
                            @empty
                                <span class="text-gray-400">-</span>
                            @endforelse
                        </td>
        
                        <!-- Procedures -->
                        <td class="px-4 py-3 text-sm">
                            @forelse ($doctor->assigned_procedure_names ?? [] as $proc)
                                <span class="inline-block bg-purple-100 text-purple-800 text-xs px-2 py-1 rounded mr-1 mb-1">
                                    {{ $proc }}
                                </span>
                            @empty
                                <span class="text-gray-400">-</span>
                            @endforelse
                        </td>
        
                        <!-- Status -->
                        <td class="px-4 py-3">
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                {{ $doctor->status === 'active'
                                    ? 'bg-green-100 text-green-800'
                                    : 'bg-red-100 text-red-800' }}">
                                {{ ucfirst($doctor->status) }}
                            </span>
                        </td>
        
                        <!-- Created -->
                        <td class="px-4 py-3 text-sm">
                            {{ $doctor->created_at?->format('d-m-Y') ?? '-' }}
                        </td>
        
                        <!-- Action -->
                        <td class="px-4 py-3 text-center action-cell relative">

                            <button class="action-btn"
                                onclick="toggleActionMenu(event,'menu-{{ $doctor->id }}')">
                                <i class="fa-solid fa-ellipsis-vertical w-4"></i>
                            </button>
                        
                            <div id="menu-{{ $doctor->id }}" class="action-menu hidden">
                                <ul class="p-2 text-sm text-gray-700 font-medium space-y-1">
                                    <li>
                                        <a href="#" class="w-full px-4 py-2 flex items-center gap-2">
                                            <i class="fa-regular fa-eye mr-2"></i> View
                                        </a>
                                    </li>
                                    @if($doctor->assignment_id)
                                        <li>
                                            <button 
                                                wire:click="editAssignment({{ $doctor->assignment_id }})"
                                                class="w-full px-4 py-2 text-left text-sm hover:bg-gray-50 flex items-center gap-2">
                                                <i class="fa-solid fa-edit"></i>
                                                Edit Assignment
                                            </button>
                                        </li>
                                        <li>
                                            <button 
                                                wire:click="delete({{ $doctor->assignment_id }})"
                                                class="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 flex items-center gap-2">
                                                <i class="fa-regular fa-trash-can mr-2"></i> Delete Assignment
                                            </button>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        
                        </td>
        
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-10 text-center text-gray-500">
                            <i class="fas fa-user-md text-gray-300 text-4xl mb-3"></i>
                            <p class="text-lg font-medium">No doctors assigned to this hospital.</p>
                        </td>
                    </tr>
                @endforelse
        
            </tbody>
        </table>        

    </div>

    <flux:modal name="delete-assignment" class="p-0" wire:close="closeModal" id="delete-org">
        <div x-data @click.outside="$wire.closeModal()">
            <div>

                <!-- Close Icon -->
                <flux:modal.close
                    class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 cursor-pointer"
                    wire:click="closeModal" />

                <!-- Title -->
                <h2 class="text-lg font-semibold text-gray-900 mb-2">
                    Delete Doctor Assignment?
                </h2>

                <!-- Description -->
                <p class="text-sm text-gray-500 mb-6 leading-relaxed">
                    You're about to delete this Doctor Assignment.<br>
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
                        Delete Doctor Assignment
                    </button>
                </div>

            </div>
        </div>
    </flux:modal>

</div>