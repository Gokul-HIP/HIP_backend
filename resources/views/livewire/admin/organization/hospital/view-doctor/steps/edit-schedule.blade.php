<div class="bg-white rounded-xl p-6 shadow space-y-6">

    {{-- TITLE --}}
    <h3 class="text-lg font-semibold text-gray-900">
        Schedule Availability
    </h3>

    {{-- ERROR --}}
    @error('schedules')
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror

    {{-- SCHEDULE BLOCKS --}}
    @foreach($schedules as $sIndex => $schedule)
        <div
            wire:key="schedule-{{ $sIndex }}"
            class="border rounded-lg p-4 bg-gray-50 space-y-4 relative">

            {{-- DELETE DAY --}}
            @if(count($schedules) > 1)
                <button
                    wire:click="removeScheduleDay({{ $sIndex }})"
                    type="button"
                    class="absolute top-3 right-3 text-red-600 hover:text-red-800 font-bold text-lg">
                    ✕
                </button>
            @endif

            {{-- DAY SELECT --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Select Day
                </label>

                <select
                    wire:model.live="schedules.{{ $sIndex }}.day"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5
                           focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">-- Select Day --</option>
                    @foreach($days as $dayOption)
                        <option value="{{ $dayOption }}">{{ $dayOption }}</option>
                    @endforeach
                </select>
            </div>

            {{-- DATE INFO --}}
            @if(!empty($schedule['day']))
                <div class="bg-blue-50 border-l-4 border-blue-500 p-3">
                    <span class="text-blue-800 text-sm font-medium">
                        {{ $schedule['day'] }} (Next Week –
                        {{ \Carbon\Carbon::parse(
                            $this->getDateForDay($schedule['day'])
                        )->format('M d, Y') }})
                    </span>
                </div>
            @endif

            {{-- TIME SLOTS --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Time Slots
                </label>

                <div class="space-y-2">
                    @foreach($schedule['slots'] as $slotIndex => $slot)
                        <div
                            wire:key="slot-{{ $sIndex }}-{{ $slotIndex }}"
                            class="flex items-center gap-3">

                            <input type="time"
                                wire:model.live="schedules.{{ $sIndex }}.slots.{{ $slotIndex }}.start"
                                class="border rounded px-3 py-2">

                            <input type="time"
                                wire:model.live="schedules.{{ $sIndex }}.slots.{{ $slotIndex }}.end"
                                class="border rounded px-3 py-2">

                            @if(count($schedule['slots']) > 1)
                                <button
                                    wire:click="removeSlot({{ $sIndex }}, {{ $slotIndex }})"
                                    type="button"
                                    class="text-red-600 text-sm font-bold">
                                    ✕
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>

                <button
                    wire:click="addSlot({{ $sIndex }})"
                    type="button"
                    class="mt-2 text-blue-600 text-sm font-medium">
                    + Add time slot
                </button>
            </div>
        </div>
    @endforeach

    {{-- ADD ANOTHER DAY --}}
    <button
        wire:click="addScheduleDay"
        type="button"
        class="flex items-center gap-2 text-blue-700 font-medium text-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 4v16m8-8H4"/>
        </svg>
        Assign another day
    </button>

    {{-- SUMMARY --}}
    @php
        $totalSlots = collect($schedules)
            ->sum(fn ($s) => count($s['slots'] ?? []));
    @endphp

    <div class="bg-gray-100 rounded-lg p-4">
        <h4 class="font-semibold text-gray-900 mb-1">Summary</h4>
        <p class="text-sm text-gray-600">
            {{ count($schedules) }} day(s),
            {{ $totalSlots }} time slot(s) scheduled
        </p>
    </div>

</div>
