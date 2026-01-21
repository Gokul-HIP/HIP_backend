<div class="bg-white rounded-xl p-6 shadow space-y-4">

    {{-- SEARCH --}}
    <input
        type="text"
        wire:model.live.debounce.300ms="procedureSearch"
        placeholder="Search by procedure name or code..."
        class="w-full border rounded-lg px-4 py-2.5
               focus:ring-2 focus:ring-blue-500 focus:border-transparent">

    {{-- PROCEDURE LIST --}}
    <div class="border rounded-lg p-3 max-h-[420px] overflow-y-auto space-y-2">

        @forelse($procedures as $procedure)
            <label
                class="flex items-center gap-3 p-4 border rounded-lg cursor-pointer
                       transition
                       {{ in_array($procedure->id, $selectedProcedures)
                            ? 'bg-blue-50 border-blue-500'
                            : 'border-gray-200 hover:bg-gray-50' }}">
                <input
                    type="checkbox"
                    wire:model.live="selectedProcedures"
                    value="{{ $procedure->id }}"
                    class="w-4 h-4 text-blue-600 focus:ring-blue-500 rounded">

                <div class="flex flex-col">
                    <span class="font-medium text-gray-900">
                        {{ $procedure->procedure_name }}
                    </span>

                    @if(!empty($procedure->procedure_code))
                        <span class="text-xs text-gray-500">
                            Code: {{ $procedure->procedure_code }}
                        </span>
                    @endif
                </div>
            </label>
        @empty
            <div class="text-center py-12 text-gray-500">
                No procedures found
            </div>
        @endforelse
    </div>

    <div class="pt-3">
        {{ $procedures->links() }}
    </div>

    {{-- ERROR --}}
    @error('selectedProcedures')
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror

    {{-- SELECTED COUNT (INSIDE CARD) --}}
    <div class="flex justify-end pt-2">
        <div class="flex items-center gap-3 bg-blue-50 border border-blue-200
                    rounded-lg px-4 py-2">
            <span class="text-sm text-gray-600">Procedures Selected</span>
            <span class="text-xl font-bold text-blue-600">
                {{ count($selectedProcedures) }}
            </span>
        </div>
    </div>

</div>

