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

                {{-- <td class="px-6 py-4 text-sm">{{ $dependent->id ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm">{{ $dependent->first_name ?? '-' }}{{ ' ' }}{{ $dependent->last_name ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm">  {{ $dependent->dob ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm">  {{ $dependent->mobile_num ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm">{{ $dependent->email ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm">  {{ $dependent->abha_id ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm">  {{ $dependent->gender ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm">  {{ $dependent->relationship ?? '-' }}</td>

                    <td class="px-6 py-4">
                        <span class="px-3 py-1 rounded-full text-xs font-medium 
                            {{ $dependent->profile_update === 1 
                                ? 'bg-green-100 text-green-700' 
                                : 'bg-red-100 text-red-700' }}">
                            {{ $dependent->profile_update === 1 ? 'Updated' : 'Not Updated' }}
                        </span>
                    </td> --}}

                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">HIP1</td>
                    <td class="px-4 py-3 font-medium">John Doe</td>
                    <td class="px-4 py-3">1990-01-01</td>
                    <td class="px-4 py-3">1234567890</td>

                    <td class="px-4 py-3 truncate" title="john.doe@example.com">
                        john.doe@example.com
                    </td>

                    <td class="px-4 py-3 truncate" title="123456789012">
                        123456789012
                    </td>

                    <td class="px-4 py-3">Male</td>
                    <td class="px-4 py-3">Parent</td>

                    <td class="px-4 py-3">
                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                            Updated
                        </span>
                    </td>

                    <td class="px-4 py-3 text-center">
                        <button
                            id="dropdownDefaultButton-1"
                            class="action-btn inline-flex items-center justify-center text-gray-700 bg-white border rounded-lg px-3 py-2"
                            type="button"
                            onclick="toggleActionMenu(event,'dropdown-1')">
                            <i class="fa-solid fa-ellipsis-vertical w-4"></i>
                        </button>

                        <!-- Dropdown menu -->
                        <div 
                            id="dropdown-1" 
                            class="action-menu z-10 hidden bg-white border rounded-lg shadow-lg w-50 absolute right-0 mt-6">
                            <ul class="p-2 text-sm text-gray-700 font-medium" aria-labelledby="dropdownDefaultButton-1">
                                <li>
                                    <a 
                                        href="{{ route('admin.organizations.show', 1) }}" 
                                        class="inline-flex items-center w-full p-2 hover:bg-gray-100 hover:text-gray-900 rounded">
                                        <i class="fa-regular fa-eye w-4 mr-2"></i>
                                        View
                                    </a>
                                </li>
                                <li>
                                    <button 
                                        type="button" 
                                        onclick="Livewire.dispatch('editOrg', { id: 1 })"
                                        class="inline-flex items-center w-full p-2 hover:bg-gray-100 hover:text-gray-900 rounded text-left">
                                        <i class="fa-regular fa-pen-to-square w-4 mr-2"></i> Edit
                                    </button>
                                </li>
                                {{-- <li>
                                    <button 
                                        type="button" 
                                        onclick="Livewire.dispatch('delete', { id: 1 })"
                                        class="inline-flex items-center w-full p-2 hover:bg-red-50 hover:text-red-600 rounded text-left text-red-600">
                                        <i class="fa-regular fa-trash-can w-4 mr-2"></i> Delete
                                    </button>
                                </li> --}}
                                <li>
                                    <button wire:click="manageDependents(1)" type="button" class="inline-flex items-center w-full p-2 hover:bg-gray-100 hover:text-gray-900 rounded">
                                        <i class="fa-regular fa-hospital w-4 mr-2"></i> Manage Dependents
                                    </button>
                                </li>
                                <li>
                                    <a 
                                        href="{{ route('admin.organizations.pharmacy.index', 1) }}" 
                                        class="inline-flex items-center w-full p-2 hover:bg-gray-100 hover:text-gray-900 rounded">
                                        <i class="fa-regular fa-calendar-days w-4 mr-2"></i> View Appointment Bookings
                                    </a>
                                </li>
                                <li>
                                    <a 
                                        href="{{ route('admin.organizations.diagnostic.index', 1) }}" 
                                        class="inline-flex items-center w-full p-2 hover:bg-gray-100 hover:text-gray-900 rounded">
                                        <i class="fa-solid fa-indian-rupee-sign w-4 mr-2"></i> View Transactions
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>

            </tbody>
        </table>
    </div>

</div>
