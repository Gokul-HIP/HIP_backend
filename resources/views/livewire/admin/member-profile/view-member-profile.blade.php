<div class="sp-profile">
@if(!empty($ready))
<style>
.sp-profile{display:flex;flex-direction:column;gap:16px;color:var(--text-color);font-family:inherit}
.sp-header-card,.sp-card,.sp-tile{background:var(--card-bg);border:1px solid var(--border-color);border-radius:var(--radius-card);box-shadow:var(--shadow-card)}
.sp-header-card{display:flex;justify-content:space-between;gap:16px;padding:20px;flex-wrap:wrap}
.sp-identity{display:flex;gap:14px;min-width:0}
.sp-avatar{width:64px;height:64px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:var(--button-color);color:var(--card-bg);font-weight:700;font-size:22px;flex:none}
.sp-name{margin:0;font-size:22px;font-weight:700}
.sp-meta{margin:4px 0 0;color:var(--muted-color);font-size:14px}
.sp-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
.sp-actions{display:flex;gap:8px;align-items:flex-start;flex-wrap:wrap}
.sp-btn{display:inline-flex;align-items:center;justify-content:center;height:42px;padding:0 14px;border-radius:10px;border:1px solid transparent;background:var(--card-bg);color:var(--text-color);font:inherit;font-size:14px;font-weight:700;cursor:pointer;text-decoration:none}
.sp-btn-ghost{border-color:var(--border-color)}
.sp-btn-ghost:hover{background:var(--page-bg)}
.sp-btn-primary{background:var(--button-color);color:var(--card-bg)}
.sp-btn-primary:hover{background:var(--button-hover)}
.sp-pill{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:var(--radius-pill);font-size:12px;font-weight:600}
.sp-pill.is-active{background:var(--success-soft);color:var(--success-color)}
.sp-pill.is-inactive{background:var(--danger-soft);color:var(--danger-color)}
.sp-pill.is-doctor{background:var(--primary-hover);color:var(--primary-color)}
.sp-pill.is-second{background:var(--info-soft);color:var(--info-color)}
.sp-pill.is-diagnostic{background:var(--success-soft);color:var(--success-color)}
.sp-pill.is-pending{background:var(--warning-soft);color:var(--warning-color)}
.sp-pill.is-confirmed{background:var(--info-soft);color:var(--info-color)}
.sp-pill.is-completed{background:var(--success-soft);color:var(--success-color)}
.sp-pill.is-danger{background:var(--danger-soft);color:var(--danger-color)}
.sp-pill.is-muted{background:var(--page-bg);color:var(--muted-color);border:1px solid var(--border-color)}
.sp-dot{width:6px;height:6px;border-radius:50%;background:currentColor}
.sp-tabs{display:flex;flex-wrap:wrap;gap:8px}
.sp-tab{height:40px;padding:0 14px;border-radius:var(--radius-pill);border:1px solid var(--border-color);background:var(--card-bg);color:var(--muted-color);font:inherit;font-weight:700;cursor:pointer}
.sp-tab.is-active{background:var(--primary-color);border-color:var(--primary-color);color:var(--card-bg)}
.sp-tiles{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
.sp-tile{padding:14px}
.sp-tile-label{margin:0;color:var(--muted-color);font-size:12px}
.sp-tile-value{margin:6px 0 0;font-size:28px;font-weight:700}
.sp-kv{display:grid;grid-template-columns:180px 1fr;gap:8px 12px;font-size:14px}
.sp-kv dt{color:var(--muted-color)}
.sp-kv dd{margin:0;font-weight:600;word-break:break-word}
.sp-card{padding:16px}
.sp-card-title{margin:0 0 12px;font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--muted-color)}
.sp-note{color:var(--muted-color);font-size:13px}
.sp-filters{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:12px}
.sp-control{box-sizing:border-box;height:42px;padding:0 12px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-color);font:inherit;font-size:14px}
.sp-control:focus{border-color:var(--primary-color);box-shadow:0 0 0 3px var(--primary-hover);outline:none}
.sp-search{flex:1 1 220px;min-width:0}
.sp-table-wrap{overflow-x:auto}
.sp-table{width:100%;border-collapse:collapse;font-size:14px}
.sp-table th{text-align:left;font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--muted-color);padding:10px 12px;background:var(--primary-hover)}
.sp-table td{padding:12px;border-top:1px solid var(--border-color);vertical-align:middle}
.sp-footer{display:flex;justify-content:space-between;gap:12px;align-items:center;margin-top:12px;color:var(--muted-color);font-size:13px;flex-wrap:wrap}
.sp-pagination-list{display:flex;gap:6px;margin:0;padding:0;list-style:none}
.sp-page-button{width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;border:1px solid var(--border-color);border-radius:10px;background:var(--card-bg);color:var(--text-color);font:inherit;font-weight:700;cursor:pointer}
.sp-page-button svg{width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:1.8}
.sp-page-button.is-active{background:var(--button-color);border-color:var(--button-color);color:var(--card-bg)}
.sp-page-button.is-disabled{opacity:.55;cursor:not-allowed;color:var(--muted-color)}
.sp-page-gap{width:36px;display:inline-flex;align-items:center;justify-content:center;color:var(--muted-color)}
.sp-coins{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:12px}
.sp-empty{padding:28px 12px;text-align:center}
.sp-empty-title{font-weight:700}
.sp-tx-description{display:flex;align-items:flex-start;gap:10px}
.sp-tx-description-main{min-width:0}
.sp-tx-badges{display:flex;gap:5px;flex-wrap:wrap;margin-top:6px}
.sp-tx-badge{display:inline-flex;align-items:center;border-radius:var(--radius-pill);padding:2px 8px;font-size:11px;font-weight:700;line-height:1.5}
.sp-tx-badge-membership{background:var(--membership-badge-bg);color:var(--membership-badge-text)}
.sp-tx-badge-reward{background:var(--reward-badge-bg);color:var(--reward-badge-text)}
.sp-tx-badge-coins{background:var(--coins-badge-bg);color:var(--coins-badge-text)}
.sp-transactions-table-wrap{overflow-x:hidden}
.sp-transactions-table{table-layout:fixed}
.sp-transactions-table th:first-child,.sp-transactions-table td.sp-tx-toggle-cell{width:7%;text-align:center}
.sp-tx-toggle-cell{padding-left:4px!important;padding-right:4px!important}
.sp-tx-toggle{flex:none;width:36px;height:36px;border:1px solid var(--border-color);border-radius:50%;background:var(--card-bg);color:var(--muted-color);display:inline-flex;align-items:center;justify-content:center;cursor:pointer}
.sp-tx-toggle svg{width:15px;height:15px;fill:none;stroke:currentColor;stroke-width:2;transition:transform .15s ease}
.sp-tx-toggle[aria-expanded="true"] svg{transform:rotate(180deg)}
.sp-tx-detail-cell{padding:16px!important;background:var(--transaction-detail-bg)}
.sp-tx-detail-cell::before{display:none!important}
.sp-tx-detail-panel{padding:0;border:0;border-radius:0;background:transparent}
.sp-tx-booking-meta{margin:0 0 12px;color:var(--text-color);font-size:14px;font-weight:700}
.sp-tx-breakdown{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 20px}
.sp-tx-breakdown-line{display:flex;justify-content:space-between;gap:12px;padding:7px 0;border-bottom:1px solid var(--border-color);font-size:13px}
.sp-tx-breakdown-label{color:var(--muted-color)}
.sp-tx-breakdown-value{font-weight:600;text-align:right;white-space:nowrap}
.sp-tx-breakdown-value.is-discount{color:var(--transaction-discount-text)}
.sp-tx-breakdown-line.is-total{grid-column:1/-1;display:grid;grid-template-columns:minmax(0,1fr) auto;border-top:2px solid var(--border-color);font-weight:700}
.sp-tx-original-amount{display:block;margin-top:3px;color:var(--muted-color);font-size:12px}
.sp-tier-header-badge{background:var(--reward-tier-badge-bg);color:var(--reward-tier-badge-text)}
.sp-member-header-badge{background:var(--membership-status-bg);color:var(--membership-status-text)}
.sp-profile-card-row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.sp-profile-detail-card{min-width:0;padding:16px;background:var(--card-bg);border:1px solid var(--border-color);border-radius:var(--radius-card);box-shadow:var(--shadow-card)}
.sp-profile-detail-card:only-child{grid-column:1/-1}
.sp-profile-detail-title{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:0 0 12px;font-size:15px;font-weight:700}
.sp-profile-detail-badge{display:inline-flex;align-items:center;padding:3px 9px;border-radius:var(--radius-pill);background:var(--reward-tier-badge-bg);color:var(--reward-tier-badge-text);font-size:12px;font-weight:700}
.sp-membership-active-badge{background:var(--membership-status-bg);color:var(--membership-status-text)}
.sp-profile-detail-meta{margin:0;color:var(--muted-color);font-size:13px;line-height:1.55}
.sp-profile-detail-meta strong{color:var(--text-color);font-weight:600}
.sp-reward-tier-labels{display:flex;justify-content:space-between;gap:12px;margin:12px 0 5px;font-size:12px;font-weight:600}
.sp-reward-progress{height:10px;overflow:hidden;border-radius:var(--radius-pill);background:var(--reward-progress-track)}
.sp-reward-progress-fill{height:100%;border-radius:inherit;background:linear-gradient(90deg,var(--reward-progress-fill-start),var(--reward-progress-fill-end));animation:sp-reward-fill-in .6s ease-out both}
.sp-reward-progress-fill.is-complete{background:var(--reward-progress-complete)}
@keyframes sp-reward-fill-in{from{width:0}to{width:var(--reward-progress-width)}}
.sp-reward-progress-label{margin:5px 0 10px;font-size:12px;color:var(--muted-color)}
@media (prefers-reduced-motion: reduce){.sp-reward-progress-fill{animation:none}}
.sp-detail-list{display:grid;gap:8px;margin-top:10px}
.sp-detail-list-item{padding-top:8px;border-top:1px solid var(--border-color);font-size:13px}
.sp-membership-expired{margin:0;color:var(--muted-color);font-size:12px}
.sp-backdrop{position:fixed;inset:0;z-index:9999;background:color-mix(in srgb, var(--text-color) 45%, transparent)}
.sp-layer{position:fixed;inset:0;z-index:10000;display:flex;align-items:center;justify-content:center;padding:24px}
.sp-modal{width:min(480px,100%);background:var(--card-bg);border-radius:var(--radius-card);box-shadow:var(--shadow-card)}
.sp-modal-head,.sp-modal-foot{display:flex;justify-content:space-between;gap:12px;padding:16px 20px;border-bottom:1px solid var(--border-color)}
.sp-modal-foot{border-bottom:0;border-top:1px solid var(--border-color);justify-content:flex-end}
.sp-modal-title{margin:0;font-size:18px}
.sp-modal-body{padding:16px 20px}
.sp-x{border:0;background:transparent;color:var(--muted-color);font-size:20px;cursor:pointer}
@media (max-width:768px){
    .sp-tiles,.sp-coins{grid-template-columns:1fr 1fr}
    .sp-kv{grid-template-columns:1fr}
    .sp-profile-card-row{grid-template-columns:1fr}
    .sp-tx-breakdown{grid-template-columns:1fr}
    .sp-table thead{display:none}
    .sp-table,.sp-table tbody,.sp-table tr,.sp-table td{display:block;width:100%}
    .sp-table tr{margin-bottom:12px;border:1px solid var(--border-color);border-radius:var(--radius-card);overflow:hidden}
    .sp-table td{display:flex;justify-content:space-between;gap:12px}
    .sp-table td::before{content:attr(data-label);color:var(--muted-color);font-size:12px;font-weight:700}
    .sp-transactions-table tr:not(.sp-tx-detail-row){position:relative}
    .sp-transactions-table tr.sp-tx-detail-row{margin-top:-12px;border-top:0;border-radius:0 0 var(--radius-card) var(--radius-card)}
    .sp-transactions-table td.sp-tx-toggle-cell{position:absolute;top:2px;right:2px;z-index:1;width:44px;height:44px;padding:4px!important;justify-content:center}
    .sp-transactions-table td.sp-tx-toggle-cell::before{display:none}
    .sp-transactions-table td[data-label="Date"]{padding-right:52px}
    .sp-transactions-table td.sp-tx-detail-cell{display:block;padding:16px!important;border-top:0}
}
</style>

