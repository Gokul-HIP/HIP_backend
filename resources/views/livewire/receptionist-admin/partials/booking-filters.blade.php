<div class="space-y-3">
    <input type="text"
        wire:model.live.debounce.300ms="search"
        placeholder="Search name, phone, member ID..."
        class="w-full text-sm rounded-xl border border-slate-200 px-4 py-3 outline-none focus:ring-2 focus:ring-sky-200">

    <div class="flex gap-3 flex-wrap items-center">
        <div class="relative" x-data="{ open: false }">
            <button type="button" @click="open = !open"
                class="inline-flex items-center gap-2 px-5 py-2 rounded-full text-sm font-medium text-white"
                style="background:var(--button-color);">
                @php
                    $selectedHospital = $hospitals->firstWhere('id', (int) $hospitalFilter);
                @endphp
                {{ $hospitalFilter === 'all' ? 'All Hospitals' : ($selectedHospital?->name ?? 'Hospital') }}
            </button>
            <div x-show="open" x-cloak @click.away="open = false"
                class="absolute z-20 mt-2 w-56 rounded-xl border border-slate-200 bg-white shadow-lg overflow-hidden">
                <button type="button" wire:click="$set('hospitalFilter', 'all')" @click="open = false"
                    class="w-full text-left px-4 py-2.5 text-sm hover:bg-slate-50">All Hospitals</button>
                @foreach ($hospitals as $hospital)
                    <button type="button" wire:click="$set('hospitalFilter', '{{ $hospital->id }}')" @click="open = false"
                        class="w-full text-left px-4 py-2.5 text-sm hover:bg-slate-50 {{ (string) $hospitalFilter === (string) $hospital->id ? 'bg-sky-50 text-sky-700 font-medium' : '' }}">
                        {{ $hospital->name }}
                    </button>
                @endforeach
            </div>
        </div>

        @isset($availableDoctors)
        <div class="relative" x-data="{ open: false }">
            <button type="button" @click="open = !open"
                class="inline-flex items-center gap-2 px-5 py-2 rounded-full text-sm font-medium text-white"
                style="background:var(--button-color);">
                @php
                    $selectedDoctor = $availableDoctors->firstWhere('id', $doctorFilter);
                @endphp
                {{ $doctorFilter === 'all' ? 'All Doctors' : ($selectedDoctor?->name ?? 'Doctor') }}
            </button>
            <div x-show="open" x-cloak @click.away="open = false"
                class="absolute z-20 mt-2 w-56 rounded-xl border border-slate-200 bg-white shadow-lg overflow-hidden max-h-64 overflow-y-auto">
                <button type="button" wire:click="$set('doctorFilter', 'all')" @click="open = false"
                    class="w-full text-left px-4 py-2.5 text-sm hover:bg-slate-50">All Doctors</button>
                @foreach ($availableDoctors as $doctor)
                    <button type="button" wire:click="$set('doctorFilter', '{{ $doctor->id }}')" @click="open = false"
                        class="w-full text-left px-4 py-2.5 text-sm hover:bg-slate-50 {{ (string) $doctorFilter === (string) $doctor->id ? 'bg-sky-50 text-sky-700 font-medium' : '' }}">
                        {{ $doctor->name }}
                    </button>
                @endforeach
            </div>
        </div>
        @endisset

        <div class="relative" x-data="{ open: false }">
            <button type="button" @click="open = !open"
                class="inline-flex items-center gap-2 px-5 py-2 rounded-full text-sm font-medium text-white"
                style="background:var(--button-color);">
                {{ $statusFilter === 'all' ? 'All Status' : ucfirst($statusFilter) }}
            </button>
            <div x-show="open" x-cloak @click.away="open = false"
                class="absolute z-20 mt-2 w-48 rounded-xl border border-slate-200 bg-white shadow-lg overflow-hidden">
                @foreach (['all', 'pending', 'confirmed', 'completed', 'cancelled'] as $status)
                    <button type="button" wire:click="$set('statusFilter', '{{ $status }}')" @click="open = false"
                        class="w-full text-left px-4 py-2.5 text-sm hover:bg-slate-50">
                        {{ $status === 'all' ? 'All Status' : ucfirst($status) }}
                    </button>
                @endforeach
            </div>
        </div>

        <input type="date" wire:model.live="dateFilter"
            class="px-4 py-2 rounded-full border border-slate-200 text-sm">

        <button type="button" wire:click="clearFilters"
            class="px-5 py-2 rounded-full text-sm font-medium text-white"
            style="background:#64748b;">
            Reset
        </button>
    </div>
</div>
