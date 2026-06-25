<div style="padding: 28px 32px;">
    <style>
    /* ── Page Header ── */
    .appt-page-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 26px;
        gap: 16px;
    }
    .appt-page-header h1 {
        font-size: 24px;
        font-weight: 700;
        color: #1a1a2e;
        line-height: 1.2;
    }
    .appt-page-header p {
        font-size: 13.5px;
        color: #6b7280;
        margin-top: 4px;
    }
    .btn-create-appt {
        display: flex;
        align-items: center;
        gap: 7px;
        background: var(--button-color);
        color: #fff;
        border: none;
        border-radius: 9px;
        padding: 11px 22px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
        transition: background 0.15s, transform 0.1s;
        text-decoration: none;
        flex-shrink: 0;
    }
    .btn-create-appt:hover { background: var(--button-hover); transform: translateY(-1px); color: #fff; }
    
    /* ── Filter Bar ── */
    .appt-filter-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 22px;
        gap: 12px;
        flex-wrap: wrap;
    }
    
    /* Tabs */
    .appt-tabs {
        display: flex;
        align-items: center;
        gap: 0;
        border-bottom: 2px solid #e8ecf0;
        overflow-x: auto;
    }
    .appt-tab {
        padding: 10px 18px;
        font-size: 14px;
        font-weight: 500;
        color: #6b7280;
        cursor: pointer;
        border: none;
        background: transparent;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
        transition: color 0.15s, border-color 0.15s;
        white-space: nowrap;
        font-family: inherit;
    }
    .appt-tab:hover { color: #1a1a2e; }
    .appt-tab.active {
        color: var(--button-color);
        font-weight: 700;
        border-bottom-color: var(--button-color);
    }
    
    /* Right filters */
    .appt-filter-right {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
    }
    .appt-select {
        display: flex;
        align-items: center;
        gap: 8px;
        border: 1.5px solid #e8ecf0;
        border-radius: 9px;
        padding: 9px 14px;
        font-size: 13.5px;
        color: #374151;
        background: #fff;
        cursor: pointer;
        font-family: inherit;
        outline: none;
        transition: border-color 0.15s;
        min-width: 150px;
        height: 40px;
    }
    .appt-select:focus { border-color: var(--primary-color); }
    
    .btn-filter {
        display: flex;
        align-items: center;
        gap: 7px;
        border: 1.5px solid #e8ecf0;
        border-radius: 9px;
        padding: 9px 16px;
        font-size: 13.5px;
        font-weight: 500;
        color: #374151;
        background: #fff;
        cursor: pointer;
        font-family: inherit;
        transition: border-color 0.15s, background 0.15s;
        height: 40px;
    }
    .btn-filter:hover { border-color: #9ca3af; background: #f9fafb; }
    .btn-filter i { color: #6b7280; font-size: 13px; }
    
    /* ── Cards Grid ── */
    .appt-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 18px;
        margin-bottom: 22px;
        align-items: stretch;
    }
    
    /* Appointment Card */
    .appt-card {
        background: #fff;
        border: 1px solid #e8ecf0;
        border-radius: 16px;
        padding: 22px;
        transition: box-shadow 0.2s, border-color 0.2s;
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    .appt-card:hover {
        box-shadow: 0 6px 20px rgba(16, 24, 40, 0.08);
        border-color: #dbe2ea;
    }
    
    /* Card top row */
    .appt-card-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 16px;
    }
    .appt-patient-info {
        display: flex;
        align-items: center;
        gap: 13px;
        min-width: 0;
    }
    .appt-patient-avatar {
        width: 50px; height: 50px;
        min-width: 50px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #e8ecf0;
        flex-shrink: 0;
    }
    .appt-patient-avatar-init {
        width: 50px; height: 50px;
        min-width: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary-color), var(--button-hover));
        display: flex; align-items: center; justify-content: center;
        color: #fff;
        font-size: 16px;
        font-weight: 700;
        flex-shrink: 0;
        border: 2px solid #e8ecf0;
        letter-spacing: 0.3px;
    }
    .appt-patient-name {
        font-size: 15.5px;
        font-weight: 700;
        color: #1a1a2e;
        line-height: 1.3;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .appt-patient-meta {
        font-size: 12.5px;
        color: #9ca3af;
        margin-top: 3px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* Status badge */
    .appt-status {
        display: inline-flex;
        align-items: center;
        padding: 5px 13px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
        flex-shrink: 0;
        line-height: 1.4;
    }
    .status-upcoming   { background: #fff7ed; color: #c2410c; }
    .status-checked-in { background: #f1f5f9; color: #475569; }
    .status-completed  { background: #ecfdf5; color: #059669; }
    .status-follow-up  { background: #f0f9ff; color: #0369a1; }
    .status-cancelled  { background: #fff0f2; color: #c8102e; }

    /* ── Date filter popover ── */
    .appt-date-filter-panel {
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        width: 260px;
        background: #fff;
        border: 1px solid #e8ecf0;
        border-radius: 12px;
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.12);
        padding: 14px;
        z-index: 40;
    }
    .appt-date-option {
        width: 100%;
        text-align: left;
        border: 1px solid #e8ecf0;
        background: #fff;
        border-radius: 8px;
        padding: 10px 12px;
        font-size: 13px;
        color: #374151;
        margin-bottom: 8px;
        cursor: pointer;
        font-family: inherit;
    }
    .appt-date-option.active,
    .appt-date-option:hover {
        border-color: var(--button-color);
        background: #f0f9ff;
        color: var(--button-color);
        font-weight: 600;
    }
    .appt-empty {
        grid-column: 1 / -1;
        background: #fff;
        border: 1px dashed #dbe2ea;
        border-radius: 16px;
        padding: 48px 24px;
        text-align: center;
        color: #6b7280;
    }
    .appt-empty h3 {
        font-size: 18px;
        font-weight: 700;
        color: #1a1a2e;
        margin-bottom: 8px;
    }

    /* ── Update status modal ── */
    .appt-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.45);
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .appt-modal {
        width: 100%;
        max-width: 560px;
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18);
        overflow: hidden;
    }
    .appt-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 22px 24px 0;
    }
    .appt-modal-header h2 {
        font-size: 22px;
        font-weight: 700;
        color: #111827;
    }
    .appt-modal-close {
        width: 34px;
        height: 34px;
        border: none;
        background: transparent;
        color: #9ca3af;
        cursor: pointer;
        font-size: 18px;
    }
    .appt-modal-body { padding: 18px 24px 24px; }
    .appt-modal-patient {
        display: flex;
        align-items: center;
        gap: 14px;
        background: #f8fafc;
        border: 1px solid #e8ecf0;
        border-radius: 14px;
        padding: 14px;
        margin-bottom: 18px;
    }
    .appt-modal-patient img,
    .appt-modal-patient .appt-patient-avatar-init {
        width: 56px;
        height: 56px;
        min-width: 56px;
    }
    .appt-modal-patient h3 {
        font-size: 18px;
        font-weight: 700;
        color: #111827;
        margin-bottom: 6px;
    }
    .appt-modal-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        font-size: 13px;
        color: #6b7280;
    }
    .appt-modal-type {
        margin-left: auto;
        background: #fff0f2;
        color: var(--button-color);
        border-radius: 999px;
        padding: 6px 12px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.04em;
    }
    .appt-modal-label {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.08em;
        color: #9ca3af;
        margin-bottom: 12px;
    }
    .appt-status-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 22px;
    }
    .appt-status-option {
        text-align: left;
        border: 1.5px solid #e8ecf0;
        border-radius: 14px;
        background: #fff;
        padding: 14px;
        cursor: pointer;
        transition: all 0.15s;
        font-family: inherit;
    }
    .appt-status-option:hover { border-color: #cbd5e1; }
    .appt-status-option.selected {
        border-color: var(--button-color);
        background: #fff7f8;
        box-shadow: inset 0 0 0 1px var(--button-color);
    }
    .appt-status-option-icon {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        background: #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6b7280;
        margin-bottom: 10px;
    }
    .appt-status-option.selected .appt-status-option-icon {
        background: #ffe4e8;
        color: var(--button-color);
    }
    .appt-status-option strong {
        display: block;
        font-size: 14px;
        color: #111827;
        margin-bottom: 4px;
    }
    .appt-status-option span {
        display: block;
        font-size: 12px;
        color: #6b7280;
        line-height: 1.45;
    }
    .appt-modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }
    .btn-modal-cancel {
        border: 1.5px solid #e8ecf0;
        background: #fff;
        color: #374151;
        border-radius: 10px;
        padding: 11px 18px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        font-family: inherit;
    }
    .btn-modal-save {
        border: none;
        background: var(--button-color);
        color: #fff;
        border-radius: 10px;
        padding: 11px 18px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        font-family: inherit;
    }
    .btn-modal-save:hover { background: var(--button-hover); }
    
    /* Divider */
    .appt-card-divider {
        height: 1px;
        background: #f0f4f8;
        margin: 14px 0;
        border: none;
    }
    
    /* Detail rows */
    .appt-details {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-bottom: 4px;
        flex: 1;
    }
    .appt-detail-row {
        display: flex;
        align-items: center;
        gap: 9px;
        font-size: 13.5px;
        color: #374151;
        flex-wrap: wrap;
    }
    .appt-detail-row i {
        width: 18px;
        text-align: center;
        color: #9ca3af;
        font-size: 14px;
        flex-shrink: 0;
    }
    .appt-detail-row .detail-sep {
        color: #d1d5db;
        margin: 0 2px;
    }
    .appt-detail-label { color: #9ca3af; }
    .appt-detail-value { color: #1a1a2e; font-weight: 500; }
    
    /* Card actions */
    .appt-card-actions {
        display: flex;
        gap: 10px;
        margin-top: 18px;
    }
    .btn-view-patient {
        flex: 1;
        height: 40px;
        border: 1.5px solid #e8ecf0;
        border-radius: 9px;
        background: #fff;
        font-size: 13.5px;
        font-weight: 600;
        color: #374151;
        cursor: pointer;
        font-family: inherit;
        transition: border-color 0.15s, background 0.15s;
        text-align: center;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .btn-view-patient:hover { border-color: #9ca3af; background: #f9fafb; }
    .btn-update {
        flex: 1;
        height: 40px;
        border: none;
        border-radius: 9px;
        background: var(--button-color);
        font-size: 13.5px;
        font-weight: 600;
        color: #fff;
        cursor: pointer;
        font-family: inherit;
        transition: background 0.15s;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .btn-update:hover { background: var(--button-hover); }
    
    /* ── Pagination bar ── */
    .appt-pagination-bar {
        background: #fff;
        border: 1px solid #e8ecf0;
        border-radius: 12px;
        padding: 14px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }
    .appt-pagination-info {
        font-size: 13.5px;
        color: #6b7280;
    }
    .appt-pagination-info strong { color: #1a1a2e; }
    
    .appt-pagination-pages {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .pg-btn {
        width: 34px; height: 34px;
        display: flex; align-items: center; justify-content: center;
        border: 1.5px solid #e8ecf0;
        border-radius: 8px;
        background: #fff;
        font-size: 13.5px;
        font-weight: 500;
        color: #374151;
        cursor: pointer;
        font-family: inherit;
        transition: all 0.15s;
        text-decoration: none;
    }
    .pg-btn:hover { border-color: #9ca3af; background: #f9fafb; }
    .pg-btn.active {
        background: var(--button-color);
        border-color: var(--button-color);
        color: #fff;
        font-weight: 700;
    }
    .pg-btn:disabled { opacity: 0.4; cursor: not-allowed; }
    
    @media (max-width: 992px) {
        .appt-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 768px) {
        .appt-filter-bar { flex-direction: column; align-items: flex-start; }
        .appt-page-header { flex-direction: column; }
        .btn-create-appt { width: 100%; justify-content: center; }
    }
    </style>
    
    {{-- ── Page Header ── --}}
    <div class="appt-page-header">
        <div>
            <h1>My Appointments</h1>
            <p>Manage and track your patient consultation schedule</p>
        </div>
        <a href="#" class="btn-create-appt">
            <i class="fas fa-plus"></i> Create New Appointment
        </a>
    </div>
    
    {{-- ── Filter Bar ── --}}
    <div class="appt-filter-bar">
        <div class="appt-tabs">
            @foreach([
                'today' => 'Today',
                'follow-up' => 'Follow-up',
                'upcoming' => 'Upcoming',
                'completed' => 'Completed',
                'cancelled' => 'Cancelled',
            ] as $tabKey => $tabLabel)
                <button type="button"
                        wire:click="setTab('{{ $tabKey }}')"
                        class="appt-tab {{ $activeTab === $tabKey ? 'active' : '' }}">
                    {{ $tabLabel }}
                </button>
            @endforeach
        </div>

        <div class="appt-filter-right">
            <select class="appt-select" wire:model.live="branchFilter">
                <option value="all">All Branches</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </select>

            <div class="relative" wire:click.away="$set('showDateFilter', false)">
                <button type="button" class="btn-filter" wire:click="toggleDateFilter">
                    <i class="fas fa-sliders-h"></i>
                    @if($datePreset === 'today')
                        Today
                    @elseif($datePreset === 'tomorrow')
                        Tomorrow
                    @else
                        {{ \Carbon\Carbon::parse($customDate)->format('M d, Y') }}
                    @endif
                </button>

                @if($showDateFilter)
                    <div class="appt-date-filter-panel">
                        <button type="button"
                                wire:click="setDatePreset('today')"
                                class="appt-date-option {{ $datePreset === 'today' ? 'active' : '' }}">
                            Today
                        </button>
                        <button type="button"
                                wire:click="setDatePreset('tomorrow')"
                                class="appt-date-option {{ $datePreset === 'tomorrow' ? 'active' : '' }}">
                            Tomorrow
                        </button>
                        <input type="date"
                               wire:model="customDate"
                               class="appt-select"
                               style="width:100%; margin-bottom:8px;">
                        <button type="button"
                                wire:click="applyCustomDate"
                                class="btn-create-appt"
                                style="width:100%; justify-content:center;">
                            Apply Date
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    {{-- ── Appointment Cards ── --}}
    <div class="appt-grid">
        @forelse($appointments as $appt)
            <div class="appt-card" wire:key="appt-{{ $appt['id'] }}">
                <div class="appt-card-top">
                    <div class="appt-patient-info">
                        @if(!empty($appt['avatar_url']))
                            <img src="{{ $appt['avatar_url'] }}"
                                 alt="{{ $appt['patient_name'] }}"
                                 class="appt-patient-avatar">
                        @else
                            <div class="appt-patient-avatar-init">{{ $appt['initials'] }}</div>
                        @endif
                        <div style="min-width:0;">
                            <div class="appt-patient-name">{{ $appt['patient_name'] }}</div>
                            <div class="appt-patient-meta">{{ $appt['patient_meta'] }}</div>
                        </div>
                    </div>
                    <span class="appt-status {{ $appt['status_class'] }}">
                        {{ $appt['status_label'] }}
                    </span>
                </div>

                <hr class="appt-card-divider">

                <div class="appt-details">
                    <div class="appt-detail-row">
                        <i class="far fa-clock"></i>
                        <span class="appt-detail-value">{{ $appt['time_only'] }}</span>
                        <span class="detail-sep">|</span>
                        <span class="appt-detail-label">Branch:</span>
                        <span class="appt-detail-value">{{ $appt['branch'] }}</span>
                    </div>
                    <div class="appt-detail-row">
                        <i class="fas fa-notes-medical"></i>
                        <span class="appt-detail-label">Reason:</span>
                        <span class="appt-detail-value">{{ $appt['reason'] }}</span>
                    </div>
                    <div class="appt-detail-row">
                        @if(str_contains(strtolower($appt['type']), 'video'))
                            <i class="fas fa-video"></i>
                        @else
                            <i class="fas fa-stethoscope"></i>
                        @endif
                        <span class="appt-detail-label">Type:</span>
                        <span class="appt-detail-value">{{ $appt['type'] }}</span>
                    </div>
                </div>

                <hr class="appt-card-divider">

                <div class="appt-card-actions">
                    <a href="#" class="btn-view-patient">View Patient</a>
                    <button type="button"
                            class="btn-update"
                            wire:click="openUpdateModal({{ $appt['id'] }})">
                        Update
                    </button>
                </div>
            </div>
        @empty
            <div class="appt-empty">
                <h3>No appointments found</h3>
                <p>There are no appointments for the selected tab, branch, and date.</p>
            </div>
        @endforelse
    </div>

    {{-- ── Pagination ── --}}
    @if($appointments->total() > 0)
        <div class="appt-pagination-bar">
            <div class="appt-pagination-info">
                Showing <strong>{{ $appointments->firstItem() }}</strong>
                to <strong>{{ $appointments->lastItem() }}</strong>
                of <strong>{{ $appointments->total() }}</strong> appointments
                for {{ $filterDateLabel }}
            </div>
            <div class="appt-pagination-pages">
                {{ $appointments->links() }}
            </div>
        </div>
    @endif

    {{-- ── Update Status Modal ── --}}
    @if($showUpdateModal && $modalBooking)
        <div class="appt-modal-overlay" wire:click.self="closeUpdateModal">
            <div class="appt-modal" role="dialog" aria-modal="true" aria-labelledby="update-appt-title">
                <div class="appt-modal-header">
                    <h2 id="update-appt-title">Update Appointment Status</h2>
                    <button type="button" class="appt-modal-close" wire:click="closeUpdateModal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="appt-modal-body">
                    <div class="appt-modal-patient">
                        @if(!empty($modalBooking['avatar_url']))
                            <img src="{{ $modalBooking['avatar_url'] }}"
                                 alt="{{ $modalBooking['patient_name'] }}"
                                 class="appt-patient-avatar">
                        @else
                            <div class="appt-patient-avatar-init">{{ $modalBooking['initials'] }}</div>
                        @endif

                        <div style="min-width:0; flex:1;">
                            <h3>{{ $modalBooking['patient_name'] }}</h3>
                            <div class="appt-modal-meta">
                                <span><i class="far fa-id-card"></i> UHID: {{ $modalBooking['uhid'] }}</span>
                                <span><i class="far fa-clock"></i> {{ $modalBooking['time_label'] }}</span>
                            </div>
                        </div>

                        <span class="appt-modal-type">{{ $modalBooking['type_badge'] }}</span>
                    </div>

                    <div class="appt-modal-label">SELECT NEW STATUS</div>

                    <div class="appt-status-grid">
                        @foreach([
                            'new_scheduled' => ['icon' => 'far fa-calendar', 'title' => 'New/Scheduled', 'desc' => 'Appointment is confirmed but not yet started'],
                            'checked_in' => ['icon' => 'fas fa-map-marker-alt', 'title' => 'Checked-In', 'desc' => 'Patient has arrived or is waiting in the lobby'],
                            'completed' => ['icon' => 'far fa-check-circle', 'title' => 'Completed', 'desc' => 'Consultation is finished and records are updated'],
                            'cancelled' => ['icon' => 'far fa-times-circle', 'title' => 'Cancelled', 'desc' => 'Patient or doctor has cancelled the session'],
                        ] as $statusKey => $statusMeta)
                            <button type="button"
                                    wire:click="selectAppointmentStatus('{{ $statusKey }}')"
                                    class="appt-status-option {{ $appointmentStatus === $statusKey ? 'selected' : '' }}">
                                <div class="appt-status-option-icon">
                                    <i class="{{ $statusMeta['icon'] }}"></i>
                                </div>
                                <strong>{{ $statusMeta['title'] }}</strong>
                                <span>{{ $statusMeta['desc'] }}</span>
                            </button>
                        @endforeach
                    </div>

                    <div class="appt-modal-actions">
                        <button type="button" class="btn-modal-cancel" wire:click="closeUpdateModal">Cancel</button>
                        <button type="button"
                                class="btn-modal-save"
                                wire:click="updateAppointmentStatus"
                                wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="updateAppointmentStatus">Update &amp; Close</span>
                            <span wire:loading wire:target="updateAppointmentStatus">Updating...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
    </div>