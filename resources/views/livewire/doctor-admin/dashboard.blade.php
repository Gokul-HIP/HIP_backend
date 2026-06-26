<div style="padding: 28px 32px;">
<style>
/* ── Page Header ── */
.dd-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 24px;
    gap: 16px;
}
.dd-header h1 {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a2e;
    line-height: 1.2;
}
.dd-header p {
    font-size: 14px;
    color: #6b7280;
    margin-top: 5px;
}
.btn-new-consultation {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #c8102e;
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 11px 22px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    white-space: nowrap;
    transition: background 0.15s, transform 0.1s;
    flex-shrink: 0;
}
.btn-new-consultation:hover { background: #a80e26; transform: translateY(-1px); }

/* ── Stat Cards ── */
.dd-stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 22px;
}
.dd-stat-card {
    background: #fff;
    border: 1px solid #e8ecf0;
    border-radius: 14px;
    padding: 20px;
    transition: box-shadow 0.2s;
}
.dd-stat-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
.dd-stat-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
}
.dd-stat-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 17px;
    flex-shrink: 0;
}
.dd-icon-red    { background: #fff0f2; color: #c8102e; }
.dd-icon-blue   { background: #eaf3ff; color: #0da2e7; }
.dd-icon-orange { background: #fff7ed; color: #f97316; }
.dd-icon-teal   { background: #eaf3ff; color: #0da2e7; }

.dd-stat-badge {
    font-size: 11.5px;
    font-weight: 600;
    padding: 3px 9px;
    border-radius: 20px;
    white-space: nowrap;
}
.badge-green  { background: #ecfdf5; color: #059669; }
.badge-blue   { background: #eaf3ff; color: #0da2e7; }
.badge-red    { background: #fff0f2; color: #c8102e; }
.badge-gray   { background: #f4f6f9; color: #6b7280; }

.dd-stat-label {
    font-size: 11px;
    font-weight: 700;
    color: #9ca3af;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    margin-bottom: 6px;
}
.dd-stat-number {
    font-size: 34px;
    font-weight: 700;
    color: #1a1a2e;
    line-height: 1;
}

/* ── Main 3-col grid ── */
.dd-main-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 16px;
    margin-bottom: 22px;
    align-items: stretch;
}

/* ── LEFT column: 2×2 sub-grid ── */
.dd-left-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    grid-template-rows: 1fr 1fr;
    gap: 16px;
    /* span the full height of the red card */
}

/* Quick cards */
.dd-quick-card {
    background: #fff;
    border: 1px solid #e8ecf0;
    border-radius: 14px;
    padding: 22px;
    cursor: pointer;
    transition: box-shadow 0.2s, transform 0.15s;
    text-decoration: none;
    display: flex;
    flex-direction: column;
}
.dd-quick-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.09); transform: translateY(-2px); }

.dd-quick-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
    margin-bottom: 12px;
    flex-shrink: 0;
}
.dd-quick-card h3 {
    font-size: 14.5px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 6px;
    line-height: 1.3;
}
.dd-quick-card p {
    font-size: 12.5px;
    color: #9ca3af;
    line-height: 1.5;
}

/* ── CENTER: Red consultation hero ── */
.dd-consult-card {
    background: var(--primary-color);
    border-radius: 14px;
    padding: 32px 26px;
    cursor: pointer;
    transition: background 0.15s, transform 0.15s;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.dd-consult-card:hover { background: var(--button-hover); transform: translateY(-2px); }
.dd-consult-icon {
    width: 52px; height: 52px;
    background: rgba(255,255,255,0.18);
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 24px;
    color: var(--primary-light);
    margin-bottom: 20px;
}
.dd-consult-card h3 {
    font-size: 22px;
    font-weight: 700;
    color: var(--primary-light);
    margin-bottom: 10px;
}
.dd-consult-card p {
    font-size: 14px;
    color: var(--primary-light);
    line-height: 1.5;
}

/* ── RIGHT: Upcoming appointment ── */
.dd-upcoming-card {
    background: #fff;
    border: 1px solid #e8ecf0;
    border-radius: 14px;
    padding: 20px;
    display: flex;
    flex-direction: column;
}
.dd-upcoming-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}
.dd-upcoming-title {
    font-size: 14px;
    font-weight: 700;
    color: #1a1a2e;
}
.dd-upcoming-pill {
    background: #fef9c3;
    color: #b45309;
    font-size: 10px;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 20px;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.dd-upcoming-patient {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 14px;
}
.dd-upcoming-avatar {
    width: 52px; height: 52px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e8ecf0;
    flex-shrink: 0;
}
.dd-upcoming-avatar-init {
    width: 52px; height: 52px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color), var(--button-hover));
    display: flex; align-items: center; justify-content: center;
    color: var(--primary-light);
    font-size: 17px;
    font-weight: 700;
    flex-shrink: 0;
}
.dd-upcoming-name {
    font-size: 16px;
    font-weight: 700;
    color: #1a1a2e;
    line-height: 1.2;
}
.dd-upcoming-id {
    font-size: 12.5px;
    color: #9ca3af;
    margin-top: 3px;
}
.dd-upcoming-meta {
    background: #f8fafc;
    border-radius: 10px;
    padding: 13px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 14px;
    flex: 1;
}
.dd-upcoming-meta-row {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    color: #374151;
}
.dd-upcoming-meta-row i { width: 16px; text-align: center; color: #9ca3af; font-size: 13px; }
.btn-start-session {
    width: 100%;
    background: var(--primary-color);
    color: var(--primary-light);
    border: none;
    border-radius: 9px;
    padding: 11px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    margin-bottom: 9px;
    transition: background 0.15s;
}
.btn-start-session:hover { background: var(--button-hover); }
.btn-view-history {
    width: 100%;
    background: transparent;
    color: #6b7280;
    border: 1.5px solid #e8ecf0;
    border-radius: 9px;
    padding: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    transition: border-color 0.15s, color 0.15s;
}
.btn-view-history:hover { border-color: #9ca3af; color: #374151; }

/* ── Recent Appointments Table ── */
.dd-appt-section {
    background: #fff;
    border: 1px solid #e8ecf0;
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 24px;
}
.dd-appt-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 22px;
    border-bottom: 1px solid #f0f4f8;
}
.dd-appt-header h2 {
    font-size: 15.5px;
    font-weight: 700;
    color: #1a1a2e;
}
.dd-see-all {
    font-size: 13.5px;
    font-weight: 600;
    color: var(--primary-color);
    text-decoration: none;
    transition: color 0.15s;
}
.dd-see-all:hover { color: var(--button-hover); text-decoration: underline; }

.dd-appt-table { width: 100%; border-collapse: collapse; }
.dd-appt-table thead tr { background: #f8fafc; }
.dd-appt-table th {
    padding: 11px 20px;
    font-size: 11px;
    font-weight: 700;
    color: #9ca3af;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    text-align: left;
    border-bottom: 1px solid #f0f4f8;
    white-space: nowrap;
}
.dd-appt-table td {
    padding: 14px 20px;
    font-size: 13.5px;
    color: #374151;
    border-bottom: 1px solid #f0f4f8;
    vertical-align: middle;
}
.dd-appt-table tbody tr:last-child td { border-bottom: none; }
.dd-appt-table tbody tr:hover td { background: #fafbfc; }

.dd-patient-cell { display: flex; align-items: center; gap: 11px; }
.dd-patient-init {
    width: 34px; height: 34px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700; color: #fff; flex-shrink: 0;
}
.dd-patient-name { font-weight: 600; color: #1a1a2e; }

.dd-visit-cell { display: flex; align-items: center; gap: 6px; font-size: 13.5px; color: #374151; }
.dd-visit-cell i { color: #9ca3af; font-size: 13px; }

.dd-appt-status {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
}
.appt-upcoming  { background: #eaf3ff; color: #0da2e7; }
.appt-waiting   { background: #fef9c3; color: #b45309; }
.appt-completed { background: #f1f5f9; color: #64748b; }
.appt-confirmed { background: #ecfdf5; color: #059669; }

.btn-dd-details {
    background: #f4f6f9;
    border: 1px solid #e8ecf0;
    border-radius: 8px;
    padding: 6px 16px;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    cursor: pointer;
    font-family: inherit;
    transition: background 0.15s;
}
.btn-dd-details:hover { background: #e8ecf0; }

/* ── Footer ── */
.dd-footer {
    text-align: center;
    font-size: 12px;
    color: #9ca3af;
    padding-top: 8px;
    padding-bottom: 4px;
}

@media (max-width: 1024px) {
    .dd-stats-row { grid-template-columns: repeat(2, 1fr); }
    .dd-main-grid { grid-template-columns: 1fr 1fr; }
    .dd-left-grid { grid-template-columns: 1fr 1fr; grid-template-rows: auto auto; }
}
@media (max-width: 640px) {
    .dd-stats-row { grid-template-columns: 1fr 1fr; }
    .dd-main-grid { grid-template-columns: 1fr; }
    .dd-left-grid { grid-template-columns: 1fr 1fr; }
}
</style>

{{-- ── Page Header ── --}}
<div class="dd-header">
    <div>
        <h1>Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}, Dr. {{ $doctorFirstName . ' ' . $doctorLastName }}</h1>
        <p>You have {{ $todayAppointmentsCount }} appointment{{ $todayAppointmentsCount === 1 ? '' : 's' }} scheduled for today.</p>
    </div>
    {{-- <button class="btn-new-consultation" wire:click="newConsultation">
        <i class="fas fa-plus"></i> New Consultation
    </button> --}}
</div>

{{-- ── Stat Cards ── --}}
<div class="dd-stats-row">
    <div class="dd-stat-card">
        <div class="dd-stat-top">
            <div class="dd-stat-icon dd-icon-red"><i class="fas fa-calendar-check"></i></div>
            <span class="dd-stat-badge badge-gray">Today</span>
        </div>
        <div class="dd-stat-label">Today's Appointments</div>
        <div class="dd-stat-number">{{ $todayAppointmentsCount }}</div>
    </div>
    <div class="dd-stat-card">
        <div class="dd-stat-top">
            <div class="dd-stat-icon dd-icon-blue"><i class="fas fa-video"></i></div>
            <span class="dd-stat-badge badge-blue">{{ $onlineConsultationsCount > 0 ? 'Today' : 'None' }}</span>
        </div>
        <div class="dd-stat-label">Online Consultations</div>
        <div class="dd-stat-number">{{ $onlineConsultationsCount }}</div>
    </div>
    {{-- <div class="dd-stat-card">
        <div class="dd-stat-top">
            <div class="dd-stat-icon dd-icon-orange"><i class="fas fa-file-prescription"></i></div>
            <span class="dd-stat-badge badge-red">Urgent</span>
        </div>
        <div class="dd-stat-label">Pending Prescriptions</div>
        <div class="dd-stat-number">{{ $pendingPrescriptionsCount ?? 3 }}</div>
    </div> --}}
    <div class="dd-stat-card">
        <div class="dd-stat-top">
            <div class="dd-stat-icon dd-icon-teal"><i class="fas fa-user-friends"></i></div>
            <span class="dd-stat-badge badge-gray">This week</span>
        </div>
        <div class="dd-stat-label">Follow-up Patients</div>
        <div class="dd-stat-number">{{ $followUpPatientsCount }}</div>
    </div>
</div>

{{-- ── Main 3-col grid ── --}}
<div class="dd-main-grid">

    {{-- LEFT: 2×2 sub-grid of quick cards ── --}}
    <div class="dd-left-grid">
        {{-- Top-left: View Today's Schedule --}}
        <a href="{{ route('doctor.my-appointment.index') }}" class="dd-quick-card">
            <div class="dd-quick-icon dd-icon-red"><i class="fas fa-calendar-alt"></i></div>
            <h3>View Today's Schedule</h3>
            <p>Check patient list and room allocations for the day.</p>
        </a>

        {{-- Top-right: (empty spacer to push upload/reports to bottom row) --}}
        {{-- Actually in the image: top row is just Schedule (full width) and bottom row is Upload + Reports --}}
        {{-- So we span schedule across both columns on top --}}
        {{-- Re-reading image: LEFT col has Schedule on top, Upload on bottom. CENTER is red card. --}}
        {{-- So left col is 1 column with 2 stacked cards, NOT a 2x2 grid --}}
        {{-- Let me just do flex column for left --}}
    </div>

    {{-- CENTER: Red hero --}}
    <a href="{{ route('doctor.online-consultation.index') }}">
        <div class="dd-consult-card">
            <div class="dd-consult-icon"><i class="fas fa-wifi"></i></div>
            <h3>Start Consultation</h3>
            <p>Launch secure video portal for remote patient sessions.</p>    
        </div>
    </a>

    {{-- RIGHT: Upcoming appointment --}}
    <div class="dd-upcoming-card">
        <div class="dd-upcoming-header">
            <span class="dd-upcoming-title">Upcoming Appointment</span>
            @if($nextAppointment)
                <span class="dd-upcoming-pill">{{ $nextMinutesLabel ?? 'Today' }}</span>
            @endif
        </div>

        @if($nextAppointment)
            <div class="dd-upcoming-patient">
                @if(!empty($nextAppointment['avatar_url']))
                    <img src="{{ $nextAppointment['avatar_url'] }}" alt="" class="dd-upcoming-avatar">
                @else
                    <div class="dd-upcoming-avatar-init">
                        {{ strtoupper(substr($nextAppointment['initials'] ?? 'P', 0, 2)) }}
                    </div>
                @endif
                <div>
                    <div class="dd-upcoming-name">{{ $nextAppointment['patient_name'] }}</div>
                    <div class="dd-upcoming-id">ID: {{ $nextAppointment['patient_id'] }}</div>
                </div>
            </div>
            <div class="dd-upcoming-meta">
                <div class="dd-upcoming-meta-row">
                    <i class="fas fa-notes-medical"></i>
                    {{ $nextAppointment['reason'] }}
                </div>
                <div class="dd-upcoming-meta-row">
                    <i class="far fa-calendar"></i>
                    {{ $nextAppointment['date'] }}
                </div>
                <div class="dd-upcoming-meta-row">
                    <i class="far fa-clock"></i>
                    {{ $nextAppointment['time_range'] }}
                </div>
                <div class="dd-upcoming-meta-row">
                    <i class="fas {{ $nextAppointment['is_online'] ? 'fa-video' : 'fa-door-open' }}"></i>
                    {{ $nextAppointment['room'] }}
                </div>
            </div>
            <button type="button" class="btn-start-session" wire:click="openNextAppointment({{ $nextAppointment['id'] }})">
                {{ $nextAppointment['action_label'] ?? 'Start Session' }}
            </button>
            <button type="button" class="btn-view-history" wire:click="viewHistory">View History</button>
        @else
            <div class="dd-upcoming-meta" style="margin-bottom:0;">
                <div class="dd-upcoming-meta-row" style="color:#9ca3af; font-style:italic;">
                    <i class="far fa-calendar"></i>
                    No more appointments scheduled for today.
                </div>
            </div>
            <a href="{{ route('doctor.my-appointment.index', ['tab' => 'today']) }}" class="btn-start-session" style="display:block; text-align:center; text-decoration:none; margin-top:14px;">
                View Today's Schedule
            </a>
        @endif
    </div>
</div>

@push('scripts')
<script>
// Fix: replace the left grid with proper stacked layout
document.addEventListener('DOMContentLoaded', function () {
    var leftGrid = document.querySelector('.dd-left-grid');
    if (leftGrid) {
        leftGrid.style.display = 'flex';
        leftGrid.style.flexDirection = 'column';
        leftGrid.style.gap = '16px';
    }
});
</script>
@endpush

{{-- ── Bottom Row: Upload Prescription + Patient Reports ── --}}
{{-- These sit below the 3-col grid, aligned under left + center ── --}}
<div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; margin-bottom:22px;">
    <a href="{{ route('doctor.upload-prescription.index') }}" class="dd-quick-card">
        <div class="dd-quick-icon dd-icon-blue"><i class="fas fa-file-medical"></i></div>
        <h3>Upload Prescription</h3>
        <p>Digitize and sign medical orders for the pharmacy.</p>
    </a>
    <a href="{{ route('doctor.patient-document.index') }}" class="dd-quick-card">
        <div class="dd-quick-icon dd-icon-teal"><i class="fas fa-chart-bar"></i></div>
        <h3>Patient Reports</h3>
        <p>Analyze lab results and imaging for pending cases.</p>
    </a>
    <div></div>{{-- spacer under upcoming card --}}
</div>

{{-- ── Recent Patient Appointments ── --}}
<div class="dd-appt-section">
    <div class="dd-appt-header">
        <h2>Recent Patient Appointments</h2>
        <a href="{{ route('doctor.my-appointment.index') }}" class="dd-see-all">See all activity</a>
    </div>
    <table class="dd-appt-table">
        <thead>
            <tr>
                <th>Patient Name</th>
                <th>Time</th>
                <th>Department</th>
                <th>Visit Type</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recentAppointments as $appt)
                <tr>
                    <td>
                        <div class="dd-patient-cell">
                            <div class="dd-patient-init" style="background:{{ $appt['avatar_color'] }};">
                                {{ $appt['initials'] }}
                            </div>
                            <span class="dd-patient-name">{{ $appt['patient_name'] }}</span>
                        </div>
                    </td>
                    <td>{{ $appt['time'] }}</td>
                    <td>{{ $appt['department'] }}</td>
                    <td>
                        <div class="dd-visit-cell">
                            <i class="{{ $appt['visit_icon'] }}"></i>
                            {{ $appt['visit_type'] }}
                        </div>
                    </td>
                    <td>
                        <span class="dd-appt-status appt-{{ $appt['status_class'] }}">
                            {{ $appt['status'] }}
                        </span>
                    </td>
                    <td>
                        <button type="button" class="btn-dd-details" wire:click="viewAppointment({{ $appt['id'] }})">Details</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center; color:#9ca3af; font-style:italic; padding:28px;">
                        No appointments scheduled for today.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- <div class="dd-footer">
    © {{ date('Y') }} Nano Hospitals HIPAA Compliant Provider Portal. Secure Session Active.
</div> --}}
</div>