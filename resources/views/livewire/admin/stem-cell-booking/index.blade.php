<div class="space-y-6" 
     x-data 
     x-init="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })"
     @relode-org.window="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })">

    <!-- OVERVIEW -->
    <div>
        <h2 class="text-2xl font-semibold mb-4 text-gray-900">Stem Cell Booking Overview</h2>

        <div class="flex gap-4 overflow-x-auto pb-2">

            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md min-w-[200px]">
                <p class="text-xs text-gray-500">Total Stem Cell Bookings</p>
                <p class="text-4xl font-bold mt-1 text-gray-900">{{ $totalBookings }}</p>
            </div>
        
            <div class="bg-white p-4 border border-gray-200 rounded-lg shadow-md min-w-[260px]">
                <p class="text-xs text-gray-500">
                    Upcoming Stem Cell Bookings
                    <span class="text-xs">(Next 7 days)</span>
                </p>
                <p class="text-4xl font-bold mt-1 text-gray-900">{{ $upcomingBookings }}</p>
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
                        placeholder="Search name, mobile, member..."
                        class="w-72 pl-10 pr-4 py-2 border rounded-lg bg-white"
                        wire:model.live.debounce.300ms="search"
                    />
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
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Booking Date</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Requested Slot</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">

                @forelse ($stemCellBookings as $stemCellBooking)
                    <tr class="hover:bg-gray-50">

                        <td class="px-6 py-4 text-sm">
                            APT-{{ str_pad($stemCellBooking->id, 4, '0', STR_PAD_LEFT) }}
                        </td>

                        <td class="px-6 py-4 text-sm">
                            {{ $stemCellBooking->name }}
                        </td>

                        <td class="px-6 py-4 text-sm">
                            @if($stemCellBooking->member)
                                {{ $stemCellBooking->member->name }}
                                <br>
                                <span class="text-xs text-gray-500">HIP-{{ str_pad($stemCellBooking->member->id, 4, '0', STR_PAD_LEFT) }}</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-sm">
                           +91 {{ $stemCellBooking->mobile_number }}
                        </td>

                        <td class="px-6 py-4 text-sm">
                            <div class="font-medium text-gray-800">
                                {{ optional($stemCellBooking->booking_date)->format('M d, Y') }}
                            </div>
                        </td>

                        <td class="px-6 py-4 text-sm">
                            <div class="text-xs text-gray-500">
                                @php
                                    $slots = $stemCellBooking->required_time_slots ?? [];
                        
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

                        <!-- ACTION MENU -->
                        <td class="px-6 py-4">
                            <div class="action-menu-wrapper">

                                <button
                                    class="action-btn"
                                    onclick="toggleActionMenu(event,'menu-{{ $stemCellBooking->id }}')">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>

                                <div id="menu-{{ $stemCellBooking->id }}" class="action-menu hidden">
                                    <ul class="p-2 text-sm text-gray-700 font-medium">

                                        <li>
                                            <a href="{{ route('admin.stemcell-booking.appointment-details', ['id' => $stemCellBooking->id]) }}"
                                                 onclick="closeAllActionMenus()"
                                            class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                <i class="fa-regular fa-eye w-4 mr-2"></i> View
                                            </a>
                                        </li>

                                        <li>
                                            <button
                                               wire:click="openDeleteBookingModal({{ $stemCellBooking->id }})"
                                                class="inline-flex items-center w-full p-2 text-red-600 rounded">
                                                <i class="fa-regular fa-trash-can w-4 mr-2"></i> Delete
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-10 text-center text-gray-500">
                            <i class="fas fa-clipboard-list text-gray-400 mb-3 text-3xl"></i>
                            <p class="text-lg font-medium text-gray-900">No stem cell bookings found</p>
                            <p class="text-sm text-gray-600">No bookings match your search criteria</p>
                        </td>
                    </tr>
                @endforelse

            </tbody>
        </table>

        <div class="mt-4">
            {{ $stemCellBookings->links() }}
        </div>
    </div>

    <!-- DELETE MODAL -->
    <flux:modal name="delete-stem-cell-booking" class="p-0" wire:close="closeDeleteBookingModal" id="delete-org">
        <div x-data @click.outside="$wire.closeDeleteBookingModal()">
            <div @click.stop>
                <!-- Close Icon -->
                <flux:modal.close class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 cursor-pointer"
                    wire:click="closeDeleteBookingModal" />

                <!-- Title -->
                <h2 class="text-lg font-semibold text-gray-900 mb-2">
                    Delete Stem Cell Booking
                </h2>

                <!-- Description -->
                <p class="text-sm text-gray-500 mb-4 leading-relaxed">
                    Are you sure you want to delete this stem cell booking? This action cannot be undone.
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
