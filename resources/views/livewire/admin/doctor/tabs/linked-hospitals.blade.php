<div class="space-y-4">

    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">Linked Hospitals Overview</h2>

        {{-- <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded flex items-center">
            + Add Hospital
        </button> --}}
        <span class="text-sm text-gray-500">Total Linked Hospitals: {{ $linkedHospitals->count() }}</span>
        {{-- <span class="text-sm text-gray-500">Total Active Hospitals: {{ $linkedHospitals->where('status',
            'active')->count() }}</span>
        <span class="text-sm text-gray-500">Total Inactive Hospitals: {{ $linkedHospitals->where('status',
            'inactive')->count() }}</span> --}}

    </div>

    <div class="overflow-x-auto rounded-lg border bg-white shadow-md">

        <table class="w-full text-sm border-collapse ">
            <thead class="bg-gray-100 border-b">
                <tr class="text-left text-gray-700 font-semibold">
                    <th class="px-4 py-3 w-[160px]">Hospital Name</th>
                    <th class="px-4 py-3 w-[240px]">Location</th>
                    <th class="px-4 py-3 w-[120px]">Type</th>
                    <th class="px-4 py-3 w-[140px]">Status</th>
                    <th class="px-4 py-3 w-[120px] text-center">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">

                @forelse ($linkedHospitals as $hospital)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">{{ $hospital->hospital_name }}</td>
                                <td class="px-4 py-3">{{ $hospital->hospital_address ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $hospital->hospital_type ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-3 py-1 rounded-full text-xs font-medium 
                                                        {{ $hospital->status === 'active'
                    ? 'bg-green-100 text-green-700'
                    : 'bg-gray-200 text-gray-600' }}">
                                        {{ ucfirst($hospital->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button class="text-gray-500 hover:text-black" wire:click='unlink({{ $hospital->id }})'>
                                        UnLink
                                    </button>
                                </td>
                            </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-3 text-center">No hospitals linked</td>
                    </tr>
                @endforelse

            </tbody>
        </table>
    </div>

</div>