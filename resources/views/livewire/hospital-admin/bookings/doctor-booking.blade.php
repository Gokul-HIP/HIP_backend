{{-- livewire/admin/bookings/doctor-bookings.blade.php --}}
<div class="space-y-6">

    <style>
        .hip-doc-bk-menu {
            position: fixed;
            z-index: 9999;
            width: 13rem;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
            overflow: hidden;
        }
        .hip-doc-bk-item {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.65rem 1rem;
            font-size: 0.875rem;
            color: #475569;
            text-decoration: none;
            background: transparent;
            border: none; border-bottom: 1px solid #f8fafc;
            width: 100%; text-align: left; cursor: pointer;
            transition: background .1s;
        }
        .hip-doc-bk-item:last-child { border-bottom: none; }
        .hip-doc-bk-item:hover { background: #f0f9ff; color: var(--button-color); }
        .hip-doc-bk-item:hover svg { color: var(--button-color); }
        .hip-doc-bk-item.danger { color: #ef4444; }
        .hip-doc-bk-item.danger:hover { background: #fff1f2; color: #ef4444; }
        .hip-doc-bk-divider { height: 1px; background: #f1f5f9; margin: 2px 0; }
        .hip-doc-bk-btn { background: transparent; border: none; cursor: pointer; }
    </style>

    {{-- ── Page Header ── --}}
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Doctor Bookings – Appointment Form Submissions</h1>
        <p class="text-sm text-slate-500 mt-1">Manage all appointment requests and service bookings</p>
    </div>

    {{-- ── Stat Cards ── --}}
    <div style="display:grid; grid-template-columns:repeat(5,1fr); gap:1rem;">

        <div class="bg-white rounded-xl border border-slate-100 shadow-sm flex items-center gap-3" style="padding:1.25rem 1rem;">
            <div class="flex-shrink-0 flex items-center justify-center rounded-lg" style="background:#f0f9ff; width:48px; height:48px;">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" style="color:var(--button-color);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="font-medium text-slate-500 uppercase leading-tight" style="font-size:10px; letter-spacing:.04em;">Total Doctor Bookings</p>
                <p class="text-3xl font-bold text-slate-800 leading-none mt-1">{{ $totalBookings }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-100 shadow-sm flex items-center gap-3" style="padding:1.25rem 1rem;">
            <div class="flex-shrink-0 flex items-center justify-center rounded-lg" style="background:#f0f9ff; width:48px; height:48px;">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" style="color:var(--button-color);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="font-medium text-slate-500 uppercase leading-tight" style="font-size:10px; letter-spacing:.04em;">Total Pending</p>
                <p class="text-3xl font-bold text-slate-800 leading-none mt-1">{{ $pendingBookings }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-100 shadow-sm flex items-center gap-3" style="padding:1.25rem 1rem;">
            <div class="flex-shrink-0 flex items-center justify-center rounded-lg" style="background:#f0f9ff; width:48px; height:48px;">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" style="color:var(--button-color);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="font-medium text-slate-500 uppercase leading-tight" style="font-size:10px; letter-spacing:.04em;">Total Cancellations</p>
                <p class="text-3xl font-bold text-slate-800 leading-none mt-1">{{ $cancelledBookings }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-100 shadow-sm flex items-center gap-3" style="padding:1.25rem 1rem;">
            <div class="flex-shrink-0 flex items-center justify-center rounded-lg" style="background:#f0f9ff; width:48px; height:48px;">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" style="color:var(--button-color);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="font-medium text-slate-500 uppercase leading-tight" style="font-size:10px; letter-spacing:.04em;">Total Completed</p>
                <p class="text-3xl font-bold text-slate-800 leading-none mt-1">{{ $completedBookings }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-100 shadow-sm flex items-center gap-3" style="padding:1.25rem 1rem;">
            <div class="flex-shrink-0 flex items-center justify-center rounded-lg" style="background:#f0f9ff; width:48px; height:48px;">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" style="color:var(--button-color);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="font-medium text-slate-500 uppercase leading-tight" style="font-size:10px; letter-spacing:.04em;">Upcoming Bookings (7d)</p>
                <p class="text-3xl font-bold text-slate-800 leading-none mt-1">{{ $upcomingBookings }}</p>
            </div>
        </div>

    </div>

    {{-- ── Search + Filters ── --}}
    <div class="space-y-3">

        {{-- Search --}}
        <div class="relative flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 pointer-events-none flex-shrink-0" style="position:absolute; left:1rem; color:#94a3b8;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35"/>
            </svg>
            <input type="text"
                placeholder="Search Member ID, Phone Number etc.."
                wire:model.live.debounce.300ms="search"
                class="w-full text-sm rounded-xl outline-none transition-all"
                style="background:#fff; border:none; box-shadow:0 1px 4px rgba(0,0,0,0.06); padding:0.875rem 1rem 0.875rem 2.75rem; line-height:1.5;"
                onfocus="this.style.boxShadow='0 0 0 2px rgba(26,159,212,0.2)';"
                onblur="this.style.boxShadow='0 1px 4px rgba(0,0,0,0.06)';">
        </div>

        {{-- Filter Pills --}}
        <div class="flex gap-3 flex-wrap">

            {{-- All Doctors --}}
            <div class="relative" x-data="{ open: false }">
                <button type="button" @click="open = !open"
                    class="inline-flex items-center gap-2 px-5 py-2 rounded-full text-sm font-medium text-white transition-all hover:opacity-90" style="background:var(--button-color);">
                    {{ $doctorFilter === 'all' ? 'All Doctors' : ($availableDoctors->firstWhere('id', (int) $doctorFilter)?->name ?? 'All Doctors') }}
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/></svg>
                </button>
                <div x-show="open" x-cloak @click.away="open = false" x-transition
                    class="absolute z-20 mt-2 w-56 rounded-xl border border-slate-200 bg-white shadow-lg overflow-hidden">
                    <button type="button" wire:click="$set('doctorFilter','all')" @click="open=false" class="hip-doc-bk-item">All Doctors</button>
                    @foreach ($availableDoctors as $doctor)
                        <button type="button" wire:click="$set('doctorFilter','{{ $doctor->id }}')" @click="open=false" class="hip-doc-bk-item">{{ $doctor->name }}</button>
                    @endforeach
                </div>
            </div>

            {{-- All Status --}}
            <div class="relative" x-data="{ open: false }">
                <button type="button" @click="open = !open"
                    class="inline-flex items-center gap-2 px-5 py-2 rounded-full text-sm font-medium text-white transition-all hover:opacity-90" style="background:var(--button-color);">
                    {{ $statusFilter === 'all' ? 'All Status' : ucfirst($statusFilter) }}
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/></svg>
                </button>
                <div x-show="open" x-cloak @click.away="open = false" x-transition
                    class="absolute z-20 mt-2 w-48 rounded-xl border border-slate-200 bg-white shadow-lg overflow-hidden">
                    <button type="button" wire:click="$set('statusFilter','all')" @click="open=false" class="hip-doc-bk-item">All Status</button>
                    <button type="button" wire:click="$set('statusFilter','pending')" @click="open=false" class="hip-doc-bk-item">Pending</button>
                    <button type="button" wire:click="$set('statusFilter','confirmed')" @click="open=false" class="hip-doc-bk-item">Confirmed</button>
                    <button type="button" wire:click="$set('statusFilter','completed')" @click="open=false" class="hip-doc-bk-item">Completed</button>
                    <button type="button" wire:click="$set('statusFilter','cancelled')" @click="open=false" class="hip-doc-bk-item">Cancelled</button>
                </div>
            </div>

            {{-- Calendar --}}
            <div class="relative" x-data>
                <input x-ref="dateInput" type="date" wire:model.live="dateFilter"
                    class="absolute opacity-0 pointer-events-none w-0 h-0" tabindex="-1" aria-hidden="true">
                <button type="button"
                    @click="if ($refs.dateInput.showPicker) { $refs.dateInput.showPicker(); } else { $refs.dateInput.focus(); $refs.dateInput.click(); }"
                    class="inline-flex items-center gap-2 px-5 py-2 rounded-full text-sm font-medium text-white transition-all hover:opacity-90"
                    style="background:var(--button-color);">
                    {{ $dateFilter ? \Carbon\Carbon::parse($dateFilter)->format('M d, Y') : 'Calendar' }}
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </button>
            </div>

            <button type="button" wire:click="clearFilters"
                class="inline-flex items-center gap-2 px-5 py-2 rounded-full text-sm font-medium text-white transition-all hover:opacity-90"
                style="background:#64748b;">
                Reset
            </button>

        </div>
    </div>

    {{-- ── Table ── --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead style="background:#EBF5FB;">
                    <tr>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider whitespace-nowrap" style="color:var(--button-color);">Apt ID</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider whitespace-nowrap" style="color:var(--button-color);">Name</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider whitespace-nowrap" style="color:var(--button-color);">Member</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider whitespace-nowrap" style="color:var(--button-color);">Contact Number</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider whitespace-nowrap" style="color:var(--button-color);">Hospital</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider whitespace-nowrap" style="color:var(--button-color);">Doctor</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider whitespace-nowrap" style="color:var(--button-color);">Requested Slot</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider whitespace-nowrap" style="color:var(--button-color);">Payment</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider whitespace-nowrap" style="color:var(--button-color);">Status</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider whitespace-nowrap" style="color:var(--button-color);">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">

                    @forelse ($bookingRows as $booking)
                    <tr class="hover:bg-slate-50 transition-colors" wire:key="doc-booking-{{ $booking['id'] }}">

                        <td class="px-6 py-4 font-medium text-slate-500 whitespace-nowrap">{{ $booking['apt_id'] }}</td>

                        <td class="px-6 py-4 font-semibold text-slate-800 whitespace-nowrap">{{ $booking['name'] }}</td>

                        <td class="px-6 py-4">
                            <p class="font-medium text-slate-800">{{ $booking['member_name'] }}</p>
                            <p class="text-xs text-slate-400">{{ $booking['member_hip_id'] }}</p>
                        </td>

                        <td class="px-6 py-4 text-slate-600 whitespace-nowrap">{{ $booking['mobile_number'] }}</td>

                        <td class="px-6 py-4">
                            <p class="font-medium text-slate-800 whitespace-nowrap">{{ $booking['hospital_name'] }}</p>
                            <p class="text-xs text-slate-400">{{ $booking['hospital_meta'] }}</p>
                        </td>

                        <td class="px-6 py-4">
                            <p class="font-medium text-slate-800 whitespace-nowrap">{{ $booking['doctor_name'] }}</p>
                            <p class="text-xs text-slate-400">{{ $booking['doctor_meta'] }}</p>
                        </td>

                        <td class="px-6 py-4">
                            <p class="font-medium text-slate-800 whitespace-nowrap">{{ $booking['booking_date'] }}</p>
                            <p class="text-xs text-slate-400">{{ $booking['booking_time'] }}</p>
                        </td>

                        <td class="px-6 py-4">
                            @php
                                $paymentLabel = $booking['payment_mode_label'];
                                $paymentStyle = match ($paymentLabel) {
                                    'Paid by online' => 'background:#DBEAFE; color:#2563EB;',
                                    'Pay by online' => 'background:#FEF3C7; color:#D97706;',
                                    default => 'background:#F1F5F9; color:#475569;',
                                };
                            @endphp
                            <span class="px-3 py-1 rounded-full text-xs font-semibold" style="{{ $paymentStyle }}">{{ $paymentLabel }}</span>
                        </td>

                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase" style="{{ $booking['status_style'] }}">{{ $booking['status_label'] }}</span>
                        </td>

                        <td class="px-6 py-4">
                            <div class="inline-block">
                                <button type="button"
                                    class="hip-doc-bk-btn w-8 h-8 inline-flex items-center justify-center rounded-lg text-slate-400 transition-colors"
                                    onclick="hipDocBkToggle(event,'docBkMenu{{ $booking['id'] }}')">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/>
                                    </svg>
                                </button>
                                <div id="docBkMenu{{ $booking['id'] }}" class="hip-doc-bk-menu" style="display:none;">
                                    {{-- View --}}
                                    <a href="{{ route('healthcare.doctor.booking.appointment-details', $booking['id']) }}" onclick="hipDocBkCloseAll()" class="hip-doc-bk-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" style="color:#1A9FD4;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        View
                                    </a>
                                    <div class="hip-doc-bk-divider"></div>
                                    <button type="button" wire:click="openRescheduleModal({{ $booking['id'] }})" onclick="hipDocBkCloseAll()" class="hip-doc-bk-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" style="color:#1A9FD4;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        Reschedule
                                    </button>
                                    <div class="hip-doc-bk-divider"></div>
                                    <button type="button" wire:click="openDeleteBookingModal({{ $booking['id'] }})" onclick="hipDocBkCloseAll()" class="hip-doc-bk-item danger">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" style="color:#ef4444;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        Delete
                                    </button>
                                    <div class="hip-doc-bk-divider"></div>
                                    {{-- Update Status --}}
                                    <button type="button" wire:click="openUpdateStatusModal({{ $booking['id'] }})" onclick="hipDocBkCloseAll()" class="hip-doc-bk-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" style="color:#1A9FD4;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                        </svg>
                                        Update Status
                                    </button>
                                </div>
                            </div>
                        </td>

                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-6 py-12 text-center">
                            <p class="text-sm font-medium text-slate-800">No doctor bookings found.</p>
                            <p class="text-xs text-slate-500 mt-1">Try adjusting the search or filters.</p>
                        </td>
                    </tr>
                    @endforelse

                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($bookingRows instanceof \Illuminate\Pagination\LengthAwarePaginator && $bookingRows->hasPages())
        <div class="px-6 py-4 flex items-center justify-between" style="border-top:1px solid #f1f5f9;">
            <p class="text-xs text-slate-500">
                Showing {{ $bookingRows->firstItem() }}–{{ $bookingRows->lastItem() }} of {{ $bookingRows->total() }} entries
            </p>
            <div>{{ $bookingRows->links() }}</div>
        </div>
        @endif

    </div>

    <flux:modal name="delete-booking" class="p-0" wire:close="closeDeleteBookingModal" id="delete-org">
        <div x-data @click.outside="$wire.closeDeleteBookingModal()">
            <div>
                <flux:modal.close class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 cursor-pointer" wire:click="closeDeleteBookingModal" />
                <h2 class="text-lg font-semibold text-gray-900 mb-2">Delete Booking</h2>
                <p class="text-sm text-gray-500 mb-4 leading-relaxed">Are you sure you want to delete this booking?</p>
                <div class="flex flex-col items-end gap-3"><div class="flex justify-end gap-3 w-full"><button type="button" wire:click="closeDeleteBookingModal" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm font-medium shadow" style="background:#6b7280; color:#ffffff !important;">Cancel</button><button type="button" wire:click="deleteBooking" wire:loading.attr="disabled" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium shadow disabled:opacity-50"><span wire:loading.remove wire:target="deleteBooking" style="color:#ffffff !important;">Delete Booking</span><span wire:loading wire:target="deleteBooking" style="color:#ffffff !important;">Deleting...</span></button></div></div>
            </div>
        </div>
    </flux:modal>

    <script>
        function hipDocBkToggle(e, id) {
            e.stopPropagation();
            var menu   = document.getElementById(id);
            var btn    = e.currentTarget;
            var isOpen = menu.style.display === 'block';
            hipDocBkCloseAll();
            if (!isOpen) {
                var rect       = btn.getBoundingClientRect();
                var menuW      = 208;
                var menuH      = 176;
                var spaceBelow = window.innerHeight - rect.bottom;
                menu.style.left    = Math.max(8, rect.right - menuW) + 'px';
                menu.style.top     = (spaceBelow < menuH ? rect.top - menuH - 4 : rect.bottom + 4) + 'px';
                menu.style.display = 'block';
            }
        }
        function hipDocBkCloseAll() {
            document.querySelectorAll('.hip-doc-bk-menu').forEach(function(m) { m.style.display = 'none'; });
        }
        document.addEventListener('click', hipDocBkCloseAll);
        window.addEventListener('scroll', hipDocBkCloseAll, true);
        window.addEventListener('resize', hipDocBkCloseAll);
    </script>

</div>
