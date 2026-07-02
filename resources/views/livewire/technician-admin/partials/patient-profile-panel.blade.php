@if($showPatientProfilePanel && $profilePatient)
    <div class="pp-overlay" wire:click="closePatientProfile"></div>
    <aside class="pp-panel" role="dialog" aria-label="Patient Profile Quick View">
        <div class="pp-panel-header">
            <h2>Patient Profile Quick View</h2>
            <button type="button" class="pp-panel-close" wire:click="closePatientProfile"><i class="fas fa-times"></i></button>
        </div>
        <div class="pp-panel-body">
            <div class="pp-patient-card">
                <div class="pp-patient-top">
                    <div class="pp-patient-avatar">{{ $profilePatient['initials'] }}</div>
                    <div>
                        <div class="pp-patient-name">{{ ucfirst($profilePatient['name']) }}</div>
                        <div class="pp-patient-uhid">UHID: {{ $profilePatient['uhid'] }}</div>
                    </div>
                </div>
                <div class="pp-badges">
                    @if(filled($profilePatient['blood_group']))
                        <span class="pp-badge blood"><i class="fas fa-tint"></i> {{ $profilePatient['blood_group'] }}</span>
                    @endif
                    @if($profilePatient['age'])<span class="pp-badge">{{ $profilePatient['age'] }} Years</span>@endif
                    @if(filled($profilePatient['gender']))<span class="pp-badge">{{ $profilePatient['gender'] }}</span>@endif
                </div>
            </div>
            <div class="pp-section">
                <div class="pp-section-title">Contact Details</div>
                <div class="pp-contact-grid">
                    <div class="pp-contact-item"><div class="pp-contact-label">Mobile</div><div class="pp-contact-value">{{ $profilePatient['mobile'] }}</div></div>
                    <div class="pp-contact-item"><div class="pp-contact-label">Email</div><div class="pp-contact-value">{{ $profilePatient['email'] }}</div></div>
                    <div class="pp-contact-item full"><div class="pp-contact-label">Address</div><div class="pp-contact-value">{{ $profilePatient['address'] ?: '—' }}</div></div>
                </div>
            </div>
            <div class="pp-section">
                <div class="pp-section-title">Diagnostic Booking Timeline</div>
                <div class="pp-timeline">
                    @forelse($profilePatient['appointment_timeline'] ?? [] as $appointment)
                        <div class="pp-timeline-item {{ $appointment['class'] }}">
                            <div class="pp-timeline-label">{{ $appointment['label'] }}</div>
                            <div class="pp-timeline-line">{{ $appointment['line'] }}</div>
                        </div>
                    @empty
                        <div class="pp-timeline-line" style="color:#9ca3af; font-style:italic;">No booking history available.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="pp-panel-footer">
            <button type="button" class="pp-footer-btn outline" wire:click="openBookingHistory({{ $profilePatient['booking_id'] }})">Booking History</button>
            <a href="{{ route('technician.patient-documents.view', ['booking_id' => $profilePatient['booking_id']]) }}" class="pp-footer-btn outline">Documents</a>
            <a href="{{ route('technician.upload-report.create', ['booking_id' => $profilePatient['booking_id']]) }}" class="pp-footer-btn primary">Upload Report</a>
        </div>
    </aside>
@endif
