@if($showAppointmentHistoryModal ?? false)
<div class="ah-overlay" wire:click.self="closeAppointmentHistory">
    <div class="ah-modal" role="dialog" aria-modal="true">

        {{-- ── Header (fixed, never scrolls) ── --}}
        <div class="ah-header">
            <div>
                <h2 class="ah-title">Appointment History</h2>
                <p class="ah-subtitle">View all previous appointments for this patient</p>
            </div>
            <button class="ah-close" wire:click="closeAppointmentHistory" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>

        {{-- ── Scrollable body ── --}}
        <div class="ah-body">

            <div class="ah-body-top">
            {{-- Patient Info --}}
            <div class="ah-patient-card">
                @if(!empty($historyPatient['avatar_url'] ?? null))
                    <img src="{{ $historyPatient['avatar_url'] }}"
                         alt="{{ $historyPatient['name'] ?? '' }}"
                         class="ah-patient-avatar">
                @else
                    <div class="ah-patient-avatar ah-patient-avatar-init"
                         style="background: {{ $historyPatient['avatar_color'] ?? 'linear-gradient(135deg,#0da2e7,#0080c5)' }};">
                        {{ $historyPatient['initials'] ?? strtoupper(substr($historyPatient['name'] ?? 'P', 0, 2)) }}
                    </div>
                @endif

                <div class="ah-patient-fields">
                    <div class="ah-patient-col">
                        <div class="ah-field-label">Patient Name</div>
                        <div class="ah-field-value">{{ $historyPatient['name'] ?? '—' }}</div>
                        <div class="ah-field-label" style="margin-top:10px;">Mobile</div>
                        <div class="ah-field-value">{{ $historyPatient['mobile'] ?? '—' }}</div>
                    </div>
                    <div class="ah-patient-col">
                        <div class="ah-field-label">UHID</div>
                        <div class="ah-field-value ah-uhid">{{ $historyPatient['uhid'] ?? '—' }}</div>
                        <div class="ah-field-label" style="margin-top:10px;">Primary Doctor</div>
                        <div class="ah-field-value">{{ $historyPatient['primary_doctor'] ?? '—' }}</div>
                    </div>
                    <div class="ah-patient-col">
                        <div class="ah-field-label">Age / Gender</div>
                        <div class="ah-field-value">
                            {{ $historyPatient['age'] ?? '—' }} Years / {{ $historyPatient['gender'] ?? '—' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stat Cards --}}
            <div class="ah-stats-row">
                <div class="ah-stat-card ah-stat-default">
                    <div class="ah-stat-label">Total Appointments</div>
                    <div class="ah-stat-bottom">
                        <div class="ah-stat-number">{{ $historyStats['total'] ?? 0 }}</div>
                        <i class="far fa-calendar-alt ah-stat-icon"></i>
                    </div>
                </div>
                <div class="ah-stat-card ah-stat-green">
                    <div class="ah-stat-label">Completed</div>
                    <div class="ah-stat-bottom">
                        <div class="ah-stat-number">{{ $historyStats['completed'] ?? 0 }}</div>
                        <i class="far fa-check-circle ah-stat-icon"></i>
                    </div>
                </div>
                <div class="ah-stat-card ah-stat-blue">
                    <div class="ah-stat-label">Upcoming</div>
                    <div class="ah-stat-bottom">
                        <div class="ah-stat-number">{{ $historyStats['upcoming'] ?? 0 }}</div>
                        <i class="far fa-clock ah-stat-icon"></i>
                    </div>
                </div>
                <div class="ah-stat-card ah-stat-red">
                    <div class="ah-stat-label">Cancelled</div>
                    <div class="ah-stat-bottom">
                        <div class="ah-stat-number">{{ $historyStats['cancelled'] ?? 0 }}</div>
                        <i class="far fa-times-circle ah-stat-icon"></i>
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="ah-filter-bar">
                <div class="ah-search">
                    <i class="fas fa-search"></i>
                    <input type="text"
                           wire:model.live.debounce.300ms="historySearch"
                           placeholder="Search by ID, doctor, or department...">
                </div>
                <select class="ah-filter-select" wire:model.live="historyTypeFilter">
                    <option value="all">Appointment Type: All</option>
                    <option value="in-clinic">In-clinic</option>
                    <option value="new">New</option>
                    <option value="follow-up">Follow-up</option>
                    <option value="online">Online</option>
                </select>
                <select class="ah-filter-select" wire:model.live="historyStatusFilter">
                    <option value="all">Status: All</option>
                    <option value="completed">Completed</option>
                    <option value="upcoming">Upcoming</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <button class="ah-btn-more-filter" type="button" wire:click="toggleHistoryDateFilter">
                    <i class="fas fa-sliders-h"></i> More Filters
                </button>
            </div>

            @if($showHistoryDateFilter ?? false)
                <div class="ah-date-filter-row">
                    <div class="ah-date-field">
                        <label>From</label>
                        <input type="date" wire:model.live="historyDateFrom" class="ah-filter-select">
                    </div>
                    <div class="ah-date-field">
                        <label>To</label>
                        <input type="date" wire:model.live="historyDateTo" class="ah-filter-select">
                    </div>
                    <button type="button" class="ah-btn-clear-dates" wire:click="clearHistoryDateFilter">
                        Clear Dates
                    </button>
                </div>
            @endif
            </div>

            {{-- Table (scrollable region) --}}
            <div class="ah-table-scroll">
            <div class="ah-table-wrap">
                <table class="ah-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date &amp; Time</th>
                            <th>Department</th>
                            <th>Doctor</th>
                            <th>Branch</th>
                            <th>Type</th>
                            <th>Status</th>
                            {{-- <th>Actions</th> --}}
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($appointmentHistory ?? [] as $appt)
                            <tr wire:key="ah-{{ $appt['id'] }}">
                                <td><span class="ah-appt-id">{{ $appt['appointment_id'] }}</span></td>
                                <td>
                                    <div class="ah-date-val">{{ $appt['date'] }}</div>
                                    <div class="ah-time-val">{{ $appt['time'] }}</div>
                                </td>
                                <td>{{ $appt['department'] }}</td>
                                <td>{{ $appt['doctor'] }}</td>
                                <td>{{ $appt['branch'] }}</td>
                                <td>{{ $appt['type'] }}</td>
                                <td>
                                    <span class="ah-status ah-status-{{ strtolower($appt['status']) }}">
                                        {{ $appt['status'] }}
                                    </span>
                                </td>
                                {{-- <td>
                                    <button class="ah-btn-view" type="button"
                                            wire:click="viewAppointmentDetail({{ $appt['id'] }})">
                                        View Details
                                    </button>
                                </td> --}}
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="ah-empty-cell">
                                    No appointment history found for the selected filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{-- Pagination inside table wrap ── avoids double showing text ── --}}
                @if(isset($appointmentHistoryPaginator) && $appointmentHistoryPaginator->total() > 0)
                    <div class="ah-pagination-bar">
                        <div class="ah-pagination-info">
                            Showing
                            <strong>{{ $appointmentHistoryPaginator->firstItem() }}–{{ $appointmentHistoryPaginator->lastItem() }}</strong>
                            of
                            <strong>{{ $appointmentHistoryPaginator->total() }}</strong>
                            appointments
                        </div>
                        <div class="ah-pages">
                            {{-- Prev --}}
                            @if($appointmentHistoryPaginator->onFirstPage())
                                <span class="ah-pg-btn ah-pg-disabled">
                                    <i class="fas fa-chevron-left" style="font-size:10px;"></i>
                                </span>
                            @else
                                <button class="ah-pg-btn" wire:click="previousHistoryPage" type="button">
                                    <i class="fas fa-chevron-left" style="font-size:10px;"></i>
                                </button>
                            @endif

                            {{-- Page numbers --}}
                            @foreach($appointmentHistoryPaginator->getUrlRange(1, $appointmentHistoryPaginator->lastPage()) as $page => $url)
                                @if($page === $appointmentHistoryPaginator->currentPage())
                                    <span class="ah-pg-btn ah-pg-active">{{ $page }}</span>
                                @else
                                    <button class="ah-pg-btn" wire:click="goToHistoryPage({{ $page }})" type="button">
                                        {{ $page }}
                                    </button>
                                @endif
                            @endforeach

                            {{-- Next --}}
                            @if($appointmentHistoryPaginator->hasMorePages())
                                <button class="ah-pg-btn" wire:click="nextHistoryPage" type="button">
                                    <i class="fas fa-chevron-right" style="font-size:10px;"></i>
                                </button>
                            @else
                                <span class="ah-pg-btn ah-pg-disabled">
                                    <i class="fas fa-chevron-right" style="font-size:10px;"></i>
                                </span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
            </div>

        </div>

        {{-- ── Footer (fixed, never scrolls) ── --}}
        <div class="ah-footer">
            <button type="button" class="ah-btn-close" wire:click="closeAppointmentHistory">
                Close
            </button>
            <button type="button" class="ah-btn-followup" wire:click="bookFollowUpFromHistory">
                Book Follow-up
            </button>
        </div>

    </div>
