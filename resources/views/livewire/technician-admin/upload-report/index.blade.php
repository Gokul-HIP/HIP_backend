<div style="padding: 28px 32px;">
<link rel="stylesheet" href="{{ asset('assets/doctor-prescription.css') }}">
@include('components.technician.patient-table-styles')

<div class="sp-page-header">
    <h1>Upload Report</h1>
    <p>Patients who booked diagnostic tests at your centers</p>
</div>

<div class="sp-filter-panel">
    <div class="sp-filter-row">
        <div class="sp-filter-group grow">
            <div class="sp-filter-label">Search Patient</div>
            <input type="text" class="sp-select" style="width:100%;" wire:model.live.debounce.300ms="search" placeholder="Name, HIP ID, or mobile number">
        </div>
        <div class="sp-filter-group">
            <div class="sp-filter-label">Hospital</div>
            <select class="sp-select" wire:model.live="hospitalFilter">
                <option value="all">All Hospitals</option>
                @foreach($hospitals as $hospital)
                    <option value="{{ $hospital->id }}">{{ $hospital->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="sp-filter-group">
            <div class="sp-filter-label">Status</div>
            <select class="sp-select" wire:model.live="statusFilter">
                <option value="all">All Status</option>
                <option value="pending">Pending</option>
                <option value="confirmed">Confirmed</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
        <button type="button" class="btn-reset-filters" wire:click="resetFilters"><i class="fas fa-redo-alt"></i> Reset</button>
    </div>
</div>

<div class="sp-table-wrap">
    <table class="sp-table">
        <thead>
            <tr>
                <th>Patient</th>
                <th>UHID</th>
                <th>Age / Gender</th>
                <th>Mobile</th>
                <th>Hospital</th>
                <th>Last Visit</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($patients as $patient)
                <tr wire:key="upload-patient-{{ $patient['booking_id'] }}">
                    <td>
                        <div class="sp-patient-cell">
                            <div class="sp-patient-init" style="background: {{ $patient['avatar_color'] }};">{{ $patient['initials'] }}</div>
                            <span class="sp-patient-name">{{ $patient['name'] }}</span>
                        </div>
                    </td>
                    <td>{{ $patient['uhid'] }}</td>
                    <td>{{ $patient['age'] ?? '—' }} / {{ $patient['gender'] }}</td>
                    <td>{{ $patient['mobile'] }}</td>
                    <td>{{ $patient['hospital_name'] }}</td>
                    <td>{{ $patient['last_visit'] }}</td>
                    <td>{{ $patient['status_label'] }}</td>
                    <td>
                        @if($patient['booking_id'])
                            <a href="{{ route('technician.upload-report.create', ['booking_id' => $patient['booking_id']]) }}"
                               class="btn-sp-action" title="Upload Document">
                                <i class="fa-solid fa-file-arrow-up"></i>
                            </a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center; color:#9ca3af; padding:40px 20px;">No patients found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    @if($patients->count() > 0)
        <div class="p-4">{{ $patients->links() }}</div>
    @endif
</div>
</div>
