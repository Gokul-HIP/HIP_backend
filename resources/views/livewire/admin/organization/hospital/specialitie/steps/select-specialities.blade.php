<div class="bg-white rounded-lg p-6 shadow-sm" wire:key="modal-content-{{ $modalKey }}">
    
    <h3 class="text-lg font-semibold mb-4">Select specialities to add to the system</h3>

    <!-- SEARCH BAR -->
    <div class="relative mb-6">
        <i class="fas fa-search absolute left-3 top-3 text-black"></i>
        <input 
            type="text" 
            wire:model.live.debounce.300ms="specialitySearch"
            placeholder="Search speciality name..." 
            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-300 focus:border-blue-500 outline-none"
        />
    </div>

    <!-- SELECT ALL CHECKBOX -->
    <div class="mb-4 pb-4 border-b border-gray-200">
        <label class="flex items-center cursor-pointer hover:bg-gray-50 p-3 rounded-lg transition-colors">
            <input
                type="checkbox"
                wire:key="select-all-{{ $modalKey }}-{{ $specialities->currentPage() }}"
                wire:click="toggleSelectAllSpecialities"
                @checked($this->isAllSpecialitiesSelectedOnPage)
                class="w-5 h-5 text-blue-600 border-gray-300 rounded"
            />
            <span class="ml-3 text-sm font-semibold text-gray-700">Select all available specialities</span>
        </label>
    </div>

    <!-- SPECIALITIES LIST -->
    <div class="space-y-2 max-h-96 overflow-y-auto">
        @forelse($specialities as $speciality)
            @php
                $isAlreadyAdded = $this->isSpecialityAlreadyAdded($speciality->id);
            @endphp
            
            <label class="flex items-start p-4 border rounded-lg transition-all duration-200
                {{ $isAlreadyAdded ? 'bg-red-50 border-red-300 opacity-60 cursor-not-allowed' : 'border-gray-200 hover:bg-blue-50 hover:border-blue-300 cursor-pointer' }}"
             wire:key="speciality-{{ $modalKey }}-{{ $speciality->id }}">
                <input
                    type="checkbox"
                    wire:key="checkbox-{{ $modalKey }}-{{ $speciality->id }}"
                    @if(!$isAlreadyAdded)
                        wire:click="toggleSpeciality({{ $speciality->id }})"
                    @endif
                    @checked(in_array($speciality->id, $selectedSpecialities))
                    @disabled($isAlreadyAdded)
                    class="w-5 h-5 text-blue-600 border-gray-300 rounded mt-0.5 {{ $isAlreadyAdded ? 'cursor-not-allowed opacity-50' : '' }}"
                />
                <div class="ml-3 flex-1">
                    <div class="flex items-center gap-2">
                        <p class="font-medium {{ $isAlreadyAdded ? 'text-gray-500' : 'text-gray-900' }}">
                            {{ $speciality->name }}
                        </p>
                        @if($isAlreadyAdded)
                            <span class="px-2 py-0.5 text-xs font-semibold text-red-700 bg-red-100 rounded-full">
                                Already Added
                            </span>
                        @endif
                    </div>
                    <p class="text-sm {{ $isAlreadyAdded ? 'text-gray-400' : 'text-gray-600' }} mt-1">
                        {{ $speciality->description }}
                    </p>
                    <div class="flex items-center gap-4 mt-2 text-xs {{ $isAlreadyAdded ? 'text-gray-400' : 'text-gray-500' }}">
                        <span><i class="fas fa-hashtag mr-1 text-black"></i>{{ $speciality->code }}</span>
                        <span><i class="fas fa-toggle-on mr-1 text-black"></i>{{ ucfirst($speciality->status) }}</span>
                    </div>
                    @if($isAlreadyAdded)
                        <p class="text-xs text-red-600 mt-2">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            This speciality is already added to this hospital
                        </p>
                    @endif
                </div>
            </label>
        @empty
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-search text-4xl mb-3 text-gray-300"></i>
                <p class="text-lg font-medium">No specialities found</p>
                <p class="text-sm">Try adjusting your search</p>
            </div>
        @endforelse
    </div>

    <!-- PAGINATION -->
    @if($specialities->hasPages())
        <div class="mt-4 pt-4 border-t border-gray-200">
            {{ $specialities->links() }}
        </div>
    @endif

    <!-- SELECTED SPECIALITIES SUMMARY -->
    <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <h4 class="font-semibold text-gray-900 mb-2">
            <i class="fas fa-check-circle text-blue-600 mr-2"></i>
            Selected Specialities ({{ count($selectedSpecialities) }})
        </h4>
        @if(count($selectedSpecialities) > 0)
            <p class="text-sm text-gray-600">
                {{ count($selectedSpecialities) }} speciality(s) selected and ready to add
            </p>
        @else
            <p class="text-sm text-gray-500">No specialities selected</p>
        @endif
    </div>

    @error('selectedSpecialities')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror

</div>