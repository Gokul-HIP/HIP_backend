<div class="relative space-y-2">

    <div class="flex items-center justify-between">
        <label class="block text-sm font-medium text-gray-700">
            Diseases <span class="text-red-500">*</span>
        </label>
        @if (!empty($this->selectedDiseaseLabels))
            <span class="text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">
                {{ count($this->selectedDiseaseLabels) }} selected
            </span>
        @endif
    </div>

    {{-- Selected department tags --}}
    <div class="min-h-[44px] flex flex-wrap items-center gap-1.5 px-3 py-2 border border-gray-300 rounded-lg bg-white cursor-default">
        @forelse($this->selectedDiseaseLabels as $dept)
            <span class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 border border-blue-200 px-2.5 py-1 rounded-full text-xs font-medium">
                <i class="fa-solid fa-hospital-user text-blue-400 text-[10px]"></i>
                {{ $dept['name'] }}
                <button
                    type="button"
                    wire:click="removeDisease({{ $dept['id'] }})"
                    class="ml-0.5 text-blue-400 hover:text-red-500 transition-colors leading-none">
                    <i class="fa-solid fa-xmark text-[10px]"></i>
                </button>
            </span>
        @empty
            <span class="text-gray-400 text-sm">Select disease departments...</span>
        @endforelse
    </div>

    {{-- Search --}}
    <div class="relative">
        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
        <input
            type="search"
            wire:model.live.debounce.250ms="disease_search"
            placeholder="Search departments..."
            class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none"
            autocomplete="off">
    </div>

    {{-- Department list --}}
    <div class="border border-gray-200 rounded-lg overflow-hidden">
        <div class="max-h-64 overflow-y-auto divide-y divide-gray-100"
            wire:loading.class="opacity-50 pointer-events-none"
            wire:target="disease_search,assigned_diseases">

            @forelse($this->filteredDiseaseData as $dept)
                <label
                    wire:key="dept-option-{{ $dept['id'] }}"
                    class="flex items-start gap-3 px-4 py-3 cursor-pointer hover:bg-gray-50 transition-colors
                        {{ in_array((string)$dept['id'], array_map('strval', $this->assigned_diseases ?? [])) ? 'bg-blue-50/60' : '' }}">

                    <div class="pt-0.5 flex-shrink-0">
                        <input
                            type="checkbox"
                            wire:model.live="assigned_diseases"
                            value="{{ (string) $dept['id'] }}"
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                    </div>

                    <div class="flex-1 min-w-0">
                        {{-- Department name --}}
                        <p class="text-sm font-medium text-gray-900 leading-tight">
                            {{ $dept['department_name'] }}
                        </p>

                        {{-- Disease names under department --}}
                        @if (!empty($dept['disease_names']))
                            <div class="mt-1.5 flex flex-wrap gap-1">
                                @foreach ($dept['disease_names'] as $diseaseName)
                                    <span class="inline-block bg-gray-100 text-gray-600 text-[11px] px-2 py-0.5 rounded-full leading-tight">
                                        {{ $diseaseName }}
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-1 text-xs text-gray-400 italic">No diseases assigned</p>
                        @endif
                    </div>
                </label>
            @empty
                <div class="px-4 py-8 text-center">
                    <i class="fa-solid fa-circle-xmark text-gray-300 text-2xl mb-2 block"></i>
                    <p class="text-sm text-gray-500">
                        {{ trim($disease_search) !== '' ? 'No departments match "' . $disease_search . '"' : 'No disease departments found' }}
                    </p>
                </div>
            @endforelse

        </div>

        {{-- Loading overlay --}}
        <div wire:loading wire:target="disease_search" class="px-4 py-3 text-center border-t border-gray-100">
            <span class="text-xs text-gray-400">
                <i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Searching...
            </span>
        </div>
    </div>

    @error('assigned_diseases')
        <p class="text-sm text-red-600 flex items-center gap-1">
            <i class="fa-solid fa-circle-exclamation text-xs"></i>
            {{ $message }}
        </p>
    @enderror

</div>