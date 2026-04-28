{{-- livewire/admin/doctor/doctor-list.blade.php --}}
<div class="space-y-6">

    <style>
        .hip-doc-menu {
            position: fixed;
            z-index: 9999;
            width: 14rem;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
            overflow: hidden;
        }
        .hip-doc-item {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.6rem 1rem;
            font-size: 0.8125rem;
            color: #475569;
            text-decoration: none;
            background: transparent;
            border: none; border-bottom: 1px solid #f8fafc;
            width: 100%; text-align: left; cursor: pointer;
            transition: background .1s;
        }
        .hip-doc-item:last-child { border-bottom: none; }
        .hip-doc-item:hover { background: #f8fafc; }
        .hip-doc-divider { height: 1px; background: #f1f5f9; margin: 4px 0; }
        .hip-doc-section {
            padding: 6px 16px 4px;
            font-size: 10px; font-weight: 800;
            color: #94a3b8; text-transform: uppercase; letter-spacing: .08em;
        }
        .hip-doc-btn { background: transparent; border: none; cursor: pointer; }
        .hip-filter-button {
            width: 100%;
            padding: 0.65rem 2.3rem 0.65rem 0.85rem;
            font-size: 0.875rem;
            color: #0f172a;
            background: #fff;
            border: 1px solid #dbe3ef;
            border-radius: 0.85rem;
            outline: none;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.05);
            text-align: left;
            transition: border-color .12s ease, box-shadow .12s ease, background .12s ease;
        }
        .hip-filter-button:hover { background: #f8fafc; }
        .hip-filter-button:focus {
            border-color: #41b7ee;
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.14);
            background: #fff;
        }
        .hip-filter-wrap { position: relative; min-width: 11rem; }
        .hip-filter-menu {
            position: absolute;
            top: calc(100% + 0.45rem);
            left: 0;
            z-index: 30;
            width: 100%;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 0.9rem;
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.14);
            padding: 0.45rem;
        }
        .hip-filter-item {
            display: block;
            width: 100%;
            padding: 0.75rem 0.9rem;
            border: 0;
            border-radius: 0.65rem;
            background: transparent;
            color: #0f172a;
            font-size: 0.9rem;
            text-align: left;
            transition: background .12s ease, color .12s ease;
        }
        .hip-filter-item:hover { background: #f8fafc; }
        .hip-filter-item.active {
            background: #d5f2ff;
            color: #0DA2E7;
            font-weight: 600;
        }
        .hip-filter-icon {
            position: absolute;
            right: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
            font-size: 0.75rem;
        }
    </style>

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-black uppercase tracking-tight text-slate-800">Doctor Profile</h1>
        <flux:modal.trigger name="add-doctor">
            <button type="button"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg text-white shadow-sm transition-all hover:opacity-90"
                style="background:#0DA2E7;">
                <i class="fa-solid fa-plus"></i>
                Add Doctor
            </button>
        </flux:modal.trigger>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-slate-500">Total Doctors</span>
            </div>
            <p class="text-3xl font-bold text-slate-900">{{ number_format($totalDoctors) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-slate-500">Active Doctors</span>
            </div>
            <p class="text-3xl font-bold text-slate-900">{{ number_format($activeDoctors) }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 flex flex-wrap gap-4 items-center justify-between" style="border-bottom:1px solid #f1f5f9;">
            <div class="relative" style="width:100%; max-width:22rem;">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35"/>
                </svg>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search by name, email or ID..."
                    class="w-full pl-10 pr-4 py-2 text-sm rounded-lg outline-none transition-all"
                    style="background:#f8fafc; border:none;"
                    onfocus="this.style.boxShadow='0 0 0 2px rgba(13,148,136,0.2)';"
                    onblur="this.style.boxShadow='none';"
                >
            </div>
            <div class="flex gap-4 items-center flex-wrap">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-slate-400 uppercase">Sort by:</span>
                    <div x-data="{ open: false }" class="hip-filter-wrap">
                        <button type="button" @click="open = !open" class="hip-filter-button">
                            {{ $sortOptions[$sortBy] ?? 'Name (A-Z)' }}
                        </button>
                        <i class="fas fa-chevron-down hip-filter-icon"></i>
                        <div x-show="open" x-transition @click.away="open = false" class="hip-filter-menu">
                            @foreach($sortOptions as $value => $label)
                                <button type="button" class="hip-filter-item {{ $sortBy === $value ? 'active' : '' }}" @click="$wire.set('sortBy', '{{ $value }}'); open = false">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-slate-400 uppercase">Status:</span>
                    <div x-data="{ open: false }" class="hip-filter-wrap">
                        <button type="button" @click="open = !open" class="hip-filter-button">
                            {{ $statusOptions[$statusFilter] ?? 'All Status' }}
                        </button>
                        <i class="fas fa-chevron-down hip-filter-icon"></i>
                        <div x-show="open" x-transition @click.away="open = false" class="hip-filter-menu">
                            @foreach($statusOptions as $value => $label)
                                <button type="button" class="hip-filter-item {{ $statusFilter === $value ? 'active' : '' }}" @click="$wire.set('statusFilter', '{{ $value }}'); open = false">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-slate-400 uppercase">Hospital:</span>
                    <div x-data="{ open: false }" class="hip-filter-wrap">
                        <button type="button" @click="open = !open" class="hip-filter-button">
                            {{ $hospitalFilter === 'all' ? 'All Hospitals' : optional($availableHospitals->firstWhere('id', (int) $hospitalFilter))->name }}
                        </button>
                        <i class="fas fa-chevron-down hip-filter-icon"></i>
                        <div x-show="open" x-transition @click.away="open = false" class="hip-filter-menu">
                            <button type="button" class="hip-filter-item {{ $hospitalFilter === 'all' ? 'active' : '' }}" @click="$wire.set('hospitalFilter', 'all'); open = false">
                                All Hospitals
                            </button>
                            @foreach($availableHospitals as $hospital)
                                <button type="button" class="hip-filter-item {{ (string) $hospitalFilter === (string) $hospital->id ? 'active' : '' }}" @click="$wire.set('hospitalFilter', '{{ $hospital->id }}'); open = false">
                                    {{ $hospital->name }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead style="background:#f8fafc;">
                    <tr>
                        <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">Doctor Name</th>
                        <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">Mobile Number</th>
                        <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">Hospital</th>
                        <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">Qualification</th>
                        <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">Experience</th>
                        <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">Status</th>
                        <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">Created At</th>
                        <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($doctors as $doctor)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    @if(!empty($doctor['doctor_image']))
                                        <img src="{{ $doctor['doctor_image'] }}"
                                            alt="{{ $doctor['name'] }}"
                                            class="w-8 h-8 rounded-full object-cover flex-shrink-0 border border-slate-200">
                                    @else
                                        <div class="w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center text-xs font-bold" style="background:#dbeafe; color:#2563eb;">{{ $doctor['initials'] }}</div>
                                    @endif
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800">{{ $doctor['name'] }}</p>
                                        <p class="text-xs text-slate-400">ID: {{ $doctor['doctor_id'] }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600 whitespace-nowrap">{{ $doctor['mobile_number'] }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ $doctor['hospital_name'] }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ $doctor['qualification'] }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600 whitespace-nowrap">{{ $doctor['experience'] }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase" style="{{ $doctor['status'] === 'active' ? 'background:#dcfce7; color:#15803d;' : 'background:#f1f5f9; color:#64748b;' }}">
                                    {{ $doctor['status_label'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600 whitespace-nowrap">{{ $doctor['created_at'] }}</td>
                            <td class="px-6 py-4">
                                <div class="inline-block">
                                    <button type="button" class="hip-doc-btn w-8 h-8 inline-flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 transition-colors" onclick="hipDocToggle(event,'docMenu{{ $doctor['id'] }}')">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <circle cx="12" cy="5" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="12" cy="19" r="1.5"/>
                                        </svg>
                                    </button>
                                    <div id="docMenu{{ $doctor['id'] }}" class="hip-doc-menu" style="display:none;">
                                        <a href="{{ route('healthcare.doctors.profile', ['id' => $doctor['id']]) }}" onclick="hipDocCloseAll()" class="hip-doc-item">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            View
                                        </a>
                                        <div class="hip-doc-divider"></div>
                                        <div class="hip-doc-section">Manage</div>
                                        <a href="{{ route('healthcare.doctors.profile', ['id' => $doctor['id'], 'tab' => 'appointments']) }}" onclick="hipDocCloseAll()" class="hip-doc-item">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            Manage Appointments
                                        </a>
                                        {{-- <button type="button" onclick="hipDocCloseAll()" class="hip-doc-item">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
                                            Manage Linked Hospitals
                                        </button> --}}
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-10 text-center text-sm text-slate-500">No doctors found for this organization.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 flex items-center justify-between" style="border-top:1px solid #f1f5f9;">
            <p class="text-xs text-slate-500">Showing {{ $doctors->firstItem() ?? 0 }} to {{ $doctors->lastItem() ?? 0 }} of {{ $doctors->total() }} entries</p>
            <div class="flex gap-2 items-center">
                @if($doctors->lastPage() > 1)
                    <a href="{{ $doctors->previousPageUrl() ?: '#' }}" class="p-2 rounded-lg border border-slate-200 text-slate-400 hover:bg-slate-50 transition-colors {{ $doctors->onFirstPage() ? 'pointer-events-none opacity-50' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                    @for($page = 1; $page <= $doctors->lastPage(); $page++)
                        <a href="{{ $doctors->url($page) }}" class="w-8 h-8 flex items-center justify-center text-xs font-bold rounded-lg {{ $doctors->currentPage() === $page ? 'text-white' : 'text-slate-600 hover:bg-slate-50' }}" style="{{ $doctors->currentPage() === $page ? 'background:#0DA2E7;' : '' }}">
                            {{ $page }}
                        </a>
                    @endfor
                    <a href="{{ $doctors->nextPageUrl() ?: '#' }}" class="p-2 rounded-lg border border-slate-200 text-slate-400 hover:bg-slate-50 transition-colors {{ $doctors->currentPage() === $doctors->lastPage() ? 'pointer-events-none opacity-50' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @endif
            </div>
        </div>

    </div>

    <script>
        function hipDocToggle(e, id) {
            e.stopPropagation();
            var menu   = document.getElementById(id);
            var btn    = e.currentTarget;
            var isOpen = menu.style.display === 'block';
            hipDocCloseAll();
            if (!isOpen) {
                var rect       = btn.getBoundingClientRect();
                var menuW      = 224;
                var menuH      = 150;
                var spaceBelow = window.innerHeight - rect.bottom;
                menu.style.left    = Math.max(8, rect.right - menuW) + 'px';
                menu.style.top     = (spaceBelow < menuH ? rect.top - menuH - 4 : rect.bottom + 4) + 'px';
                menu.style.display = 'block';
            }
        }
        function hipDocCloseAll() {
            document.querySelectorAll('.hip-doc-menu').forEach(function(m) { m.style.display = 'none'; });
        }
        document.addEventListener('click', hipDocCloseAll);
        window.addEventListener('scroll', hipDocCloseAll, true);
        window.addEventListener('resize', hipDocCloseAll);
    </script>

</div>
