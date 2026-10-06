<div class="doc-oc"
     @if(! $showUpdateModal && empty($showPatientProfilePanel) && empty($showBookFollowUpModal) && empty($showAppointmentHistoryModal))
         wire:poll.30s.visible
     @endif>
    <style>
        @import '../../../assets/doctor-prescription.css';
    </style>

    <div class="doc-appt-page-header">
        <div>
            <h1>Online Consultation</h1>
            <p>Start, track and manage your video consultations</p>
        </div>
        <button type="button"
                class="doc-btn"
                wire:click="refreshList"
                wire:loading.attr="disabled"
                wire:target="refreshList"
                aria-label="Refresh consultations">
            <i class="fas fa-sync-alt" wire:loading.class="fa-spin" wire:target="refreshList"></i>
            Refresh
        </button>
    </div>

    @if($heroCall)
        <section class="doc-oc-hero {{ $heroCall['status_key'] === 'checked_in' ? 'is-waiting' : '' }}">
            <div class="doc-oc-hero-deco" aria-hidden="true"></div>
            <div class="doc-oc-hero-deco doc-oc-hero-deco-2" aria-hidden="true"></div>
            <span class="doc-oc-hero-pill">{{ $heroCall['hero_pill'] }}</span>
            <h2>{{ $heroCall['patient_name'] }}</h2>
            <p>{{ $heroCall['hero_meta'] }}</p>
            <div class="doc-oc-hero-chips">
                <span>{{ $heroCall['time_chip'] }}</span>
                @if($heroCall['hero_state'])
                    <span class="{{ $heroCall['countdown_tone'] }}">{{ $heroCall['hero_state'] }}</span>
                @endif
            </div>
            <div class="doc-oc-hero-actions">
                @if($heroCall['join_url'])
                    <a class="doc-btn doc-btn-primary"
                       href="{{ $heroCall['join_url'] }}"
                       target="_blank"
                       rel="noopener noreferrer">
                        {{ $heroCall['join_label'] }}
                    </a>
                @else
                    <button type="button"
                            class="doc-btn doc-btn-primary"
                            disabled
                            title="Add a consultation link using Update">
                        {{ $heroCall['join_label'] }}
                    </button>
                @endif
                <button type="button"
                        class="doc-btn doc-oc-hero-ghost"
                        wire:click="openPatientProfile({{ $heroCall['id'] }})">
                    View patient
                </button>
            </div>
        </section>
    @else
        <section class="doc-oc-hero is-empty">
            <h2>No upcoming video consultations</h2>
            <p>There is no checked-in or upcoming online consultation right now.</p>
        </section>
    @endif

    <div class="doc-dash-kpis doc-oc-kpis">
        <div class="doc-kpi">
            <div class="doc-kpi-top">
                <div class="doc-kpi-icon is-today"><i class="fas fa-user-clock"></i></div>
                <span class="doc-kpi-chip">Checked in</span>
            </div>
            <div class="doc-kpi-label">Waiting / checked in</div>
            <div class="doc-kpi-number">{{ (int) ($kpis['waiting_checked_in'] ?? $ongoingSessions) }}</div>
        </div>
        <div class="doc-kpi">
            <div class="doc-kpi-top">
                <div class="doc-kpi-icon is-follow"><i class="fas fa-video"></i></div>
                <span class="doc-kpi-chip">Today</span>
            </div>
            <div class="doc-kpi-label">Today's video calls</div>
            <div class="doc-kpi-number">{{ (int) ($kpis['today_calls'] ?? $totalAppointments) }}</div>
        </div>
        <div class="doc-kpi">
            <div class="doc-kpi-top">
                <div class="doc-kpi-icon is-upcoming"><i class="fas fa-clock"></i></div>
                <span class="doc-kpi-chip">Upcoming</span>
            </div>
            <div class="doc-kpi-label">Upcoming</div>
            <div class="doc-kpi-number">{{ (int) ($kpis['upcoming'] ?? 0) }}</div>
        </div>
        <div class="doc-kpi">
            <div class="doc-kpi-top">
                <div class="doc-kpi-icon is-completed"><i class="fas fa-check-circle"></i></div>
                <span class="doc-kpi-chip">This month</span>
            </div>
            <div class="doc-kpi-label">Completed</div>
            <div class="doc-kpi-number">{{ (int) ($kpis['completed'] ?? 0) }}</div>
        </div>
    </div>

    {{-- ── Filter Toolbar ── --}}
    <div class="oc-toolbar doc-oc-toolbar">
        <div class="oc-search">
            <i class="fas fa-search"></i>
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="Search by name, HIP ID, user ID, or person ID..."
                   aria-label="Search consultations">
        </div>

        <select class="oc-select doc-appt-select" wire:model.live="datePreset" aria-label="Date">
            <option value="today">Date: Today</option>
            <option value="tomorrow">Date: Tomorrow</option>
            <option value="next_7">Date: Next 7 days</option>
            <option value="custom">Date: Custom</option>
        </select>

        @if($datePreset === 'custom')
            <input type="date"
                   wire:model.live="customDate"
                   class="oc-select doc-appt-select"
                   aria-label="Custom date">
        @endif

        <select class="oc-select doc-appt-select" wire:model.live="branchFilter" aria-label="Branch">
            <option value="all">Branch: All</option>
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}">Branch: {{ $branch->name }}</option>
            @endforeach
        </select>

        <select class="oc-select doc-appt-select"
                wire:model.live="statusFilter"
                aria-label="Status"
                @if($listTab !== 'upcoming') hidden @endif>
            <option value="all">Status: All</option>
            @foreach($tabStatusOptions as $statusKey => $statusLabel)
                <option value="{{ $statusKey }}">Status: {{ $statusLabel }}</option>
            @endforeach
        </select>

        <div class="relative" wire:click.away="$set('showDateFilter', false)">
            <button type="button" class="btn-more-filter btn-filter" wire:click="toggleDateFilter">
                <i class="fas fa-filter"></i> More
            </button>
            @if($showDateFilter)
                <div class="oc-date-panel appt-date-filter-panel">
                    <button type="button"
                            wire:click="set('datePreset', 'today')"
                            class="btn-more-filter appt-date-option {{ $datePreset === 'today' ? 'active' : '' }}">
                        Today
                    </button>
                    <button type="button"
                            wire:click="set('datePreset', 'tomorrow')"
                            class="btn-more-filter appt-date-option {{ $datePreset === 'tomorrow' ? 'active' : '' }}">
                        Tomorrow
                    </button>
                    <button type="button"
                            wire:click="set('datePreset', 'next_7')"
                            class="btn-more-filter appt-date-option {{ $datePreset === 'next_7' ? 'active' : '' }}">
                        Next 7 days
                    </button>
                    <input type="date"
                           wire:model="customDate"
                           class="oc-select doc-appt-select"
                           aria-label="More custom date">
                    <button type="button"
                            wire:click="applyCustomDate"
                            class="btn-call-update doc-btn doc-btn-primary"
                            style="width:100%; justify-content:center;">
                        Apply Date
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- ── Upcoming Video Calls Panel ── --}}
    <div class="oc-panel doc-oc-panel">
        <div class="oc-panel-header">
            <div>
                <div class="oc-panel-title">Upcoming Video Calls</div>
                <div class="doc-appt-tabs">
                    @foreach(['upcoming' => 'Upcoming', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $tabKey => $tabLabel)
                        <button type="button"
                                class="doc-appt-tab {{ $listTab === $tabKey ? 'active' : '' }}"
                                wire:click="setListTab('{{ $tabKey }}')">
                            {{ $tabLabel }}
                            <span class="doc-appt-tab-count">{{ (int) ($tabCounts[$tabKey] ?? 0) }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
            <div class="oc-panel-actions">
                <button type="button" class="oc-icon-btn" wire:click="refreshList" title="Refresh" aria-label="Refresh list">
                    <i class="fas fa-sync-alt" wire:loading.class="fa-spin" wire:target="refreshList"></i>
                </button>
            </div>
        </div>

        @forelse($groupedCalls as $dayKey => $group)
            <div class="doc-oc-day-group" wire:key="oc-day-{{ $dayKey }}">
                <div class="doc-oc-day">{{ $group['label'] }}</div>
                @foreach($group['rows'] as $call)
                    <div class="oc-call-row doc-oc-row is-{{ $call['status_key'] }}" wire:key="oc-call-{{ $call['id'] }}">
                        <div class="doc-oc-time">
                            <strong>{{ $call['time_range'] }}</strong>
                            @if($call['countdown'])
                                <span class="doc-oc-note {{ $call['countdown_tone'] }}">{{ $call['countdown'] }}</span>
                            @endif
                        </div>
                        <div class="oc-call-patient">
                            <div class="oc-avatar-slot doc-avatar is-{{ $call['avatar_tone'] }}">
                                @if(!empty($call['avatar_url']))
                                    <img src="{{ $call['avatar_url'] }}"
                                         alt=""
                                         class="oc-call-avatar"
                                         onerror="ocAvatarError(this)">
                                    <div class="oc-call-avatar oc-call-avatar-fallback is-hidden"
                                         aria-hidden="true">{{ $call['initials'] }}</div>
                                @else
                                    <div class="oc-call-avatar oc-call-avatar-fallback">{{ $call['initials'] }}</div>
                                @endif
                                <span class="doc-avatar-dot" aria-hidden="true"></span>
                            </div>
                            <div style="min-width:0;">
                                <div class="oc-call-name">{{ $call['patient_name'] }}</div>
                                <div class="oc-call-time">
                                    @if($call['age'] !== null){{ $call['age'] }} yrs @endif
                                    @if($call['gender_label'])
                                        {{ $call['age'] !== null ? '· ' : '' }}{{ $call['gender_label'] }}
                                    @endif
                                    · UHID {{ $call['uhid'] }}
                                    @if(!empty($call['is_follow_up']))
                                        <span class="doc-kpi-chip doc-oc-follow">Follow-up</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="doc-oc-reason">{{ $call['reason_display'] }}</div>
                        <div class="oc-call-right">
                            <span class="oc-call-status doc-status {{ $call['status_class'] }}">
                                <i class="fas fa-circle" aria-hidden="true"></i>
                                {{ $call['status_label'] }}
                            </span>
                            <div class="oc-call-divider"></div>
                            @if($call['join_url'])
                                <a class="doc-btn doc-btn-primary"
                                   href="{{ $call['join_url'] }}"
                                   target="_blank"
                                   rel="noopener noreferrer">
                                    {{ $call['join_label'] }}
                                </a>
                            @else
                                <button type="button"
                                        class="doc-btn doc-btn-primary"
                                        disabled
                                        title="Add a consultation link using Update">
                                    {{ $call['join_label'] }}
                                </button>
                            @endif
                            <button type="button"
                                    class="doc-btn"
                                    wire:click="openPatientProfile({{ $call['id'] }})">
                                View
                            </button>
                            <button type="button"
                                    class="btn-call-update doc-btn doc-btn-primary"
                                    wire:click="openUpdateModal({{ $call['id'] }})">
                                Update
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @empty
            <div class="oc-empty appt-empty">
                <h3>No consultations found</h3>
                <p>There are no online consultations for the selected search, date, branch, and status.</p>
            </div>
        @endforelse

        @if($videoCalls->total() > 0)
            <x-doctor.pagination-footer
                :paginator="$videoCalls"
                label="consultations"
                class="oc-list-pagination" />
        @endif
    </div>

    @if($showUpdateModal && $modalBooking)
        <div class="oc-modal-overlay doc-appt-modal-overlay" wire:click.self="closeUpdateModal">
            <div class="oc-modal doc-appt-modal" role="dialog" aria-modal="true">
                <div class="oc-modal-header doc-appt-modal-header">
                    <h2>Update Appointment Status</h2>
                    <button type="button" class="oc-modal-close doc-appt-modal-close" wire:click="closeUpdateModal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="oc-modal-body doc-appt-modal-body">
                    <div class="oc-modal-patient doc-appt-modal-patient">
                        <div class="oc-avatar-slot doc-avatar is-primary">
                            @if(!empty($modalBooking['avatar_url']))
                                <img src="{{ $modalBooking['avatar_url'] }}"
                                     alt=""
                                     class="oc-call-avatar"
                                     onerror="ocAvatarError(this)">
                                <div class="oc-call-avatar oc-call-avatar-fallback is-hidden"
                                     aria-hidden="true">{{ $modalBooking['initials'] }}</div>
                            @else
                                <div class="oc-call-avatar oc-call-avatar-fallback">
                                    {{ $modalBooking['initials'] }}
                                </div>
                            @endif
                        </div>

                        <div style="min-width:0; flex:1;">
                            <h3>{{ $modalBooking['patient_name'] }}</h3>
                            <div class="oc-modal-meta">
                                <span><i class="far fa-id-card"></i> UHID: {{ $modalBooking['uhid'] }}</span>
                                <span><i class="far fa-clock"></i> {{ $modalBooking['time_label'] }}</span>
                            </div>
                        </div>

                        <span class="oc-modal-type">ONLINE</span>
                    </div>

                    <div class="oc-modal-label doc-appt-modal-label">SELECT NEW STATUS</div>

                    <div class="oc-status-grid doc-appt-status-grid">
                        @foreach([
                            'new_scheduled' => ['icon' => 'far fa-calendar', 'title' => 'New/Scheduled', 'desc' => 'Appointment is confirmed but not yet started'],
                            'checked_in' => ['icon' => 'fas fa-video', 'title' => 'Checked-In', 'desc' => 'Patient has joined or is waiting for the session'],
                            'completed' => ['icon' => 'far fa-check-circle', 'title' => 'Completed', 'desc' => 'Consultation is finished and records are updated'],
                            'cancelled' => ['icon' => 'far fa-times-circle', 'title' => 'Cancelled', 'desc' => 'Patient or doctor has cancelled the session'],
                        ] as $statusKey => $statusMeta)
                            <button type="button"
                                    wire:click="selectAppointmentStatus('{{ $statusKey }}')"
                                    class="oc-status-option doc-appt-status-option {{ $appointmentStatus === $statusKey ? 'selected' : '' }}">
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
                                    class="btn-copy-link doc-btn"
                                    @click="copy($wire.consultationLink)">
                                <span x-show="!copied">Copy Link</span>
                                <span x-show="copied" x-cloak>Copied</span>
                            </button>
                        </div>
                        @error('consultationLink')
                            <p class="doc-oc-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="oc-modal-actions doc-appt-modal-actions">
                        <button type="button" class="btn-oc-cancel btn-modal-cancel" wire:click="closeUpdateModal">Cancel</button>
                        <button type="button"
                                class="btn-oc-save btn-modal-save"
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

    @include('components.doctor.patient-profile-panel')

    @include('components.doctor.appointment-history-modal', [
        'appointmentHistory' => $appointmentHistory ?? [],
        'appointmentHistoryPaginator' => $appointmentHistoryPaginator ?? null,
    ])

    @include('components.doctor.follow-up-modal')

    <script>
        function ocAvatarError(img) {
            img.onerror = null;
            img.style.display = 'none';
            var fallback = img.nextElementSibling;
            if (fallback) {
                fallback.classList.remove('is-hidden');
                fallback.style.display = 'flex';
            }
        }
    </script>
    </div>
