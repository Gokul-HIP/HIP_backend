<div class="doc-appt">
    <style>
        @import '../../../assets/doctor-prescription.css';
    </style>
    {{-- ── Page Header ── --}}
    <div class="doc-appt-page-header">
        <div>
            <h1>My Appointments</h1>
            <p>Manage and track your patient consultation schedule</p>
        </div>
        {{-- <a href="#" class="btn-create-appt">
            <i class="fas fa-plus"></i> Create New Appointment
        </a> --}}
    </div>

    <div class="doc-appt-kpis">
        @foreach([
            'today' => ['label' => "Today", 'icon' => 'is-today', 'fa' => 'fa-calendar-day'],
            'follow-up' => ['label' => 'Follow-up', 'icon' => 'is-follow', 'fa' => 'fa-redo'],
            'upcoming' => ['label' => 'Upcoming', 'icon' => 'is-upcoming', 'fa' => 'fa-clock'],
            'completed' => ['label' => 'Completed', 'icon' => 'is-completed', 'fa' => 'fa-check-circle'],
            'cancelled' => ['label' => 'Cancelled', 'icon' => 'is-cancelled', 'fa' => 'fa-times-circle'],
        ] as $tabKey => $kpi)
            <button type="button"
                    class="doc-kpi {{ $activeTab === $tabKey ? 'is-active' : '' }}"
                    wire:click="setTab('{{ $tabKey }}')"
                    aria-pressed="{{ $activeTab === $tabKey ? 'true' : 'false' }}">
                <div class="doc-kpi-top">
                    <div class="doc-kpi-icon {{ $kpi['icon'] }}"><i class="fas {{ $kpi['fa'] }}"></i></div>
                    <span class="doc-kpi-chip">{{ in_array($tabKey, ['completed', 'cancelled'], true) ? 'All' : ($tabKey === 'follow-up' ? 'Today' : $kpi['label']) }}</span>
                </div>
                <div class="doc-kpi-label">{{ $kpi['label'] }}</div>
                <div class="doc-kpi-number">{{ (int) ($kpiCounts[$tabKey] ?? 0) }}</div>
            </button>
        @endforeach
    </div>

    {{-- ── Filter Bar ── --}}
    <div class="doc-appt-filter-bar">
        <div class="doc-appt-tabs appt-tabs">
            @foreach([
                'today' => 'Today',
                'follow-up' => 'Follow-up',
                'upcoming' => 'Upcoming',
                'completed' => 'Completed',
                'cancelled' => 'Cancelled',
            ] as $tabKey => $tabLabel)
                <button type="button"
                        wire:click="setTab('{{ $tabKey }}')"
                        class="doc-appt-tab appt-tab {{ $activeTab === $tabKey ? 'active' : '' }}">
                    {{ $tabLabel }}
                    <span class="doc-appt-tab-count">{{ (int) ($kpiCounts[$tabKey] ?? 0) }}</span>
                </button>
            @endforeach
        </div>

        <div class="doc-appt-filter-right appt-filter-right">
            <select class="doc-appt-select appt-select" wire:model.live="branchFilter" aria-label="Branch">
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
                               style="width:100%; margin-bottom:8px;"
                               aria-label="Custom date">
                        <button type="button"
                                wire:click="applyCustomDate"
                                class="doc-btn doc-btn-primary"
                                style="width:100%; justify-content:center;">
                            Apply Date
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Appointment Cards ── --}}
    <div class="doc-appt-grid">
        @forelse($appointments as $appt)
            <div class="doc-appt-card is-{{ $appt['status_key'] ?? 'new_scheduled' }}" wire:key="appt-{{ $appt['id'] }}">
                <div class="doc-appt-card-top">
                    <div class="doc-patient">
                        <div class="doc-avatar">
                            @if(!empty($appt['avatar_url']))
                                <img src="{{ $appt['avatar_url'] }}"
                                     alt="{{ $appt['patient_name'] }}"
                                     onerror="this.style.display='none'; this.nextElementSibling?.removeAttribute('hidden');">
                                <span hidden>{{ $appt['initials'] }}</span>
                            @else
                                {{ $appt['initials'] }}
                            @endif
                            @if(!empty($appt['is_online']))
                                <span class="doc-avatar-dot" aria-label="Online visit"></span>
                            @endif
                        </div>
                        <div style="min-width:0;">
                            <div class="doc-patient-name">{{ $appt['patient_name'] }}</div>
                            <div class="doc-patient-meta">{{ ($appt['age'] ?? '—') }} yrs · {{ $appt['gender_label'] ?? '—' }}</div>
                            <span class="doc-uhid">UHID {{ $appt['uhid'] }}</span>
                        </div>
                    </div>
                    <span class="doc-status {{ $appt['status_class'] }}">
                        <i class="fas fa-circle" aria-hidden="true"></i>
                        {{ $appt['status_label'] }}
                    </span>
                </div>

                <div class="doc-info-strip">
                    <div>
                        <div class="doc-info-label">{{ $appt['day_label'] ?? '' }}</div>
                        <div class="doc-info-value">{{ $appt['time_only'] }}</div>
                    </div>
                    <div>
                        <div class="doc-info-label">Branch</div>
                        <div class="doc-info-value">{{ $appt['branch'] }}</div>
                    </div>
                    <div>
                        <div class="doc-info-label">Visit type</div>
                        <div class="doc-info-value">
                            @if(!empty($appt['is_online']))
                                <i class="fas fa-video" aria-hidden="true"></i>
                            @endif
                            {{ $appt['type'] }}
                        </div>
                    </div>
                </div>

                <div class="doc-reason">
                    <span>Reason:</span> {{ $appt['reason_display'] ?? $appt['reason'] }}
                </div>

                <div class="doc-appt-card-actions">
                    <button type="button"
                            class="btn-view-patient"
                            wire:click="openPatientProfile({{ $appt['id'] }})">
                        View Patient
                    </button>
                    {{-- <button type="button"
                            class="btn-view-patient"
                            wire:click="openAppointmentHistory({{ $appt['id'] }})">
                        View History
                    </button> --}}
                    <button type="button"
                            class="btn-update"
                            wire:click="openUpdateModal({{ $appt['id'] }})">
                        Update status
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
        <x-doctor.pagination-footer
            :paginator="$appointments"
            label="appointments"
            class="appt-list-pagination" />
    @endif

    {{-- ── Update Status Modal ── --}}
    @if($showUpdateModal && $modalBooking)
        <div class="doc-appt-modal-overlay"
             wire:ignore
             x-data="{ status: $wire.entangle('appointmentStatus') }"
             wire:click.self="closeUpdateModal"
             role="presentation">
            <div class="doc-appt-modal" role="dialog" aria-modal="true" aria-labelledby="update-appt-title">
                <div class="doc-appt-modal-header">
                    <h2 id="update-appt-title">Update Appointment Status</h2>
                    <button type="button" class="doc-appt-modal-close" wire:click="closeUpdateModal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="doc-appt-modal-body">
                    <div class="doc-appt-modal-patient">
                        <div class="doc-avatar">
                            @if(!empty($modalBooking['avatar_url']))
                                <img src="{{ $modalBooking['avatar_url'] }}"
                                     alt="{{ $modalBooking['patient_name'] }}"
                                     onerror="this.style.display='none'; this.nextElementSibling?.removeAttribute('hidden');">
                                <span hidden>{{ $modalBooking['initials'] }}</span>
                            @else
                                {{ $modalBooking['initials'] }}
                            @endif
                        </div>

                        <div style="min-width:0; flex:1;">
                            <h3>{{ $modalBooking['patient_name'] }}</h3>
                            <div class="doc-patient-meta">
                                <span>UHID: {{ $modalBooking['uhid'] }}</span>
                                ·
                                <span>{{ $modalBooking['time_label'] }}</span>
                            </div>
                        </div>

                        <span class="doc-uhid">{{ $modalBooking['type_badge'] }}</span>
                    </div>

                    <div class="doc-appt-modal-label">SELECT NEW STATUS</div>

                    <div class="doc-appt-status-grid">
                        @foreach([
                            'new_scheduled' => ['icon' => 'far fa-calendar', 'title' => 'New/Scheduled', 'desc' => 'Appointment is confirmed but not yet started'],
                            'checked_in' => ['icon' => 'fas fa-map-marker-alt', 'title' => 'Checked-In', 'desc' => 'Patient has arrived or is waiting in the lobby'],
                            'completed' => ['icon' => 'far fa-check-circle', 'title' => 'Completed', 'desc' => 'Consultation is finished and records are updated'],
                            'cancelled' => ['icon' => 'far fa-times-circle', 'title' => 'Cancelled', 'desc' => 'Patient or doctor has cancelled the session'],
                        ] as $statusKey => $statusMeta)
                            <button type="button"
                                    wire:click="selectAppointmentStatus('{{ $statusKey }}')"
                                    @click="status = '{{ $statusKey }}'"
                                    class="doc-appt-status-option {{ $appointmentStatus === $statusKey ? 'selected' : '' }}"
                                    :class="status === '{{ $statusKey }}' && 'selected'">
                                <div class="appt-status-option-icon">
                                    <i class="{{ $statusMeta['icon'] }}"></i>
                                </div>
                                <strong>{{ $statusMeta['title'] }}</strong>
                                <span>{{ $statusMeta['desc'] }}</span>
                            </button>
                        @endforeach
                    </div>

                    <div class="doc-appt-modal-actions">
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

    @include('components.doctor.patient-profile-panel')

    @include('components.doctor.appointment-history-modal', [
        'appointmentHistory' => $appointmentHistory ?? [],
        'appointmentHistoryPaginator' => $appointmentHistoryPaginator ?? null,
    ])

    @include('components.doctor.follow-up-modal')
</div>

@assets
    <script src="{{ versioned_asset('assets/js/doctor-dash-appt.js') }}"></script>
@endassets

@script
<script>
    $wire.on('doctor-appt-modal-opened', () => {
        queueMicrotask(() => window.DoctorDashAppt?.trapModal(document.querySelector('.doc-appt-modal-overlay')));
    });
    $wire.on('doctor-appt-modal-closed', () => window.DoctorDashAppt?.releaseModal());

    document.addEventListener('doc-appt-close', () => $wire.closeUpdateModal());
</script>
@endscript
