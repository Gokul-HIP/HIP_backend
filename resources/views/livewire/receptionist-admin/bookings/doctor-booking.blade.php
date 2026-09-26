<div class="space-y-6">
    <style>
        .rec-bk-menu { position: fixed; z-index: 9999; width: 13rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 0.75rem; box-shadow: 0 10px 30px rgba(0,0,0,0.12); overflow: hidden; }
        .rec-bk-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.65rem 1rem; font-size: 0.875rem; color: #475569; text-decoration: none; background: transparent; border: none; border-bottom: 1px solid #f8fafc; width: 100%; text-align: left; cursor: pointer; }
        .rec-bk-item:last-child { border-bottom: none; }
        .rec-bk-item:hover { background: #f0f9ff; color: var(--button-color); }
        .rec-bk-item.danger { color: #ef4444; }
        .rec-bk-item.danger:hover { background: #fff1f2; color: #ef4444; }
        .rec-bk-divider { height: 1px; background: #f1f5f9; margin: 2px 0; }
        .rec-bk-btn { background: transparent; border: none; cursor: pointer; }
    </style>

    <div>
        <h1 class="text-2xl font-bold text-slate-900">Doctor Bookings</h1>
        <p class="text-sm text-slate-500 mt-1">Appointment requests for your hospital</p>
    </div>

    <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:1rem;">
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
            <p class="text-xs text-slate-500 uppercase">Total</p>
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

    @include('livewire.receptionist-admin.partials.booking-filters')

    <div class="bg-white rounded-xl shadow-sm overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead style="background:#EBF5FB;">
                <tr>
                    <th class="px-4 py-3 text-xs font-bold uppercase" style="color:var(--button-color);">Apt ID</th>
                    <th class="px-4 py-3 text-xs font-bold uppercase" style="color:var(--button-color);">Patient</th>
                    <th class="px-4 py-3 text-xs font-bold uppercase" style="color:var(--button-color);">Member</th>
                    <th class="px-4 py-3 text-xs font-bold uppercase" style="color:var(--button-color);">Hospital</th>
                    <th class="px-4 py-3 text-xs font-bold uppercase" style="color:var(--button-color);">Doctor</th>
                    <th class="px-4 py-3 text-xs font-bold uppercase" style="color:var(--button-color);">Date</th>
                    <th class="px-4 py-3 text-xs font-bold uppercase" style="color:var(--button-color);">Payment</th>
                    <th class="px-4 py-3 text-xs font-bold uppercase" style="color:var(--button-color);">Status</th>
                    <th class="px-4 py-3 text-xs font-bold uppercase" style="color:var(--button-color);">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($bookings as $booking)
                    @php
                        $slots = collect($booking->required_time_slots ?? [])->filter()->values();
                        $timeLabel = $slots->count() ? $slots->first() : null;
                    @endphp
                    <tr class="hover:bg-slate-50" wire:key="rec-doc-{{ $booking->id }}">
                        <td class="px-4 py-3 font-medium" style="color:var(--button-color);">APT-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}</td>
                        <td class="px-4 py-3">
                            <div>{{ $booking->name ?: '—' }}</div>
                            <div class="text-xs text-slate-500">{{ $booking->mobile_number ? '+91 ' . $booking->mobile_number : '—' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div>{{ $booking->member?->name ?: '—' }}</div>
                            <div class="text-xs text-slate-500">{{ $booking->member?->hip_id ?: '—' }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $booking->hospital?->name ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $booking->doctor?->name ? 'Dr ' . $booking->doctor->name : '—' }}</td>
                        <td class="px-4 py-3">
                            <div>{{ $booking->booking_date?->format('d M Y') ?: '—' }}</div>
                            @if($timeLabel)<div class="text-xs text-slate-500">{{ $timeLabel }}</div>@endif
                        </td>
                        <td class="px-4 py-3"><x-admin.booking-payment-cell :booking="$booking" /></td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium
                                {{ $booking->status === 'confirmed' ? 'bg-green-100 text-green-700' : ($booking->status === 'cancelled' ? 'bg-red-100 text-red-700' : ($booking->status === 'completed' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700')) }}">
                                {{ ucfirst($booking->status ?? 'pending') }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="inline-block">
                                <button type="button" class="rec-bk-btn w-8 h-8 inline-flex items-center justify-center rounded-lg text-slate-400"
                                    onclick="recBkToggle(event,'recDocMenu{{ $booking->id }}')">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <div id="recDocMenu{{ $booking->id }}" class="rec-bk-menu" style="display:none;">
                                    <a href="{{ route('receptionist.doctor-bookings.appointment-details', $booking->id) }}" onclick="recBkCloseAll()" class="rec-bk-item">
                                        <i class="fa-regular fa-eye w-4"></i> View
                                    </a>
                                    <div class="rec-bk-divider"></div>
                                    <button type="button" wire:click="openRescheduleModal({{ $booking->id }})" onclick="recBkCloseAll()" class="rec-bk-item">
                                        <i class="fa-regular fa-calendar w-4"></i> Reschedule
                                    </button>
                                    <div class="rec-bk-divider"></div>
                                    <button type="button" wire:click="openDeleteBookingModal({{ $booking->id }})" onclick="recBkCloseAll()" class="rec-bk-item danger">
                                        <i class="fa-regular fa-trash-can w-4"></i> Delete
                                    </button>
                                    <div class="rec-bk-divider"></div>
                                    <button type="button" wire:click="openUpdateStatusModal({{ $booking->id }})" onclick="recBkCloseAll()" class="rec-bk-item">
                                        <i class="fas fa-toggle-on w-4"></i> Update Status
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-6 py-10 text-center text-gray-500">No doctor bookings found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $bookings->links() }}</div>
    </div>

    <flux:modal name="delete-booking" class="p-0" wire:close="closeDeleteBookingModal">
        <div class="p-6">
            <h2 class="text-lg font-semibold mb-2">Delete Booking</h2>
            <p class="text-sm text-gray-500 mb-4">Are you sure you want to delete this booking?</p>
            <div class="flex justify-end gap-3">
                <button wire:click="closeDeleteBookingModal" class="bg-gray-500 text-white px-4 py-2 rounded-lg text-sm">Cancel</button>
                <button wire:click="deleteBooking" class="bg-red-500 text-white px-4 py-2 rounded-lg text-sm">Delete</button>
            </div>
        </div>
    </flux:modal>

    <script>
        function recBkToggle(e, id) {
            e.stopPropagation();
            var menu = document.getElementById(id);
            var btn = e.currentTarget;
            var isOpen = menu.style.display === 'block';
            recBkCloseAll();
            if (!isOpen) {
                var rect = btn.getBoundingClientRect();
                menu.style.left = Math.max(8, rect.right - 208) + 'px';
                menu.style.top = (rect.bottom + 4) + 'px';
                menu.style.display = 'block';
            }
        }
        function recBkCloseAll() {
            document.querySelectorAll('.rec-bk-menu').forEach(function(m) { m.style.display = 'none'; });
        }
        document.addEventListener('click', recBkCloseAll);
    </script>
</div>
