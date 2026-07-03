@extends('technician-admin.layout.technicianadmin')

@section('title', 'Technician Admin Dashboard')
@section('breadcrumb', 'Dashboard')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

    .pf-wrap {
        font-family: 'Inter', sans-serif;
        background: #f8fafc;
        min-height: 100vh;
        padding: 28px 28px 32px;
        display: flex;
        flex-direction: column;
        gap: 22px;
    }

    .pf-page-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }
    .pf-page-header h1 {
        font-size: 22px;
        font-weight: 700;
        color: #111827;
        margin: 0 0 3px;
        line-height: 1.2;
    }
    .pf-page-header p {
        font-size: 13.5px;
        color: #6b7280;
        margin: 0;
    }
    .pf-btn-new {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--button-color);
        color: #fff;
        font-size: 13.5px;
        font-weight: 600;
        font-family: 'Inter', sans-serif;
        padding: 10px 20px;
        border-radius: 10px;
        border: none;
        cursor: pointer;
        text-decoration: none;
        transition: background 0.15s, transform 0.1s;
        flex-shrink: 0;
        white-space: nowrap;
    }
    .pf-btn-new:hover { filter: brightness(0.9); transform: translateY(-1px); color: #fff; }

    .pf-stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }
    .pf-stat-card {
        background: #fff;
        border: 1px solid #e8edf2;
        border-radius: 14px;
        padding: 20px 22px;
        display: flex;
        align-items: center;
        gap: 16px;
        transition: box-shadow 0.2s;
    }
    .pf-stat-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.07); }
    .pf-stat-icon {
        width: 46px; height: 46px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }
    .pf-stat-icon.blue   { background: #eaf3ff; color: #0da2e7; }
    .pf-stat-icon.green  { background: #ecfdf5; color: #059669; }
    .pf-stat-icon.orange { background: #fff7ed; color: #f97316; }
    .pf-stat-info { flex: 1; min-width: 0; }
    .pf-stat-label {
        font-size: 12px;
        color: #9ca3af;
        font-weight: 500;
        margin-bottom: 5px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .pf-stat-value {
        font-size: 28px;
        font-weight: 700;
        color: #111827;
        line-height: 1;
    }
    .pf-stat-value.orange { color: #f97316; }
    .pf-stat-value.green  { color: #059669; }

    .pf-body-grid {
        display: grid;
        grid-template-columns: 1fr 340px;
        gap: 20px;
        align-items: start;
    }

    .pf-card {
        background: #fff;
        border: 1px solid #e8edf2;
        border-radius: 14px;
        overflow: hidden;
    }
    .pf-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        border-bottom: 1px solid #f0f4f8;
    }
    .pf-card-title {
        font-size: 14.5px;
        font-weight: 700;
        color: #111827;
    }
    .pf-view-all {
        font-size: 13px;
        font-weight: 600;
        color: var(--button-color);
        text-decoration: none;
        white-space: nowrap;
    }
    .pf-view-all:hover { text-decoration: underline; }

    .pf-table { width: 100%; border-collapse: collapse; }
    .pf-table thead tr { background: #f8fafc; }
    .pf-table th {
        padding: 10px 16px;
        font-size: 11px;
        font-weight: 700;
        color: #9ca3af;
        text-align: left;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        border-bottom: 1px solid #f0f4f8;
        white-space: nowrap;
    }
    .pf-table td {
        padding: 13px 16px;
        font-size: 13.5px;
        color: #374151;
        border-bottom: 1px solid #f0f4f8;
        vertical-align: middle;
    }
    .pf-table tbody tr:last-child td { border-bottom: none; }
    .pf-table tbody tr:hover td { background: #fafbfc; }

    .pf-invoice-id { font-weight: 600; color: var(--button-color); }
    .pf-patient-name { font-weight: 500; color: #111827; }

    .pf-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11.5px;
        font-weight: 600;
        white-space: nowrap;
    }
    .pf-badge-paid     { background: #ecfdf5; color: #059669; }
    .pf-badge-pending  { background: #fff7ed; color: #f97316; }
    .pf-badge-cancelled{ background: #fff0f2; color: #dc2626; }

    .pf-table-empty {
        padding: 40px 20px;
        text-align: center;
        color: #9ca3af;
    }
    .pf-table-empty i { font-size: 36px; color: #e5e7eb; margin-bottom: 10px; display: block; }
    .pf-table-empty p { font-size: 14px; color: #6b7280; margin: 0; }
    .pf-table-empty small { font-size: 12.5px; color: #9ca3af; }

    .pf-right-col { display: flex; flex-direction: column; gap: 16px; }

    .pf-quick-card {
        background: #fff;
        border: 1px solid #e8edf2;
        border-radius: 14px;
        padding: 18px 20px;
    }
    .pf-quick-title {
        font-size: 14px;
        font-weight: 700;
        color: #111827;
        margin-bottom: 14px;
    }
    .pf-quick-list { display: flex; flex-direction: column; gap: 8px; }
    .pf-quick-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 11px 14px;
        border: 1px solid #e8edf2;
        border-radius: 10px;
        text-decoration: none;
        color: #374151;
        font-size: 13.5px;
        font-weight: 500;
        transition: background 0.15s, border-color 0.15s;
    }
    .pf-quick-item:hover { background: #f8fafc; border-color: #d1d5db; color: #111827; }
    .pf-quick-item i { width: 16px; text-align: center; color: #9ca3af; font-size: 14px; }
    .pf-quick-item-label { flex: 1; }
    .pf-quick-item-arrow { color: #d1d5db; font-size: 12px; }

    .pf-summary-list { display: flex; flex-direction: column; }
    .pf-summary-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid #f0f4f8;
        font-size: 13.5px;
    }
    .pf-summary-row:last-child { border-bottom: none; }
    .pf-summary-key { color: #6b7280; }
    .pf-summary-val { font-weight: 600; color: #111827; }
    .pf-summary-val.orange { color: #f97316; }
    .pf-summary-val.green  { color: #059669; }

    @media (max-width: 1024px) {
        .pf-stats-grid { grid-template-columns: 1fr 1fr; }
        .pf-body-grid  { grid-template-columns: 1fr; }
    }
    @media (max-width: 640px) {
        .pf-stats-grid { grid-template-columns: 1fr; }
        .pf-wrap { padding: 16px; }
    }
</style>

<div class="pf-wrap">

    <div class="pf-page-header">
        <div>
            <h1>Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }},
                {{ ucfirst(auth()->user()->name) }} 👋</h1>
            <p>Here's your diagnostic package activity for today, {{ now()->format('d M Y') }}</p>
        </div>
        <a href="{{ route('technician.payments.create') }}" class="pf-btn-new">
            <i class="fas fa-plus" style="font-size:11px;"></i> New Payment
        </a>
    </div>

    <div class="pf-stats-grid">
        <div class="pf-stat-card">
            <div class="pf-stat-icon blue"><i class="fas fa-receipt"></i></div>
            <div class="pf-stat-info">
                <div class="pf-stat-label">Total Diagnostic Transactions</div>
                <div class="pf-stat-value">{{ number_format($stats['total_transactions'] ?? 0) }}</div>
            </div>
        </div>
        <div class="pf-stat-card">
            <div class="pf-stat-icon green"><i class="fas fa-indian-rupee-sign"></i></div>
            <div class="pf-stat-info">
                <div class="pf-stat-label">Daily Diagnostic Revenue</div>
                <div class="pf-stat-value green">₹{{ number_format($stats['daily_revenue'] ?? 0, 2) }}</div>
            </div>
        </div>
        <div class="pf-stat-card">
            <div class="pf-stat-icon orange"><i class="fas fa-clock"></i></div>
            <div class="pf-stat-info">
                <div class="pf-stat-label">Pending Diagnostic Invoices</div>
                <div class="pf-stat-value orange">{{ number_format($stats['pending_invoices'] ?? 0) }}</div>
            </div>
        </div>
    </div>

    <div class="pf-body-grid">

        <div class="pf-card">
            <div class="pf-card-header">
                <span class="pf-card-title">Recent Transactions</span>
                <a href="{{ route('technician.payments.index') }}" class="pf-view-all">View all payments →</a>
            </div>

            @if($recentTransactions->isNotEmpty())
                <table class="pf-table">
                    <thead>
                        <tr>
                            <th>Invoice ID</th>
                            <th>Patient</th>
                            <th>Service</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentTransactions as $txn)
                            <tr>
                                <td><span class="pf-invoice-id">{{ $txn['invoice_id'] }}</span></td>
                                <td><span class="pf-patient-name">{{ $txn['patient_name'] }}</span></td>
                                <td>{{ $txn['service_summary'] }}</td>
                                <td>₹{{ number_format($txn['amount'], 2) }}</td>
                                <td>{{ $txn['created_at'] ? $txn['created_at']->format('d M Y') : '—' }}</td>
                                <td>
                                    <span class="pf-badge pf-badge-{{ $txn['badge_class'] }}">
                                        {{ $txn['status_label'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="pf-table-empty">
                    <i class="fas fa-file-invoice"></i>
                    <p>No diagnostic transactions yet</p>
                    <small>Lab tests, packages and diagnostic package payments will appear here</small>
                </div>
            @endif
        </div>

        <div class="pf-right-col">
            <div class="pf-quick-card">
                <div class="pf-quick-title">Quick Actions</div>
                <div class="pf-quick-list">
                    <a href="{{ route('technician.payments.create') }}" class="pf-quick-item">
                        <i class="fas fa-plus-circle"></i>
                        <span class="pf-quick-item-label">New Payment</span>
                        <i class="fas fa-chevron-right pf-quick-item-arrow"></i>
                    </a>
                    <a href="{{ route('technician.payments.index') }}" class="pf-quick-item">
                        <i class="fas fa-list-alt"></i>
                        <span class="pf-quick-item-label">All Payments</span>
                        <i class="fas fa-chevron-right pf-quick-item-arrow"></i>
                    </a>
                    <a href="{{ route('technician.diagnostic-bookings.index') }}" class="pf-quick-item">
                        <i class="fas fa-vial"></i>
                        <span class="pf-quick-item-label">Diagnostic Bookings</span>
                        <i class="fas fa-chevron-right pf-quick-item-arrow"></i>
                    </a>
                </div>
            </div>

            <div class="pf-quick-card">
                <div class="pf-quick-title">Today's Summary</div>
                <div class="pf-summary-list">
                    <div class="pf-summary-row">
                        <span class="pf-summary-key">Total Transactions</span>
                        <span class="pf-summary-val">{{ number_format($stats['total_transactions'] ?? 0) }}</span>
                    </div>
                    <div class="pf-summary-row">
                        <span class="pf-summary-key">Daily Revenue</span>
                        <span class="pf-summary-val green">₹{{ number_format($stats['daily_revenue'] ?? 0, 2) }}</span>
                    </div>
                    <div class="pf-summary-row">
                        <span class="pf-summary-key">Pending Invoices</span>
                        <span class="pf-summary-val orange">{{ number_format($stats['pending_invoices'] ?? 0) }}</span>
                    </div>
                    <div class="pf-summary-row">
                        <span class="pf-summary-key">Last Updated</span>
                        <span class="pf-summary-val" style="font-size:12.5px;">{{ now()->format('h:i A') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection
