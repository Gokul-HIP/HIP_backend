<div class="space-y-6">

      <!-- HERO BANNER -->
    <div class="bg-white rounded-xl shadow-md border overflow-hidden">
        <div class="relative h-48 md:h-52 rounded-xl overflow-hidden">

            <!-- IMAGE -->
            <img 
                src="{{ asset('assets/lab-test.jpg') }}"
                class="w-full h-full object-cover rounded-xl object-center"
            >

            <!-- OVERLAY -->
            <div class="absolute inset-0 bg-black/30 rounded-xl"></div>

            <!-- CONTENT -->
            <div class="absolute inset-0 flex items-start justify-between p-6">

                <div class="backdrop-blur-md bg-white/20 border border-white/30 rounded-xl px-4 py-2 shadow-lg">
                    <h1 class="text-white text-2xl font-bold">
                        {{ $diagnostic->name }} - Diagnostic Center
                    </h1>
                    <p class="text-sm text-white/90 mt-1">
                        Manage {{ $diagnostic->name }} Lab Tests
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
        <h2 class="text-lg font-semibold mb-4">Test List Overview</h2>

        <div class="grid grid-cols-2 gap-4 max-w-md">
            <div class="bg-white p-4 border rounded-lg shadow-md">
                <p class="text-xs text-gray-500">Total Test</p>
                @php
                    $total = App\Models\DiagnosticLabTest::where('diagnostic_id', $diagnostic->id)->count();
                @endphp
                <p class="text-4xl font-bold mt-1">{{ $total }}</p>
            </div>

            <div class="bg-white p-4 border rounded-lg shadow-md">
                <p class="text-xs text-gray-500">Completed Test</p>
                @php
                    $completed = App\Models\DiagnosticLabTest::where('test_status','active')->where('diagnostic_id', $diagnostic->id)->count();
                @endphp
                <p class="text-4xl font-bold mt-1">{{ $completed }}</p>
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
                        placeholder="Search diagnostic,location,status..."
                        class="w-72 pl-10 pr-4 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" 
                        wire:model.live.debounce.300ms="search" />
                </div>

                <!-- LOCATION DROPDOWN -->
                {{-- <div class="relative">
                    <button onclick="toggleFilter('locFilter')" class="filter-btn">
                        <i class="fas fa-map-marker-alt mr-2 text-gray-700"></i>
                        <span class="filter-label">
                            {{ $locationFilter === 'all' ? 'All Locations' : ucfirst($locationFilter) }}
                            <i class="fa-solid fa-angle-down w-4 ml-3"></i>
                        </span>
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
                </div> --}}

                <!-- TYPE DROPDOWN -->
                {{-- <div class="relative">
                    <button onclick="toggleFilter('typeFilter')" class="filter-btn">
                        <i class="fas fa-list mr-2 text-gray-700"></i>
                        <span class="filter-label">
                            {{ $type === 'all' ? 'All Types' : ucfirst($type) }}
                            <i class="fa-solid fa-angle-down w-4 ml-3"></i>
                        </span>
                        <i data-lucide="chevron-down" class="w-4 ml-2"></i>
                    </button>
                    <div id="typeFilter" class="filter-dropdown hidden">
                        <ul class="p-2 text-sm text-gray-700 font-medium">
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 hover:text-gray-900 rounded" onclick="selectFilter(this,'typeFilter')" wire:click="$set('type','all')">
                                    <i class="fas fa-list mr-2 text-gray-700"></i>All Types</button>
                            </li>
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-green-50 hover:text-green-700 rounded" onclick="selectFilter(this,'typeFilter')" wire:click="$set('type','hospital')">
                                    <i class="fas fa-hospital mr-2 text-gray-700"></i>Hospital</button>
                            </li>
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-green-50 hover:text-green-700 rounded" onclick="selectFilter(this,'typeFilter')" wire:click="$set('type','pharmacy')">
                                    <i class="fas fa-pills mr-2 text-gray-700"></i>Pharmacy</button>
                            </li>
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-green-50 hover:text-green-700 rounded" onclick="selectFilter(this,'typeFilter')" wire:click="$set('type','diagnostics')">
                                    <i class="fas fa-microscope mr-2 text-gray-700"></i>Diagnostics</button>
                            </li>
                        </ul>
                    </div>
                </div> --}}

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
                                <button
                                    class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded"
                                    onclick="selectFilter(this,'statusFilter')"
                                    wire:click="$set('statusFilter','all')">
                                    <i class="fas fa-list mr-2 text-gray-700"></i> All Status
                                </button>
                            </li>
                            
                            <li>
                                <button
                                    class="inline-flex items-center w-full p-2 hover:bg-green-50 rounded"
                                    onclick="selectFilter(this,'statusFilter')"
                                    wire:click="$set('statusFilter','active')">
                                    <i class="fas fa-check-circle mr-2 text-gray-700"></i> Active
                                </button>
                            </li>
                            
                            <li>
                                <button
                                    class="inline-flex items-center w-full p-2 hover:bg-red-50 rounded"
                                    onclick="selectFilter(this,'statusFilter')"
                                    wire:click="$set('statusFilter','inactive')">
                                    <i class="fas fa-times-circle mr-2 text-gray-700"></i> Inactive
                                </button>
                            </li>
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
                                {{ $availableCategories->firstWhere('id', $categoryFilter)['name'] ?? 'All Categories' }}
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
                                    wire:click="$set('categoryFilter','{{ $category['id'] }}')">
                                    <i class="fas fa-folder mr-2 text-gray-700"></i> {{ $category['name'] }}
                                </button>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

            </div>

            <!-- ADD TEST -->
            <div class="flex items-center gap-3">
                <flux:modal.trigger name="add-lab-test">
                    <button
                        class="text-white px-6 py-2 rounded-lg shadow-md flex items-center justify-center min-w-[160px]"
                        style="background:#0da2e7;">
                        <i class="fa-solid fa-plus w-4 mr-2 text-white"></i>
                        <span class="hidden sm:inline text-white">Add Lab Test</span>
                        <span class="sm:hidden text-white">Add</span>
                    </button>
                </flux:modal.trigger>
            
                <button
                    wire:click="$dispatch('open-bulk-add-test', { diagnosticId: {{ $diagnosticId }} })"
                    class="px-6 py-2 rounded-lg shadow-md flex items-center justify-center min-w-[160px] text-white hover:bg-blue-600 transition-all duration-300"
                    style="background:#0da2e7;">
                    <i class="fa-solid fa-plus w-4 mr-2 text-white"></i>
                    Add Bulk Test
                </button>
            </div>
            
        </div>

        <!-- TABLE -->
        <table class="w-full border-collapse table-fixed shadow-md rounded-lg">
            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Test Name</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Category</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Code</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">

                @forelse($labTests as $labTest) 
                <tr class="hover:bg-gray-50">

                    <td class="px-6 py-4 text-sm">{{ $labTest->test_name }}</td>
                    <td class="px-6 py-4 text-sm">{{ $labTest->category->category_name ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm">{{ $labTest->test_code }}</td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 rounded-full text-xs font-medium 
                            {{ $labTest->test_status === 'active'
                                ? 'bg-green-100 text-green-700'
                                : 'bg-red-100 text-red-700' }}">
                            {{ ucfirst($labTest->test_status) }}
                        </span>
                    </td>

                    <!-- ACTION MENU (KEEP ORIGINAL CSS + JS) -->
                    <td class="px-6 py-4 action-cell">

                        <button onclick="toggleActionMenu(event,'menu-{{ $labTest->id }}')" class="action-btn">
                            <i class="fa-solid fa-ellipsis-vertical w-4"></i>
                        </button>

                        <div id="menu-{{ $labTest->id }}" class="action-menu hidden">
                            <ul class="p-2 text-sm text-gray-700 font-medium">

                                <li>
                                    <a href="{{ route('admin.organizations.show', $labTest->id) }}">
                                        <i class="fa-solid fa-eye"></i> View
                                    </a>
                                </li>

                                <li>
                                    <button 
                                        type="button" 
                                        wire:click="editLabTest({{ $labTest->id }})">
                                        <i class="fa-solid fa-pen-to-square w-4"></i> Edit
                                    </button>
                                </li>

                                <li>
                                    <button 
                                        type="button" 
                                        class="delete" 
                                        wire:click="deleteLabTest({{ $labTest->id }})">
                                        <i class="fa-regular fa-trash-can"></i> Delete
                                    </button>
                                </li>

                                <li>
                                    <a href="{{ route('admin.organizations.diagnostic.lab-test.index', $labTest->id) }}">
                                        <i class="fa-solid fa-microscope"></i> Manage Lab Tests
                                    </a>
                                </li>
                                
                                <li>
                                    <a href="{{ route('admin.organizations.pharmacy.index', $labTest->id) }}">
                                        <i class="fa-solid fa-barcode"></i> Manage Transactions
                                    </a>
                                </li>
                                
                                <li>
                                    <a href="{{ route('admin.member-profile.member-index') }}">
                                        <i class="fa-solid fa-users"></i> Manage Users
                                    </a>
                            </ul>
                        </div>

                    </td>
                </tr>
                @empty
                <tr>
                    <td  class="px-6 py-8 text-center justify-center items-center text-gray-500" colspan="5">
                        <i class="fas fa-clipboard-list text-gray-400 mb-3" style="font-size: 3rem;"></i>
                        <p class="text-lg font-medium text-gray-900">No lab tests found</p>
                        <p class="text-sm text-gray-600">Start by adding your first lab test</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">
            {{ $labTests->links() }}
        </div>
    </div>

   <!-- DELETE MODAL -->
   <flux:modal name="delete-lab-test" class="p-0" wire:close="closeModal" id="delete-org">
    <div x-data @click.outside="$wire.closeModal()">
        <div>

            <!-- Close Icon -->
            <flux:modal.close
                class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 cursor-pointer"
                wire:click="closeModal" />

            <!-- Title -->
            <h2 class="text-lg font-semibold text-gray-900 mb-2">
                Delete Lab Test?
            </h2>

            <!-- Description -->
            <p class="text-sm text-gray-500 mb-6 leading-relaxed">
                You're about to delete this lab test.<br>
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
                    Delete Lab Test
                </button>
            </div>

        </div>
    </div>
</flux:modal>

</div>
