<div class="bg-white rounded-lg p-6 shadow-sm">
    
    <h3 class="text-lg font-semibold mb-4">Review Selected Specialities</h3>

    <!-- ACTION BUTTONS -->
    <div class="flex items-center justify-between mb-6">
        <button 
            wire:click="backToAddMore" 
            type="button"
            class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-blue-600 transition-colors">
            <i class="fas fa-arrow-left"></i>
            Back to Add More
        </button>

        <button 
            wire:click="deleteSelected" 
            type="button"
            :disabled="{{ empty($toRemove) ? 'true' : 'false' }}"
            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ empty($toRemove) ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-red-500 text-white hover:bg-red-600' }}">
            <i class="fas fa-trash-alt"></i>
            Delete Selected
        </button>
    </div>

    <!-- SPECIALITIES TABLE -->
    <div class="border border-gray-200 rounded-lg overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider w-12">
                        <input 
                            type="checkbox"
                            wire:model.live="selectAllToRemove"
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded"
                        />
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Speciality Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Code</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($selectedSpecialitiesDetails as $speciality)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-4">
                            <input 
                                type="checkbox"
                                wire:model.live="toRemove"
                                value="{{ $speciality->id }}"
                                class="w-4 h-4 text-blue-600 border-gray-300 rounded"
                            />
                        </td>
                        <td class="px-4 py-4 text-sm font-medium text-gray-900">
                            {{ $speciality->name }}
                        </td>
                        <td class="px-4 py-4 text-sm text-gray-600">
                            {{ $speciality->code }}
                        </td>
                        <td class="px-4 py-4 text-sm text-gray-600">
                            <span class="px-2 py-1 rounded-full text-xs font-medium 
                                {{ $speciality->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                {{ ucfirst($speciality->status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-inbox text-3xl mb-2 text-gray-300"></i>
                            <p class="text-sm">No specialities selected</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- SUMMARY -->
    <div class="mt-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Total Specialities Selected</p>
                <p class="text-2xl font-bold text-gray-900">{{ count($selectedSpecialities) }}</p>
            </div>
            @if(!empty($toRemove))
                <div class="text-right">
                    <p class="text-sm text-gray-600">To be Removed</p>
                    <p class="text-2xl font-bold text-red-600">{{ count($toRemove) }}</p>
                </div>
            @endif
        </div>
    </div>

    <!-- WARNING MESSAGE -->
    {{-- <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
        <div class="flex items-start gap-3">
            <i class="fas fa-exclamation-triangle text-yellow-600 mt-0.5"></i>
            <div>
                <h5 class="font-semibold text-gray-900 mb-1">Important Note</h5>
                <p class="text-sm text-gray-700">
                    These specialities will be added with an "Inactive" status. You can activate them individually after adding.
                    Speciality codes will be automatically generated based on your hospital name.
                </p>
            </div>
        </div>
    </div> --}}

    @error('save')
        <p class="mt-4 text-sm text-red-600 flex items-center gap-2">
            <i class="fas fa-exclamation-circle"></i>
            {{ $message }}
        </p>
    @enderror

</div>