@php
    $fullName = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
    $fullName = $fullName !== '' ? $fullName : 'Unnamed patient';
    $initials = collect(preg_split('/\s+/', $fullName))->filter()->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('');
    $initials = $initials !== '' ? $initials : '?';
    $isActive = (bool) ($user->is_active ?? true);
    $dobSource = $user->dob ?: $primary?->dob;
    $dobLabel = $dobSource ? \Illuminate\Support\Carbon::parse($dobSource)->format('d M Y') : null;
    $statusClass = function ($status) {
        return match ($status) {
            'pending' => 'is-pending',
            'confirmed' => 'is-confirmed',
            'completed' => 'is-completed',
            'cancelled', 'missed', 'failed', 'refunded' => 'is-danger',
            default => 'is-muted',
        };
    };
@endphp

<header class="sp-header-card">
    <div class="sp-identity">
        <div class="sp-avatar" aria-hidden="true">{{ $initials }}</div>
        <div>
            <h1 class="sp-name">{{ $fullName }}</h1>
            <p class="sp-meta">{{ $user->hip_id ?: 'No HIP ID' }} · {{ $user->mobile_num ?: 'No mobile' }} · {{ $user->email ?: 'No email' }}</p>
            <div class="sp-chips">
                @if($isActive)
                    <span class="sp-pill is-active"><span class="sp-dot"></span>Active</span>
                @else
                    <span class="sp-pill is-inactive"><span class="sp-dot"></span>Inactive</span>
                @endif
                @if(!empty($rewardTierCard['tier_name']))
                    <span class="sp-pill sp-tier-header-badge">{{ $rewardTierCard['tier_name'] }}</span>
                @endif
                @if(!empty($membershipCard['active']))
                    <span class="sp-pill sp-member-header-badge">Member</span>
                @endif
            </div>
        </div>
    </div>
    <div class="sp-actions">
        <a class="sp-btn sp-btn-ghost" href="{{ route('admin.member-profile.member-index') }}">Back to patients</a>
        <button type="button" class="sp-btn sp-btn-primary" wire:click="askStatusChange">{{ $isActive ? 'Deactivate' : 'Activate' }}</button>
    </div>
