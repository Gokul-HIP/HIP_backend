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
    </div> --}}

    <!-- OVERVIEW -->
    <div>
        <h2 class="text-lg font-semibold mb-4 text-gray-900">Doctor Booking Overview</h2>

        <div class="flex gap-4 overflow-x-auto pb-2">

            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md min-w-[200px]">
                <p class="text-xs text-gray-500">Total Doctor Bookings</p>
                <p class="text-4xl font-bold mt-1 text-gray-900">{{ $doctorBookings->count() }}</p>
            </div>
        
            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md min-w-[200px]">
                <p class="text-xs text-gray-500">Total Pending</p>
                <p class="text-4xl font-bold mt-1 text-gray-900">
                    {{ $doctorBookings->where('status', 'pending')->count() }}
                </p>
            </div>
        
            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md min-w-[200px]">
                <p class="text-xs text-gray-500">Total Cancellation</p>
                <p class="text-4xl font-bold mt-1 text-gray-900">
                    {{ $doctorBookings->where('status', 'cancelled')->count() }}
                </p>
            </div>
        
            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md min-w-[200px]">
                <p class="text-xs text-gray-500">Total Completed</p>
                <p class="text-4xl font-bold mt-1 text-gray-900">
                    {{ $doctorBookings->where('status', 'completed')->count() }}
                </p>
            </div>
        
            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md min-w-[260px]">
                <p class="text-xs text-gray-500">
                    Upcoming Doctor Bookings
                    <span class="text-xs">(Next 7 days)</span>
                </p>
                <p class="text-4xl font-bold mt-1 text-gray-900">
                    {{ $doctorBookings->where('booking_date', '<=', now()->addDays(7))->count() }}
                </p>
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
                        placeholder="Search name, mobile, member, hospital, doctor..."
                        class="w-72 pl-10 pr-4 py-2 border rounded-lg bg-white"
                        wire:model.live.debounce.300ms="search"
                    />
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

                <!-- DOCTOR DROPDOWN -->
                <div class="relative">
                    <button onclick="toggleFilter('doctorFilter')" class="filter-btn">
                        <i class="fas fa-user-doctor mr-2 text-gray-700"></i>
                        <span class="filter-label">
                            @if($doctorFilter === 'all')
                                All Doctors
                            @else
                                {{ $availableDoctors->where('id', $doctorFilter)->first()->doctor_name ?? 'All Doctors' }}
                            @endif
                        </span>
                        <i class="fa-solid fa-angle-down w-4 ml-3"></i>
                    </button>

                    <div id="doctorFilter" class="filter-dropdown hidden max-h-96 overflow-y-auto">
                        <ul class="p-2 text-sm text-gray-700 font-medium">
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded"
                                    onclick="selectFilter(this,'doctorFilter')"
                                    wire:click="$set('doctorFilter','all')">
                                    <i class="fas fa-list mr-2 text-gray-700"></i> All Doctors
                                </button>
                            </li>
                            @foreach($availableDoctors as $doctor)
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded"
                                    onclick="selectFilter(this,'doctorFilter')"
                                    wire:click="$set('doctorFilter',{{ $doctor->id }})">
                                    <i class="fas fa-user-md mr-2 text-gray-700"></i> {{ $doctor->doctor_name }}
                                </button>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <!-- HOSPITAL DROPDOWN -->
                <div class="relative">
                    <button onclick="toggleFilter('hospitalFilter')" class="filter-btn">
                        <i class="fas fa-hospital mr-2 text-gray-700"></i>
                        <span class="filter-label">
                            @if($hospitalFilter === 'all')
                                All Hospitals
                            @else
                                {{ $availableHospitals->where('id', $hospitalFilter)->first()->hospital_name ?? 'All Hospitals' }}
                            @endif
                        </span>
                        <i class="fa-solid fa-angle-down w-4 ml-3"></i>
                    </button>

                    <div id="hospitalFilter" class="filter-dropdown hidden max-h-96 overflow-y-auto">
                        <ul class="p-2 text-sm text-gray-700 font-medium">
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded"
                                    onclick="selectFilter(this,'hospitalFilter')"
                                    wire:click="$set('hospitalFilter','all')">
                                    <i class="fas fa-list mr-2 text-gray-700"></i> All Hospitals
                                </button>
                            </li>
                            @foreach($availableHospitals as $hospital)
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded"
                                    onclick="selectFilter(this,'hospitalFilter')"
                                    wire:click="$set('hospitalFilter',{{ $hospital->id }})">
                                    <i class="fas fa-hospital mr-2 text-gray-700"></i> {{ $hospital->hospital_name }}
                                </button>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <!-- DATE FILTER -->
                <div class="relative">
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
                </div>

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
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Hospital</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Doctor</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Requested Slot</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">

                @forelse ($doctorBookings as $doctorBooking)
                    <tr class="hover:bg-gray-50">

                        <td class="px-6 py-4 text-sm">
                            APT-{{ str_pad($doctorBooking->id, 4, '0', STR_PAD_LEFT) }}
                        </td>

                        <td class="px-6 py-4 text-sm">
                            {{ $doctorBooking->name }}
                        </td>

                        <td class="px-6 py-4 text-sm">
                            {{ $doctorBooking->member->name }}
                            <br>
                            <span class="text-xs text-gray-500">HIP-{{ str_pad($doctorBooking->member->id, 4, '0', STR_PAD_LEFT) }}</span>
                        </td>

                        <td class="px-6 py-4 text-sm">
                           +91 {{ $doctorBooking->mobile_number }}
                        </td>

                        <td class="px-6 py-4 text-sm">
                            {{ $doctorBooking->hospital->hospital_name }}
                            <br>
                            <span class="text-xs text-gray-500">HOS-{{ str_pad($doctorBooking->hospital->id, 4, '0', STR_PAD_LEFT) }}</span>
                        </td>

                        <td class="px-6 py-4">
                            {{ $doctorBooking->doctor->doctor_name }}
                            <br>
                            <span class="text-xs text-gray-500">DOC-{{ str_pad($doctorBooking->doctor->id, 4, '0', STR_PAD_LEFT) }}</span>
                        </td>

                        <td class="px-6 py-4 text-sm">
                            <div class="font-medium text-gray-800">
                                {{ optional($doctorBooking->booking_date)->format('M d, Y') }}
                            </div>
                        
                            <div class="text-xs text-gray-500 mt-1">
                                @php
                                    $slots = $doctorBooking->required_time_slots ?? [];
                        
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
                        </td>

                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                {{ $doctorBooking->status === 'confirmed'
                                    ? 'bg-green-100 text-green-700'
                                    : ($doctorBooking->status === 'cancelled'
                                        ? 'bg-red-100 text-red-700'
                                        : ($doctorBooking->status === 'pending'
                                            ? 'bg-yellow-100 text-yellow-700'
                                            : ($doctorBooking->status === 'completed'
                                                ? 'bg-blue-100 text-blue-700'
                                                : 'bg-gray-100 text-gray-700'))) }}">
                                {{ ucfirst($doctorBooking->status) }}
                            </span>
                        </td>

                        <!-- ACTION MENU (MEMBER STYLE) -->
                        <td class="px-6 py-4">
                            <div class="action-menu-wrapper">

                                <button
                                    class="action-btn"
                                    onclick="toggleActionMenu(event,'menu-{{ $doctorBooking->id }}')">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>

                                <div id="menu-{{ $doctorBooking->id }}" class="action-menu hidden">
                                    <ul class="p-2 text-sm text-gray-700 font-medium">

                                        <li>
                                            <a href="{{ route('admin.doctor-booking.appointment-details', $doctorBooking->id) }}"
                                                 onclick="closeAllActionMenus()"
                                            class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                <i class="fa-regular fa-eye w-4 mr-2"></i> View
                                            </a>
                                        </li>

                                        <li>
                                            <button
                                                 onclick="closeAllActionMenus(); Livewire.dispatch('editDoctorBooking',{id:{{ $doctorBooking->id }}});"
                                                class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                <i class="fa-regular fa-pen-to-square w-4 mr-2"></i> Edit
                                            </button>
                                        </li>

                                        <li>
                                            <button
                                               wire:click="openDeleteBookingModal({{ $doctorBooking->id }})"
                                                class="inline-flex items-center w-full p-2 text-red-600 rounded">
                                                <i class="fa-regular fa-trash-can w-4 mr-2"></i> Delete
                                            </button>
                                        </li>

                                        <li>
                                            <button
                                                wire:click="openUpdateStatusModal({{ $doctorBooking->id }})"
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
                        <td colspan="9" class="px-6 py-10 text-center text-gray-500">
                            <i class="fas fa-clipboard-list text-gray-400 mb-3 text-3xl"></i>
                            <p class="text-lg font-medium text-gray-900">No doctor bookings found</p>
                            <p class="text-sm text-gray-600">No bookings match your search criteria</p>
                        </td>
                    </tr>
                @endforelse

            </tbody>
        </table>

        <div class="mt-4">
            {{ $doctorBookings->links() }}
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
                            class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm font-medium shadow">
                            Cancel
                        </button>
                        <button type="button" wire:click="deleteBooking" wire:loading.attr="disabled"
                            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium shadow disabled:opacity-50">
                            <span wire:loading.remove wire:target="deleteBooking">Delete Booking</span>
                            <span wire:loading wire:target="deleteBooking">Deleting...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </flux:modal>
</div>

