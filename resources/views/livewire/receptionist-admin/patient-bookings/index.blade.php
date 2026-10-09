<div class="pb-page">
    <style>
        .pb-page{display:flex;flex-direction:column;gap:12px;padding:4px 0;color:var(--text-color)}
        .pb-header{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
        .pb-title{margin:0;color:var(--text-color);font-size:24px;font-weight:700}
        .pb-subtitle{margin:4px 0 0;color:var(--muted-color);font-size:14px}
        .pb-count{color:var(--muted-color);font-size:13px;font-weight:600;white-space:nowrap}
        .pb-type-filter{display:flex;align-items:center;gap:8px;max-width:100%;overflow-x:auto;scrollbar-width:thin}
        .pb-type-filter-button{flex:0 0 auto;height:36px;padding:0 14px;border:1px solid var(--border-color);border-radius:999px;background:var(--card-bg);color:var(--muted-color);font-size:13px;font-weight:600;white-space:nowrap;cursor:pointer;transition:background-color .15s,border-color .15s,color .15s}
        .pb-type-filter-button:hover{background:var(--primary-hover)}
        .pb-type-filter-button.is-all-active{border-color:var(--button-color);background:var(--button-color);color:var(--card-bg)}
        .pb-type-filter-button.is-doctor-active{border-color:var(--primary-color);background:var(--primary-hover);color:var(--primary-color)}
        .pb-type-filter-button.is-second-active{border-color:var(--info-color);background:var(--info-soft);color:var(--info-color)}
        .pb-type-filter-button.is-diagnostic-active{border-color:var(--success-color);background:var(--success-soft);color:var(--success-color)}
        .pb-filter-card{display:flex;align-items:center;flex-wrap:wrap;gap:12px;padding:14px 16px;border:1px solid var(--border-color);border-radius:var(--radius-card);background:var(--card-bg);box-shadow:var(--shadow-card)}
        .pb-search-wrap{position:relative;flex:1 1 280px;min-width:0}
        .pb-search-icon{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--muted-color);pointer-events:none}
        .pb-control{box-sizing:border-box;width:100%;height:42px;padding:0 12px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-color);font:inherit;font-size:14px;outline:none!important;transition:border-color .15s,box-shadow .15s}
        .pb-search-wrap .pb-control{padding-left:38px}
        .pb-control:focus{border-color:var(--primary-color)!important;box-shadow:0 0 0 3px color-mix(in srgb,var(--primary-color) 18%,transparent)!important;outline:none!important}
        .pb-hospital-control{flex:0 0 200px;width:200px}
        .pb-status-control{flex:0 0 180px;width:180px}
        .pb-date-control{position:relative;flex:0 0 168px;width:168px}
        .pb-date-control .pb-control{padding-right:38px}
        .pb-date-clear{position:absolute;right:6px;top:6px;width:30px;height:30px;border:0;border-radius:8px;background:transparent;color:var(--muted-color);font-size:18px;line-height:1;cursor:pointer}
        .pb-date-clear:hover{background:var(--primary-hover);color:var(--text-color)}
        .pb-reset{display:inline-flex;align-items:center;justify-content:center;flex:0 0 auto;height:42px;min-width:76px;padding:0 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-color);font-size:14px;font-weight:600;cursor:pointer;transition:background-color .15s,border-color .15s}
        .pb-reset:hover{background:var(--primary-hover)}
        .pb-dropdown{position:relative;flex:0 0 200px}
        .pb-dropdown.pb-status-dropdown{flex-basis:180px}
        .pb-dropdown-trigger{display:flex;align-items:center;justify-content:space-between;gap:10px;width:100%;height:42px;padding:0 12px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-color);font:inherit;font-size:14px;text-align:left;cursor:pointer;outline:none!important}
        .pb-dropdown-trigger:focus-visible{border-color:var(--primary-color)!important;box-shadow:0 0 0 3px color-mix(in srgb,var(--primary-color) 18%,transparent)!important;outline:none!important}
        .pb-dropdown-menu{position:absolute;top:calc(100% + 5px);left:0;z-index:30;width:100%;max-height:240px;overflow-y:auto;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);box-shadow:var(--shadow-card)}
        .pb-dropdown-option{display:block;width:100%;padding:10px 12px;border:0;background:transparent;color:var(--text-color);font:inherit;font-size:14px;text-align:left;cursor:pointer}
        .pb-dropdown-option:hover,.pb-dropdown-option.is-selected{background:var(--primary-hover)}
        .pb-table-card{overflow:visible;border:1px solid var(--border-color);border-radius:var(--radius-card);background:var(--card-bg);box-shadow:var(--shadow-card)}
        .pb-table-wrap{overflow-x:auto}
        .pb-table{width:100%;min-width:960px;border-collapse:separate;border-spacing:0;color:var(--text-color);font-size:14px}
        .pb-table thead{background:var(--primary-hover)}
        .pb-table th{height:44px;padding:0 14px;color:var(--muted-color);font-size:11px;font-weight:700;letter-spacing:.06em;text-align:left;text-transform:uppercase;white-space:nowrap}
        .pb-table td{height:64px;padding:10px 14px;border-top:1px solid var(--border-color);vertical-align:middle}
        .pb-row{background:var(--card-bg);transition:background-color .15s}
        .pb-row:hover{background:var(--page-bg)}
        .pb-nowrap{white-space:nowrap;font-variant-numeric:tabular-nums}
        .pb-member-id{font-weight:600}
        .pb-last-booking{color:var(--muted-color)}
        .pb-empty-value{color:var(--muted-color)}
        .pb-booking-types{display:flex;flex-wrap:wrap;gap:5px}
        .pb-type-pill{display:inline-flex;align-items:center;padding:4px 10px;border-radius:999px;font-size:11.5px;font-weight:600;line-height:1.2;white-space:nowrap}
        .pb-type-doctor{background:var(--primary-hover);color:var(--primary-color)}
        .pb-type-second{background:var(--info-soft);color:var(--info-color)}
        .pb-type-diagnostic{background:var(--success-soft);color:var(--success-color)}
        .pb-type-other{background:var(--page-bg);color:var(--muted-color)}
        .pb-booking-count{color:var(--text-color);font-weight:700}
        .pb-status-pill{display:inline-flex;align-items:center;gap:7px;padding:5px 10px;border-radius:999px;font-size:12px;font-weight:600;line-height:1.2;white-space:nowrap}
        .pb-status-dot{width:6px;height:6px;border-radius:50%;background:currentColor}
        .pb-status-doctor{background:var(--primary-hover);color:var(--primary-color)}
        .pb-status-second{background:var(--info-soft);color:var(--info-color)}
        .pb-status-diagnostic{background:var(--success-soft);color:var(--success-color)}
        .pb-status-other{background:var(--page-bg);color:var(--muted-color)}
        .pb-empty-state{padding:36px 16px!important;text-align:center}
        .pb-empty-title{color:var(--text-color);font-size:15px;font-weight:700}
        .pb-empty-copy{margin-top:4px;color:var(--muted-color);font-size:13px}
        .pb-footer{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 16px;border-top:1px solid var(--border-color)}
        .pb-footer-summary{color:var(--muted-color);font-size:13px;white-space:nowrap}
        .pb-pagination{display:flex;min-width:0}
        .pb-pagination .rp-pagination-list{display:flex;align-items:center;justify-content:flex-end;gap:6px;margin:0;padding:0;list-style:none}
        .pb-pagination .rp-page-button{box-sizing:border-box;display:inline-flex;width:36px;height:36px;align-items:center;justify-content:center;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-color);font-size:13px;font-weight:600;text-decoration:none;transition:background-color .15s,border-color .15s,color .15s}
        .pb-pagination .rp-page-button svg{width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
        .pb-pagination .rp-page-button:hover{background:var(--primary-hover)}
        .pb-pagination .rp-page-button.is-active{border-color:var(--button-color);background:var(--button-color);color:var(--card-bg)}
        .pb-pagination .rp-page-button.is-disabled{color:var(--muted-color);cursor:not-allowed;opacity:.55}
        .pb-pagination .rp-page-button.is-disabled:hover{background:var(--card-bg)}
        .pb-pagination .rp-page-gap{display:inline-flex;width:36px;height:36px;align-items:center;justify-content:center;color:var(--muted-color);font-size:13px}
        @media(max-width:640px){.pb-header{align-items:flex-start}.pb-type-filter{width:100%;overscroll-behavior-x:contain}.pb-filter-card{align-items:stretch}.pb-search-wrap,.pb-hospital-control,.pb-status-control,.pb-date-control,.pb-dropdown,.pb-dropdown.pb-status-dropdown,.pb-reset{flex:1 1 100%;width:100%;min-width:0}.pb-footer{flex-direction:column;justify-content:center}.pb-footer-summary{text-align:center}.pb-pagination .rp-pagination-list{justify-content:center}}
    </style>

    <header class="pb-header">
        <div>
            <h1 class="pb-title">Patient Bookings</h1>
            <p class="pb-subtitle">Patients from doctor, second opinion and diagnostic bookings</p>
        </div>
        <div class="pb-count">{{ $patients->total() }} patients</div>
    </header>

    <div class="pb-type-filter" role="group" aria-label="Filter by booking type">
        <button type="button" wire:click="$set('typeFilter','all')" aria-pressed="{{ $typeFilter === 'all' ? 'true' : 'false' }}" class="pb-type-filter-button {{ $typeFilter === 'all' ? 'is-all-active' : '' }}">All types</button>
        <button type="button" wire:click="$set('typeFilter','doctor')" aria-pressed="{{ $typeFilter === 'doctor' ? 'true' : 'false' }}" class="pb-type-filter-button {{ $typeFilter === 'doctor' ? 'is-doctor-active' : '' }}">Doctor</button>
        <button type="button" wire:click="$set('typeFilter','second_opinion')" aria-pressed="{{ $typeFilter === 'second_opinion' ? 'true' : 'false' }}" class="pb-type-filter-button {{ $typeFilter === 'second_opinion' ? 'is-second-active' : '' }}">Second Opinion</button>
        <button type="button" wire:click="$set('typeFilter','diagnostic')" aria-pressed="{{ $typeFilter === 'diagnostic' ? 'true' : 'false' }}" class="pb-type-filter-button {{ $typeFilter === 'diagnostic' ? 'is-diagnostic-active' : '' }}">Diagnostic</button>
    </div>

    <div class="pb-filter-card">
        <label class="pb-search-wrap">
            <i class="pb-search-icon fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search name, phone, member ID..." aria-label="Search patient bookings" class="pb-control">
        </label>

        <div class="pb-dropdown" x-data="{ open: false }">
            <button type="button" @click="open = !open" class="pb-dropdown-trigger" aria-haspopup="listbox" :aria-expanded="open">
                <span>
                    @php
                        $selectedHospital = collect($hospitals)->firstWhere('id', (int) $hospitalFilter);
                    @endphp
                    {{ $hospitalFilter === 'all' ? 'All Hospitals' : ($selectedHospital?->name ?? 'Hospital') }}
                </span>
                <span aria-hidden="true">⌄</span>
            </button>
            <div x-show="open" x-cloak @click.away="open = false" class="pb-dropdown-menu" role="listbox">
                <button type="button" wire:click="$set('hospitalFilter', 'all')" @click="open = false" class="pb-dropdown-option {{ $hospitalFilter === 'all' ? 'is-selected' : '' }}">All Hospitals</button>
                @foreach ($hospitals as $hospital)
                    <button type="button" wire:click="$set('hospitalFilter', '{{ $hospital->id }}')" @click="open = false" class="pb-dropdown-option {{ (string) $hospitalFilter === (string) $hospital->id ? 'is-selected' : '' }}">{{ $hospital->name }}</button>
                @endforeach
            </div>
        </div>

        <div class="pb-dropdown pb-status-dropdown" x-data="{ open: false }">
            <button type="button" @click="open = !open" class="pb-dropdown-trigger" aria-haspopup="listbox" :aria-expanded="open">
                <span>{{ $statusFilter === 'all' ? 'All Status' : ucfirst($statusFilter) }}</span>
                <span aria-hidden="true">⌄</span>
            </button>
            <div x-show="open" x-cloak @click.away="open = false" class="pb-dropdown-menu" role="listbox">
                <button type="button" wire:click="$set('statusFilter', 'all')" @click="open = false" class="pb-dropdown-option {{ $statusFilter === 'all' ? 'is-selected' : '' }}">All Status</button>
                @foreach ($statusOptions as $status => $label)
                    <button type="button" wire:click="$set('statusFilter', '{{ $status }}')" @click="open = false" class="pb-dropdown-option {{ $statusFilter === $status ? 'is-selected' : '' }}">{{ $label }}</button>
                @endforeach
            </div>
        </div>

        <div class="pb-date-control">
            <input type="date" wire:model.live="dateFilter" aria-label="Filter by date" class="pb-control">
            @if($dateFilter !== '')
                <button type="button" wire:click="$set('dateFilter', '')" class="pb-date-clear" aria-label="Clear date">×</button>
            @endif
        </div>

        <button type="button" wire:click="clearFilters" class="pb-reset">Reset</button>
    </div>

    <div class="pb-table-card">
        <div class="pb-table-wrap">
            <table class="pb-table">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Member ID</th>
                        <th>Phone</th>
                        <th>Hospital</th>
                        <th>Booking types</th>
                        <th>Bookings</th>
                        <th>Last booking</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($patients as $patient)
                        <tr wire:key="rec-patient-{{ $patient['patient_key'] }}" class="pb-row">
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0" style="background:var(--button-color);">
                                        {{ $patient['initials'] }}
                                    </div>
                                    <span class="font-medium">{{ $patient['name'] }}</span>
                                </div>
                            </td>
                            <td class="pb-nowrap pb-member-id">{{ $patient['member_hip_id'] ?: '—' }}</td>
                            <td class="pb-nowrap">{{ $patient['mobile'] ?: '—' }}</td>
                            <td>{{ $patient['hospital_name'] ?: '—' }}</td>
                            <td>
                                <div class="pb-booking-types">
                                    @foreach($patient['booking_types'] as $type)
                                        @php
                                            $typeClass = match (strtolower(trim($type))) {
                                                'doctor' => 'pb-type-doctor',
                                                'second opinion' => 'pb-type-second',
                                                'diagnostic' => 'pb-type-diagnostic',
                                                default => 'pb-type-other',
                                            };
                                        @endphp
                                        <span class="pb-type-pill {{ $typeClass }}">{{ $type }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="pb-nowrap pb-booking-count">{{ $patient['bookings_count'] }}</td>
                            <td class="pb-nowrap pb-last-booking">{{ $patient['last_booking_date'] ?: '—' }}</td>
                            <td>
                                <div class="pb-booking-types">
                                    @foreach($patient['booking_types'] as $type)
                                        @php
                                            $typeKey = match (strtolower(trim($type))) {
                                                'doctor' => 'doctor',
                                                'second opinion' => 'second',
                                                'diagnostic' => 'diagnostic',
                                                default => 'other',
                                            };
                                            $typeLabel = $patient['type_statuses'][$type] ?? null;
                                        @endphp
                                        <span class="pb-status-pill pb-status-{{ $typeKey }}" title="{{ $type }}: {{ $typeLabel ?: '—' }}" aria-label="{{ $type }}: {{ $typeLabel ?: '—' }}">
                                            <span class="pb-status-dot" aria-hidden="true"></span>{{ $typeLabel ?: '—' }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="pb-empty-state">
                                <div class="pb-empty-title">No bookings found</div>
                                <div class="pb-empty-copy">Try changing the filters</div>
                                <button type="button" wire:click="clearFilters" class="pb-reset mt-3">Reset</button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <footer class="pb-footer">
            <div class="pb-footer-summary">Showing {{ $patients->firstItem() ?? 0 }}–{{ $patients->lastItem() ?? 0 }} of {{ $patients->total() }}</div>
            <div class="pb-pagination">{{ $patients->links('pagination.rp') }}</div>
        </footer>
    </div>
</div>
