<div class="min-h-screen bg-gray-50 p-4 sm:p-6 lg:p-8">

    <style>
        [x-cloak] { display: none !important; }

        .filter-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: #fff;
            border: 1px solid #d1eaf9;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            color: #334155;
            cursor: pointer;
            transition: all 0.15s;
        }
        .filter-btn:hover { border-color: var(--button-color); background: #f0faff; color: var(--button-color); }

        .action-menu-wrapper { position: relative; display: inline-block; }
        .action-btn {
            padding: 6px 8px;
            border-radius: 6px;
            border: none;
            background: transparent;
            cursor: pointer;
            color: #6b7280;
            transition: background 0.12s;
        }
        .action-btn:hover { background: #f3f4f6; color: #111827; }
        .action-menu {
            position: absolute;
            right: 0;
            top: calc(100% + 4px);
            z-index: 9999;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            min-width: 200px;
        }
        .action-menu ul { list-style: none; padding: 8px; margin: 0; }
        .action-menu ul li a,
        .action-menu ul li button {
            display: inline-flex;
            align-items: center;
            width: 100%;
            padding: 8px 8px;
            font-size: 13px;
            font-weight: 500;
            color: #374151;
            border-radius: 6px;
            border: none;
            background: none;
            text-align: left;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.1s;
        }
        .action-menu ul li a:hover,
        .action-menu ul li button:hover { background: #f3f4f6; }
        .action-menu ul li button.text-red-600 { color: #dc2626; }
        .action-menu ul li button.text-red-600:hover { background: #fef2f2; }
        .action-menu ul li a i,
        .action-menu ul li button i { width: 16px; margin-right: 8px; }

        .tbl-th { padding: 12px 16px; font-size: 13px; font-weight: 600; color: #374151; text-align: left; }
        .tbl-td { padding: 14px 16px; font-size: 13.5px; color: #334155; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
        tr:hover td { background: #f9fafb; }
        tr:last-child td { border-bottom: none; }

        .badge { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 500; }
        .badge-active    { background: #dcfce7; color: #16a34a; }
        .badge-pending   { background: #fef9c3; color: #b45309; }
        .badge-draft     { background: #f1f5f9; color: #64748b; }
        .badge-stopped   { background: #fee2e2; color: #dc2626; }
        .badge-completed { background: #ede9fe; color: #7c3aed; }

        .prio-high   { color: #dc2626; font-weight: 700; font-size: 13px; }
        .prio-medium { color: #d97706; font-weight: 700; font-size: 13px; }
        .prio-low    { color: #64748b; font-weight: 600; font-size: 13px; }

        .media-thumb {
            width: 50px; height: 50px; background: #f1f5f9; border-radius: 8px;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            font-size: 10px; color: #94a3b8; gap: 2px;
        }
        .media-thumb i { font-size: 15px; color: #cbd5e1; }

        .custom-select-trigger {
            width: 100%; display: flex; align-items: center; justify-content: space-between;
            padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 10px;
            font-size: 13.5px; color: #334155; background: #fff; cursor: pointer;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .custom-select-trigger.open { border-color: #0DA2E7; box-shadow: 0 0 0 3px rgba(13,162,231,0.12); }

        .dropdown-option { width: 100%; padding: 9px 12px; font-size: 13px; color: #475569; border-radius: 8px; border: none; background: none; text-align: left; cursor: pointer; transition: background 0.1s; display: block; }
        .dropdown-option:hover { background: rgba(13,162,231,0.06); }
        .dropdown-option.selected { background: rgba(13,162,231,0.12); color: #0DA2E7; font-weight: 600; }
    </style>

    <!-- ── Header ── -->
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-800 tracking-tight">Ad Management</h1>
            <p class="text-slate-400 text-sm mt-1">Create, schedule, and monitor ads for targeted audience engagement.</p>
        </div>
        <a href="{{ route('healthcare.ads.ad-management.create-ad') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-semibold transition-all duration-150 active:scale-[0.98]"
           style="background:var(--button-color);box-shadow:0 4px 14px rgba(13,162,231,0.3);"
           onmouseover="this.style.background:var(--button-hover);this.style.boxShadow='0 6px 18px rgba(13,162,231,0.45)';" onmouseout="this.style.background:var(--button-color);this.style.boxShadow='0 4px 14px rgba(13,162,231,0.35)';">
            <i class="fas fa-plus text-xs"></i> Create Ad
        </a>
    </div>

    <!-- ── Filter Bar ── -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 mb-5">
        <div class="flex flex-wrap items-center gap-3">

            <!-- Search + Reset -->
            <div class="flex items-center gap-2 flex-1 min-w-0" style="min-width: 200px;">
                <div class="relative flex-1 min-w-0 max-w-md">
                    <i class="fas fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                    <input type="text" placeholder="Search by title..."
                           wire:model.live.debounce.300ms="search"
                           class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg text-sm text-slate-700 bg-white outline-none transition-all"
                           onfocus="this.style.borderColor:var(--button-color);this.style.boxShadow='0 0 0 3px rgba(13,162,231,0.12)';"
                           onblur="this.style.borderColor='';this.style.boxShadow='';">
                </div>
                <button type="button" wire:click="resetFilters"
                        class="filter-btn shrink-0 flex items-center gap-1.5" title="Reset all filters">
                    <i class="fas fa-rotate-left text-xs"></i>
                    <span>Reset</span>
                </button>
            </div>

            <!-- Hospital Dropdown -->
            @php
                $hospitalLabel = 'All Hospitals';
                if ($hospitalFilter !== '') {
                    $hSel = $hospitals->firstWhere('id', (int) $hospitalFilter);
                    if ($hSel) $hospitalLabel = $hSel->name;
                }
            @endphp
            <div x-data="{open:false}" @click.away="open=false" class="relative">
                <button type="button" @click="open=!open" class="filter-btn" :class="open?'!border-[var(--button-color)] !text-[var(--button-color)]':''">
                    <i class="fas fa-map-marker-alt text-xs text-gray-700"></i>
                    <span>{{ $hospitalLabel }}</span>
                    <i class="fa-solid fa-angle-down text-xs text-slate-400 transition-transform duration-200" :class="{'rotate-180':open}"></i>
                </button>
                <div x-show="open" x-cloak @click.stop
                     x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                     class="absolute left-0 top-full mt-1 z-[9999] w-52 bg-white border border-slate-200 rounded-xl shadow-2xl p-2 space-y-0.5">
                    <button type="button" wire:click="$set('hospitalFilter', '')" @click="open=false"
                            class="w-full text-left px-3 py-2 text-sm rounded-lg transition-colors duration-100 {{ $hospitalFilter === '' ? '!bg-sky-50 !text-[#0DA2E7] font-semibold' : 'text-gray-600' }}">All Hospitals</button>
                    @foreach($hospitals as $h)
                        <button type="button" wire:click="$set('hospitalFilter', '{{ $h->id }}')" @click="open=false"
                                class="w-full text-left px-3 py-2 text-sm rounded-lg transition-colors duration-100 {{ $hospitalFilter == $h->id ? '!bg-sky-50 !text-[#0DA2E7] font-semibold' : 'text-gray-600' }}">{{ $h->name }}</button>
                    @endforeach
                </div>
            </div>

            <!-- Status Dropdown -->
            @php $statusOpts = ['' => 'All Status', 'active' => 'Active', 'pending' => 'Pending', 'draft' => 'Draft', 'stopped' => 'Stopped', 'completed' => 'Completed']; @endphp
            <div x-data="{open:false}" @click.away="open=false" class="relative">
                <button type="button" @click="open=!open" class="filter-btn" :class="open?'!border-[var(--button-color)] !text-[var(--button-color)]':''">
                    <i class="fas fa-toggle-on text-xs text-gray-700"></i>
                    <span>{{ $statusOpts[$statusFilter] ?? 'All Status' }}</span>
                    <i class="fa-solid fa-angle-down text-xs text-slate-400 transition-transform duration-200" :class="{'rotate-180':open}"></i>
                </button>
                <div x-show="open" x-cloak @click.stop
                     x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                     class="absolute left-0 top-full mt-1 z-[9999] w-44 bg-white border border-slate-200 rounded-xl shadow-2xl p-2 space-y-0.5">
                    @foreach($statusOpts as $val => $label)
                        <button type="button" wire:click="$set('statusFilter', '{{ $val }}')" @click="open=false"
                                class="w-full text-left px-3 py-2 text-sm rounded-lg transition-colors duration-100"
                                style="{{ $statusFilter === $val ? 'background:rgba(13,162,231,0.12);color:var(--button-color);font-weight:600;' : 'color:#475569;' }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            <!-- Date -->
            <div class="flex items-center gap-2">
                <input type="date" wire:model.live="dateFilter" class="filter-btn shrink-0" style="color-scheme:light;">
                @if($dateFilter !== '')
                    <button type="button" wire:click="$set('dateFilter', '')" class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600" title="Clear date">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                @endif
            </div>

            <!-- Priority Dropdown -->
            @php
                $prioOpts = ['' => 'Any Priority', 'high' => 'High', 'medium' => 'Medium', 'low' => 'Low'];
                $priorityFilterStr = (string) $priorityFilter;
            @endphp
            <div x-data="{open:false}" @click.away="open=false" class="relative">
                <button type="button" @click="open=!open" class="filter-btn" :class="open?'!border-[var(--button-color)] !text-[var(--button-color)]':''">
                    <i class="fas fa-flag text-xs text-gray-700"></i>
                    <span>{{ $prioOpts[$priorityFilterStr] ?? 'Any Priority' }}</span>
                    <i class="fa-solid fa-angle-down text-xs text-slate-400 transition-transform duration-200" :class="{'rotate-180':open}"></i>
                </button>
                <div x-show="open" x-cloak @click.stop
                     x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                     class="absolute left-0 top-full mt-1 z-[9999] w-40 bg-white border border-slate-200 rounded-xl shadow-2xl p-2 space-y-0.5">
                    @foreach($prioOpts as $val => $label)
                        <button type="button" wire:click="$set('priorityFilter', '{{ $val }}')" @click="open=false"
                                class="w-full text-left px-3 py-2 text-sm rounded-lg transition-colors duration-100 {{ $priorityFilterStr === (string)$val ? '!bg-sky-50 !text-[var(--button-color)] font-semibold' : 'text-gray-600' }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            <!-- Export -->
            <div class="ml-auto flex items-center gap-2">
                <div x-data="{open:false,val:'PNG',opts:['PNG','CSV','PDF']}" @click.away="open=false" class="relative">
                    <button @click="open=!open" class="filter-btn" :class="open?'!border-[var(--button-color)] !text-[var(--button-color)]':''">
                        <span x-text="val"></span>
                        <i class="fa-solid fa-angle-down text-xs text-slate-400 transition-transform duration-200" :class="{'rotate-180':open}"></i>
                    </button>
                    <div x-show="open" x-cloak @click.stop
                         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                         class="absolute right-0 top-full mt-1 z-[9999] w-28 bg-white border border-slate-200 rounded-xl shadow-2xl p-2 space-y-0.5">
                        <template x-for="o in opts" :key="o">
                            <button @click="val=o;open=false" x-text="o"
                                    class="w-full text-left px-3 py-2 text-sm rounded-lg transition-colors duration-100"
                                    :style="val===o?'background:rgba(13,162,231,0.12);color:var(--button-color);font-weight:600;':'color:#475569;'"
                                    onmouseover="if(!this.style.background.includes('0.12'))this.style.background='rgba(13,162,231,0.06)'"
                                    onmouseout="if(!this.style.background.includes('0.12'))this.style.background=''"></button>
                        </template>
                    </div>
                </div>
                <button class="filter-btn" style="color:var(--button-color);border-color:rgba(13,162,231,0.3);">
                    <i class="fas fa-download text-xs"></i> Export
                </button>
            </div>

        </div>
    </div>

    <!-- ── Table Card ── (Alpine state so action menu works after Livewire navigate from edit) ── -->
    <div class="bg-white rounded-lg shadow-md p-6 border"
         x-data="{ openMenuId: null }"
         @click.outside="openMenuId = null">

        <table class="w-full border-collapse shadow-md rounded-lg">
            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="tbl-th">Ad Preview</th>
                    <th class="tbl-th">Title</th>
                    <th class="tbl-th">Hospital</th>
                    <th class="tbl-th">Schedule</th>
                    <th class="tbl-th">Priority</th>
                    <th class="tbl-th">Engagement</th>
                    <th class="tbl-th">Status</th>
                    <th class="tbl-th">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">
                @forelse($ads as $ad)
                    @php
                        $mediaType = strtolower($ad->media_type ?? 'image');
                        $prioLabel = \App\Livewire\HospitalAdmin\Ads\AdManagement::priorityLabel((int) ($ad->priority ?? 50));
                        $prioClass = $prioLabel === 'High' ? 'prio-high' : ($prioLabel === 'Medium' ? 'prio-medium' : 'prio-low');
                        $badgeClass = 'badge-' . ($ad->status ?? 'draft');
                        $mediaUrl = asset('storage/' . $ad->media_url);
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="tbl-td">
                            <div class="media-thumb">
                                @if($mediaType === 'video')
                                    <a href="{{ $mediaUrl }}" target="_blank">
                                        <i class="fas fa-video"></i>
                                        <span>Video</span>
                                    </a>
                                @else
                                    <a href="{{ $mediaUrl }}" target="_blank">
                                        <i class="fas fa-image"></i>
                                        <span>Image</span>
                                    </a>
                                @endif
                            </div>
                        </td>
                        <td class="tbl-td">
                            <p class="font-semibold text-slate-800 leading-tight">{{ $ad->title }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">ID: {{ $ad->id }}</p>
                        </td>
                        <td class="tbl-td text-sm">{{ $ad->hospital?->name ?? '—' }}</td>
                        <td class="tbl-td text-xs text-slate-500 leading-relaxed">
                            @if($ad->start_date && $ad->end_date)
                                {{ $ad->start_date->format('M j, Y') }}<br>to {{ $ad->end_date->format('M j, Y') }}
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="tbl-td"><span class="{{ $prioClass }}">{{ $ad->priority_type }}</span></td>
                        <td class="tbl-td">
                            <div class="flex gap-4">
                                <div><p class="text-xs text-gray-500">Impressions</p><p class="font-bold text-gray-900 text-sm">{{ \App\Livewire\HospitalAdmin\Ads\AdManagement::formatCount($ad->impressions_count ?? 0) }}</p></div>
                                <div><p class="text-xs text-gray-500">Clicks</p><p class="font-bold text-gray-900 text-sm">{{ \App\Livewire\HospitalAdmin\Ads\AdManagement::formatCount($ad->clicks_count ?? 0) }}</p></div>
                            </div>
                        </td>
                        <td class="tbl-td">
                            <span class="badge {{ $badgeClass }}">{{ ucfirst($ad->status ?? 'draft') }}</span>
                        </td>

                        <!-- Actions (Alpine: works after return from edit page) -->
                        <td class="tbl-td" @click.stop>
                            <div class="action-menu-wrapper">
                                <button type="button" class="action-btn"
                                        @click="openMenuId = openMenuId === {{ $ad->id }} ? null : {{ $ad->id }}; if (openMenuId === {{ $ad->id }}) { $nextTick(() => document.getElementById('ad-menu-{{ $ad->id }}')?.classList.remove('hidden')) }">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>

                                <div id="ad-menu-{{ $ad->id }}"
                                     x-show="openMenuId === {{ $ad->id }}"
                                     x-cloak
                                     x-transition:enter="transition ease-out duration-150"
                                     x-transition:enter-start="opacity-0 -translate-y-1"
                                     x-transition:enter-end="opacity-100 translate-y-0"
                                     x-transition:leave="transition ease-in duration-100"
                                     x-transition:leave-start="opacity-100"
                                     x-transition:leave-end="opacity-0"
                                     class="action-menu">
                                    <ul class="p-2 text-sm text-gray-700 font-medium">
                                        <li>
                                            <a href="{{ route('healthcare.ads.ad-management.index') }}"
                                               @click="openMenuId = null"
                                               class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                <i class="fa-regular fa-eye w-4 mr-2"></i> View Engagement
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{ route('healthcare.ads.ad-management.edit-ad', ['id' => $ad->id]) }}"
                                               @click="openMenuId = null"
                                               class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                <i class="fa-regular fa-pen-to-square w-4 mr-2"></i> Edit Ad
                                            </a>
                                        </li>
                                        @if(($ad->status ?? '') === 'active')
                                            <li>
                                                <button type="button"
                                                        @click="openMenuId = null"
                                                        wire:click="stopAd({{ $ad->id }})"
                                                        class="inline-flex items-center w-full p-2 text-red-600 hover:bg-red-50 rounded">
                                                    <i class="fa-regular fa-trash-can w-4 mr-2"></i> Stop Ad
                                                </button>
                                            </li>
                                        @endif
                                        <li>
                                            <button type="button"
                                                    @click="openMenuId = null"
                                                    wire:click="openUpdateStatusModal({{ $ad->id }})"
                                                    class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                <i class="fa-solid fa-file-lines w-4 mr-2"></i> Update Status
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="tbl-td text-center py-12 text-slate-500">
                            <i class="fas fa-ad text-4xl text-slate-300 mb-3 block"></i>
                            <p class="font-medium">No ads found</p>
                            <p class="text-sm mt-1">Create your first ad or adjust filters.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Pagination -->
        @if($ads->hasPages())
            <div class="mt-4 flex items-center justify-between">
                <p class="text-xs text-slate-400">
                    Showing {{ $ads->firstItem() }} to {{ $ads->lastItem() }} of {{ $ads->total() }} ads
                </p>
                <div>{{ $ads->links() }}</div>
            </div>
        @elseif($ads->total() > 0)
            <div class="mt-4">
                <p class="text-xs text-slate-400">Showing {{ $ads->total() }} {{ Str::plural('ad', $ads->total()) }}</p>
            </div>
        @endif

    </div>

    <!-- Hidden trigger for Update Status modal (opened via Livewire dispatch) -->
    <div class="hidden">
        <flux:modal.trigger name="update-ad-status">
            <button type="button" id="btn-open-update-ad-status">Open</button>
        </flux:modal.trigger>
    </div>

    <!-- ── Update Status Modal ── -->
    <flux:modal name="update-ad-status" class="p-0 !overflow-visible" wire:close="closeUpdateStatusModal" id="delete-org">
        <div class="p-4 overflow-visible">
            <flux:modal.close class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 cursor-pointer z-10" wire:click="closeUpdateStatusModal" />
            <h2 class="text-lg font-semibold text-gray-900 mb-2 pr-8">Update Ad Status</h2>
            <p class="text-sm text-gray-500 mb-4">Select the new status for this ad.</p>

            @php $statusOptions = ['active' => 'Active', 'pending' => 'Pending', 'draft' => 'Draft', 'stopped' => 'Stopped', 'completed' => 'Completed']; @endphp

            <div class="mb-6"
                 x-data="{ open: false }"
                 @click.outside="open = false"
                 style="position: relative;">

                <button type="button"
                        @click="open = !open"
                        class="custom-select-trigger"
                        :class="open ? 'open' : ''">
                    <span class="{{ $updateStatusNewStatus ? 'text-slate-700' : 'text-slate-400' }}">
                        {{ $statusOptions[$updateStatusNewStatus] ?? 'Select status...' }}
                    </span>
                    <i class="fas fa-chevron-down text-xs text-slate-400 transition-transform duration-200"
                       :class="{ 'rotate-180': open }"></i>
                </button>

                <div x-show="open"
                     x-cloak
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     style="
                         position: absolute;
                         top: calc(100% + 4px);
                         left: 0; right: 0;
                         background: #fff;
                         border: 1px solid #e2e8f0;
                         border-radius: 12px;
                         box-shadow: 0 8px 30px rgba(0,0,0,0.12);
                         padding: 6px;
                         z-index: 99999;
                     ">
                    @foreach($statusOptions as $val => $label)
                        <button type="button"
                                wire:click="$set('updateStatusNewStatus', '{{ $val }}')"
                                @click="open = false"
                                class="dropdown-option {{ $updateStatusNewStatus === $val ? 'selected' : '' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" wire:click="closeUpdateStatusModal"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition"
                        style="background:#6b7280; color:#fff;">Cancel</button>
                <button type="button" wire:click="updateAdStatus" wire:loading.attr="disabled"
                        class="px-4 py-2 rounded-lg text-sm font-medium text-white transition disabled:opacity-50"
                        style="background:#0DA2E7;">
                    <span wire:loading.remove wire:target="updateAdStatus">Update Status</span>
                    <span wire:loading wire:target="updateAdStatus">Updating...</span>
                </button>
            </div>
        </div>
    </flux:modal>

</div>

@script
<script>
    // Open Update Status modal when Livewire dispatches (after openUpdateStatusModal)
    Livewire.on('open-modal', function(name) {
        if (name === 'update-ad-status') {
            var btn = document.getElementById('btn-open-update-ad-status');
            if (btn) btn.click();
        }
    });
</script>
@endscript


