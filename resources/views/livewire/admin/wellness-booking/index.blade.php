<div class="space-y-6" 
     x-data 
     x-init="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })"
     @relode-org.window="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })"
     @refresh-wellness-bookings.window="$wire.$refresh()">

    <!-- OVERVIEW -->
    <div>
        <h2 class="text-2xl font-semibold mb-4 text-gray-900">Wellness Booking Overview</h2>

        <div class="flex gap-4 overflow-x-auto pb-2">

            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md min-w-[200px]">
                <p class="text-xs text-gray-500">Total Wellness Bookings</p>
                <p class="text-4xl font-bold mt-1 text-gray-900">{{ $totalBookings }}</p>
            </div>
        
            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md min-w-[200px]">
                <p class="text-xs text-gray-500">Total Pending</p>
                <p class="text-4xl font-bold mt-1 text-gray-900">{{ $totalPending }}</p>
            </div>
        
            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md min-w-[200px]">
                <p class="text-xs text-gray-500">Total Cancellation</p>
                <p class="text-4xl font-bold mt-1 text-gray-900">{{ $totalCancellations }}</p>
            </div>
        
            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md min-w-[200px]">
                <p class="text-xs text-gray-500">Total Completed</p>
                <p class="text-4xl font-bold mt-1 text-gray-900">{{ $totalCompleted }}</p>
            </div>
        
            {{-- <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md min-w-[260px]">
                <p class="text-xs text-gray-500">
                    Upcoming Wellness Bookings
                    <span class="text-xs">(Next 7 days)</span>
                </p>
                <p class="text-4xl font-bold mt-1 text-gray-900">{{ $upcomingBookings }}</p>
            </div> --}}
        
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
                        placeholder="Search Member ID, Phone Number etc.."
                        class="w-72 pl-10 pr-4 py-2 border rounded-lg bg-white"
                        wire:model.live.debounce.300ms="search"
                    />
                </div>

                <!-- TYPE DROPDOWN -->
                <div class="relative">
                    <button onclick="toggleFilter('typeFilter')" class="filter-btn">
                        <i class="fas fa-tags mr-2 text-gray-700"></i>
                        <span class="filter-label">
                            {{ $typeFilter === 'all' ? 'All Types' : $typeFilter }}
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

                <!-- LOCATION DROPDOWN -->
                <div class="relative">
                    <button onclick="toggleFilter('locationFilter')" class="filter-btn">
                        <i class="fas fa-map-marker-alt mr-2 text-gray-700"></i>
                        <span class="filter-label">
                            {{ $locationFilter === 'all' ? 'All Locations' : $locationFilter }}
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
                                <button class="inline-flex items-center w-full p-2 hover:bg-yellow-50 rounded"
                                    onclick="selectFilter(this,'statusFilter')"
                                    wire:click="$set('status','pending')">
                                    <i class="fas fa-clock mr-2 text-gray-700"></i> Pending
                                </button>
                            </li>
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-green-50 rounded"
                                    onclick="selectFilter(this,'statusFilter')"
                                    wire:click="$set('status','confirmed')">
                                    <i class="fas fa-check-circle mr-2 text-gray-700"></i> Confirmed
                                </button>
                            </li>
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-blue-50 rounded"
                                    onclick="selectFilter(this,'statusFilter')"
                                    wire:click="$set('status','completed')">
                                    <i class="fas fa-check-double mr-2 text-gray-700"></i> Completed
                                </button>
                            </li>
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-red-50 rounded"
                                    onclick="selectFilter(this,'statusFilter')"
                                    wire:click="$set('status','cancelled')">
                                    <i class="fas fa-times-circle mr-2 text-gray-700"></i> Cancelled
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- DATE FILTER -->
                {{-- <div class="relative">
                    <div class="flex items-center border border-gray-300 rounded-lg bg-white px-4 py-2">
                        <i class="fas fa-calendar-alt mr-2 text-gray-700"></i>
                        <input
                            type="date"
                            class="border-none outline-none bg-transparent text-sm text-gray-700 cursor-pointer flex-1"
                            wire:model.live="dateFilter"
                            placeholder="Select Date"
                        />
                        @if($dateFilter)
                        <button 
                            type="button"
                            wire:click="clearDateFilter"
                            class="ml-2 text-gray-400 hover:text-gray-600 cursor-pointer"
                            title="Clear date filter">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                        @endif
                    </div>
                </div> --}}

            </div>

        </div>

        <!-- TABLE -->
        <table class="w-full border-collapse table-fixed shadow-md rounded-lg">

            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Apt ID</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Name</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Member</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Contact Number</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Centre</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">

                @forelse ($wellnessBookings as $wellnessBooking)
                    <tr class="hover:bg-gray-50">

                        <td class="px-6 py-4 text-sm">
                            APT{{ str_pad($wellnessBooking->id, 5, '0', STR_PAD_LEFT) }}
                        </td>

                        <td class="px-6 py-4 text-sm">
                            {{ $wellnessBooking->name }}
                        </td>

                        <td class="px-6 py-4 text-sm">
                            @if($wellnessBooking->member)
                                {{ $wellnessBooking->member->first_name ?? '' }} {{ $wellnessBooking->member->last_name ?? '' }}
                                <br>
                                <span class="text-xs text-gray-500">HIP{{ str_pad($wellnessBooking->member->id, 6, '0', STR_PAD_LEFT) }}</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-sm">
                           +91 {{ $wellnessBooking->mobile_number }}
                        </td>

                        <td class="px-6 py-4 text-sm">
                            @if($wellnessBooking->center)
                                {{ $wellnessBooking->center->centre_name }}
                                <br>
                                <span class="text-xs text-gray-500">HOS{{ str_pad($wellnessBooking->center->id, 4, '0', STR_PAD_LEFT) }}</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>

                        {{-- <td class="px-6 py-4 text-sm">
                            <div class="font-medium text-gray-800">
                                {{ optional($wellnessBooking->booking_date)->format('M d, Y') }}
                            </div>
                        
                            <div class="text-xs text-gray-500 mt-1">
                                @php
                                    $slots = $wellnessBooking->required_time_slots ?? [];
                        
                                    if (count($slots) >= 2) {
                                        $timeText = $slots[0] . ' - ' . end($slots);
                                    } elseif (count($slots) === 1) {
                                        $timeText = $slots[0];
                                    } else {
                                        $timeText = '-';
                                    }
                                @endphp
                        
                                {{ $timeText }}
                            </div>
                        </td> --}}

                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                {{ $wellnessBooking->status === 'confirmed'
                                    ? 'bg-green-100 text-green-700'
                                    : ($wellnessBooking->status === 'cancelled'
                                        ? 'bg-red-100 text-red-700'
                                        : ($wellnessBooking->status === 'pending'
                                            ? 'bg-yellow-100 text-yellow-700'
                                            : ($wellnessBooking->status === 'completed'
                                                ? 'bg-blue-100 text-blue-700'
                                                : 'bg-gray-100 text-gray-700'))) }}">
                                {{ ucfirst($wellnessBooking->status) }}
                            </span>
                        </td>

                        <!-- ACTION MENU -->
                        <td class="px-6 py-4">
                            <div class="action-menu-wrapper">

                                <button
                                    class="action-btn"
                                    onclick="toggleActionMenu(event,'menu-{{ $wellnessBooking->id }}')">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>

                                <div id="menu-{{ $wellnessBooking->id }}" class="action-menu hidden">
                                    <ul class="p-2 text-sm text-gray-700 font-medium">

                                        <li>
                                            <a href="{{ route('admin.wellness-booking.appointment-details', $wellnessBooking->id) }}"
                                                 onclick="closeAllActionMenus()"
                                            class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                <i class="fa-regular fa-eye w-4 mr-2"></i> View
                                            </a>
                                        </li>

                                        <li>
                                            <button
                                               wire:click="openDeleteBookingModal({{ $wellnessBooking->id }})"
                                                class="inline-flex items-center w-full p-2 text-red-600 rounded">
                                                <i class="fa-regular fa-trash-can w-4 mr-2"></i> Delete
                                            </button>
                                        </li>

                                        <li>
                                            <button
                                                wire:click="openUpdateStatusModal({{ $wellnessBooking->id }})"
                                                class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                <i class="fas fa-toggle-on mr-2 text-gray-700"></i> Update Status
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center text-gray-500">
                            <i class="fas fa-clipboard-list text-gray-400 mb-3 text-3xl"></i>
                            <p class="text-lg font-medium text-gray-900">No wellness bookings found</p>
                            <p class="text-sm text-gray-600">No bookings match your search criteria</p>
                        </td>
                    </tr>
                @endforelse

            </tbody>
        </table>

        <div class="mt-4">
            {{ $wellnessBookings->links() }}
        </div>
    </div>
    
    <flux:modal name="delete-booking" class="p-0" wire:close="closeDeleteBookingModal" id="delete-org">
        <div x-data @click.outside="$wire.closeDeleteBookingModal()">
            <div>
                <!-- Close Icon -->
                <flux:modal.close class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 cursor-pointer"
                    wire:click="closeDeleteBookingModal" />

                <!-- Title -->
                <h2 class="text-lg font-semibold text-gray-900 mb-2">
                    Delete Booking
                </h2>

                <!-- Description -->
                <p class="text-sm text-gray-500 mb-4 leading-relaxed">
                    Are you sure you want to delete this booking?
                </p>

                <!-- Buttons -->
                <div class="flex flex-col items-end gap-3">
                    <div class="flex justify-end gap-3 w-full">
                        <button type="button" wire:click="closeDeleteBookingModal"
                            class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm font-medium shadow"
                            style="background:#6b7280; color:#ffffff !important;">
                            <i class="fa-solid fa-times mr-2" style="color:#ffffff !important;"></i>
                            <span class="hidden sm:inline" style="color:#ffffff !important;"></span>Cancel</span>
                            <span class="sm:hidden" style="color:#ffffff !important;">Cancel</span>
                        </button>
                        <button type="button" wire:click="deleteBooking" wire:loading.attr="disabled"
                            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium shadow disabled:opacity-50">
                            <span wire:loading.remove wire:target="deleteBooking" style="color:#ffffff !important;">Delete Booking</span>
                            <span wire:loading wire:target="deleteBooking" style="color:#ffffff !important;">Deleting...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </flux:modal>
    
    @livewire('admin.wellness-booking.update-status')
</div>
