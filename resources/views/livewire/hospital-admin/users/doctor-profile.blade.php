{{-- livewire/admin/doctor/doctor-view.blade.php --}}
<div class="space-y-6">

    <style>
        .hip-doc-profile-menu {
            position: fixed;
            z-index: 9999;
            width: 13rem;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
            overflow: hidden;
        }
        .hip-doc-profile-menu-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            width: 100%;
            padding: 0.65rem 1rem;
            border: 0;
            border-bottom: 1px solid #f8fafc;
            background: transparent;
            color: #475569;
            font-size: 0.875rem;
            text-align: left;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.12s ease;
        }
        .hip-doc-profile-menu-item:last-child { border-bottom: none; }
        .hip-doc-profile-menu-item:hover { background: #f0f9ff; color: #0ea5e9; }
    </style>

    @php
        $doctorName = filled($doctor->name) ? $doctor->name : ($doctor->doctor_name ?: 'Doctor');
    @endphp

    {{-- ── Page Header ── --}}
    <div class="mb-2">
        <h1 class="text-3xl font-black tracking-tight text-slate-900">Dr {{ $doctorName }}</h1>
        <p class="mt-1 text-sm font-medium text-slate-500">Member ID: {{ $doctorIdLabel }}</p>
    </div>

    {{-- ── Tabs ── --}}
    <div class="flex flex-wrap gap-8 border-b border-slate-200">
        <button type="button" wire:click="setTab('profile')"
            class="pb-4 border-b-[3px] px-1 text-sm font-bold transition-colors {{ $tab === 'profile' ? 'border-sky-500 text-sky-600' : 'border-transparent text-slate-400 hover:text-slate-700' }}">
            Profile
        </button>
        {{-- <button type="button" wire:click="setTab('hospitals')"
            class="pb-4 border-b-[3px] px-1 text-sm font-bold transition-colors {{ $tab === 'hospitals' ? 'border-sky-500 text-sky-600' : 'border-transparent text-slate-400 hover:text-slate-700' }}">
            Linked Hospitals
        </button> --}}
        <button type="button" wire:click="setTab('appointments')"
            class="pb-4 border-b-[3px] px-1 text-sm font-bold transition-colors {{ $tab === 'appointments' ? 'border-sky-500 text-sky-600' : 'border-transparent text-slate-400 hover:text-slate-700' }}">
            Appointment Bookings
        </button>
        <button type="button" wire:click="setTab('schedules')"
            class="pb-4 border-b-[3px] px-1 text-sm font-bold transition-colors {{ $tab === 'schedules' ? 'border-sky-500 text-sky-600' : 'border-transparent text-slate-400 hover:text-slate-700' }}">
            Doctor Schedules
        </button>
    </div>

    {{-- ══════════════════════════════════════
         TAB: PROFILE
    ══════════════════════════════════════ --}}
    @if($tab === 'profile')
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 sm:px-8" style="background:#f8fafc;">
            <h2 class="text-[11px] font-black uppercase tracking-[0.14em] text-sky-600">Personal Information</h2>
        </div>
        <div class="grid grid-cols-1 gap-x-12 gap-y-10 px-6 py-8 sm:px-8 lg:grid-cols-2">
            <div class="space-y-6">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">Full Name</p>
                        <p class="mt-1.5 text-sm font-semibold text-slate-900">Dr {{ $doctorName }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">Date of Birth</p>
                        <p class="mt-1.5 text-sm font-semibold text-slate-900">{{ optional($doctor->dob)->format('d-m-Y') ?: '-' }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">Gender</p>
                        <p class="mt-1.5 text-sm font-semibold text-slate-900">{{ filled($doctor->gender) ? ucfirst((string) $doctor->gender) : '-' }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">Blood Group</p>
                        <p class="mt-1.5 text-sm font-semibold text-slate-900">{{ $doctor->blood_group ?: '-' }}</p>
                    </div>
                </div>
                <div>
                    <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">Nationality</p>
                    <p class="mt-1.5 text-sm font-semibold text-slate-900">{{ $doctor->nationality ?: '-' }}</p>
                </div>
            </div>
            <div class="space-y-6">
                <div class="border-b border-slate-100 pb-2">
                    <h3 class="text-[11px] font-black uppercase tracking-[0.14em] text-sky-600">Professional Details</h3>
                </div>
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">Qualification</p>
                        <p class="mt-1.5 text-sm font-semibold text-slate-900">{{ $doctor->qualification_names ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">Specialization</p>
                        <p class="mt-1.5 text-sm font-semibold text-slate-900">{{ $doctor->speciality_names ?: '-' }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">Experience</p>
                        <p class="mt-1.5 text-sm font-semibold text-slate-900">{{ $doctor->working_since ? $doctor->working_since . ' Years' : '-' }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">Registration Number</p>
                        <p class="mt-1.5 text-sm font-semibold text-slate-900">{{ $doctor->registration_number ?: '-' }}</p>
                    </div>
                </div>
                <div>
                    <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">Bio</p>
                    <p class="mt-1.5 text-sm font-semibold leading-7 text-slate-700">{{ $doctor->about_doctor ?: 'No doctor bio available.' }}</p>
                </div>
            </div>
            <div class="lg:col-span-2">
                <div class="mb-5 border-b border-slate-100 pb-2">
                    <h3 class="text-[11px] font-black uppercase tracking-[0.14em] text-sky-600">Contact Information</h3>
                </div>
                <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">Mobile Number</p>
                        <p class="mt-1.5 text-sm font-semibold text-slate-900">{{ $doctor->mobile_number ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">Email Address</p>
                        <p class="mt-1.5 break-all text-sm font-semibold text-slate-900">{{ $doctor->email ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">Address</p>
                        <p class="mt-1.5 text-sm font-semibold text-slate-900">{{ $doctor->address ?: '-' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════
         TAB: LINKED HOSPITALS
    ══════════════════════════════════════ --}}
    {{-- @if($tab === 'hospitals')
    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        @forelse($linkedHospitals as $hospital)
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-black text-slate-900">{{ $hospital->name }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ $hospital->address ?: '-' }}</p>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-bold uppercase {{ strtolower((string) $hospital->status) === 'active' ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-600' }}">
                    {{ ucfirst((string) ($hospital->status ?? 'inactive')) }}
                </span>
            </div>
            <div class="mt-6 grid grid-cols-2 gap-4">
                <div>
                    <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">City</p>
                    <p class="mt-1.5 text-sm font-semibold text-slate-900">{{ $hospital->city ?: '-' }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">State</p>
                    <p class="mt-1.5 text-sm font-semibold text-slate-900">{{ $hospital->state ?: '-' }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">Admin Email</p>
                    <p class="mt-1.5 break-all text-sm font-semibold text-slate-900">{{ $hospital->admin_email ?: '-' }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">Admin Contact</p>
                    <p class="mt-1.5 text-sm font-semibold text-slate-900">{{ $hospital->admin_contact ?: '-' }}</p>
                </div>
            </div>
        </div>
        @empty
        <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-6 py-10 text-sm text-slate-500">
            No linked hospitals found for this doctor.
        </div>
        @endforelse
    </div>
    @endif --}}

    {{-- ══════════════════════════════════════
         TAB: APPOINTMENT BOOKINGS
    ══════════════════════════════════════ --}}
    @if($tab === 'appointments')
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-visible">
        <div class="border-b border-slate-200 px-6 py-5 sm:px-8">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div class="relative w-full">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35"/>
                    </svg>
                    <input type="text" wire:model.live.debounce.300ms="appointmentSearch"
                        placeholder="Search by member, hospital, booking ID..."
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-10 pr-4 text-sm font-medium text-slate-700 outline-none transition focus:border-sky-400 focus:bg-white">
                </div>
                <div class="flex flex-wrap items-end gap-3 xl:flex-nowrap">
                    <div>
                        <label class="mb-1 block text-[10px] font-black uppercase tracking-[0.14em] text-slate-400">Date</label>
                        <input type="date" wire:model.live="appointmentDate"
                            class="min-w-[150px] rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-sky-400">
                    </div>
                    <div>
                        <label class="mb-1 block text-[10px] font-black uppercase tracking-[0.14em] text-slate-400">Status</label>
                        <div x-data="{ open: false }" class="relative min-w-[140px]">
                            <button type="button" @click="open = !open"
                                class="flex w-full items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-sm outline-none transition hover:border-slate-300">
                                <span class="whitespace-nowrap">{{ $appointmentStatusOptions[$appointmentStatus] ?? 'All' }}</span>
                                <svg class="h-4 w-4 text-slate-400 transition ml-2" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="open" x-transition @click.away="open = false"
                                class="absolute left-0 top-full z-30 mt-2 min-w-[190px] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl">
                                @foreach($appointmentStatusOptions as $value => $label)
                                <button type="button" wire:click="$set('appointmentStatus','{{ $value }}')" @click="open=false"
                                    class="block w-full whitespace-nowrap px-4 py-3 text-left text-sm text-slate-700 transition hover:bg-slate-100 {{ $appointmentStatus === $value ? 'bg-slate-100 font-medium' : '' }}">
                                    {{ $label }}
                                </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-[10px] font-black uppercase tracking-[0.14em] text-slate-400">Hospital</label>
                        <div x-data="{ open: false }" class="relative min-w-[170px]">
                            <button type="button" @click="open = !open"
                                class="flex w-full items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-sm outline-none transition hover:border-slate-300">
                                <span class="truncate">{{ $appointmentHospital === 'all' ? 'All' : optional($appointmentHospitals->firstWhere('id', (int) $appointmentHospital))->name }}</span>
                                <svg class="ml-3 h-4 w-4 flex-shrink-0 text-slate-400 transition" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="open" x-transition @click.away="open = false"
                                class="absolute left-0 top-full z-30 mt-2 min-w-[220px] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl">
                                <button type="button" wire:click="$set('appointmentHospital','all')" @click="open=false"
                                    class="block w-full whitespace-nowrap px-4 py-3 text-left text-sm text-slate-700 transition hover:bg-slate-100 {{ $appointmentHospital === 'all' ? 'bg-slate-100 font-medium' : '' }}">All</button>
                                @foreach($appointmentHospitals as $hospital)
                                <button type="button" wire:click="$set('appointmentHospital','{{ $hospital->id }}')" @click="open=false"
                                    class="block w-full whitespace-nowrap px-4 py-3 text-left text-sm text-slate-700 transition hover:bg-slate-100 {{ (string) $appointmentHospital === (string) $hospital->id ? 'bg-slate-100 font-medium' : '' }}">
                                    {{ $hospital->name }}
                                </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <button type="button" wire:click="clearAppointmentFilters"
                        class="rounded-xl border border-slate-200 bg-slate-50 px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-100">
                        Reset
                    </button>
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full table-fixed text-left">
                <thead>
                    <tr style="background:#EBF5FB;">
                        <th class="px-6 py-4 text-[11px] font-black uppercase tracking-[0.14em] whitespace-nowrap" style="color:#1A9FD4;">Booking ID</th>
                        <th class="px-6 py-4 text-[11px] font-black uppercase tracking-[0.14em] whitespace-nowrap" style="color:#1A9FD4;">Member Name</th>
                        <th class="px-6 py-4 text-[11px] font-black uppercase tracking-[0.14em] whitespace-nowrap" style="color:#1A9FD4;">Hospital</th>
                        <th class="px-6 py-4 text-[11px] font-black uppercase tracking-[0.14em] whitespace-nowrap" style="color:#1A9FD4;">Appt. Date</th>
                        <th class="px-6 py-4 text-[11px] font-black uppercase tracking-[0.14em] whitespace-nowrap" style="color:#1A9FD4;">Time Slot</th>
                        <th class="px-6 py-4 text-[11px] font-black uppercase tracking-[0.14em] whitespace-nowrap" style="color:#1A9FD4;">Status</th>
                        <th class="px-6 py-4 text-right text-[11px] font-black uppercase tracking-[0.14em] whitespace-nowrap" style="color:#1A9FD4;">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($appointments as $appointment)
                    <tr class="transition-colors hover:bg-slate-50">
                        <td class="px-6 py-4 text-sm font-bold text-slate-800">{{ $appointment['booking_id'] }}</td>
                        <td class="px-6 py-4">
                            <p class="truncate text-sm font-semibold text-slate-800">{{ $appointment['member_name'] }}</p>
                            <p class="truncate text-xs text-slate-400">{{ $appointment['member_meta'] }}</p>
                        </td>
                        <td class="truncate px-6 py-4 text-sm text-slate-500">{{ $appointment['hospital_name'] }}</td>
                        <td class="px-6 py-4 text-sm text-slate-500">{{ $appointment['appointment_date'] }}</td>
                        <td class="truncate px-6 py-4 text-sm text-slate-500">{{ $appointment['time_slot'] }}</td>
                        <td class="px-6 py-4">
                            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $statusPillClasses[$appointment['status']] ?? 'bg-slate-100 text-slate-600' }}">
                                {{ $appointment['status_label'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="inline-block">
                                <button type="button"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                                    onclick="hipDoctorProfileMenuToggle(event,'doctorProfileAppointmentMenu{{ $appointment['id'] }}')">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                        <circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/>
                                    </svg>
                                </button>
                                <div id="doctorProfileAppointmentMenu{{ $appointment['id'] }}" class="hip-doc-profile-menu" style="display:none;">
                                    <a href="{{ route('healthcare.doctor.booking.appointment-details', ['id' => $appointment['id']]) }}"
                                        onclick="hipDoctorProfileMenuCloseAll()" class="hip-doc-profile-menu-item">
                                        <svg class="h-4 w-4 flex-shrink-0" style="color:#1A9FD4;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        View
                                    </a>
                                    <button type="button" wire:click="openAppointmentStatusModal({{ $appointment['id'] }})"
                                        onclick="hipDoctorProfileMenuCloseAll()" class="hip-doc-profile-menu-item">
                                        <svg class="h-4 w-4 flex-shrink-0" style="color:#1A9FD4;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622C17.176 19.29 21 14.591 21 9c0-1.042-.133-2.052-.382-3.016z"/>
                                        </svg>
                                        Update Status
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center text-sm font-medium text-slate-500">
                            No appointment bookings found for this doctor.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="flex flex-col gap-3 border-t border-slate-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-500">
                Showing <span class="font-bold text-slate-900">{{ $appointments->firstItem() ?? 0 }} – {{ $appointments->lastItem() ?? 0 }}</span>
                of {{ $appointments->total() }} results
            </p>
            <div>{{ $appointments->links() }}</div>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════
         TAB: DOCTOR SCHEDULES  ← FIXED
    ══════════════════════════════════════ --}}
    @if($tab === 'schedules')
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">

        {{-- ── Card Header: title left, button right ── --}}
        <div class="flex items-center justify-between gap-4 px-6 py-5 sm:px-8" style="border-bottom:1px solid #e2e8f0;">
            <div>
                <h2 class="text-lg font-black text-slate-900">Doctor Schedules Management</h2>
                <p class="mt-0.5 text-sm text-slate-500">Manage daily available time slots for this doctor.</p>
            </div>
            <button type="button" wire:click="openAddScheduleModal"
                class="inline-flex flex-shrink-0 items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:opacity-90 active:opacity-80"
                style="background:#0ea5e9;">
                <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Add New Schedule
            </button>
        </div>

        {{-- ── Schedules Table ── --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <colgroup>
                    <col style="width:200px;">
                    <col>{{-- middle column takes all remaining space --}}
                    <col style="width:110px;">
                </colgroup>
                <thead>
                    <tr style="background:#EBF5FB;">
                        <th class="px-6 py-4 text-[11px] font-black uppercase tracking-[0.12em] whitespace-nowrap" style="color:#1A9FD4;">
                            Date
                        </th>
                        <th class="px-6 py-4 text-[11px] font-black uppercase tracking-[0.12em] whitespace-nowrap" style="color:#1A9FD4;">
                            Time Slots &amp; Availability
                        </th>
                        <th class="px-6 py-4 text-right text-[11px] font-black uppercase tracking-[0.12em] whitespace-nowrap" style="color:#1A9FD4;">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($doctorSchedules as $schedule)
                    <tr class="transition-colors hover:bg-slate-50" wire:key="sched-{{ $schedule['id'] }}">

                        {{-- Date --}}
                        <td class="px-6 py-5 align-top">
                            <p class="text-sm font-bold text-slate-800">
                                {{ \Carbon\Carbon::parse($schedule['date'])->format('d M Y') }}
                            </p>
                            <p class="mt-0.5 text-xs font-semibold uppercase tracking-wide text-slate-400">
                                {{ \Carbon\Carbon::parse($schedule['date'])->format('l') }}
                            </p>
                        </td>

                        {{-- Time Slots --}}
                        <td class="px-6 py-5 align-top">
                            <div class="flex flex-wrap gap-2">
                                @foreach($schedule['slots'] as $slot)
                                <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700">
                                    <svg class="h-3 w-3 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"/>
                                    </svg>
                                    {{ $slot['from'] }} – {{ $slot['to'] }}
                                </span>
                                @endforeach
                            </div>
                        </td>

                        {{-- Actions --}}
                        <td class="px-6 py-5 text-right align-top">
                            <div class="inline-flex items-center gap-1">
                                {{-- Copy --}}
                                <button type="button" wire:click="copySchedule({{ $schedule['id'] }})"
                                    class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" title="Copy">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </button>
                                {{-- Delete — FIX: corrected broken SVG path --}}
                                <button type="button" wire:click="deleteSchedule({{ $schedule['id'] }})"
                                    wire:confirm="Delete all slots for this date?"
                                    class="rounded-lg p-2 text-slate-400 transition hover:bg-red-50 hover:text-red-500" title="Delete">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </td>

                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="px-6 py-20 text-center">
                            {{-- FIX: vertically centred empty state with proper calendar icon --}}
                            <div class="flex flex-col items-center justify-center gap-3">
                                <div class="flex h-14 w-14 items-center justify-center rounded-2xl" style="background:#f1f5f9;">
                                    <svg class="h-7 w-7 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <p class="text-sm font-semibold text-slate-400">No schedules found for this month.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ── Footer: month navigation ── --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between px-6 py-4" style="border-top:1px solid #e2e8f0;">
            <p class="text-sm text-slate-500">
                Showing schedules for
                <span class="font-semibold text-slate-800">
                    {{ \Carbon\Carbon::create()->month($scheduleMonth)->format('F') }} {{ $scheduleYear }}
                </span>
            </p>
            <div class="flex items-center gap-2">
                {{-- Previous: outlined --}}
                <button type="button" wire:click="previousMonth"
                    class="rounded-xl border border-slate-200 bg-white px-5 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 active:bg-slate-100">
                    Previous
                </button>
                {{-- Next: solid sky --}}
                <button type="button" wire:click="nextMonth"
                    class="rounded-xl px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 active:opacity-80"
                    style="background:#0ea5e9;">
                    Next
                </button>
            </div>
        </div>

    </div>

    {{-- ── Add / Edit Schedule Modal ── --}}
    <flux:modal name="add-doctor-schedule" class="max-w-3xl">
        <div class="space-y-6">

            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">{{ $editingScheduleId ? 'Update Schedule' : 'Add New Schedule' }}</h3>
                    <p class="mt-1 text-sm text-slate-500">Add multiple available time slots for one day.</p>
                </div>
                <flux:modal.close wire:click="closeAddScheduleModal"
                    class="cursor-pointer rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"/>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">Select Date</label>
                    <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 focus-within:border-sky-400">
                        <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <input type="date" wire:model.live="scheduleDate"
                            class="w-full border-none bg-transparent p-0 text-sm font-medium text-slate-700 outline-none">
                    </div>
                    @error('scheduleDate')
                    <p class="mt-1 text-xs font-medium text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">Month (Reference)</label>
                    <div class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-600" style="background:#f8fafc;">
                        {{ \Carbon\Carbon::create($scheduleYear, $scheduleMonth, 1)->format('F Y') }}
                    </div>
                </div>
            </div>

            <div>
                <p class="mb-3 text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">Configure Time Slots</p>
                <div class="space-y-3">
                    @foreach($scheduleTimeSlots as $index => $slot)
                    <div class="flex items-center gap-3">
                        <div class="flex flex-1 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 focus-within:border-sky-400">
                            <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"/>
                            </svg>
                            <input type="time" wire:model.live="scheduleTimeSlots.{{ $index }}.from"
                                class="w-full border-none bg-transparent p-0 text-sm font-medium text-slate-700 outline-none">
                        </div>
                        <span class="shrink-0 text-xs font-bold uppercase tracking-widest text-slate-400">to</span>
                        <div class="flex flex-1 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 focus-within:border-sky-400">
                            <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"/>
                            </svg>
                            <input type="time" wire:model.live="scheduleTimeSlots.{{ $index }}.to"
                                class="w-full border-none bg-transparent p-0 text-sm font-medium text-slate-700 outline-none">
                        </div>
                        <button type="button" wire:click="removeTimeSlot({{ $index }})"
                            class="shrink-0 rounded-lg p-2 text-slate-300 transition hover:bg-red-50 hover:text-red-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    @endforeach
                </div>
                @error('scheduleTimeSlots')
                <p class="mt-2 text-xs font-medium text-red-500">{{ $message }}</p>
                @enderror
                @error('scheduleTimeSlots.*.from')
                <p class="mt-1 text-xs font-medium text-red-500">{{ $message }}</p>
                @enderror
                @error('scheduleTimeSlots.*.to')
                <p class="mt-1 text-xs font-medium text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <button type="button" wire:click="addTimeSlot"
                class="flex items-center gap-1.5 text-sm font-bold text-sky-500 transition hover:text-sky-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <circle cx="12" cy="12" r="10"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v8m-4-4h8"/>
                </svg>
                Add Another Time Block
            </button>

            <div class="flex justify-end gap-3">
                <button type="button" wire:click="closeAddScheduleModal"
                    class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-600 transition hover:bg-slate-50">
                    Cancel
                </button>
                <button type="button" wire:click="saveSchedule"
                    class="inline-flex items-center gap-2 rounded-xl px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:opacity-90"
                    style="background:#0ea5e9;">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    Save Schedule
                </button>
            </div>

        </div>
    </flux:modal>
    @endif

    {{-- ── JS: fixed-position action menu ── --}}
    <script>
        function hipDoctorProfileMenuToggle(event, id) {
            event.stopPropagation();
            var menu   = document.getElementById(id);
            var btn    = event.currentTarget;
            var isOpen = menu.style.display === 'block';
            hipDoctorProfileMenuCloseAll();
            if (!isOpen) {
                var rect       = btn.getBoundingClientRect();
                var menuW      = 208;
                var menuH      = 116;
                var spaceBelow = window.innerHeight - rect.bottom;
                menu.style.left    = Math.max(8, rect.right - menuW) + 'px';
                menu.style.top     = (spaceBelow < menuH ? rect.top - menuH - 4 : rect.bottom + 4) + 'px';
                menu.style.display = 'block';
            }
        }
        function hipDoctorProfileMenuCloseAll() {
            document.querySelectorAll('.hip-doc-profile-menu').forEach(function(m) {
                m.style.display = 'none';
            });
        }
        document.addEventListener('click', hipDoctorProfileMenuCloseAll);
        window.addEventListener('scroll', hipDoctorProfileMenuCloseAll, true);
        window.addEventListener('resize', hipDoctorProfileMenuCloseAll);
    </script>

</div>