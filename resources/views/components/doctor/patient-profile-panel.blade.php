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
                    @forelse($profilePatient['appointment_timeline'] ?? [] as $appointment)
                        <div class="pp-timeline-item {{ $appointment['class'] }}">
                            <div class="pp-timeline-label">{{ $appointment['label'] }}</div>
                            <div class="pp-timeline-line">{{ $appointment['line'] }}</div>
                        </div>
                    @empty
                        <div class="pp-timeline-line" style="color:#9ca3af; font-style:italic;">No appointment history available.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="pp-panel-footer">
            <button type="button" class="pp-footer-btn outline" wire:click="openFullHistoryFromProfile">
                View Full History
            </button>
            <a href="{{ route('doctor.patient-document.view-document', ['patient_id' => $profilePatient['booking_id']]) }}"
               class="pp-footer-btn outline">
                Documents
            </a>
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
