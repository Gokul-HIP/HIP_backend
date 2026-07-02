<div style="padding: 28px 32px;">
<link rel="stylesheet" href="{{ asset('assets/doctor-prescription.css') }}">
@include('components.technician.patient-table-styles')

<div class="mp-page-header">
    <div>
        <h1>Patients</h1>
        <p>Patients who booked diagnostic tests at your centers</p>
    </div>
</div>

<div class="mp-filter-panel">
    <div class="mp-filter-row">
        <div class="mp-filter-group grow">
            <div class="mp-filter-label">Search Patient</div>
            <div class="mp-search">
                <i class="fas fa-user-friends"></i>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Name, HIP ID, or mobile number">
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
                <th>Bookings</th>
                <th>Last Booking</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($patients as $patient)
                <tr wire:key="tech-patient-{{ $patient['booking_id'] }}">
                    <td>
                        <div class="sp-patient-cell">
                            <div class="sp-patient-init" style="background:#1A9FD4;">{{ $patient['initials'] }}</div>
                            <span class="sp-patient-name">{{ trim($patient['name'] ?? '') ?: '-' }}</span>
                        </div>
                    </td>
                    <td>{{ $patient['uhid'] ?? '—' }}</td>
                    <td>{{ $patient['mobile'] ?? '—' }}</td>
                    <td>{{ $patient['hospital_name'] }}</td>
                    <td>{{ $patient['bookings_count'] }}</td>
                    <td>{{ $patient['last_booking_date'] }}</td>
                    <td>{{ $patient['last_status'] }}</td>
                    <td>
                        <div class="mp-actions">
                            <a href="{{ route('technician.upload-report.create', ['booking_id' => $patient['last_booking_id']]) }}"
                               class="btn-mp-icon" title="Upload Document">
                                <i class="fa-solid fa-file-arrow-up"></i>
                            </a>
                            <a href="{{ route('technician.patient-documents.view', ['booking_id' => $patient['last_booking_id']]) }}"
                               class="btn-mp-icon" title="Patient Documents">
                                <i class="fa-regular fa-folder"></i>
                            </a>
                            <button type="button" class="btn-mp-update" wire:click="openPatientProfile({{ $patient['last_booking_id'] }})">
                                View Profile
                            </button>
                            <button type="button" class="btn-mp-update" wire:click="openBookingHistory({{ $patient['last_booking_id'] }})">
                                Booking History
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center; color:#9ca3af; padding:40px 20px;">No patients found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="p-4">{{ $patients->links() }}</div>
</div>

@include('livewire.technician-admin.partials.patient-profile-panel')
@include('livewire.technician-admin.partials.booking-history-panel')
</div>
