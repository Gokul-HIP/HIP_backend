<div style="padding: 28px 32px;">
<style>
@import '../../../assets/doctor-prescription.css';

/* ── Breadcrumb ── */
.sp-breadcrumb {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: #9ca3af;
    margin-bottom: 18px;
}
.sp-breadcrumb a {
    color: #9ca3af;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: color 0.15s;
}
.sp-breadcrumb a:hover { color: #374151; }
.sp-breadcrumb .sep { color: #d1d5db; }
.sp-breadcrumb .current { color: #c8102e; font-weight: 600; }

/* ── Page Header ── */
.sp-page-header { margin-bottom: 24px; }
.sp-page-header h1 {
    font-size: 26px;
    font-weight: 700;
    color: #1a1a2e;
    line-height: 1.2;
}
.sp-page-header p {
    font-size: 13.5px;
    color: #6b7280;
    margin-top: 4px;
}

/* ── Stat Cards ── */
.sp-stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
.sp-stat-card {
    background: #fff;
    border: 1px solid #e8ecf0;
    border-radius: 14px;
    padding: 18px 20px;
    display: flex;
    align-items: center;
    gap: 14px;
}
.sp-stat-icon {
    width: 42px; height: 42px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}
.sp-icon-red    { background: #fff0f2; color: #c8102e; }
.sp-icon-blue   { background: #fff0f2; color: var(--primary-color); }
.sp-icon-orange { background: #fff3e0; color: #e09b1a; }
.sp-icon-green  { background: #ecfdf5; color: #059669; }

.sp-stat-info {}
.sp-stat-label {
    font-size: 12.5px;
    color: #6b7280;
    margin-bottom: 2px;
}
.sp-stat-number {
    font-size: 22px;
    font-weight: 700;
    color: #1a1a2e;
    line-height: 1.1;
}

/* ── Filter Panel ── */
.sp-filter-panel {
    background: #fff;
    border: 1px solid #e8ecf0;
    border-radius: 14px;
    padding: 20px 22px;
    margin-bottom: 20px;
}
.sp-filter-row {
    display: flex;
    align-items: flex-end;
    gap: 14px;
    flex-wrap: wrap;
}
.sp-filter-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.sp-filter-group.grow { flex: 1; min-width: 220px; }
.sp-filter-label {
    font-size: 11.5px;
    font-weight: 600;
    color: #6b7280;
    letter-spacing: 0.03em;
}

/* Search input */
.sp-search {
    display: flex;
    align-items: center;
    gap: 9px;
    border: 1.5px solid #e8ecf0 !important;
    border-radius: 10px;
    padding: 9px 14px;
    background: #f9fafb;
    height: 40px;
}
.sp-search:focus-within {
    border-color: #e8ecf0 !important;
    box-shadow: none !important;
}
.sp-search i { color: #9ca3af; font-size: 14px; flex-shrink: 0; }
.sp-search input {
    border: none !important;
    background: transparent;
    outline: none !important;
    box-shadow: none !important;
    font-size: 13.5px;
    color: #374151;
    font-family: inherit;
    width: 100%;
}
.sp-search input:focus,
.sp-search input:focus-visible {
    outline: none !important;
    box-shadow: none !important;
    border: none !important;
}
.sp-search input::placeholder { color: #9ca3af; }

/* Selects */
.sp-select {
    height: 40px;
    border: 1.5px solid #e8ecf0;
    border-radius: 10px;
    padding: 0 32px 0 12px;
    font-size: 13.5px;
    color: #374151;
    background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%239ca3af' d='M6 8L1 3h10z'/%3E%3C/svg%3E") no-repeat right 12px center;
    appearance: none;
    cursor: pointer;
    font-family: inherit;
    outline: none;
    min-width: 160px;
    transition: border-color 0.15s;
}
.sp-select:focus { border-color: var(--primary-color); }

/* Reset button */
.btn-reset-filters {
    display: flex;
    align-items: center;
    gap: 6px;
    border: none;
    background: transparent;
    color: #c8102e;
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    padding: 0 4px;
    height: 40px;
    white-space: nowrap;
    transition: color 0.15s;
}
.btn-reset-filters:hover { color: #a80e26; }
.btn-reset-filters i { font-size: 13px; }

/* Active filter tags */
.sp-filter-tags {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 14px;
    padding-top: 14px;
    border-top: 1px solid #f0f4f8;
}
.sp-filter-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f4f6f9;
    border: 1px solid #e8ecf0;
    border-radius: 20px;
    padding: 4px 12px;
    font-size: 12.5px;
    color: #374151;
    font-weight: 500;
}
.sp-filter-tag button {
    border: none;
    background: transparent;
    color: #9ca3af;
    cursor: pointer;
    font-size: 12px;
    padding: 0;
    line-height: 1;
    display: flex;
    align-items: center;
    transition: color 0.12s;
}
.sp-filter-tag button:hover { color: #c8102e; }

/* ── Patients Table ── */
.sp-table-wrap {
    background: #fff;
    border: 1px solid #e8ecf0;
    border-radius: 14px;
    overflow: hidden;
}
.sp-table {
    width: 100%;
    border-collapse: collapse;
}
.sp-table thead tr { background: #f8fafc; }
.sp-table th {
    padding: 12px 20px;
    font-size: 11.5px;
    font-weight: 700;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    text-align: left;
    border-bottom: 1px solid #f0f4f8;
    white-space: nowrap;
}
.sp-table td {
    padding: 16px 20px;
    font-size: 13.5px;
    color: #374151;
    border-bottom: 1px solid #f0f4f8;
    vertical-align: middle;
}
.sp-table tbody tr:last-child td { border-bottom: none; }
.sp-table tbody tr:hover td { background: #fafbfc; }

/* Patient cell */
.sp-patient-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}
.sp-patient-init {
    width: 36px; height: 36px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px;
    font-weight: 700;
    color: #fff;
    flex-shrink: 0;
    object-fit: cover;
}
.sp-patient-init.is-hidden { display: none; }
.sp-avatar-slot {
    position: relative;
    width: 36px;
    height: 36px;
    flex-shrink: 0;
}
.sp-patient-name { font-weight: 600; color: #1a1a2e; }

/* Visit type badge */
.sp-visit-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
.visit-follow-up { background: #f0f9ff; color: #0369a1; }
.visit-new       { background: #ecfdf5; color: #059669; }
.visit-online    { background: #fff0f2; color: var(--primary-color); }
.visit-review    { background: #fdf4ff; color: #7e22ce; }

/* Action button */
.btn-sp-action {
    width: 34px; height: 34px;
    display: flex; align-items: center; justify-content: center;
    border: 1.5px solid #e8ecf0;
    border-radius: 9px;
    background: #fff;
    color: #6b7280;
    cursor: pointer;
    transition: border-color 0.15s, color 0.15s, background 0.15s;
    text-decoration: none;
}
.btn-sp-action:hover {
    border-color: #c8102e;
    color: #c8102e;
    background: #fff0f2;
}

@media (max-width: 1024px) {
    .sp-stats-row { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
    .sp-stats-row { grid-template-columns: 1fr 1fr; }
    .sp-filter-row { flex-direction: column; align-items: stretch; }
    .sp-select { min-width: unset; width: 100%; }
}
</style>

{{-- ── Breadcrumb ── --}}
{{-- <div class="sp-breadcrumb">
    <a href="{{ route('doctor.dashboard.index') }}">
        <i class="fas fa-chevron-left" style="font-size:10px;"></i> Back
    </a>
    <span class="sep">/</span>
    <span class="current">Upload Prescription</span>
</div> --}}

{{-- ── Page Header ── --}}
<div class="sp-page-header">
    <h1>Select Patients</h1>
    <p>Manage and access your patient records securely.</p>
</div>

{{-- ── Stat Cards ── --}}
<div class="sp-stats-row">
    <div class="sp-stat-card">
        <div class="sp-stat-icon sp-icon-red"><i class="fas fa-user-injured"></i></div>
        <div class="sp-stat-info">
            <div class="sp-stat-label">My Patients</div>
            <div class="sp-stat-number">{{ $myPatientsCount }}</div>
        </div>
    </div>
    <div class="sp-stat-card">
        <div class="sp-stat-icon sp-icon-blue"><i class="fas fa-calendar-day"></i></div>
        <div class="sp-stat-info">
            <div class="sp-stat-label">Today's Patients</div>
            <div class="sp-stat-number">{{ $todayPatientsCount }}</div>
        </div>
    </div>
    <div class="sp-stat-card">
        <div class="sp-stat-icon sp-icon-orange"><i class="fas fa-history"></i></div>
        <div class="sp-stat-info">
            <div class="sp-stat-label">Follow-up Patients</div>
            <div class="sp-stat-number">{{ $followUpCount }}</div>
        </div>
    </div>
    <div class="sp-stat-card">
        <div class="sp-stat-icon sp-icon-green"><i class="fas fa-user-check"></i></div>
        <div class="sp-stat-info">
            <div class="sp-stat-label">New This Month</div>
            <div class="sp-stat-number">{{ $newThisMonthCount }}</div>
        </div>
    </div>
</div>

{{-- ── Filter Panel ── --}}
<div class="sp-filter-panel">
    <div class="sp-filter-row">
        {{-- Search --}}
        <div class="sp-filter-group grow">
            <div class="sp-filter-label">Search Patient</div>
            <div class="sp-search">
                <i class="fas fa-user-friends"></i>
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Search by name, HIP ID, user ID, person ID, or mobile number...">
            </div>
        </div>

        {{-- Branch --}}
        <div class="sp-filter-group">
            <div class="sp-filter-label">Branch</div>
            <select class="sp-select" wire:model.live="branchFilter">
                <option value="all">All Branches</option>
                @foreach($branches ?? [] as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Visit Type --}}
        <div class="sp-filter-group">
            <div class="sp-filter-label">Visit Type</div>
            <select class="sp-select" wire:model.live="visitTypeFilter">
                <option value="all">All Types</option>
                <option value="new">New</option>
                <option value="follow-up">Follow-up</option>
                <option value="online">Online</option>
            </select>
        </div>

        {{-- Status --}}
        <div class="sp-filter-group">
            <div class="sp-filter-label">Status</div>
            <select class="sp-select" wire:model.live="statusFilter">
                <option value="all">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>

        {{-- Reset --}}
        <button type="button" class="btn-reset-filters" wire:click="resetFilters">
            <i class="fas fa-redo-alt"></i> Reset Filters
        </button>
    </div>

    {{-- Active filter tags --}}
    @if(!empty($activeFilterTags))
        <div class="sp-filter-tags">
            @foreach($activeFilterTags as $tag)
                <span class="sp-filter-tag">
                    {{ $tag['label'] }}
                    <button type="button" wire:click="removeTag('{{ $tag['key'] }}')">
                        <i class="fas fa-times"></i>
                    </button>
                </span>
            @endforeach
        </div>
    @endif
</div>

{{-- ── Patients Table ── --}}
<div class="sp-table-wrap">
    <table class="sp-table">
        <thead>
            <tr>
                <th>Patient</th>
                <th>UHID</th>
                <th>Age / Gender</th>
                <th>Mobile</th>
                <th>Last Visit</th>
                <th>Next Appt.</th>
                <th>Visit Type</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($patients as $patient)
                <tr>
                    <td>
                        <div class="sp-patient-cell">
                            <div class="sp-avatar-slot">
                                @if(!empty($patient['avatar_url']))
                                    <img src="{{ $patient['avatar_url'] }}"
                                         alt="{{ $patient['name'] }}"
                                         class="sp-patient-init"
                                         onerror="spAvatarError(this)">
                                    <div class="sp-patient-init is-hidden"
                                         style="background: {{ $patient['avatar_color'] }};"
                                         data-fallback>{{ $patient['initials'] }}</div>
                                @else
                                    <div class="sp-patient-init"
                                         style="background: {{ $patient['avatar_color'] }};">
                                        {{ $patient['initials'] }}
                                    </div>
                                @endif
                            </div>
                            <span class="sp-patient-name">{{ $patient['name'] }}</span>
                        </div>
                    </td>
                    <td>{{ $patient['uhid'] }}</td>
                    <td>{{ $patient['age'] ?? '—' }} / {{ $patient['gender'] }}</td>
                    <td>{{ $patient['mobile'] }}</td>
                    <td>{{ $patient['last_visit'] }}</td>
                    <td>{{ $patient['next_appointment'] }}</td>
                    <td>
                        <span class="sp-visit-badge {{ $patient['visit_type_class'] }}">
                            {{ $patient['visit_type'] }}
                        </span>
                    </td>
                    <td>
                        @if($patient['booking_id'])
                            <a href="{{ route("doctor.upload-prescription.create-prescription.index", ["patient_id" => $patient['booking_id']]) }}" type="button"
                                class="btn-sp-action"
                                title="Upload Prescription">
                                <i class="fa-solid fa-clipboard-list"></i>
                        </a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center; color:#9ca3af; padding:40px 20px;">
                        No patients found for the selected filters.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($patients->total() > 0)
        <x-doctor.pagination-footer :paginator="$patients" label="patients" />
    @endif
</div>

<script>
    function spAvatarError(img) {
        img.style.display = 'none';
        const fallback = img.parentElement?.querySelector('[data-fallback]');
        if (fallback) {
            fallback.classList.remove('is-hidden');
        }
    }
</script>

</div>