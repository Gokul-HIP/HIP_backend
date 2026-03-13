{{-- livewire/admin/hospital/hospital-list.blade.php --}}
<div class="space-y-6"
    x-data
    x-init="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })"
    @relode-hos.window="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })">

    <style>
        ui-modal#delete-org dialog { max-width: 420px !important; }
        [data-flux-modal="delete-hos"] dialog,
        [data-flux-modal="delete-hos"] dialog * { color-scheme: light !important; background-color: #ffffff !important; color: #111827 !important; border-color: #d1d5db !important; }
        [data-flux-modal="delete-hos"] dialog { background-color: #ffffff !important; border-color: #d1d5db !important; }
        [data-flux-modal="delete-hos"] dialog input,
        [data-flux-modal="delete-hos"] dialog textarea,
        [data-flux-modal="delete-hos"] dialog select,
        [data-flux-modal="delete-hos"] dialog button,
        [data-flux-modal="delete-hos"] dialog div,
        [data-flux-modal="delete-hos"] dialog .border,
        [data-flux-modal="delete-hos"] dialog [class*="border"] { border-color: #d1d5db !important; }

        /* ── Action Menu ── */
        .hip-action-wrapper { position: relative; display: inline-block; }

        .hip-action-btn {
            width: 2rem; height: 2rem;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 0.5rem;
            border: 1px solid #e2e8f0;
            color: #94a3b8;
            background: transparent;
            cursor: pointer;
            transition: color .15s, border-color .15s, background .15s;
        }
        .hip-action-btn:hover { color: #0ea5e9; }
        .hip-action-btn.open {
            color: #0ea5e9;
            border-color: rgba(14,165,233,0.35);
            background: rgba(14,165,233,0.06);
        }

        .hip-action-menu {
            display: none;
            position: fixed;
            z-index: 9999;
            width: 13rem;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
            overflow: hidden;
        }
        .hip-action-menu.show { display: block; }

        .hip-menu-item {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.65rem 1rem;
            font-size: 0.875rem;
            color: #475569;
            text-decoration: none;
            background: transparent;
            border: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
            transition: background .12s;
            border-bottom: 1px solid #f8fafc;
        }
        .hip-menu-item:last-child { border-bottom: none; }
        .hip-menu-item:hover { background: #f8fafc; }
        .hip-menu-item.green { color: #16a34a; }
        .hip-menu-item.green:hover { background: #f0fdf4; }
        .hip-menu-item.red { color: #dc2626; }
        .hip-menu-item.red:hover { background: #fff1f2; }

        /* ── Filter pills ── */
        .hip-filter-pill {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.4rem 1rem;
            font-size: 0.875rem;
            border-radius: 9999px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #374151;
            cursor: pointer;
            transition: background .12s;
            white-space: nowrap;
        }
        .hip-filter-pill:hover { background: #f1f5f9; }

        .hip-filter-dropdown {
            display: none;
            position: absolute;
            left: 0; top: calc(100% + 4px);
            z-index: 40;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            min-width: 11rem;
        }
        .hip-filter-dropdown.show { display: block; }

        .hip-filter-item {
            display: inline-flex; align-items: center; gap: 0.5rem;
            width: 100%; padding: 0.5rem 0.75rem;
            font-size: 0.875rem; color: #374151;
            background: transparent; border: none;
            text-align: left; cursor: pointer;
            transition: background .12s;
        }
        .hip-filter-item:hover { background: #f8fafc; }
        .hip-filter-item.green:hover { background: #f0fdf4; color: #15803d; }
        .hip-filter-item.red:hover { background: #fff1f2; color: #dc2626; }
    </style>

    {{-- ── Hero Banner ── --}}
    <div class="relative rounded-2xl overflow-hidden shadow-lg border border-slate-200" style="height:11rem;">
        <img src="{{ asset('assets/hospital.png') }}"
            alt="Hospital Banner"
            class="w-full h-full object-cover"
            style="filter:brightness(0.55);">
        <div class="absolute inset-0 bg-gradient-to-r from-black/50 to-transparent"></div>

        <div class="absolute bottom-5 left-6 p-4 rounded-xl text-white"
            style="background:rgba(0,0,0,0.45); backdrop-filter:blur(10px); border:1px solid rgba(255,255,255,0.2);">
            <h2 class="text-xl font-bold">{{ $organization->name }}</h2>
            <p class="text-xs mt-0.5" style="opacity:0.8;">
                Manage multiple branches of <strong>{{ $organization->name }}</strong> across different locations.
            </p>
        </div>

        <button type="button"
            class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-lg text-white transition-all"
            style="background:rgba(255,255,255,0.2); backdrop-filter:blur(8px);"
            onmouseover="this.style.background='rgba(255,255,255,0.4)';"
            onmouseout="this.style.background='rgba(255,255,255,0.2)';">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
        </button>
    </div>

    {{-- ── Onboarding Alert for Newly Added Hospitals (same as dashboard) ── --}}
    @php
        $orgId = auth()->user()->organization_id ?? null;
        $pendingHospitals = collect();
        if ($orgId) {
            $pendingHospitals = \App\Models\Hospital::query()
                ->where('organization_id', $orgId)
                ->where('status', 'inactive')
                ->where(function ($q) {
                    $q->whereNull('onboarding_status')
                      ->orWhere('onboarding_status', 'draft');
                })
                ->orderBy('created_at', 'desc')
                ->get();
        }
    @endphp

    @if($pendingHospitals->count() > 0)
        @foreach($pendingHospitals as $hospital)
            <div class="mt-4 mb-2 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-yellow-800">
                            New hospital added: {{ $hospital->name }}
                        </p>
                        <p class="mt-1 text-xs text-yellow-700">
                            Complete the onboarding steps to activate this hospital on the platform.
                        </p>
                    </div>
                    <a href="{{ route('healthcare.hospital-profile.index', ['hospital_id' => $hospital->id]) }}"
                       class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-full bg-yellow-600 text-white hover:bg-yellow-700">
                        Go to Onboarding
                    </a>
                </div>
            </div>
        @endforeach
    @endif

    {{-- ── Stat Cards ── --}}
    <div class="space-y-3">
        <h3 class="text-xs font-bold uppercase tracking-widest text-slate-500">Hospital List</h3>
        <div class="flex gap-4">
            <div class="bg-white border border-slate-200 p-4 rounded-xl shadow-sm" style="min-width:10rem;">
                <p class="font-bold text-slate-400 uppercase" style="font-size:10px; letter-spacing:.08em;">Total Hospital</p>
                <p class="text-4xl font-bold text-slate-900 mt-1">{{ $totalCount }}</p>
            </div>
            <div class="bg-white border border-slate-200 p-4 rounded-xl shadow-sm" style="min-width:10rem;">
                <p class="font-bold text-slate-400 uppercase" style="font-size:10px; letter-spacing:.08em;">Active Hospital</p>
                <p class="text-4xl font-bold text-slate-900 mt-1">{{ $activeCount }}</p>
            </div>
        </div>
    </div>

    {{-- ── Table Card ── --}}
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm mb-20">

        {{-- Filter Bar --}}
        <div class="p-4 flex items-center gap-3 flex-wrap" style="border-bottom:1px solid #f1f5f9;">

            {{-- Search --}}
            <div class="relative" style="flex:1; min-width:14rem; max-width:22rem;">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35"/>
                </svg>
                <input type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search hospital name, address..."
                    class="w-full pl-9 pr-4 py-2 text-sm rounded-full outline-none transition-all"
                    style="background:#f8fafc; border:1px solid #e2e8f0;"
                    onfocus="this.style.background='#fff'; this.style.boxShadow='0 0 0 2px rgba(14,165,233,0.2)'; this.style.borderColor='#bae6fd';"
                    onblur="this.style.background='#f8fafc'; this.style.boxShadow='none'; this.style.borderColor='#e2e8f0';">
            </div>

            {{-- Location Filter --}}
            <div class="relative" id="locWrap">
                <button type="button" class="hip-filter-pill" onclick="hipToggleFilter(event,'locDrop')">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span id="locLabel">{{ $locationFilter === 'all' ? 'All Locations' : ucfirst($locationFilter) }}</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="locDrop" class="hip-filter-dropdown">
                    <ul class="p-1.5">
                        <li><button class="hip-filter-item" wire:click="$set('locationFilter','all')" onclick="hipCloseFilter('locDrop')">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                            All Locations
                        </button></li>
                        @foreach(['Mumbai','Bengaluru','Chennai'] as $city)
                        <li><button class="hip-filter-item" wire:click="$set('locationFilter','{{ strtolower($city) }}')" onclick="hipCloseFilter('locDrop')">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ $city }}
                        </button></li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- Status Filter --}}
            <div class="relative" id="statusWrap">
                <button type="button" class="hip-filter-pill" onclick="hipToggleFilter(event,'statusDrop')">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/>
                    </svg>
                    <span id="statusLabel">{{ $statusFilter === 'all' ? 'All Status' : ucfirst($statusFilter) }}</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="statusDrop" class="hip-filter-dropdown">
                    <ul class="p-1.5">
                        <li><button class="hip-filter-item" wire:click="$set('statusFilter','all')" onclick="hipCloseFilter('statusDrop')">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                            All Status
                        </button></li>
                        <li><button class="hip-filter-item green" wire:click="$set('statusFilter','active')" onclick="hipCloseFilter('statusDrop')">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Active
                        </button></li>
                        <li><button class="hip-filter-item red" wire:click="$set('statusFilter','inactive')" onclick="hipCloseFilter('statusDrop')">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Inactive
                        </button></li>
                    </ul>
                </div>
            </div>

        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr style="background:rgba(248,250,252,0.6); border-bottom:1px solid #f1f5f9;">
                        <th class="px-6 py-4 font-semibold text-slate-500">Hospital Name</th>
                        <th class="px-6 py-4 font-semibold text-slate-500">Location</th>
                        <th class="px-6 py-4 font-semibold text-slate-500">Organization</th>
                        <th class="px-6 py-4 font-semibold text-slate-500">Status</th>
                        <th class="px-6 py-4 font-semibold text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">

                    @forelse ($hospitals as $hos)
                    <tr class="hover:bg-slate-50 transition-colors">

                        <td class="px-6 py-4">
                            <a href="{{ route('admin.organizations.hospital.show', $hos->id) }}"
                                class="font-medium text-slate-800 hover:text-primary hover:underline transition-colors">
                                {{ $hos->name }}
                            </a>
                        </td>

                        <td class="px-6 py-4 text-slate-400">{{ $hos->address ?? '-' }}</td>

                        <td class="px-6 py-4 text-slate-600">{{ $hos->organization->name ?? '-' }}</td>

                        <td class="px-6 py-4">
                            @if($hos->status === 'active')
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full" style="background:#dcfce7; color:#16a34a;">Active</span>
                            @else
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full" style="background:#fff1f2; color:#e11d48;">Inactive</span>
                            @endif
                        </td>

                        {{-- Action Menu (smart flip) --}}
                        <td class="px-6 py-4">
                            <div class="hip-action-wrapper">

                                <button type="button"
                                    class="hip-action-btn"
                                    onclick="hipToggleAction(event, 'hosMenu{{ $hos->id }}')">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="currentColor" viewBox="0 0 24 24">
                                        <circle cx="12" cy="5" r="1.5"/>
                                        <circle cx="12" cy="12" r="1.5"/>
                                        <circle cx="12" cy="19" r="1.5"/>
                                    </svg>
                                </button>

                                <div id="hosMenu{{ $hos->id }}" class="hip-action-menu">

                                    {{-- View / Onboarding --}}
                                    <a href="{{ route('healthcare.hospital-profile.index', ['hospital_id' => $hos->id]) }}"
                                        onclick="hipCloseAllActions()"
                                        class="hip-menu-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        View
                                    </a>

                                    <a href="{{ route('healthcare.hospitals.specialities.index', $hos->id) }}"
                                        onclick="hipCloseAllActions()"
                                        class="hip-menu-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                        Manage Specialities
                                    </a>

                                    <a href="{{ route('healthcare.hospitals.procedures.index', $hos->id) }}"
                                        onclick="hipCloseAllActions()"
                                        class="hip-menu-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                        Manage Procedures
                                    </a>

                                    {{-- <button type="button"
                                        wire:click="updateHospitalStatus({{ $hos->id }}, 'active')"
                                        onclick="hipCloseAllActions()"
                                        class="hip-menu-item green">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Set Active
                                    </button>

                                    <button type="button"
                                        wire:click="updateHospitalStatus({{ $hos->id }}, 'inactive')"
                                        onclick="hipCloseAllActions()"
                                        class="hip-menu-item red">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Set Inactive
                                    </button> --}}

                                    <a href="{{ route('healthcare.doctors.index', ['hospital_id' => $hos->id]) }}"
                                        onclick="hipCloseAllActions()"
                                        class="hip-menu-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        Manage Doctors
                                    </a>

                                </div>
                            </div>
                        </td>

                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-14 h-14 rounded-2xl flex items-center justify-center" style="background:#f1f5f9;">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                </div>
                                <p class="text-base font-semibold text-slate-700">No hospitals found</p>
                                <p class="text-sm text-slate-400">Start by adding your first hospital</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse

                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-6 py-4" style="border-top:1px solid #f1f5f9;">
            {{ $hospitals->links() }}
        </div>

    </div>

    {{-- ── Delete Modal ── --}}
    <flux:modal name="delete-hos" class="p-0" wire:close="closeModal" id="delete-org">
        <div x-data @click.outside="$wire.closeModal()">
            <flux:modal.close class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 cursor-pointer" wire:click="closeModal"/>
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Delete Hospital?</h2>
            <p class="text-sm text-gray-500 mb-6 leading-relaxed">
                You're about to delete this Hospital.<br>This action cannot be reversed.
            </p>
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="closeModal" class="text-sm font-medium text-slate-600 hover:text-slate-900">
                    Cancel
                </flux:button>
                <button type="button" wire:click="destroy"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-white shadow transition-colors"
                    style="background:#ef4444;"
                    onmouseover="this.style.background='#dc2626';" onmouseout="this.style.background='#ef4444';">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete Hospital
                </button>
            </div>
        </div>
    </flux:modal>

    {{-- ── JS: Smart action menus + filter dropdowns ── --}}
    <script>
        /* ── Action menus ── */
        function hipToggleAction(e, id) {
            e.stopPropagation();
            var menu   = document.getElementById(id);
            var btn    = e.currentTarget;
            var isOpen = menu.classList.contains('show');

            hipCloseAllActions();
            hipCloseAllFilters();

            if (!isOpen) {
                var btnRect    = btn.getBoundingClientRect();
                var menuWidth  = 208; // 13rem
                var menuHeight = 290; // approx 6 items
                var spaceBelow = window.innerHeight - btnRect.bottom;
                var left       = btnRect.right - menuWidth;

                // flip upward if not enough space below
                if (spaceBelow < menuHeight) {
                    menu.style.top    = (btnRect.top - menuHeight - 4) + 'px';
                } else {
                    menu.style.top    = (btnRect.bottom + 4) + 'px';
                }
                menu.style.left = Math.max(8, left) + 'px';

                menu.classList.add('show');
                btn.classList.add('open');
            }
        }

        function hipCloseAllActions() {
            document.querySelectorAll('.hip-action-menu').forEach(function(m) { m.classList.remove('show'); });
            document.querySelectorAll('.hip-action-btn').forEach(function(b) { b.classList.remove('open'); });
        }

        /* ── Filter dropdowns ── */
        function hipToggleFilter(e, id) {
            e.stopPropagation();
            var drop   = document.getElementById(id);
            var isOpen = drop.classList.contains('show');

            hipCloseAllFilters();
            hipCloseAllActions();

            if (!isOpen) { drop.classList.add('show'); }
        }

        function hipCloseFilter(id) {
            var drop = document.getElementById(id);
            if (drop) drop.classList.remove('show');
        }

        function hipCloseAllFilters() {
            document.querySelectorAll('.hip-filter-dropdown').forEach(function(d) { d.classList.remove('show'); });
        }

        /* ── Click outside closes everything ── */
        document.addEventListener('click', function() {
            hipCloseAllActions();
            hipCloseAllFilters();
        });

        /* ── Close on scroll or resize ── */
        window.addEventListener('scroll', hipCloseAllActions, true);
        window.addEventListener('resize', hipCloseAllActions);
    </script>

</div>