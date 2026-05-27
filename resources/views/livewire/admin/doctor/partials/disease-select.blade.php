<div class="relative">
    <label class="block text-sm font-medium mb-1">Diseases</label>

    <div class="min-h-[44px] flex flex-wrap items-center gap-2 px-3 py-2 border rounded-lg bg-white mb-3">
        @forelse($this->selectedDiseaseLabels as $disease)
            <span class="flex items-center gap-1 bg-[#DBEAFE] text-[#0369A1] px-2 py-1 rounded-full text-xs">
                <span>{{ $disease['name'] }}</span>
                <button type="button" class="ml-1 hover:text-red-600" wire:click="removeDisease({{ $disease['id'] }})">✕</button>
            </span>
        @empty
            <span class="text-gray-400 text-sm">Select diseases...</span>
        @endforelse
    </div>

    <input type="text"
        wire:model.live="disease_search"
        placeholder="Search diseases..."
        class="w-full px-3 py-2 mb-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#DBEAFE]/40 focus:border-[#DBEAFE]"
        autocomplete="off">

    <div class="border rounded-lg h-48 overflow-y-auto p-3 space-y-2"
        wire:loading.class="opacity-60"
        wire:target="disease_search,assigned_diseases">
        @forelse($this->filteredDiseaseData as $disease)
            <label class="flex items-center gap-3 text-sm cursor-pointer" wire:key="disease-option-{{ $disease['id'] }}">
                <input type="checkbox"
                    class="rounded border-gray-300 text-[#DBEAFE] focus:ring-[#DBEAFE]"
                    wire:model.live="assigned_diseases"
                    value="{{ (string) $disease['id'] }}">
                <span>{{ $disease['name'] }}</span>
            </label>
        @empty
            <p class="text-sm text-gray-500 text-center">
                {{ trim($disease_search) !== '' ? 'No diseases match your search' : 'No diseases found' }}
            </p>
        @endforelse
    </div>

    @error('assigned_diseases')
        <span class="text-red-500 text-sm">{{ $message }}</span>
    @enderror
</div>
