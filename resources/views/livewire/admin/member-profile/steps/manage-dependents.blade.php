<div class="space-y-4">

    <h2 class="text-lg font-semibold">Manage Dependents</h2>

    <div class="overflow-x-auto rounded-lg border bg-white shadow-md">

        <table class="w-full text-sm border-collapse ">
            <thead class="bg-gray-100 border-b">
                <tr class="text-left text-gray-700 font-semibold">
                    <th class="px-4 py-3 w-[90px]">ID</th>
                    <th class="px-4 py-3 w-[160px]">Name</th>
                    <th class="px-4 py-3 w-[120px]">DOB</th>
                    <th class="px-4 py-3 w-[140px]">Mobile</th>
                    <th class="px-4 py-3 w-[240px]">Email</th>
                    <th class="px-4 py-3 w-[160px]">ABHA ID</th>
                    <th class="px-4 py-3 w-[100px]">Gender</th>
                    <th class="px-4 py-3 w-[140px]">Relationship</th>
                    <th class="px-4 py-3 w-[130px]">Profile</th>
                    <th class="px-4 py-3 w-[120px] text-center">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">
                @forelse($dependents as $dependent)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">HIP{{ str_pad((string) $dependent->id, 4, '0', STR_PAD_LEFT) }}</td>
                        <td class="px-4 py-3 font-medium">
                            {{ trim(($dependent->first_name ?? '') . ' ' . ($dependent->last_name ?? '')) ?: '-' }}
                        </td>
                        <td class="px-4 py-3">{{ $dependent->dob ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $dependent->mobile ?? '-' }}</td>
                        <td class="px-4 py-3 truncate" title="{{ $dependent->email ?? '-' }}">
                            {{ $dependent->email ?? '-' }}
                        </td>
                        <td class="px-4 py-3">-</td>
                        <td class="px-4 py-3">{{ $dependent->gender ?? '-' }}</td>
                        <td class="px-4 py-3">Dependent</td>
                        <td class="px-4 py-3">
                            <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                Updated
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-gray-400">-</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                            No dependents found for this member.
                        </td>
                    </tr>
                @endforelse

            </tbody>
        </table>
    </div>

</div>
