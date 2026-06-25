<div style="padding: 28px 32px;">
    <style>
        @import '../../../assets/doctor-prescription.css';
    </style>
    
    {{-- ── Breadcrumb ── --}}
    <div class="mp-breadcrumb">
        <a href="{{ route('doctor.dashboard.index') }}">
            <i class="fas fa-chevron-left" style="font-size:10px;"></i> Back
        </a>
        <span class="sep">/</span>
        <span class="current">Patients</span>
    </div>
    
    {{-- ── Page Header ── --}}
    <div class="mp-page-header">
        <div>
            <h1>My Patients</h1>
            <p>Manage and access your patient records securely.</p>
        </div>
        <div class="mp-header-actions">
            <button type="button" class="btn-export" wire:click="exportList">
                <i class="fas fa-download"></i> Export List
            </button>
            <button type="button" class="btn-register" wire:click="openRegisterModal">
                <i class="fas fa-user-plus"></i> Register Patient
            </button>
        </div>
    </div>
    
    {{-- ── Stat Cards ── --}}
    <div class="mp-stats-row">
        <div class="mp-stat-card">
            <div class="mp-stat-icon mp-icon-red"><i class="fas fa-user-injured"></i></div>
            <div>
                <div class="mp-stat-label">My Patients</div>
                <div class="mp-stat-number">{{ $myPatientsCount }}</div>
            </div>
        </div>
        <div class="mp-stat-card">
            <div class="mp-stat-icon mp-icon-blue"><i class="fas fa-calendar-day"></i></div>
            <div>
                <div class="mp-stat-label">Today's Patients</div>
                <div class="mp-stat-number">{{ $todayPatientsCount }}</div>
            </div>
        </div>
        <div class="mp-stat-card">
            <div class="mp-stat-icon mp-icon-orange"><i class="fas fa-history"></i></div>
            <div>
                <div class="mp-stat-label">Follow-up Patients</div>
                <div class="mp-stat-number">{{ $followUpCount }}</div>
            </div>
        </div>
        <div class="mp-stat-card">
            <div class="mp-stat-icon mp-icon-green"><i class="fas fa-user-check"></i></div>
            <div>
                <div class="mp-stat-label">New This Month</div>
                <div class="mp-stat-number">{{ $newThisMonthCount }}</div>
            </div>
        </div>
    </div>
    
    {{-- ── Filter Panel ── --}}
    <div class="mp-filter-panel">
        <div class="mp-filter-row">
            <div class="mp-filter-group grow">
                <div class="mp-filter-label">Search Patient</div>
                <div class="mp-search">
                    <i class="fas fa-user-friends"></i>
                    <input type="text"
                           wire:model.live.debounce.300ms="search"
                           placeholder="Name, HIP ID, UHID, or mobile number">
                </div>
            </div>
            <div class="mp-filter-group">
                <div class="mp-filter-label">Branch</div>
                <select class="mp-select" wire:model.live="branchFilter">
                    <option value="all">All Branches</option>
                    @foreach($branches ?? [] as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mp-filter-group">
                <div class="mp-filter-label">Visit Type</div>
                <select class="mp-select" wire:model.live="visitTypeFilter">
                    <option value="all">All Types</option>
                    <option value="new">New</option>
                    <option value="follow-up">Follow-up</option>
                    <option value="online">Online</option>
                </select>
            </div>
            <div class="mp-filter-group">
                <div class="mp-filter-label">Status</div>
                <select class="mp-select" wire:model.live="statusFilter">
                    <option value="all">All Status</option>
                    <option value="active">Active</option>
                    <option value="upcoming">Upcoming</option>
                    <option value="completed">Completed</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <button type="button" class="btn-mp-reset" wire:click="resetFilters">
                <i class="fas fa-redo-alt"></i> Reset Filters
            </button>
        </div>
    
        {{-- Active filter tags --}}
        @if(!empty($activeFilterTags))
            <div class="mp-filter-tags">
                @foreach($activeFilterTags as $tag)
                    <span class="mp-filter-tag">
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
    <div class="mp-table-wrap">
        <table class="mp-table">
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>UHID</th>
                    <th>Age / Gender</th>
                    <th>Mobile</th>
                    <th>Last Visit</th>
                    <th>Next Appt.</th>
                    <th>Visit Type</th>
                    {{-- <th>Status</th> --}}
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($patients as $patient)
                    <tr wire:key="patient-{{ $patient['booking_id'] }}-{{ $patient['id'] }}">
                        <td>
                            <div class="mp-patient-cell">
                                <div class="mp-avatar-slot">
                                    @if(!empty($patient['avatar_url']))
                                        <img src="{{ $patient['avatar_url'] }}"
                                             alt="{{ $patient['name'] }}"
                                             class="mp-patient-init"
                                             onerror="this.style.display='none'; this.nextElementSibling?.classList.remove('is-hidden');">
                                        <div class="mp-patient-init is-hidden"
                                             style="background: {{ $patient['avatar_color'] }};">
                                            {{ $patient['initials'] }}
                                        </div>
                                    @else
                                        <div class="mp-patient-init"
                                             style="background: {{ $patient['avatar_color'] }};">
                                            {{ $patient['initials'] }}
                                        </div>
                                    @endif
                                </div>
                                <span class="mp-patient-name">{{ $patient['name'] }}</span>
                            </div>
                        </td>
                        <td>{{ $patient['uhid'] }}</td>
                        <td>{{ $patient['age'] ?? '—' }} / {{ $patient['gender'] }}</td>
                        <td>{{ $patient['mobile'] }}</td>
                        <td>{{ $patient['last_visit'] }}</td>
                        <td>{{ $patient['next_appointment'] }}</td>
                        <td>
                            <span class="mp-visit-badge {{ $patient['visit_type_class'] }}">
                                {{ $patient['visit_type'] }}
                            </span>
                        </td>
                        {{-- <td>
                            <span class="mp-status mp-status-{{ $patient['status_class'] }}">
                                {{ $patient['status'] }}
                            </span>
                        </td> --}}
                        <td>
                            <div class="mp-actions">
                                @if($patient['booking_id'])
                                    {{-- <button type="button"
                                            class="btn-mp-update"
                                            wire:click="openUpdateModal({{ $patient['booking_id'] }})">
                                        Update
                                    </button> --}}
                                    <button type="button"
                                            class="btn-mp-update"
                                            wire:click="openPatientProfile({{ $patient['booking_id'] }})">
                                        View Profile
                                    </button>
                                    <a href="{{ route('doctor.upload-prescription.create-prescription.index', ['patient_id' => $patient['booking_id']]) }}"
                                       class="btn-mp-icon"
                                       title="Create Prescription">
                                       <i class="fa-regular fa-folder"></i>
                                    </a>
                                    <a href="{{ route('doctor.upload-prescription.create-prescription.index', ['patient_id' => $patient['booking_id']]) }}"
                                       class="btn-mp-icon"
                                       title="Create Prescription">
                                       <i class="fa-solid fa-clipboard-list"></i>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align:center; color:#9ca3af; padding:32px 20px;">
                            No patients found for the selected filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    
        @if($patients->total() > 0)
            <div class="mp-table-footer">
                <div class="mp-showing">
                    Showing <strong>{{ $patients->firstItem() }}–{{ $patients->lastItem() }}</strong>
                    of <strong>{{ $patients->total() }}</strong> patients
                </div>
                <div class="mp-pages">
                    @if($patients->hasPages())
                        {{ $patients->onEachSide(1)->links() }}
                    @endif
                </div>
            </div>
        @endif
    </div>
    
    {{-- @if($showUpdateModal && $modalBooking)
        <div class="mp-modal-overlay" wire:click.self="closeUpdateModal">
            <div class="mp-modal" role="dialog" aria-modal="true" aria-labelledby="mp-update-title">
                <div class="mp-modal-header">
                    <h2 id="mp-update-title">Update Appointment Status</h2>
                    <button type="button" class="mp-modal-close" wire:click="closeUpdateModal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
    
                <div class="mp-modal-body">
                    <div class="mp-modal-patient">
                        <div class="mp-patient-init" style="background:#c8102e; width:42px; height:42px;">
                            {{ $modalBooking['initials'] }}
                        </div>
                        <div style="min-width:0; flex:1;">
                            <h3>{{ $modalBooking['patient_name'] }}</h3>
                            <div class="mp-modal-meta">
                                <span><i class="far fa-id-card"></i> UHID: {{ $modalBooking['uhid'] }}</span>
                                <span><i class="far fa-clock"></i> {{ $modalBooking['time_label'] }}</span>
                            </div>
                        </div>
                        <span class="mp-visit-badge visit-new">{{ $modalBooking['type_badge'] }}</span>
                    </div>
    
                    <div class="mp-modal-label">SELECT NEW STATUS</div>
    
                    <div class="mp-status-grid">
                        @foreach([
                            'new_scheduled' => ['title' => 'New/Scheduled', 'desc' => 'Appointment is confirmed but not yet started'],
                            'checked_in' => ['title' => 'Checked-In', 'desc' => 'Patient has arrived or is waiting in the lobby'],
                            'completed' => ['title' => 'Completed', 'desc' => 'Consultation is finished and records are updated'],
                            'cancelled' => ['title' => 'Cancelled', 'desc' => 'Patient or doctor has cancelled the session'],
                        ] as $statusKey => $statusMeta)
                            <button type="button"
                                    wire:click="selectAppointmentStatus('{{ $statusKey }}')"
                                    class="mp-status-option {{ $appointmentStatus === $statusKey ? 'selected' : '' }}">
                                <strong>{{ $statusMeta['title'] }}</strong>
                                <span>{{ $statusMeta['desc'] }}</span>
                            </button>
                        @endforeach
                    </div>
    
                    <div class="mp-modal-actions">
                        <button type="button" class="btn-mp-modal-cancel" wire:click="closeUpdateModal">Cancel</button>
                        <button type="button"
                                class="btn-mp-modal-save"
                                wire:click="updateAppointmentStatus"
                                wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="updateAppointmentStatus">Update &amp; Close</span>
                            <span wire:loading wire:target="updateAppointmentStatus">Updating...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif --}}

    @if($showPatientProfilePanel && $profilePatient)
        <div class="pp-overlay" wire:click="closePatientProfile"></div>
        <aside class="pp-panel" role="dialog" aria-label="Patient Profile Quick View">
            <div class="pp-panel-header">
                <h2>Patient Profile Quick View</h2>
                <button type="button" class="pp-panel-close" wire:click="closePatientProfile" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="pp-panel-body">
                <div class="pp-patient-card">
                    <div class="pp-patient-top">
                        <div class="pp-patient-avatar">
                            @if(!empty($profilePatient['avatar_url']))
                                <img src="{{ $profilePatient['avatar_url'] }}" alt="{{ $profilePatient['name'] }}">
                            @else
                                {{ $profilePatient['initials'] }}
                            @endif
                        </div>
                        <div>
                            <div class="pp-patient-name">{{ ucfirst($profilePatient['name']) }}</div>
                            <div class="pp-patient-uhid">UHID: {{ $profilePatient['uhid'] }}</div>
                        </div>
                    </div>
                    <div class="pp-badges">
                        @if(filled($profilePatient['blood_group']))
                            <span class="pp-badge blood"><i class="fas fa-tint"></i> {{ $profilePatient['blood_group'] }}</span>
                        @endif
                        @if($profilePatient['age'])
                            <span class="pp-badge">{{ $profilePatient['age'] }} Years</span>
                        @endif
                        @if(filled($profilePatient['gender']))
                            <span class="pp-badge">{{ $profilePatient['gender'] }}</span>
                        @endif
                    </div>
                </div>

                <div class="pp-section">
                    <div class="pp-section-title">Contact Details</div>
                    <div class="pp-contact-grid">
                        <div class="pp-contact-item">
                            <div class="pp-contact-label">Mobile</div>
                            <div class="pp-contact-value">{{ $profilePatient['mobile'] }}</div>
                        </div>
                        <div class="pp-contact-item">
                            <div class="pp-contact-label">Email</div>
                            <div class="pp-contact-value">{{ $profilePatient['email'] }}</div>
                        </div>
                        <div class="pp-contact-item full">
                            <div class="pp-contact-label">Address</div>
                            <div class="pp-contact-value">{{ $profilePatient['address'] ?: '—' }}</div>
                        </div>
                        <div class="pp-contact-item">
                            <div class="pp-contact-label">Emergency Contact</div>
                            <div class="pp-contact-value">{{ $profilePatient['emergency_contact'] }}</div>
                        </div>
                        <div class="pp-contact-item">
                            <div class="pp-contact-label">Emergency Mobile</div>
                            <div class="pp-contact-value">{{ $profilePatient['emergency_mobile'] }}</div>
                        </div>
                    </div>
                </div>

                <div class="pp-section">
                    <div class="pp-section-title">Clinical Highlights</div>
                    <div class="pp-clinical-item">
                        <div class="pp-clinical-icon warn"><i class="fas fa-exclamation-triangle"></i></div>
                        <div>
                            <div class="pp-clinical-label">Known Allergies</div>
                            <div class="pp-clinical-value danger">{{ $profilePatient['allergies'] ?: '—' }}</div>
                        </div>
                    </div>
                    <div class="pp-clinical-item">
                        <div class="pp-clinical-icon med"><i class="fas fa-briefcase-medical"></i></div>
                        <div>
                            <div class="pp-clinical-label">Medical History</div>
                            <div class="pp-clinical-value">{{ $profilePatient['medical_history'] ?: '—' }}</div>
                        </div>
                    </div>
                    <div class="pp-clinical-item">
                        <div class="pp-clinical-icon pill"><i class="fas fa-pills"></i></div>
                        <div>
                            <div class="pp-clinical-label">Current Medications</div>
                            <div class="pp-clinical-value">{{ $profilePatient['current_medications'] }}</div>
                        </div>
                    </div>
                </div>

                <div class="pp-recent-row">
                    <div class="pp-recent-card">
                        <span class="pp-recent-card-when">{{ $profilePatient['recent_lab']['when'] }}</span>
                        <div class="pp-recent-card-icon"><i class="fas fa-microscope"></i></div>
                        <div class="pp-recent-card-title">Lab Reports</div>
                        <div class="pp-recent-card-sub">{{ $profilePatient['recent_lab']['subtitle'] }}</div>
                    </div>
                    <div class="pp-recent-card">
                        <span class="pp-recent-card-when">{{ $profilePatient['recent_prescription']['when'] }}</span>
                        <div class="pp-recent-card-icon"><i class="fas fa-link"></i></div>
                        <div class="pp-recent-card-title">{{ $profilePatient['recent_prescription']['title'] }}</div>
                        <div class="pp-recent-card-sub">{{ $profilePatient['recent_prescription']['subtitle'] }}</div>
                    </div>
                </div>

                <div class="pp-section">
                    <div class="pp-section-title">Appointment Timeline</div>
                    <div class="pp-timeline">
                        @if($profilePatient['upcoming_appointment'])
                            <div class="pp-timeline-item upcoming">
                                <div class="pp-timeline-label">{{ $profilePatient['upcoming_appointment']['label'] }}</div>
                                <div class="pp-timeline-line">{{ $profilePatient['upcoming_appointment']['line'] }}</div>
                            </div>
                        @endif
                        @if($profilePatient['last_consultation'])
                            <div class="pp-timeline-item past">
                                <div class="pp-timeline-label">{{ $profilePatient['last_consultation']['label'] }}</div>
                                <div class="pp-timeline-line">{{ $profilePatient['last_consultation']['line'] }}</div>
                            </div>
                        @endif
                        @if(!$profilePatient['upcoming_appointment'] && !$profilePatient['last_consultation'])
                            <div class="pp-timeline-line" style="color:#9ca3af; font-style:italic;">No appointment history available.</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="pp-panel-footer">
                <button type="button" class="pp-footer-btn outline" wire:click="closePatientProfile">
                    View Full History
                </button>
                <button type="button" class="pp-footer-btn outline" wire:click="showPatientDocuments">
                    Documents
                </button>
                <a href="{{ route('doctor.upload-prescription.create-prescription.index', ['patient_id' => $profilePatient['booking_id']]) }}"
                   class="pp-footer-btn outline">
                    Prescription
                </a>
                <button type="button"
                        class="pp-footer-btn primary"
                        wire:click="bookFollowUp">
                    Book Follow-up
                </button>
            </div>
        </aside>
    @endif

    </div>