<div class="space-y-6" style="padding: 4px 0;">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Patients</h1>
        <p class="text-sm text-slate-500 mt-1">Patients from doctor, second opinion and diagnostic bookings</p>
    </div>

    @include('livewire.receptionist-admin.partials.booking-filters')

    <div class="bg-white rounded-xl shadow-sm overflow-x-auto border border-slate-100">
        <table class="w-full text-left text-sm">
            <thead style="background:#EBF5FB;">
                <tr>
                    <th class="px-4 py-3 text-xs font-bold uppercase" style="color:var(--button-color);">Patient</th>
                    <th class="px-4 py-3 text-xs font-bold uppercase" style="color:var(--button-color);">Member ID</th>
                    <th class="px-4 py-3 text-xs font-bold uppercase" style="color:var(--button-color);">Phone</th>
                    <th class="px-4 py-3 text-xs font-bold uppercase" style="color:var(--button-color);">Hospital</th>
                    <th class="px-4 py-3 text-xs font-bold uppercase" style="color:var(--button-color);">Booking Types</th>
                    <th class="px-4 py-3 text-xs font-bold uppercase" style="color:var(--button-color);">Bookings</th>
                    <th class="px-4 py-3 text-xs font-bold uppercase" style="color:var(--button-color);">Last Booking</th>
                    <th class="px-4 py-3 text-xs font-bold uppercase" style="color:var(--button-color);">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($patients as $patient)
                    <tr wire:key="rec-patient-{{ $patient['patient_key'] }}" class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                                    style="background:var(--button-color);">
                                    {{ $patient['initials'] }}
                                </div>
                                <span class="font-medium text-slate-800">{{ $patient['name'] }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ $patient['member_hip_id'] }}</td>
                        <td class="px-4 py-3">{{ $patient['mobile'] }}</td>
                        <td class="px-4 py-3">{{ $patient['hospital_name'] }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                @foreach($patient['booking_types'] as $type)
                                    <span class="px-2 py-0.5 rounded-full text-xs bg-sky-50 text-sky-700">{{ $type }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ $patient['bookings_count'] }}</td>
                        <td class="px-4 py-3">{{ $patient['last_booking_date'] }}</td>
                        <td class="px-4 py-3">{{ $patient['last_status'] }}</td>
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