</div>

<style>
/* ── Overlay ── */
.ah-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    z-index: 1050;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    /* allow overlay itself to scroll on very small screens */
    overflow-y: auto;
}

/* ── Modal: KEY FIX — explicit height chain ── */
.ah-modal {
    width: 100%;
    max-width: 860px;
    background: #f4f6f9;
    border-radius: 20px;
    box-shadow: 0 24px 64px rgba(15, 23, 42, 0.20);
    display: flex;
    flex-direction: column;
    /* This is the critical fix: explicit max-height + overflow hidden on modal */
    height: 90vh;
    max-height: 90vh;
    overflow: hidden; /* modal itself clips */
    flex-shrink: 0;
}

/* ── Header: fixed, never grows ── */
.ah-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 22px 26px 18px;
    background: #fff;
    border-radius: 20px 20px 0 0;
    border-bottom: 1px solid #f0f4f8;
    flex-shrink: 0; /* never shrinks */
}
.ah-title { font-size: 21px; font-weight: 700; color: #1a1a2e; line-height: 1.2; margin-bottom: 4px; }
.ah-subtitle { font-size: 13px; color: #6b7280; }
.ah-close {
    width: 32px; height: 32px;
    display: flex; align-items: center; justify-content: center;
    border: none; background: transparent; color: #9ca3af;
    cursor: pointer; font-size: 17px; border-radius: 8px;
    transition: background 0.15s, color 0.15s; flex-shrink: 0;
}
.ah-close:hover { background: #f4f6f9; color: #374151; }

/* ── Body: top fixed, table scrolls ── */
.ah-body {
    flex: 1;
    min-height: 0;
    overflow: hidden;
    padding: 18px 22px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.ah-body-top {
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.ah-table-scroll {
    flex: 1;
    min-height: 0;
    overflow: auto;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior: contain;
    border-radius: 14px;
    border: 1px solid #e8ecf0;
    background: #fff;
}

/* ── Footer: fixed, never grows ── */
.ah-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    padding: 14px 26px 18px;
    background: #fff;
    border-radius: 0 0 20px 20px;
    border-top: 1px solid #f0f4f8;
    flex-shrink: 0; /* never shrinks */
}

/* ── Patient Card ── */
.ah-patient-card {
    background: #fff;
    border: 1px solid #e8ecf0;
    border-radius: 14px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 18px;
    flex-wrap: wrap;
    flex-shrink: 0;
}
.ah-patient-avatar {
    width: 60px; height: 60px;
    border-radius: 12px;
    object-fit: cover;
    border: 2px solid #e8ecf0;
    flex-shrink: 0;
}
.ah-patient-avatar-init {
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 18px; font-weight: 700;
}
.ah-patient-fields { display: flex; gap: 28px; flex-wrap: wrap; flex: 1; }
.ah-patient-col { display: flex; flex-direction: column; }
.ah-field-label {
    font-size: 10px; font-weight: 700; color: #9ca3af;
    letter-spacing: 0.07em; text-transform: uppercase; margin-bottom: 3px;
}
.ah-field-value { font-size: 14px; font-weight: 700; color: #1a1a2e; }
.ah-uhid { color: #c8102e !important; }

/* ── Stat Cards ── */
.ah-stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    flex-shrink: 0;
}
.ah-stat-card { border-radius: 12px; padding: 14px 16px; border: 1px solid transparent; }
.ah-stat-default { background: #eef0f6; border-color: #dde0ea; }
.ah-stat-green   { background: #ecfdf5; border-color: #a7f3d0; }
.ah-stat-blue    { background: #eff6ff; border-color: #bfdbfe; }
.ah-stat-red     { background: #fff0f2; border-color: #fecdd3; }
.ah-stat-label { font-size: 13px; font-weight: 600; margin-bottom: 8px; }
.ah-stat-default .ah-stat-label { color: #374151; }
.ah-stat-green   .ah-stat-label { color: #059669; }
.ah-stat-blue    .ah-stat-label { color: #2563eb; }
.ah-stat-red     .ah-stat-label { color: #c8102e; }
.ah-stat-bottom { display: flex; align-items: flex-end; justify-content: space-between; }
.ah-stat-number { font-size: 30px; font-weight: 700; line-height: 1; }
.ah-stat-default .ah-stat-number { color: #1a1a2e; }
.ah-stat-green   .ah-stat-number { color: #059669; }
.ah-stat-blue    .ah-stat-number { color: #2563eb; }
.ah-stat-red     .ah-stat-number { color: #c8102e; }
.ah-stat-icon { font-size: 20px; opacity: 0.3; }

/* ── Filter Bar ── */
.ah-filter-bar { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; flex-shrink: 0; }
.ah-search {
    flex: 1; min-width: 180px;
    display: flex; align-items: center; gap: 9px;
    background: #fff;
    border: 1.5px solid #e8ecf0 !important;
    border-radius: 10px; padding: 8px 14px;
}
.ah-search:focus-within { border-color: #e8ecf0 !important; box-shadow: none !important; }
.ah-search i { color: #9ca3af; font-size: 13px; flex-shrink: 0; }
.ah-search input {
    border: none !important; background: transparent;
    outline: none !important; box-shadow: none !important;
    font-size: 13px; color: #374151; font-family: inherit; width: 100%;
}
.ah-search input:focus { outline: none !important; box-shadow: none !important; border: none !important; }
.ah-search input::placeholder { color: #9ca3af; }

.ah-filter-select {
    height: 38px;
    border: 1.5px solid #e8ecf0 !important;
    border-radius: 10px;
    padding: 0 26px 0 11px;
    font-size: 13px;
    color: #374151;
    background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 12 12'%3E%3Cpath fill='%239ca3af' d='M6 8L1 3h10z'/%3E%3C/svg%3E") no-repeat right 10px center;
    appearance: none; cursor: pointer;
    font-family: inherit; outline: none !important; white-space: nowrap;
}
.ah-btn-more-filter {
    display: flex; align-items: center; gap: 7px;
    height: 38px; padding: 0 14px;
    border: 1.5px solid #e8ecf0; border-radius: 10px;
    background: #fff; font-size: 13px; font-weight: 600;
    color: #374151; cursor: pointer; font-family: inherit; white-space: nowrap;
    transition: border-color 0.15s, background 0.15s;
}
.ah-btn-more-filter:hover { border-color: #9ca3af; background: #f9fafb; }

/* Date filter row */
.ah-date-filter-row {
    display: flex; align-items: flex-end; gap: 12px; flex-wrap: wrap;
    background: #fff; border: 1px solid #e8ecf0; border-radius: 12px; padding: 14px 16px;
    flex-shrink: 0;
}
.ah-date-field { display: flex; flex-direction: column; gap: 6px; }
.ah-date-field label { font-size: 11px; font-weight: 700; color: #9ca3af; text-transform: uppercase; }
.ah-btn-clear-dates {
    height: 38px; padding: 0 14px;
    border: 1.5px solid #e8ecf0; border-radius: 10px;
    background: #fff; font-size: 13px; font-weight: 600;
    color: #374151; cursor: pointer; font-family: inherit;
}

/* ── Table ── */
.ah-table-wrap {
    background: #fff;
    border-radius: 14px;
    min-width: min(100%, 720px);
}
.ah-table {
    width: 100%;
    min-width: 720px;
    border-collapse: collapse;
}
.ah-table thead tr { background: #f8fafc; }
.ah-table th {
    padding: 10px 14px;
    font-size: 11.5px; font-weight: 600; color: #6b7280;
    text-align: left; border-bottom: 1px solid #f0f4f8; white-space: nowrap;
    position: sticky;
    top: 0;
    z-index: 2;
    background: #f8fafc;
    box-shadow: 0 1px 0 #f0f4f8;
}
.ah-table td {
    padding: 14px 14px;
    font-size: 13px; color: #374151;
    border-bottom: 1px solid #f0f4f8; vertical-align: middle;
}
.ah-table tbody tr:last-child td { border-bottom: none; }
.ah-table tbody tr:hover td { background: #fafbfc; }
.ah-empty-cell { text-align: center; color: #9ca3af; padding: 28px 14px !important; }

.ah-appt-id { font-weight: 700; color: #c8102e; font-size: 13px; white-space: nowrap; }
.ah-date-val { font-weight: 600; color: #1a1a2e; font-size: 13px; white-space: nowrap; }
.ah-time-val { font-size: 11.5px; color: #9ca3af; margin-top: 2px; }

.ah-status {
    display: inline-block; padding: 4px 11px;
    border-radius: 20px; font-size: 11.5px; font-weight: 600; white-space: nowrap;
}
.ah-status-completed { background: #ecfdf5; color: #059669; }
.ah-status-upcoming  { background: #eff6ff; color: #2563eb; }
.ah-status-cancelled { background: #fff0f2; color: #c8102e; }
.ah-status-pending   { background: #fff7ed; color: #c2410c; }

.ah-btn-view {
    border: none; background: transparent;
    color: #c8102e; font-size: 13px; font-weight: 700;
    cursor: pointer; font-family: inherit; padding: 0; white-space: nowrap;
}
.ah-btn-view:hover { color: #a80e26; text-decoration: underline; }

/* ── Pagination bar (inside table wrap) ── */
.ah-pagination-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-top: 1px solid #f0f4f8;
    gap: 12px;
    flex-wrap: wrap;
}
.ah-pagination-info { font-size: 13px; color: #6b7280; }
.ah-pagination-info strong { color: #1a1a2e; }
.ah-pages { display: flex; align-items: center; gap: 4px; }
.ah-pg-btn {
    width: 32px; height: 32px;
    display: flex; align-items: center; justify-content: center;
    border: 1.5px solid #e8ecf0;
    border-radius: 7px;
    background: #fff;
    font-size: 13px; font-weight: 500; color: #374151;
    cursor: pointer; font-family: inherit;
    transition: all 0.15s; text-decoration: none;
}
.ah-pg-btn:hover { border-color: #9ca3af; background: #f9fafb; }
.ah-pg-active { background: var(--primary-color) !important; border-color: var(--primary-color) !important; color: #fff !important; font-weight: 700 !important; }
.ah-pg-disabled { opacity: 0.35; cursor: not-allowed; }

/* ── Footer buttons ── */
.ah-btn-close {
    padding: 10px 22px;
    border: 1.5px solid #e8ecf0; border-radius: 10px;
    background: #fff; font-size: 14px; font-weight: 600;
    color: #374151; cursor: pointer; font-family: inherit;
    transition: border-color 0.15s, background 0.15s;
}
.ah-btn-close:hover { border-color: #9ca3af; background: #f9fafb; }
.ah-btn-followup {
    padding: 10px 22px;
    border: none; border-radius: 10px;
    background: var(--primary-color); font-size: 14px; font-weight: 700;
    color: #fff; cursor: pointer; font-family: inherit;
    transition: background 0.15s;
}
.ah-btn-followup:hover { background: var(--button-hover); }

@media (max-width: 700px) {
    .ah-stats-row { grid-template-columns: repeat(2, 1fr); }
    .ah-patient-fields { gap: 16px; }
    .ah-modal { height: 96vh; max-height: 96vh; }
}
</style>
@endif