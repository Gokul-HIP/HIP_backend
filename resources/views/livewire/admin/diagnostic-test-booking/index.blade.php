<div class="space-y-6" 
     x-data 
     x-init="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })"
     @relode-org.window="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })">

    <!-- OVERVIEW -->
    <div>
        <h2 class="text-2xl font-semibold mb-4 text-gray-900">Diagnostic Test Booking Overview</h2>

        <div class="flex gap-4 overflow-x-auto pb-2">

            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md min-w-[200px]">
                <p class="text-xs text-gray-500">Total Diagnostic Bookings</p>
                <p class="text-4xl font-bold mt-1 text-gray-900">{{ $diagnosticTestBookings->count() }}</p>
            </div>
        
            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md min-w-[200px]">
                <p class="text-xs text-gray-500">Total Single Test Bookings</p>
                <p class="text-4xl font-bold mt-1 text-gray-900">
                    {{ $diagnosticTestBookings->where('test_type', 'single')->count() }}
                </p>
            </div>

            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md min-w-[200px]">
                <p class="text-xs text-gray-500">Total Multi Test Bookings</p>
                <p class="text-4xl font-bold mt-1 text-gray-900">
                    {{ $diagnosticTestBookings->where('test_type', 'multi')->count() }}
                </p>
            </div>

            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md min-w-[200px]">
                <p class="text-xs text-gray-500">Total Package Bookings</p>
                <p class="text-4xl font-bold mt-1 text-gray-900">
                    {{ $diagnosticTestBookings->where('test_type', 'package')->count() }}
                </p>
            </div>

            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md min-w-[200px]">
                <p class="text-xs text-gray-500">Total Pending</p>
                <p class="text-4xl font-bold mt-1 text-gray-900">
                    {{ $diagnosticTestBookings->where('status', 'pending')->count() }}
                </p>
            </div>
        
            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md min-w-[200px]">
                <p class="text-xs text-gray-500">Total Cancellation</p>
                <p class="text-4xl font-bold mt-1 text-gray-900">
                    {{ $diagnosticTestBookings->where('status', 'cancelled')->count() }}
                </p>
            </div>
        
            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md min-w-[200px]">
                <p class="text-xs text-gray-500">Total Completed</p>
                <p class="text-4xl font-bold mt-1 text-gray-900">
                    {{ $diagnosticTestBookings->where('status', 'completed')->count() }}
                </p>
            </div>
        
            {{-- <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md min-w-[260px]">
                <p class="text-xs text-gray-500">
                    Upcoming Diagnostic Bookings
                    <span class="text-xs">(Next 7 days)</span>
                </p>
                <p class="text-4xl font-bold mt-1 text-gray-900">
                    {{ $diagnosticTestBookings->where('booking_date', '<=', now()->addDays(7))->count() }}
                </p>
            </div> --}}
        
        </div>        
    </div>

   <!-- TABLE CARD -->
    <div class="bg-white rounded-lg shadow-md p-6 border">

        <!-- FILTER BAR -->
        <div class="flex items-center justify-between mb-6">

            <div class="flex items-center space-x-3 flex-wrap gap-3">

                <!-- SEARCI -->
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 w-4 text-gray-400"></i>
                    <input
                        type="text"
                        placeholder="Search name, mobile, member, diagnostic..."
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

                <!-- TEST TYPE DROPDOWN -->
                <div class="relative">
                    <button onclick="toggleFilter('testTypeFilter')" class="filter-btn">
                        <i class="fas fa-vial mr-2 text-gray-700"></i>
                        <span class="filter-label">
                            @if($testTypeFilter === 'all')
                                All Tests
                            @else
                                {{ $testTypeFilter === 'single' ? 'Single Test' : ($testTypeFilter === 'multi' ? 'Multi Test' : 'Package') }}
                            @endif
                        </span>
                        <i class="fa-solid fa-angle-down w-4 ml-3"></i>
                    </button>

                    <div id="testTypeFilter" class="filter-dropdown hidden">
                        <ul class="p-2 text-sm text-gray-700 font-medium">
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded"
                                    onclick="selectFilter(this,'testTypeFilter')"
                                    wire:click="$set('testTypeFilter','all')">
                                    <i class="fas fa-list mr-2 text-gray-700"></i> All Tests
                                </button>
                            </li>
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded"
                                    onclick="selectFilter(this,'testTypeFilter')"
                                    wire:click="$set('testTypeFilter','single')">
                                    <i class="fas fa-flask mr-2 text-gray-700"></i> Single Test
                                </button>
                            </li>
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded"
                                    onclick="selectFilter(this,'testTypeFilter')"
                                    wire:click="$set('testTypeFilter','multi')">
                                    <i class="fas fa-vials mr-2 text-gray-700"></i> Multi Test
                                </button>
                            </li>
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded"
                                    onclick="selectFilter(this,'testTypeFilter')"
                                    wire:click="$set('testTypeFilter','package')">
                                    <i class="fas fa-box mr-2 text-gray-700"></i> Package
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- DIAGNOSTIC DROPDOWN -->
                <div class="relative">
                    <button onclick="toggleFilter('diagnosticFilter')" class="filter-btn">
                        <i class="fas fa-hospital mr-2 text-gray-700"></i>
                        <span class="filter-label">
                            @if($diagnosticFilter === 'all')
                                All Diagnostics
                            @else
                                {{ $availableDiagnostics->where('id', $diagnosticFilter)->first()->name ?? 'All Diagnostics' }}
                            @endif
                        </span>
                        <i class="fa-solid fa-angle-down w-4 ml-3"></i>
                    </button>

                    <div id="diagnosticFilter" class="filter-dropdown hidden max-h-96 overflow-y-auto">
                        <ul class="p-2 text-sm text-gray-700 font-medium">
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded"
                                    onclick="selectFilter(this,'diagnosticFilter')"
                                    wire:click="$set('diagnosticFilter','all')">
                                    <i class="fas fa-list mr-2 text-gray-700"></i> All Diagnostics
                                </button>
                            </li>
                            @foreach($availableDiagnostics as $diagnostic)
                            <li>
                                <button class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded"
                                    onclick="selectFilter(this,'diagnosticFilter')"
                                    wire:click="$set('diagnosticFilter',{{ $diagnostic->id }})">
                                    <i class="fas fa-hospital mr-2 text-gray-700"></i> {{ $diagnostic->name }}
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
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Test ID</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Name</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Member</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Contact Number</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Centre</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Test/Package</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Requested Date</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Sample Collection</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Payment</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">

                @forelse ($diagnosticTestBookings as $booking)
                    <tr class="hover:bg-gray-50">

                        <td class="px-6 py-4 text-sm">
                            APT-{{ str_pad($booking->id, 4, '0', STR_PAD_LEFT) }}
                        </td>

                        <td class="px-6 py-4 text-sm">
                            {{ $booking->name ?? '-' }}
                        </td>

                        <td class="px-6 py-4 text-sm">
                            {{ optional($booking->member)->name ?? '-' }}
                            <br>
                            <span class="text-xs text-gray-500">{{ $booking->member->hip_id ?? 'N/A' }}</span>
                        </td>

                        <td class="px-6 py-4 text-sm">
                           +91 {{ $booking->mobile_number ?? '-' }}
                        </td>

                        <td class="px-6 py-4 text-sm">
                            {{ optional($booking->diagnosticCenter)->name ?? '-' }}
                            <br>
                            <span class="text-xs text-gray-500">DGN-{{ str_pad(optional($booking->diagnosticCenter)->id ?? 0, 4, '0', STR_PAD_LEFT) }}</span>
                        </td>

                        <td class="px-6 py-4 text-sm">
                            {{ $booking->test_type === 'single' ? 'Single Test' : ($booking->test_type === 'multi' ? 'Multi Test' : ($booking->test_type === 'package' ? 'Package' : '-')) }}
                        </td>

                        <td class="px-6 py-4 text-sm">
                            <div class="font-medium text-gray-800">
                                {{ optional($booking->booking_date)->format('M d, Y') }}
                            </div>
                        
                            <div class="text-xs text-gray-500 mt-1">
                                @php
                                    $slots = $booking->required_time_slots ?? [];
                        
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

                        <td class="px-6 py-4 text-sm">
                            {{ $booking->sample_collection === 'home' ? 'At Home' : ($booking->sample_collection === 'lab' ? 'Lab Visit' : '-') }}
                        </td>

                        <td class="px-6 py-4">
                            @php
                                $paymentLabel = $booking->payment_mode_label;
                                $paymentClass = match ($paymentLabel) {
                                    'Paid by online' => 'bg-blue-100 text-blue-700',
                                    'Pay by online' => 'bg-amber-100 text-amber-700',
                                    default => 'bg-slate-100 text-slate-700',
                                };
                            @endphp
                            <span class="px-3 py-1 rounded-full text-xs font-medium {{ $paymentClass }}">
                                {{ $paymentLabel }}
                            </span>
                        </td>

                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                {{ $booking->status === 'confirmed'
                                    ? 'bg-green-100 text-green-700'
                                    : ($booking->status === 'cancelled'
                                        ? 'bg-red-100 text-red-700'
                                        : ($booking->status === 'pending'
                                            ? 'bg-yellow-100 text-yellow-700'
                                            : ($booking->status === 'completed'
                                                ? 'bg-blue-100 text-blue-700'
                                                : 'bg-gray-100 text-gray-700'))) }}">
                                {{ ucfirst($booking->status) }}
                            </span>
                        </td>

                        <!-- ACTION MENU -->
                        <td class="px-6 py-4">
                            <div class="action-menu-wrapper">

                                <button
                                    class="action-btn"
                                    onclick="toggleActionMenu(event,'menu-{{ $booking->id }}')">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>

                                <div id="menu-{{ $booking->id }}" class="action-menu hidden">
                                    <ul class="p-2 text-sm text-gray-700 font-medium">

                                        <li>
                                            <a href="{{ route('admin.diagnostic-test-booking.appointment-details', $booking->id) }}"
                                                 onclick="closeAllActionMenus()"
                                            class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                <i class="fa-regular fa-eye w-4 mr-2"></i> View
                                            </a>
                                        </li>

                                        <li>
                                            <button
                                                 onclick="closeAllActionMenus(); Livewire.dispatch('editDiagnosticTestBooking',{id:{{ $booking->id }}});"
                                                class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                <i class="fa-regular fa-pen-to-square w-4 mr-2"></i> Edit
                                            </button>
                                        </li>

                                        <li>
                                            <button
                                               wire:click="openDeleteBookingModal({{ $booking->id }})"
                                                class="inline-flex items-center w-full p-2 text-red-600 rounded">
                                                <i class="fa-regular fa-trash-can w-4 mr-2"></i> Delete
                                            </button>
                                        </li>

                                        <li>
                                            <button
                                                wire:click="openUpdateStatusModal({{ $booking->id }})"
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
                        <td colspan="10" class="px-6 py-10 text-center text-gray-500">
                            <i class="fas fa-clipboard-list text-gray-400 mb-3 text-3xl"></i>
                            <p class="text-lg font-medium text-gray-900">No diagnostic test bookings found</p>
                            <p class="text-sm text-gray-600">No bookings match your search criteria</p>
                        </td>
                    </tr>
                @endforelse

            </tbody>
        </table>

        <div class="mt-4">
            {{ $diagnosticTestBookings->links() }}
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
</div>

