<div class="bg-white rounded-lg p-6 shadow-sm" wire:key="modal-content-{{ $modalKey }}">
    
    <h3 class="text-lg font-semibold mb-4">Select medicines to add to the pharmacy</h3>

    <!-- SEARCH BAR -->
    <div class="relative mb-6">
        <i class="fas fa-search absolute left-3 top-3 text-black"></i>
        <input 
            type="text" 
            wire:model.live.debounce.300ms="medicineSearch"
            placeholder="Search medicine name, code, category, brand..." 
            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-300 focus:border-blue-500 outline-none"
        />
    </div>

    <!-- SELECT ALL CHECKBOX -->
    <div class="mb-4 pb-4 border-b border-gray-200">
        <label class="flex items-center cursor-pointer hover:bg-gray-50 p-3 rounded-lg transition-colors">
            <input
                type="checkbox"
                wire:key="select-all-{{ $modalKey }}-{{ $medicines->currentPage() }}"
                wire:click="toggleSelectAllMedicines"
                @checked($this->isAllMedicinesSelectedOnPage)
                class="w-5 h-5 text-blue-600 border-gray-300 rounded"
            />
            <span class="ml-3 text-sm font-semibold text-gray-700">Select all available medicines</span>
        </label>
    </div>

    <!-- MEDICINES LIST -->
    <div class="space-y-2 max-h-96 overflow-y-auto">
        @forelse($medicines as $medicine)
            @php
                $isAlreadyAdded = $this->isMedicineAlreadyAdded($medicine->id);
            @endphp
            
            <label class="flex items-start p-4 border rounded-lg transition-all duration-200
                {{ $isAlreadyAdded ? 'bg-red-50 border-red-300 opacity-60 cursor-not-allowed' : 'border-gray-200 hover:bg-blue-50 hover:border-blue-300 cursor-pointer' }}"
             wire:key="medicine-{{ $modalKey }}-{{ $medicine->id }}">
                <input
                    type="checkbox"
                    wire:key="checkbox-{{ $modalKey }}-{{ $medicine->id }}"
                    @if(!$isAlreadyAdded)
                        wire:click="toggleMedicine({{ $medicine->id }})"
                    @endif
                    @checked(in_array($medicine->id, $selectedMedicines))
                    @disabled($isAlreadyAdded)
                    class="w-5 h-5 text-blue-600 border-gray-300 rounded mt-0.5 {{ $isAlreadyAdded ? 'cursor-not-allowed opacity-50' : '' }}"
                />
                <div class="ml-3 flex-1">
                    <div class="flex items-center gap-2">
                        <p class="font-medium {{ $isAlreadyAdded ? 'text-gray-500' : 'text-gray-900' }}">
                            {{ $medicine->name }}
                        </p>
                        @if($isAlreadyAdded)
                            <span class="px-2 py-0.5 text-xs font-semibold text-red-700 bg-red-100 rounded-full">
                                Already Added
                            </span>
                        @endif
                    </div>
                    <div class="flex items-center gap-4 mt-1 text-xs {{ $isAlreadyAdded ? 'text-gray-400' : 'text-gray-600' }}">
                        @if($medicine->brand_name)
                            <span><i class="fas fa-tag mr-1 text-black"></i>{{ $medicine->brand_name }}</span>
                        @endif
                        @if($medicine->category)
                            <span><i class="fas fa-folder mr-1 text-black"></i>{{ $medicine->category }}</span>
                        @endif
                        @if($medicine->strength)
                            <span><i class="fas fa-weight-hanging mr-1 text-black"></i>{{ $medicine->strength }}</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-4 mt-2 text-xs {{ $isAlreadyAdded ? 'text-gray-400' : 'text-gray-500' }}">
                        <span><i class="fas fa-hashtag mr-1 text-black"></i>{{ $medicine->code }}</span>
                        <span><i class="fas fa-toggle-on mr-1 text-black"></i>{{ ucfirst($medicine->status) }}</span>
                        @if($medicine->selling_price)
                            <span><i class="fas fa-rupee-sign mr-1 text-black"></i>₹{{ number_format($medicine->selling_price, 2) }}</span>
                        @endif
                    </div>
                    @if($isAlreadyAdded)
                        <p class="text-xs text-red-600 mt-2">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            This medicine is already added to this pharmacy
                        </p>
                    @endif
                </div>
            </label>
        @empty
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-search text-4xl mb-3 text-gray-300"></i>
                <p class="text-lg font-medium">No medicines found</p>
                <p class="text-sm">Try adjusting your search</p>
            </div>
        @endforelse
    </div>

    <!-- PAGINATION -->
    @if($medicines->hasPages())
        <div class="mt-4 pt-4 border-t border-gray-200">
            {{ $medicines->links() }}
        </div>
    @endif

    <!-- SELECTED MEDICINES SUMMARY -->
    <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <h4 class="font-semibold text-gray-900 mb-2">
            <i class="fas fa-check-circle text-blue-600 mr-2"></i>
            Selected Medicines ({{ count($selectedMedicines) }})
        </h4>
        @if(count($selectedMedicines) > 0)
            <p class="text-sm text-gray-600">
                {{ count($selectedMedicines) }} medicine(s) selected and ready to add
            </p>
        @else
            <p class="text-sm text-gray-500">No medicines selected</p>
        @endif
    </div>

    @error('selectedMedicines')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror

</div>