</header>

<nav class="sp-tabs" aria-label="Patient profile">
    <button type="button" class="sp-tab {{ $tab === 'overview' ? 'is-active' : '' }}" wire:click="$set('tab', 'overview')">Overview</button>
    <button type="button" class="sp-tab {{ $tab === 'dependents' ? 'is-active' : '' }}" wire:click="$set('tab', 'dependents')">Dependents</button>
    <button type="button" class="sp-tab {{ $tab === 'appointments' ? 'is-active' : '' }}" wire:click="$set('tab', 'appointments')">Appointments</button>
    <button type="button" class="sp-tab {{ $tab === 'transactions' ? 'is-active' : '' }}" wire:click="$set('tab', 'transactions')">Transactions</button>
</nav>

@if($tab === 'overview')
    <section class="sp-tiles" aria-label="Summary">
        <article class="sp-tile"><p class="sp-tile-label">Dependents</p><p class="sp-tile-value">{{ $dependentCount }}</p></article>
        <article class="sp-tile"><p class="sp-tile-label">Appointments</p><p class="sp-tile-value">{{ $appointmentCount }}</p></article>
        <article class="sp-tile"><p class="sp-tile-label">Transactions</p><p class="sp-tile-value">{{ $transactionCount }}</p></article>
        <article class="sp-tile"><p class="sp-tile-label">Coin balance</p><p class="sp-tile-value">{{ $coinBalance === null ? '—' : $coinBalance }}</p></article>
    </section>
    <section class="sp-card">
        <h2 class="sp-card-title">Overview</h2>
        @if(! $primary)
            <p class="sp-note">No primary person record is linked to this account.</p>
        @endif
        <dl class="sp-kv">
            <dt>Name</dt><dd>{{ $fullName }}</dd>
            <dt>HIP ID</dt><dd>{{ $user->hip_id ?: '—' }}</dd>
            <dt>HIP card ID</dt><dd>{{ $hipCardId ?: '—' }}</dd>
            <dt>Mobile</dt><dd>{{ $user->mobile_num ?: '—' }}</dd>
            <dt>Email</dt><dd>{{ $user->email ?: '—' }}</dd>
            <dt>Gender</dt><dd>{{ $user->gender ?: ($primary->gender ?? '—') }}</dd>
            <dt>Date of birth</dt><dd>{{ $dobLabel ? $dobLabel.($age !== null ? ' · '.$age.' yrs' : '') : '—' }}</dd>
            <dt>Blood group</dt><dd>{{ $primary->blood_group ?? $user->blood_group ?? '—' }}</dd>
            <dt>Hospital</dt><dd>{{ $hospitalName ?: '—' }}</dd>
            <dt>Address</dt><dd>{{ $primary->address ?? '—' }}</dd>
            <dt>Emergency contact</dt><dd>{{ $primary->emergency_contact_name ?? '—' }}</dd>
            <dt>Emergency number</dt><dd>{{ $primary->emergency_contact_number ?? '—' }}</dd>
            <dt>MRN</dt><dd>{{ $primary->mrn ?? '—' }}</dd>
            <dt>Joined</dt><dd>{{ $joined ?: '—' }}</dd>
            <dt>Status</dt><dd>{{ $isActive ? 'Active' : 'Inactive' }}</dd>
            <dt>Created by</dt><dd>{{ $creatorName }}</dd>
        </dl>
    </section>
