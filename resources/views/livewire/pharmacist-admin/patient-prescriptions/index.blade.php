<div style="padding: 28px 32px;">
<link rel="stylesheet" href="{{ asset('assets/doctor-prescription.css') }}">
@include('components.technician.patient-table-styles')

<div class="mp-page-header">
    <div>
        <h1>Patient Prescription</h1>
        <p>Patients with prescriptions at your hospital</p>
    </div>
</div>

<div class="mp-filter-panel">
    <div class="mp-filter-row">
        <div class="mp-filter-group grow">
            <div class="mp-filter-label">Search Patient</div>
            <div class="mp-search">
                <i class="fas fa-user-friends"></i>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Name, HIP ID, mobile, or doctor">
            </div>
        </div>
        <div class="mp-filter-group">
            <div class="mp-filter-label">Hospital</div>
            <select class="mp-select" wire:model.live="hospitalFilter">
                <option value="all">All Hospitals</option>
                @foreach($hospitals as $hospital)
                    <option value="{{ $hospital->id }}">{{ $hospital->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="button" class="btn-mp-reset" wire:click="clearFilters"><i class="fas fa-redo-alt"></i> Reset</button>
    </div>
</div>

<div class="sp-table-wrap">
    <table class="sp-table">
        <thead>
            <tr>
                <th>Patient</th>
                <th>Member ID</th>
                <th>Phone</th>
                <th>Hospital</th>
                <th>Prescriptions</th>
                <th>Last Prescription</th>
                <th>Last Doctor</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($patients as $patient)
                <tr wire:key="pharm-patient-{{ $patient['patient_key'] }}">
                    <td>
                        <div class="sp-patient-cell">
                            <div class="sp-patient-init" style="background:#E91E63;">{{ $patient['initials'] }}</div>
                            <span class="sp-patient-name">{{ $patient['name'] }}</span>
                        </div>
                    </td>
                    <td>{{ $patient['member_hip_id'] }}</td>
                    <td>{{ $patient['mobile'] }}</td>
                    <td>{{ $patient['hospital_name'] }}</td>
                    <td>{{ $patient['prescriptions_count'] }}</td>
                    <td>{{ $patient['last_prescription_date'] }}</td>
                    <td>{{ $patient['last_doctor'] }}</td>
                    <td>{{ $patient['last_status'] }}</td>
                    <td>
                        <div class="mp-actions">
                            <a href="{{ route('pharmacist.patient-prescriptions.view', array_filter([
                                'patient_id' => $patient['patient_id'],
                                'member_id' => $patient['member_id'],
                            ])) }}"
                               class="btn-mp-icon"
                               title="View All Prescriptions">
                                <i class="fa-solid fa-prescription-bottle-medical"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align:center; color:#9ca3af; padding:40px 20px;">No patients with prescriptions found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="p-4">{{ $patients->links() }}</div>
</div>
</div>
