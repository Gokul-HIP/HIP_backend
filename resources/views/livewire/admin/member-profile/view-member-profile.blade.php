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
    .sp-table thead{display:none}
    .sp-table,.sp-table tbody,.sp-table tr,.sp-table td{display:block;width:100%}
    .sp-table tr{margin-bottom:12px;border:1px solid var(--border-color);border-radius:var(--radius-card);overflow:hidden}
    .sp-table td{display:flex;justify-content:space-between;gap:12px}
    .sp-table td::before{content:attr(data-label);color:var(--muted-color);font-size:12px;font-weight:700}
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
        <article class="sp-tile"><p class="sp-tile-label">Coins earned</p><p class="sp-tile-value">—</p><p class="sp-note">Earned coins are not recorded</p></article>
        <article class="sp-tile"><p class="sp-tile-label">Coins used</p><p class="sp-tile-value">{{ $coinsUsed }}</p><p class="sp-note">from bookings</p></article>
        <article class="sp-tile"><p class="sp-tile-label">Coins balance</p><p class="sp-tile-value">{{ $coinBalance === null ? '—' : $coinBalance }}</p></article>
    </section>
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
        <div class="sp-table-wrap">
            <table class="sp-table">
                <thead>
                    <tr>
                        <th>Date</th><th>Invoice</th><th>Description</th><th>Amount</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr wire:key="sp-tx-{{ $transaction->id }}">
                            <td data-label="Date">{{ $transaction->when_label ?: '—' }}</td>
                            <td data-label="Invoice">{{ $transaction->invoice_id }}</td>
                            <td data-label="Description">{{ $transaction->description }}</td>
                            <td data-label="Amount">{{ $transaction->total_amount ?? '—' }}</td>
                            <td data-label="Status"><span class="sp-pill {{ $statusClass($transaction->status) }}">{{ ucfirst((string) $transaction->status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="sp-empty"><div class="sp-empty-title">No transactions</div><p class="sp-note">No payments match these filters.</p></td></tr>
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
                        <p>Status only — login blocking comes in a later step</p>
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