@endif

@if($tab === 'dependents')
    <section class="sp-card">
        <h2 class="sp-card-title">Dependents</h2>
        @if(! $primary)
            <div class="sp-empty"><div class="sp-empty-title">No primary person</div><p class="sp-note">Dependents are linked from a primary person record.</p></div>
        @else
            <div class="sp-filters">
                <input class="sp-control sp-search" type="search" wire:model.live.debounce.300ms="dependentSearch" placeholder="Search dependents" aria-label="Search dependents">
            </div>
            <div class="sp-table-wrap">
                <table class="sp-table">
                    <thead>
                        <tr>
                            <th>Name</th><th>Relationship</th><th>Mobile</th><th>Gender</th><th>Date of birth</th><th>Blood group</th><th>Hospital</th><th>MRN</th><th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dependents as $dependent)
                            @php
                                $dependentName = trim(($dependent->first_name ?? '').' '.($dependent->last_name ?? ''));
                                $relationship = strtolower((string) $dependent->relationship) === 'other'
                                    ? ($dependent->relationship_other ?: 'Other')
                                    : ($dependent->relationship ?: '—');
                            @endphp
                            <tr wire:key="sp-dependent-{{ $dependent->id }}">
                                <td data-label="Name">{{ $dependentName !== '' ? $dependentName : 'Unnamed patient' }}</td>
                                <td data-label="Relationship">{{ $relationship }}</td>
                                <td data-label="Mobile">{{ $dependent->mobile ?: '—' }}</td>
                                <td data-label="Gender">{{ $dependent->gender ?: '—' }}</td>
                                <td data-label="Date of birth">{{ $dependent->dob ? \Illuminate\Support\Carbon::parse($dependent->dob)->format('d M Y') : '—' }}</td>
                                <td data-label="Blood group">{{ $dependent->blood_group ?: '—' }}</td>
                                <td data-label="Hospital">{{ $dependent->hospital_name ?: '—' }}</td>
                                <td data-label="MRN">{{ $dependent->mrn ?: '—' }}</td>
                                <td data-label="Status">
                                    @if($dependent->is_active)
                                        <span class="sp-pill is-active">Active</span>
                                    @else
                                        <span class="sp-pill is-inactive">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="sp-empty"><div class="sp-empty-title">No dependents</div><p class="sp-note">This patient has no linked family members.</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <footer class="sp-footer">
                <span>Showing {{ $dependents->firstItem() ?? 0 }}–{{ $dependents->lastItem() ?? 0 }} of {{ $dependents->total() }}</span>
                {{ $dependents->links('pagination.sp') }}
            </footer>
        @endif
    </section>
