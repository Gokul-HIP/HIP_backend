<div style="padding: 28px 32px;">
    <style>
    /* ── Stat Cards ── */
    .oc-stats-row {
        display: flex;
        gap: 18px;
        margin-bottom: 22px;
        flex-wrap: wrap;
    }
    .oc-stat-card {
        background: #fff;
        border: 1px solid #e8ecf0;
        border-radius: 16px;
        padding: 20px 22px;
        min-width: 220px;
        flex: 1;
        max-width: 280px;
    }
    .oc-stat-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
    }
    .oc-stat-icon {
        width: 38px; height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }
    .oc-stat-icon.icon-live { background: #eaf3ff; color: #0da2e7; }
    .oc-stat-icon.icon-today { background: #fff3e0; color: #e09b1a; }
    
    .oc-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 11px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .pill-live { background: #ecfdf5; color: #059669; }
    .pill-today { color: #9ca3af; font-weight: 500; font-size: 13px; }
    
    .oc-stat-number {
        font-size: 28px;
        font-weight: 700;
        color: #1a1a2e;
        line-height: 1.1;
        margin-bottom: 4px;
    }
    .oc-stat-label {
        font-size: 13.5px;
        color: #6b7280;
    }
    
    /* ── Filter Toolbar ── */
    .oc-toolbar {
        background: #fff;
        border: 1px solid #e8ecf0;
        border-radius: 14px;
        padding: 14px 18px;
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 22px;
        flex-wrap: wrap;
    }
    .oc-search {
        flex: 1;
        min-width: 220px;
        display: flex;
        align-items: center;
        gap: 10px;
        border: 1.5px solid #e8ecf0;
        border-radius: 10px;
        padding: 10px 14px;
        background: #f9fafb;
    }
    .oc-search i { color: #9ca3af; font-size: 14px; }
    .oc-search input {
        border: none;
        background: transparent;
        outline: none;
        font-size: 13.5px;
        color: #374151;
        font-family: inherit;
        width: 100%;
    }
    .oc-search input::placeholder { color: #9ca3af; }
    
    .oc-select {
        display: flex;
        align-items: center;
        gap: 8px;
        border: 1.5px solid #e8ecf0;
        border-radius: 10px;
        padding: 9px 14px;
        font-size: 13.5px;
        color: #374151;
        background: #fff;
        cursor: pointer;
        font-family: inherit;
        outline: none;
        height: 40px;
    }
    .oc-select:focus { border-color: #0da2e7; }
    
    .btn-more-filter {
        display: flex;
        align-items: center;
        gap: 7px;
        border: 1.5px solid #e8ecf0;
        border-radius: 10px;
        padding: 9px 16px;
        font-size: 13.5px;
        font-weight: 500;
        color: #374151;
        background: #fff;
        cursor: pointer;
        font-family: inherit;
        transition: border-color 0.15s, background 0.15s;
        height: 40px;
        white-space: nowrap;
    }
    .btn-more-filter:hover { border-color: #9ca3af; background: #f9fafb; }
    
    /* ── Upcoming Video Calls Panel ── */
    .oc-panel {
        background: #fff;
        border: 1px solid #e8ecf0;
        border-radius: 16px;
        overflow: hidden;
    }
    .oc-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 18px 22px;
        border-bottom: 1px solid #f0f4f8;
    }
    .oc-panel-title {
        font-size: 16px;
        font-weight: 700;
        color: #1a1a2e;
    }
    .oc-panel-actions {
        display: flex;
        gap: 8px;
    }
    .oc-icon-btn {
        width: 36px; height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1.5px solid #e8ecf0;
        border-radius: 9px;
        background: #fff;
        color: #6b7280;
        cursor: pointer;
        transition: border-color 0.15s, background 0.15s;
    }
    .oc-icon-btn:hover { border-color: #9ca3af; background: #f9fafb; }
    
    /* Call rows */
    .oc-call-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 18px 22px;
        border-bottom: 1px solid #f0f4f8;
        gap: 16px;
        flex-wrap: wrap;
    }
    .oc-call-row:last-of-type { border-bottom: none; }
    
    .oc-call-patient {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
    }
    .oc-call-avatar {
        width: 48px; height: 48px;
        min-width: 48px;
        border-radius: 12px;
        object-fit: cover;
        flex-shrink: 0;
    }
    .oc-call-name {
        font-size: 15px;
        font-weight: 600;
        color: #1a1a2e;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .oc-call-time {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        color: #9ca3af;
        margin-top: 3px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .oc-call-time i { font-size: 12px; }
    
    .oc-call-right {
        display: flex;
        align-items: center;
        gap: 14px;
        flex-shrink: 0;
    }
    .oc-call-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        white-space: nowrap;
    }
    .status-started { background: #fff0f2; color: var(--button-color); }
    .status-started .dot { width: 6px; height: 6px; border-radius: 50%; background: var(--button-color); }
    .status-scheduled { background: #f4f4f6; color: #6b7280; }
    .status-completed-call { background: #ecfdf5; color: #059669; }
    .status-cancelled-call { background: #fff0f2; color: #c8102e; }
    .oc-empty {
        padding: 40px 22px;
        text-align: center;
        color: #6b7280;
    }
    .oc-empty h3 {
        font-size: 17px;
        font-weight: 700;
        color: #1a1a2e;
        margin-bottom: 6px;
    }

    /* Modal (shared with my-appointment pattern) */
    .oc-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.45);
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .oc-modal {
        width: 100%;
        max-width: 560px;
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18);
        overflow: hidden;
        max-height: 92vh;
        overflow-y: auto;
    }
    .oc-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 22px 24px 0;
    }
    .oc-modal-header h2 {
        font-size: 22px;
        font-weight: 700;
        color: #111827;
    }
    .oc-modal-close {
        width: 34px;
        height: 34px;
        border: none;
        background: transparent;
        color: #9ca3af;
        cursor: pointer;
        font-size: 18px;
    }
    .oc-modal-body { padding: 18px 24px 24px; }
    .oc-modal-patient {
        display: flex;
        align-items: center;
        gap: 14px;
        background: #f8fafc;
        border: 1px solid #e8ecf0;
        border-radius: 14px;
        padding: 14px;
        margin-bottom: 18px;
    }
    .oc-modal-patient img,
    .oc-modal-patient .oc-call-avatar {
        width: 56px;
        height: 56px;
        min-width: 56px;
        border-radius: 12px;
    }
    .oc-modal-patient h3 {
        font-size: 18px;
        font-weight: 700;
        color: #111827;
        margin-bottom: 6px;
    }
    .oc-modal-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        font-size: 13px;
        color: #6b7280;
    }
    .oc-modal-type {
        margin-left: auto;
        background: #eaf3ff;
        color: #0da2e7;
        border-radius: 999px;
        padding: 6px 12px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.04em;
    }
    .oc-modal-label {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.08em;
        color: #9ca3af;
        margin-bottom: 12px;
    }
    .oc-status-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 18px;
    }
    .oc-status-option {
        text-align: left;
        border: 1.5px solid #e8ecf0;
        border-radius: 14px;
        background: #fff;
        padding: 14px;
        cursor: pointer;
        transition: all 0.15s;
        font-family: inherit;
    }
    .oc-status-option.selected {
        border-color: var(--button-color);
        background: #f0f9ff;
        box-shadow: inset 0 0 0 1px var(--button-color);
    }
    .oc-status-option-icon {
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
    .oc-status-option.selected .oc-status-option-icon {
        background: #eaf3ff;
        color: var(--button-color);
    }
    .oc-status-option strong {
        display: block;
        font-size: 14px;
        color: #111827;
        margin-bottom: 4px;
    }
    .oc-status-option span {
        display: block;
        font-size: 12px;
        color: #6b7280;
        line-height: 1.45;
    }
    .oc-link-block {
        border: 1px solid #e8ecf0;
        border-radius: 14px;
        padding: 14px;
        margin-bottom: 18px;
        background: #fafbfc;
    }
    .oc-link-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 10px;
        gap: 10px;
    }
    .oc-link-head strong {
        font-size: 14px;
        color: #111827;
    }
    .oc-link-virtual {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: #0da2e7;
        font-weight: 600;
    }
    .oc-link-input-wrap {
        display: flex;
        align-items: stretch;
        gap: 8px;
    }
    .oc-link-input {
        flex: 1;
        border: 1.5px solid #e8ecf0;
        border-radius: 10px;
        padding: 11px 14px;
        font-size: 13px;
        color: #374151;
        font-family: inherit;
        outline: none;
        background: #fff;
    }
    .oc-link-input:focus { border-color: #0da2e7; }
    .btn-copy-link {
        border: 1.5px solid #fecdd3;
        background: #fff;
        color: #c8102e;
        border-radius: 10px;
        padding: 0 14px;
        font-size: 12.5px;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
        font-family: inherit;
    }
    .oc-modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }
    .btn-oc-cancel {
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
    .btn-oc-save {
        border: none;
        background: #c8102e;
        color: #fff;
        border-radius: 10px;
        padding: 11px 18px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        font-family: inherit;
    }
    .oc-date-panel {
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
    
    .oc-call-divider {
        width: 1px;
        height: 20px;
        background: #e8ecf0;
    }
    
    .btn-call-update {
        padding: 9px 20px;
        border: none;
        border-radius: 9px;
        background: var(--button-color);
        color: #fff;
        font-size: 13.5px;
        font-weight: 600;
        cursor: pointer;
        font-family: inherit;
        transition: background 0.15s;
        white-space: nowrap;
    }
    .btn-call-update:hover { background: var(--button-hover); }
    
    .oc-panel-footer {
        padding: 16px 22px;
        text-align: center;
    }
    .oc-show-all {
        color: var(--button-color);
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
    }
    .oc-show-all:hover { text-decoration: underline; }
    
    @media (max-width: 768px) {
        .oc-call-row { flex-direction: column; align-items: flex-start; }
        .oc-call-right { width: 100%; justify-content: space-between; }
        .oc-stat-card { max-width: none; }
    }
    </style>
    
    {{-- ── Stat Cards ── --}}
    <div class="oc-stats-row">
        <div class="oc-stat-card">
            <div class="oc-stat-top">
                <div class="oc-stat-icon icon-live"><i class="fas fa-video"></i></div>
                <span class="oc-pill pill-live">Live Now</span>
            </div>
            <div class="oc-stat-number">{{ str_pad($ongoingSessions, 2, '0', STR_PAD_LEFT) }}</div>
            <div class="oc-stat-label">Ongoing Sessions</div>
        </div>
    
        <div class="oc-stat-card">
            <div class="oc-stat-top">
                <div class="oc-stat-icon icon-today"><i class="fas fa-clipboard-list"></i></div>
                <span class="oc-pill pill-today">Today</span>
            </div>
            <div class="oc-stat-number">{{ $totalAppointments }}</div>
            <div class="oc-stat-label">Total Appointments</div>
        </div>
    </div>
    
    {{-- ── Filter Toolbar ── --}}
    <div class="oc-toolbar">
        <div class="oc-search">
            <i class="fas fa-search"></i>
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="Search by name or UHID...">
        </div>

        <select class="oc-select" wire:model.live="datePreset">
            <option value="today">Date: Today</option>
            <option value="tomorrow">Date: Tomorrow</option>
            <option value="custom">Date: Custom</option>
        </select>

        @if($datePreset === 'custom')
            <input type="date"
                   wire:model.live="customDate"
                   class="oc-select"
                   style="width:auto;">
        @endif

        <select class="oc-select" wire:model.live="branchFilter">
            <option value="all">Branch: All</option>
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}">Branch: {{ $branch->name }}</option>
            @endforeach
        </select>

        <select class="oc-select" wire:model.live="statusFilter">
            <option value="all">Status: All</option>
            @foreach($statusOptions as $statusKey => $statusLabel)
                <option value="{{ $statusKey }}">Status: {{ $statusLabel }}</option>
            @endforeach
        </select>

        <div class="relative" wire:click.away="$set('showDateFilter', false)">
            <button type="button" class="btn-more-filter" wire:click="toggleDateFilter">
                <i class="fas fa-filter"></i> More
            </button>
            @if($showDateFilter)
                <div class="oc-date-panel">
                    <button type="button"
                            wire:click="set('datePreset', 'today')"
                            class="btn-more-filter"
                            style="width:100%; margin-bottom:8px; justify-content:center;">
                        Today
                    </button>
                    <button type="button"
                            wire:click="set('datePreset', 'tomorrow')"
                            class="btn-more-filter"
                            style="width:100%; margin-bottom:8px; justify-content:center;">
                        Tomorrow
                    </button>
                    <input type="date"
                           wire:model="customDate"
                           class="oc-select"
                           style="width:100%; margin-bottom:8px;">
                    <button type="button"
                            wire:click="applyCustomDate"
                            class="btn-call-update"
                            style="width:100%; justify-content:center;">
                        Apply Date
                    </button>
                </div>
            @endif
        </div>
    </div>
    
    {{-- ── Upcoming Video Calls Panel ── --}}
    <div class="oc-panel">
        <div class="oc-panel-header">
            <div class="oc-panel-title">Upcoming Video Calls</div>
            <div class="oc-panel-actions">
                <button type="button" class="oc-icon-btn" wire:click="refreshList" title="Refresh">
                    <i class="fas fa-sync-alt" wire:loading.class="fa-spin" wire:target="refreshList"></i>
                </button>
            </div>
        </div>

        @forelse($videoCalls as $call)
            <div class="oc-call-row" wire:key="oc-call-{{ $call['id'] }}">
                <div class="oc-call-patient">
                    @if(!empty($call['avatar_url']))
                        <img src="{{ $call['avatar_url'] }}"
                             alt="{{ $call['patient_name'] }}"
                             class="oc-call-avatar">
                    @else
                        <div class="oc-call-avatar" style="background:linear-gradient(135deg,#0da2e7,#0080c5); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:15px;">
                            {{ $call['initials'] }}
                        </div>
                    @endif
                    <div style="min-width:0;">
                        <div class="oc-call-name">{{ $call['patient_name'] }}</div>
                        <div class="oc-call-time">
                            <i class="far fa-clock"></i>
                            {{ $call['time_range'] }}
                        </div>
                    </div>
                </div>
                <div class="oc-call-right">
                    <span class="oc-call-status {{ $call['status_class'] }}">
                        @if($call['status_key'] === 'checked_in')
                            <span class="dot"></span>
                        @endif
                        {{ $call['status_label'] }}
                    </span>
                    <div class="oc-call-divider"></div>
                    <button type="button"
                            class="btn-call-update"
                            wire:click="openUpdateModal({{ $call['id'] }})">
                        Update
                    </button>
                </div>
            </div>
        @empty
            <div class="oc-empty">
                <h3>No online consultations found</h3>
                <p>Try changing the date, branch, or status filters.</p>
            </div>
        @endforelse

        @if($videoCalls->total() > 0)
            <div class="oc-panel-footer">
                {{ $videoCalls->links() }}
            </div>
        @endif
    </div>

    @if($showUpdateModal && $modalBooking)
        <div class="oc-modal-overlay" wire:click.self="closeUpdateModal">
            <div class="oc-modal" role="dialog" aria-modal="true">
                <div class="oc-modal-header">
                    <h2>Update Appointment Status</h2>
                    <button type="button" class="oc-modal-close" wire:click="closeUpdateModal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="oc-modal-body">
                    <div class="oc-modal-patient">
                        @if(!empty($modalBooking['avatar_url']))
                            <img src="{{ $modalBooking['avatar_url'] }}"
                                 alt="{{ $modalBooking['patient_name'] }}"
                                 class="oc-call-avatar">
                        @else
                            <div class="oc-call-avatar" style="background:linear-gradient(135deg,#0da2e7,#0080c5); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700;">
                                {{ $modalBooking['initials'] }}
                            </div>
                        @endif

                        <div style="min-width:0; flex:1;">
                            <h3>{{ $modalBooking['patient_name'] }}</h3>
                            <div class="oc-modal-meta">
                                <span><i class="far fa-id-card"></i> UHID: {{ $modalBooking['uhid'] }}</span>
                                <span><i class="far fa-clock"></i> {{ $modalBooking['time_label'] }}</span>
                            </div>
                        </div>

                        <span class="oc-modal-type">ONLINE</span>
                    </div>

                    <div class="oc-modal-label">SELECT NEW STATUS</div>

                    <div class="oc-status-grid">
                        @foreach([
                            'new_scheduled' => ['icon' => 'far fa-calendar', 'title' => 'New/Scheduled', 'desc' => 'Appointment is confirmed but not yet started'],
                            'checked_in' => ['icon' => 'fas fa-video', 'title' => 'Checked-In', 'desc' => 'Patient has joined or is waiting for the session'],
                            'completed' => ['icon' => 'far fa-check-circle', 'title' => 'Completed', 'desc' => 'Consultation is finished and records are updated'],
                            'cancelled' => ['icon' => 'far fa-times-circle', 'title' => 'Cancelled', 'desc' => 'Patient or doctor has cancelled the session'],
                        ] as $statusKey => $statusMeta)
                            <button type="button"
                                    wire:click="selectAppointmentStatus('{{ $statusKey }}')"
                                    class="oc-status-option {{ $appointmentStatus === $statusKey ? 'selected' : '' }}">
                                <div class="oc-status-option-icon">
                                    <i class="{{ $statusMeta['icon'] }}"></i>
                                </div>
                                <strong>{{ $statusMeta['title'] }}</strong>
                                <span>{{ $statusMeta['desc'] }}</span>
                            </button>
                        @endforeach
                    </div>

                    <div class="oc-link-block" x-data="{ copied: false, copy(value) { if (!value) return; navigator.clipboard.writeText(value); this.copied = true; setTimeout(() => this.copied = false, 2000); } }">
                        <div class="oc-link-head">
                            <strong>Online Consultation Link</strong>
                            <span class="oc-link-virtual"><i class="fas fa-info-circle"></i> Virtual Only</span>
                        </div>
                        <div class="oc-link-input-wrap">
                            <input type="url"
                                   wire:model="consultationLink"
                                   class="oc-link-input"
                                   placeholder="https://meet.healthinpocket.in/consult/...">
                            <button type="button"
                                    class="btn-copy-link"
                                    @click="copy($wire.consultationLink)">
                                <span x-show="!copied">Copy Link</span>
                                <span x-show="copied" x-cloak>Copied</span>
                            </button>
                        </div>
                        @error('consultationLink')
                            <p style="color:#dc2626; font-size:12px; margin-top:8px;">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="oc-modal-actions">
                        <button type="button" class="btn-oc-cancel" wire:click="closeUpdateModal">Cancel</button>
                        <button type="button"
                                class="btn-oc-save"
                                wire:click="updateAppointment"
                                wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="updateAppointment">Update &amp; Close</span>
                            <span wire:loading wire:target="updateAppointment">Updating...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
    </div>