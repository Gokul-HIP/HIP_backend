<div class="space-y-6" 
     x-data 
     x-init="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })"
     @relode-org.window="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })">
    
     <div class="bg-white rounded-xl shadow-md border overflow-hidden">
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
                        Member Profile List
                    </h1>
                    <p class="text-sm text-white/90 mt-1">
                        Manage Member Profile List
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
    </div>

    <!-- OVERVIEW -->
    <div>
        <h2 class="text-lg font-semibold mb-4">Member Profile List</h2>

        <div class="grid grid-cols-2 gap-4 max-w-md">
            <div class="bg-white p-4 border rounded-lg shadow-md">
                <p class="text-xs text-gray-500">Total Members</p>
                <p class="text-4xl font-bold mt-1">{{ $members->count() }}</p>
            </div>

            <div class="bg-white p-4 border rounded-lg shadow-md">
                <p class="text-xs text-gray-500">Active Members</p>
                <p class="text-4xl font-bold mt-1">{{ $members->where('status', 'active')->count() }}</p>
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

                       <input type="text"  placeholder="Search members, mobile number, gender..." class="w-72 pl-10 pr-4 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                            wire:model.live.debounce.300ms="search"/>
                </div>

                <!-- LOCATION DROPDOWN -->
                {{-- <div class="relative">
                    <button onclick="toggleFilter('locFilter')" class="filter-btn">
                        All Locations
                        <i data-lucide="chevron-down" class="w-4 ml-2"></i>
                    </button>
                    <div id="locFilter" class="filter-dropdown hidden">
                        <button class="filter-item" onclick="selectFilter(this,'locFilter')">All Locations</button>
                        <button class="filter-item" onclick="selectFilter(this,'locFilter')">Mumbai</button>
                        <button class="filter-item" onclick="selectFilter(this,'locFilter')">Bengaluru</button>
                        <button class="filter-item" onclick="selectFilter(this,'locFilter')">Chennai</button>
                    </div>
                </div> --}}

                <!-- TYPE DROPDOWN -->
                <div class="relative">
                    <button onclick="toggleFilter('typeFilter')" class="filter-btn">
                        <i class="fas fa-user mr-2 text-gray-700"></i>
                        <span class="filter-label">
                            {{ $gender === 'all' ? 'All Genders' : ucfirst($gender) }}
                        </span>
                        <i class="fa-solid fa-angle-down w-4 ml-3"></i>
                    </button>
                    <div id="typeFilter" class="filter-dropdown hidden">
                        <ul class="p-2 text-sm text-gray-700 font-medium">
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 hover:text-gray-900 rounded" onclick="selectFilter(this,'typeFilter')" wire:click="$set('gender','all')">
                                    <i class="fas fa-list mr-2 text-gray-700"></i>All Genders</button>
                            </li>
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-green-50 hover:text-green-700 rounded" onclick="selectFilter(this,'typeFilter')" wire:click="$set('gender','Male')">
                                    <i class="fas fa-male mr-2 text-gray-700"></i>Male</button>
                            </li>
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-green-50 hover:text-green-700 rounded" onclick="selectFilter(this,'typeFilter')" wire:click="$set('gender','Female')">
                                    <i class="fas fa-female mr-2 text-gray-700"></i>Female</button>
                            </li>
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-green-50 hover:text-green-700 rounded" onclick="selectFilter(this,'typeFilter')" wire:click="$set('gender','Other')">
                                    <i class="fas fa-genderless mr-2 text-gray-700"></i>Other</button>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- STATUS DROPDOWN -->
                <div class="relative">
                    <button onclick="toggleFilter('statusFilter')" class="filter-btn">
                        <i class="fas fa-toggle-on mr-2 text-gray-700"></i>
                        <span class="filter-label">
                            {{ $profileUpdate === 'all' ? 'All Profile Updates' : ucfirst($profileUpdate) }}
                        </span>
                        <i class="fa-solid fa-angle-down w-4 ml-3"></i>
                    </button>
                    <div id="statusFilter" class="filter-dropdown hidden">
                            <ul class="p-2 text-sm text-gray-700 font-medium">
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 hover:text-gray-900 rounded" onclick="selectFilter(this,'statusFilter')" wire:click="$set('profileUpdate','all')">
                                    <i class="fas fa-list mr-2 text-gray-700"></i>All Profile Updates</button>
                            </li>
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-green-50 hover:text-green-700 rounded" onclick="selectFilter(this,'statusFilter')" wire:click="$set('profileUpdate','updated')">
                                    <i class="fas fa-check-circle mr-2 text-gray-700"></i>Updated</button>
                            </li>
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-red-50 hover:text-red-700 rounded" onclick="selectFilter(this,'statusFilter')" wire:click="$set('profileUpdate','not_updated')">
                                    <i class="fas fa-times-circle mr-2 text-gray-700"></i>Not Updated</button>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>

            <!-- ADD MEMBER -->
            {{-- <div class="ml-auto flex-shrink-0">
                <flux:modal.trigger name="add-organization">
                    <flux:button variant="primary" class="text-white px-6 py-2 rounded-lg shadow flex items-center" style="background:#0da2e7;">
                        <i data-lucide="plus" class="w-4 mr-2"></i>
                        Add Member
                    </flux:button>
                </flux:modal.trigger>
            </div> --}}

        </div>

        <!-- TABLE -->
        <table class="w-full border-collapse table-fixed shadow-md rounded-lg">
            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">First Name</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Last Name</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Mobile Number</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Gender</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Date of Birth</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Profile Update</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">

                @forelse($members as $member)

                    <tr class="hover:bg-gray-50">

                        <td class="px-6 py-4 text-sm">{{ $member->first_name ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm">{{ $member->last_name ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm">{{ $member->mobile_num ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm">{{ $member->gender ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm">  {{ $member->dob ?? '-' }}</td>

                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-medium 
                                {{ $member->profile_update === 1 
                                    ? 'bg-green-100 text-green-700' 
                                    : 'bg-red-100 text-red-700' }}">
                                {{ $member->profile_update === 1 ? 'Updated' : 'Not Updated' }}
                            </span>
                        </td>

                        <!-- ACTION MENU -->
                        <td class="px-6 py-4">
                            <div class="action-menu-wrapper">
                                <button
                                    id="dropdownDefaultButton-{{ $member->id }}"
                                    class="action-btn inline-flex items-center justify-center text-gray-700 bg-white border rounded-lg px-3 py-2"
                                    type="button"
                                    onclick="toggleActionMenu(event,'dropdown-{{ $member->id }}')">
                                    <i class="fa-solid fa-ellipsis-vertical w-4"></i>
                                </button>

                                <!-- Dropdown menu -->
                                <div 
                                    id="dropdown-{{ $member->id }}" 
                                    class="action-menu hidden bg-white border rounded-lg shadow-lg">
                                    <ul class="p-2 text-sm text-gray-700 font-medium" aria-labelledby="dropdownDefaultButton-{{ $member->id }}">
                                        <li>
                                            <a 
                                                href="{{ route('admin.organizations.show', $member->id) }}" 
                                                class="inline-flex items-center w-full p-2 hover:bg-gray-100 hover:text-gray-900 rounded">
                                                <i class="fa-regular fa-eye w-4 mr-2"></i>
                                                View
                                            </a>
                                        </li>
                                        <li>
                                            <button 
                                                type="button" 
                                                onclick="Livewire.dispatch('editOrg', { id: {{ $member->id }} })"
                                                class="inline-flex items-center w-full p-2 hover:bg-gray-100 hover:text-gray-900 rounded text-left">
                                                <i class="fa-regular fa-pen-to-square w-4 mr-2"></i> Edit
                                            </button>
                                        </li>
                                        <li>
                                            <button wire:click="manageDependents({{ $member->id }})" type="button" class="inline-flex items-center w-full p-2 hover:bg-gray-100 hover:text-gray-900 rounded">
                                                <i class="fa-regular fa-hospital w-4 mr-2"></i> Manage Dependents
                                            </button>
                                        </li>
                                        <li>
                                            <a 
                                                href="{{ route('admin.organizations.pharmacy.index', $member->id) }}" 
                                                class="inline-flex items-center w-full p-2 hover:bg-gray-100 hover:text-gray-900 rounded">
                                                <i class="fa-regular fa-calendar-days w-4 mr-2"></i> View Appointment Bookings
                                            </a>
                                        </li>
                                        <li>
                                            <a 
                                                href="{{ route('admin.organizations.diagnostic.index', $member->id) }}" 
                                                class="inline-flex items-center w-full p-2 hover:bg-gray-100 hover:text-gray-900 rounded">
                                                <i class="fa-solid fa-indian-rupee-sign w-4 mr-2"></i> View Transactions
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center justify-center items-center text-gray-500">
                            <i class="fas fa-clipboard-list text-gray-400 mb-3" style="font-size: 3rem;"></i>
                            <p class="text-lg font-medium text-gray-900">No member profile found</p>
                            <p class="text-sm text-gray-600">Start by adding your first member profile</p>
                        </td>
                    </tr>
                @endforelse  
            </tbody>
        </table>

        <div class="mt-4">
            {{ $members->links() }}
        </div>
    </div>
    
</div>