@endif

@if($tab === 'appointments')
    <section class="sp-card">
        <h2 class="sp-card-title">Appointments</h2>
        <div class="sp-filters">
            <select class="sp-control" wire:model.live="appointmentHospital" aria-label="Hospital">
                <option value="">All hospitals</option>
                @foreach($hospitals as $hospitalOption)
                    <option value="{{ $hospitalOption->id }}">{{ $hospitalOption->name }}</option>
                @endforeach
            </select>
            <select class="sp-control" wire:model.live="appointmentType" aria-label="Booking type">
                <option value="all">All types</option>
                <option value="doctor">Doctor</option>
                <option value="second_opinion">Second opinion</option>
                <option value="diagnostic">Diagnostic</option>
            </select>
            <select class="sp-control" wire:model.live="appointmentStatus" aria-label="Status">
                <option value="all">All status</option>
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <input class="sp-control" type="date" wire:model.live="appointmentFrom" aria-label="From date">
            <input class="sp-control" type="date" wire:model.live="appointmentTo" aria-label="To date">
            <button type="button" class="sp-btn sp-btn-ghost" wire:click="clearAppointmentFilters">Clear</button>
        </div>
        <div class="sp-table-wrap">
            <table class="sp-table">
                <thead>
                    <tr>
                        <th>Type</th><th>Booked for</th><th>Hospital</th><th>Doctor / test</th><th>Date</th><th>Status</th><th>Amount</th><th>Coins used (from bookings)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($appointments as $appointment)
                        @php
                            $typeClass = $appointment->booking_type === 'doctor' ? 'is-doctor' : ($appointment->booking_type === 'second_opinion' ? 'is-second' : 'is-diagnostic');
                            $typeLabel = $appointment->booking_type === 'doctor' ? 'Doctor' : ($appointment->booking_type === 'second_opinion' ? 'Second opinion' : 'Diagnostic');
                        @endphp
                        <tr wire:key="sp-appt-{{ $appointment->booking_type }}-{{ $appointment->booking_id }}">
                            <td data-label="Type"><span class="sp-pill {{ $typeClass }}">{{ $typeLabel }}</span></td>
                            <td data-label="Booked for">{{ $appointment->person_name ?: '—' }}</td>
                            <td data-label="Hospital">{{ $appointment->hospital_name ?: '—' }}</td>
                            <td data-label="Doctor / test">{{ $appointment->detail_name ?: '—' }}</td>
                            <td data-label="Date">{{ $appointment->appt_date ? \Illuminate\Support\Carbon::parse($appointment->appt_date)->format('d M Y') : '—' }}</td>
                            <td data-label="Status"><span class="sp-pill {{ $statusClass($appointment->booking_status) }}">{{ ucfirst((string) $appointment->booking_status) }}</span></td>
                            <td data-label="Amount">{{ $appointment->amount !== null ? $appointment->amount : '—' }}</td>
                            <td data-label="Coins used (from bookings)">{{ $appointment->booking_type === 'second_opinion' || $appointment->coins_used === null ? '—' : (int) $appointment->coins_used }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="sp-empty"><div class="sp-empty-title">No appointments</div><p class="sp-note">No bookings match these filters.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <footer class="sp-footer">
            <span>Showing {{ $appointments->firstItem() ?? 0 }}–{{ $appointments->lastItem() ?? 0 }} of {{ $appointments->total() }}</span>
            {{ $appointments->links('pagination.sp') }}
        </footer>
    </section>
@endif

@if($tab === 'transactions')
    <section class="sp-coins" aria-label="Coins">
        <article class="sp-tile"><p class="sp-tile-label">Coins earned</p><p class="sp-tile-value">{{ $coinsEarned['total'] }}</p><p class="sp-note">{{ $coinsEarned['note'] }}</p></article>
        <article class="sp-tile"><p class="sp-tile-label">Coins used</p><p class="sp-tile-value">{{ $coinsUsed }}</p><p class="sp-note">from bookings</p></article>
        <article class="sp-tile"><p class="sp-tile-label">Coins balance</p><p class="sp-tile-value">{{ $coinBalance === null ? '—' : $coinBalance }}</p></article>
    </section>
    @if(!empty($rewardTierCard) || !empty($membershipCard['active']))
        <section class="sp-profile-card-row" aria-label="Reward and membership details">
            @if(!empty($rewardTierCard))
                <article class="sp-profile-detail-card">
                    @if($rewardTierCard['no_tier'])
                        <h2 class="sp-profile-detail-title">Reward tier</h2>
                        <p class="sp-profile-detail-meta">No reward tier yet</p>
                    @else
                    <h2 class="sp-profile-detail-title">
                        <span>Reward tier</span>
                        <span class="sp-profile-detail-badge">{{ $rewardTierCard['tier_name'] }}</span>
                    </h2>
                    @php
                        $discountPercentage = $rewardTierCard['discount_percentage'];
                        $discountLabel = $discountPercentage === null
                            ? 'No discount'
                            : rtrim(rtrim(number_format((float) $discountPercentage, 2, '.', ''), '0'), '.').'%';
                        $progressValue = $rewardTierCard['next_tier_name'] ? $rewardTierCard['progress_percentage'] : 100;
                        $nextTierMinCoins = $rewardTierCard['earned_in_tier'] + $rewardTierCard['coins_to_next_tier'];
                    @endphp
                    <p class="sp-profile-detail-meta"><strong>{{ $discountLabel }}</strong> discount · {{ number_format($rewardTierCard['lifetime_coins']) }} lifetime coins</p>
                    <div class="sp-reward-tier-labels">
                        <span>{{ $rewardTierCard['tier_name'] }}</span>
                        @if($rewardTierCard['next_tier_name'])<span>{{ $rewardTierCard['next_tier_name'] }}</span>@endif
                    </div>
                    <div class="sp-reward-progress" role="progressbar" aria-label="Reward tier progress{{ $rewardTierCard['next_tier_name'] ? ' to '.$rewardTierCard['next_tier_name'] : '' }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progressValue }}">
                        <div class="sp-reward-progress-fill {{ $rewardTierCard['next_tier_name'] ? '' : 'is-complete' }}" style="--reward-progress-width: {{ $progressValue }}%; width: {{ $progressValue }}%"></div>
                    </div>
                    @if($rewardTierCard['next_tier_name'])
                        <p class="sp-reward-progress-label">{{ number_format($rewardTierCard['earned_in_tier']) }} / {{ number_format($nextTierMinCoins) }} coins - {{ number_format($rewardTierCard['coins_to_next_tier']) }} coins to {{ $rewardTierCard['next_tier_name'] }}</p>
                    @else
                        <p class="sp-reward-progress-label">Highest tier reached</p>
                    @endif
                    <p class="sp-profile-detail-meta">Discounted bookings: <strong>{{ number_format($rewardTierCard['used']) }} of {{ $rewardTierCard['limit'] === 0 ? 'Unlimited' : number_format($rewardTierCard['limit']) }}</strong>
                        · Remaining: <strong>{{ $rewardTierCard['limit'] === 0 ? 'Unlimited' : number_format((int) $rewardTierCard['remaining']) }}</strong>
                    </p>
                    @endif
                </article>
            @endif
            @if(!empty($membershipCard['active']))
                @php
                    $membership = $membershipCard['active'];
                @endphp
                <article class="sp-profile-detail-card">
                    <h2 class="sp-profile-detail-title">
                        <span>{{ $membership['plan_name'] }}</span>
                        <span class="sp-profile-detail-badge sp-membership-active-badge">{{ $membership['status'] }}</span>
                    </h2>
                    <p class="sp-profile-detail-meta">{{ $membership['start_date'] }} – {{ $membership['end_date'] }} · {{ $membership['days_left'] }} days left</p>
                    <p class="sp-profile-detail-meta">Branches: <strong>{{ implode(', ', $membership['branches']) }}</strong></p>
                    <p class="sp-profile-detail-meta">Free consultations: <strong>{{ number_format($membership['consultations_used']) }} used of {{ $membership['consultations_limit'] === 0 ? 'Unlimited' : number_format($membership['consultations_limit']) }}</strong></p>
                    @if($membership['free_packages'] !== [])
                        <div class="sp-detail-list">
                            @foreach($membership['free_packages'] as $freePackage)
                                <div class="sp-detail-list-item"><strong>{{ $freePackage['name'] }}</strong><br>{{ number_format($freePackage['used']) }} used of {{ $freePackage['limit'] === 0 ? 'Unlimited' : number_format($freePackage['limit']) }} lab tests</div>
                            @endforeach
                        </div>
                    @endif
                </article>
            @endif
        </section>
    @endif
    @if(empty($membershipCard['active']) && !empty($membershipCard['expired_label']))
        <p class="sp-membership-expired">{{ $membershipCard['expired_label'] }}</p>
    @endif
    <section class="sp-card">
        <h2 class="sp-card-title">Transactions</h2>
        <div class="sp-filters">
            <select class="sp-control" wire:model.live="transactionStatus" aria-label="Transaction status">
                <option value="all">All status</option>
                <option value="pending">Pending</option>
                <option value="completed">Completed</option>
                <option value="failed">Failed</option>
                <option value="refunded">Refunded</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <input class="sp-control" type="date" wire:model.live="transactionFrom" aria-label="From date">
            <input class="sp-control" type="date" wire:model.live="transactionTo" aria-label="To date">
            <button type="button" class="sp-btn sp-btn-ghost" wire:click="clearTransactionFilters">Clear</button>
        </div>
        <div class="sp-table-wrap sp-transactions-table-wrap" x-data="{ expandedTransaction: null }">
            <table class="sp-table sp-transactions-table">
                <thead>
                    <tr>
                        <th aria-label="Transaction details"></th><th>Date</th><th>Invoice</th><th>Description</th><th>Amount</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        @php
                            $breakdown = $transaction->breakdown;
                            $money = fn ($amount) => 'Rs '.number_format((float) $amount, 2);
                            $detailPanelId = 'sp-tx-detail-panel-'.$transaction->id;
                            $transactionTypeLabel = match ($breakdown['booking_type']) {
                                'doctor' => 'Doctor consultation',
                                'second_opinion' => 'Second opinion',
                                'package' => 'Diagnostic package',
                                'pharmacy' => 'Pharmacy',
                                default => 'Transaction',
                            };
                            $breakdownLines = [
                                ['Original amount', $breakdown['original_amount'], false, true],
                                ['Membership discount', $breakdown['membership_discount'], true, false],
                                ['Reward discount', $breakdown['reward_discount'], true, false],
                                ['Coins used ('.$breakdown['coins_used'].' coins)', $breakdown['coins_used'], false, false, true],
                                ['Coin discount', $breakdown['coin_discount'], true, false],
                                ['Discount (source not recorded)', $breakdown['unexplained_discount'], true, false],
                                ['Service charge', $breakdown['service_charge'], false, false],
                                ['GST', $breakdown['gst'], false, false],
                                ['Gateway charge', $breakdown['gateway_charge'], false, false],
                                ['Total paid', $breakdown['total_paid'], false, true, false, true],
                            ];
                        @endphp
                        <tr wire:key="sp-tx-{{ $transaction->id }}">
                            <td class="sp-tx-toggle-cell">
                                <button type="button" class="sp-tx-toggle" x-on:click="expandedTransaction = expandedTransaction === {{ (int) $transaction->id }} ? null : {{ (int) $transaction->id }}" x-bind:aria-expanded="expandedTransaction === {{ (int) $transaction->id }}" aria-controls="{{ $detailPanelId }}" aria-label="Toggle transaction breakdown">
                                    <svg viewBox="0 0 20 20" aria-hidden="true"><path d="m5 7.5 5 5 5-5" /></svg>
                                </button>
                            </td>
                            <td data-label="Date">{{ $transaction->when_label ?: '—' }}</td>
                            <td data-label="Invoice">{{ $transaction->invoice_id }}</td>
                            <td data-label="Description">
                                <div class="sp-tx-description">
                                    <div class="sp-tx-description-main">
                                        <div>{{ $transaction->description }}</div>
                                        <div class="sp-tx-badges">
                                            @if($breakdown['has_membership'])<span class="sp-tx-badge sp-tx-badge-membership">Membership</span>@endif
                                            @if($breakdown['has_reward_tier'])
                                                <span class="sp-tx-badge sp-tx-badge-reward">
                                                    Reward tier
                                                    @if($breakdown['reward_tier_name']) · {{ $breakdown['reward_tier_name'] }}@endif
                                                    @if($breakdown['reward_tier_percentage'] !== null) {{ number_format($breakdown['reward_tier_percentage'], 0) }}%@endif
                                                </span>
                                            @endif
                                            @if($breakdown['has_coins'])<span class="sp-tx-badge sp-tx-badge-coins">Coins ({{ $breakdown['coins_used'] }})</span>@endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Amount">
                                {{ $money($breakdown['total_paid']) }}
                                @if($breakdown['original_amount'] > $breakdown['total_paid'])
                                    <span class="sp-tx-original-amount"><s>{{ $money($breakdown['original_amount']) }}</s></span>
                                @endif
                            </td>
                            <td data-label="Status"><span class="sp-pill {{ $statusClass($transaction->status) }}">{{ ucfirst((string) $transaction->status) }}</span></td>
                        </tr>
                        <tr class="sp-tx-detail-row" wire:key="sp-tx-detail-{{ $transaction->id }}" x-show="expandedTransaction === {{ (int) $transaction->id }}" x-cloak>
                            <td colspan="6" class="sp-tx-detail-cell">
                                <div id="{{ $detailPanelId }}" class="sp-tx-detail-panel">
                                    <p class="sp-tx-booking-meta">{{ $transactionTypeLabel }}@if($breakdown['booking_id']) #{{ $breakdown['booking_id'] }}@endif</p>
                                    <div class="sp-tx-breakdown">
                                        @foreach($breakdownLines as $line)
                                            @php
                                                $isCoinsLine = $line[4] ?? false;
                                                $isTotalLine = $line[5] ?? false;
                                                $lineAmount = (float) $line[1];
                                            @endphp
                                            @if($line[3] || $lineAmount > 0)
                                                <div class="sp-tx-breakdown-line {{ $isTotalLine ? 'is-total' : '' }}">
                                                    <span class="sp-tx-breakdown-label">{{ $line[0] }}</span>
                                                    <span class="sp-tx-breakdown-value {{ $line[2] && $lineAmount > 0 ? 'is-discount' : '' }}">
                                                        @if($isCoinsLine){{ (int) $lineAmount }} coins
                                                        @elseif($line[2] && $lineAmount > 0)−{{ $money($lineAmount) }}
                                                        @else{{ $money($lineAmount) }}@endif
                                                    </span>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="sp-empty"><div class="sp-empty-title">No transactions</div><p class="sp-note">No payments match these filters.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <footer class="sp-footer">
            <span>Showing {{ $transactions->firstItem() ?? 0 }}–{{ $transactions->lastItem() ?? 0 }} of {{ $transactions->total() }}</span>
            {{ $transactions->links('pagination.sp') }}
        </footer>
    </section>
@endif

@if($showStatusDialog)
    <template x-teleport="body">
        <div>
            <div class="sp-backdrop" wire:click="$set('showStatusDialog', false)"></div>
            <div class="sp-layer">
                <div class="sp-modal" role="alertdialog" aria-modal="true" aria-labelledby="sp-profile-status-title">
                    <div class="sp-modal-head">
                        <h2 id="sp-profile-status-title" class="sp-modal-title">{{ $isActive ? 'Deactivate' : 'Activate' }} {{ $fullName }}?</h2>
                        <button type="button" class="sp-x" wire:click="$set('showStatusDialog', false)" aria-label="Close">&times;</button>
                    </div>
                    <div class="sp-modal-body">
                        <p>The account will be signed out and blocked from logging in until reactivated.</p>
                    </div>
                    <div class="sp-modal-foot">
                        <button type="button" class="sp-btn sp-btn-ghost" wire:click="$set('showStatusDialog', false)">Cancel</button>
                        <button type="button" class="sp-btn sp-btn-primary" wire:click="confirmStatusChange">{{ $isActive ? 'Deactivate' : 'Activate' }}</button>
                    </div>
                </div>
            </div>
        </div>
    </template>
@endif
@endif
</div>
