<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Patients</h1>
        <p class="text-sm text-slate-500 mt-1">Patients who booked diagnostic tests at your centers</p>
    </div>

    <div class="flex flex-wrap gap-3">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search patient name, phone, member ID..."
            class="flex-1 min-w-[240px] text-sm rounded-xl border border-slate-200 px-4 py-3">

        <div class="relative" x-data="{ open: false }">
            <button type="button" @click="open = !open" class="inline-flex items-center gap-2 px-5 py-2 rounded-full text-sm font-medium text-white" style="background:#1A9FD4;">
                {{ $hospitalFilter === 'all' ? 'All Hospitals' : ($hospitals->firstWhere('id', $hospitalFilter)?->name ?? 'Hospital') }}
            </button>
            <div x-show="open" x-cloak @click.away="open = false" class="absolute z-20 mt-2 w-56 rounded-xl border bg-white shadow-lg overflow-hidden">
                <button type="button" wire:click="$set('hospitalFilter', 'all')" @click="open = false" class="block w-full text-left px-4 py-2 text-sm hover:bg-slate-50">All Hospitals</button>
                @foreach ($hospitals as $hospital)
                    <button type="button" wire:click="$set('hospitalFilter', '{{ $hospital->id }}')" @click="open = false" class="block w-full text-left px-4 py-2 text-sm hover:bg-slate-50">{{ $hospital->name }}</button>
                @endforeach
            </div>
        </div>

        <button type="button" wire:click="clearFilters" class="px-5 py-2 rounded-full text-sm text-white" style="background:#64748b;">Reset</button>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-x-auto">
        <table class="w-full text-left">
            <thead style="background:#EBF5FB;">
                <tr>
                    <th class="px-4 py-3 text-xs font-bold uppercase" style="color:#1A9FD4;">Patient</th>
                    <th class="px-4 py-3 text-xs font-bold uppercase" style="color:#1A9FD4;">Member ID</th>
                    <th class="px-4 py-3 text-xs font-bold uppercase" style="color:#1A9FD4;">Phone</th>
                    <th class="px-4 py-3 text-xs font-bold uppercase" style="color:#1A9FD4;">Hospital</th>
                    <th class="px-4 py-3 text-xs font-bold uppercase" style="color:#1A9FD4;">Bookings</th>
                    <th class="px-4 py-3 text-xs font-bold uppercase" style="color:#1A9FD4;">Last Booking</th>
                    <th class="px-4 py-3 text-xs font-bold uppercase" style="color:#1A9FD4;">Status</th>
                    <th class="px-4 py-3 text-xs font-bold uppercase" style="color:#1A9FD4;">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($patients as $patient)
                    <tr class="hover:bg-slate-50" wire:key="patient-{{ $patient['patient_key'] }}">
                        <td class="px-4 py-3 text-sm font-medium">{{ trim($patient['patient_name']) ?: '-' }}</td>
                        <td class="px-4 py-3 text-sm">{{ $patient['member_hip_id'] }}</td>
                        <td class="px-4 py-3 text-sm">{{ $patient['mobile'] }}</td>
                        <td class="px-4 py-3 text-sm">{{ $patient['hospital_name'] }}</td>
                        <td class="px-4 py-3 text-sm">{{ $patient['bookings_count'] }}</td>
                        <td class="px-4 py-3 text-sm">{{ $patient['last_booking_date'] }}</td>
                        <td class="px-4 py-3 text-sm">{{ $patient['last_status'] }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('technician.diagnostic-bookings.appointment-details', $patient['last_booking_id']) }}"
                               class="text-sm text-blue-600 font-medium">View Booking</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-10 text-center text-gray-500">No patients found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $patients->links() }}</div>
    </div>
</div>
