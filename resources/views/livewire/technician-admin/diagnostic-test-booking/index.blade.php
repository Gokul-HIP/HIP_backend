<div class="space-y-6">
    <style>
        .hip-diag-menu { position: fixed; z-index: 9999; width: 12rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 0.75rem; box-shadow: 0 10px 30px rgba(0,0,0,0.12); overflow: hidden; }
        .hip-diag-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.6rem 1rem; font-size: 0.875rem; color: #475569; text-decoration: none; background: transparent; border: none; border-bottom: 1px solid #f8fafc; width: 100%; text-align: left; cursor: pointer; }
        .hip-diag-item:last-child { border-bottom: none; }
        .hip-diag-item:hover { background: #f0f9ff; color: #1A9FD4; }
    </style>

    <div>
        <h1 class="text-2xl font-bold text-slate-900">Diagnostic Test Bookings</h1>
        <p class="text-sm text-slate-500 mt-1">Manage diagnostic bookings across your hospitals</p>
    </div>

    <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:1rem;">
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
            <p class="text-xs text-slate-500 uppercase">Total Bookings</p>
            <p class="text-3xl font-bold text-slate-900 mt-1">{{ $totalBookings }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
            <p class="text-xs text-slate-500 uppercase">Pending</p>
            <p class="text-3xl font-bold text-slate-900 mt-1">{{ $pendingBookings }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
            <p class="text-xs text-slate-500 uppercase">Completed</p>
            <p class="text-3xl font-bold text-slate-900 mt-1">{{ $completedBookings }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
            <p class="text-xs text-slate-500 uppercase">Cancelled</p>
            <p class="text-3xl font-bold text-slate-900 mt-1">{{ $cancelledBookings }}</p>
        </div>
    </div>

    <div class="space-y-3">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search name, phone, member ID..."
            class="w-full text-sm rounded-xl border border-slate-200 px-4 py-3">

        <div class="flex gap-3 flex-wrap">
            <div class="relative" x-data="{ open: false }">
                <button type="button" @click="open = !open" class="inline-flex items-center gap-2 px-5 py-2 rounded-full text-sm font-medium text-white" style="background:var(--button-color);">
                    {{ $hospitalFilter === 'all' ? 'All Hospitals' : ($hospitals->firstWhere('id', $hospitalFilter)?->name ?? 'Hospital') }}
                </button>
                <div x-show="open" x-cloak @click.away="open = false" class="absolute z-20 mt-2 w-56 rounded-xl border bg-white shadow-lg overflow-hidden">
                    <button type="button" wire:click="$set('hospitalFilter', 'all')" @click="open = false" class="hip-diag-item w-full">All Hospitals</button>
                    @foreach ($hospitals as $hospital)
                        <button type="button" wire:click="$set('hospitalFilter', '{{ $hospital->id }}')" @click="open = false" class="hip-diag-item w-full">{{ $hospital->name }}</button>
                    @endforeach
                </div>
            </div>

            <div class="relative" x-data="{ open: false }">
                <button type="button" @click="open = !open" class="inline-flex items-center gap-2 px-5 py-2 rounded-full text-sm font-medium text-white" style="background:var(--button-color);">
                    {{ $statusFilter === 'all' ? 'All Status' : ucfirst($statusFilter) }}
                </button>
                <div x-show="open" x-cloak @click.away="open = false" class="absolute z-20 mt-2 w-48 rounded-xl border bg-white shadow-lg overflow-hidden">
                    @foreach (['all', 'pending', 'confirmed', 'completed', 'cancelled'] as $status)
                        <button type="button" wire:click="$set('statusFilter', '{{ $status }}')" @click="open = false" class="hip-diag-item w-full">{{ $status === 'all' ? 'All Status' : ucfirst($status) }}</button>
                    @endforeach
                </div>
            </div>

            <input type="date" wire:model.live="dateFilter" class="px-4 py-2 rounded-full border text-sm">
            <button type="button" wire:click="clearFilters" class="px-5 py-2 rounded-full text-sm text-white" style="background:var(--button-color);">Reset</button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-x-auto">
        <table class="w-full text-left">
            <thead style="background:#EBF5FB;">
                <tr>
                    <th class="px-4 py-3 text-xs font-bold uppercase" style="color:var(--button-color);">Apt ID</th>
                    <th class="px-4 py-3 text-xs font-bold uppercase" style="color:var(--button-color);">Patient</th>
                    <th class="px-4 py-3 text-xs font-bold uppercase" style="color:var(--button-color);">Hospital</th>
                    <th class="px-4 py-3 text-xs font-bold uppercase" style="color:var(--button-color);">Centre</th>
                    <th class="px-4 py-3 text-xs font-bold uppercase" style="color:var(--button-color);">Test Type</th>
                    {{-- <th class="px-4 py-3 text-xs font-bold uppercase" style="color:var(--button-color);">Date</th> --}}
                    <th class="px-4 py-3 text-xs font-bold uppercase" style="color:var(--button-color);">Payment</th>
                    <th class="px-4 py-3 text-xs font-bold uppercase" style="color:var(--button-color);">Status</th>
                    <th class="px-4 py-3 text-xs font-bold uppercase" style="color:var(--button-color);">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($bookings as $booking)
                    @php
                        $hospitalNames = $hospitalsByDiagnostic->get($booking->diagnostic_center_id, collect())->pluck('name')->join(', ');
                        $testItems = collect($booking->test_items ?? [])->map(fn ($id) => $labTests->get((int) $id))->filter();
                    @endphp
                    <tr class="hover:bg-slate-50" wire:key="tech-booking-{{ $booking->id }}">
                        <td class="px-4 py-3 text-sm">APT-{{ str_pad($booking->id, 4, '0', STR_PAD_LEFT) }}</td>
                        <td class="px-4 py-3 text-sm">
                            <div>{{ $booking->name }}</div>
                            <div class="text-xs text-slate-500">+91 {{ $booking->mobile_number }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm">{{ $booking->branch?->name ?: ($hospitalNames ?: '-') }}</td>
                        <td class="px-4 py-3 text-sm">{{ $booking->diagnosticCenter?->name ?: '-' }}</td>
                        <td class="px-4 py-3 text-sm">{{ ucfirst($booking->test_type ?? '-') }}</td>
                        {{-- <td class="px-4 py-3 text-sm">{{ $booking->booking_date?->format('d M Y') ?: '-' }}</td> --}}
                        <td class="px-4 py-3"><x-admin.booking-payment-cell :booking="$booking" /></td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium
                                {{ $booking->status === 'confirmed' ? 'bg-green-100 text-green-700' : ($booking->status === 'cancelled' ? 'bg-red-100 text-red-700' : ($booking->status === 'completed' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700')) }}">
                                {{ ucfirst($booking->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="action-menu-wrapper">
                                <button class="action-btn" onclick="toggleActionMenu(event,'menu-{{ $booking->id }}')">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <div id="menu-{{ $booking->id }}" class="action-menu hidden">
                                    <ul class="p-2 text-sm">
                                        <li>
                                            <a href="{{ route('technician.diagnostic-bookings.appointment-details', $booking->id) }}" onclick="closeAllActionMenus()" class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                <i class="fa-regular fa-eye w-4 mr-2"></i> View
                                            </a>
                                        </li>
                                        <li>
                                            <button wire:click="openUpdateStatusModal({{ $booking->id }})" class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                <i class="fas fa-toggle-on mr-2"></i> Update Status
                                            </button>
                                        </li>
                                        <li>
                                            <a href="{{ route('technician.upload-report.create', $booking->id) }}" onclick="closeAllActionMenus()" class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                <i class="fa-regular fa-file-lines w-4 mr-2"></i> Upload Report
                                            </a>
                                        </li>
                                        <li>
                                            <button wire:click="openDeleteBookingModal({{ $booking->id }})" class="inline-flex items-center w-full p-2 text-red-600 rounded">
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
                        <td colspan="9" class="px-6 py-10 text-center text-gray-500">No diagnostic bookings found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $bookings->links() }}</div>
    </div>

    <flux:modal name="delete-booking" class="p-0" wire:close="closeDeleteBookingModal" id="delete-org">
        <div class="p-6">
            <h2 class="text-lg font-semibold mb-2">Delete Booking</h2>
            <p class="text-sm text-gray-500 mb-4">Are you sure you want to delete this booking?</p>
            <div class="flex justify-end gap-3">
                <button wire:click="closeDeleteBookingModal" class="bg-gray-500 text-white px-4 py-2 rounded-lg text-sm">Cancel</button>
                <button wire:click="deleteBooking" class="bg-red-500 text-white px-4 py-2 rounded-lg text-sm">Delete</button>
            </div>
        </div>
    </flux:modal>
</div>
