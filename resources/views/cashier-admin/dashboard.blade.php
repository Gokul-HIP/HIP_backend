@extends('cashier-admin.layout.cashieradmin')

@section('title', 'Cashier Admin Dashboard')
@section('breadcrumb', 'Dashboard')

@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

    .pf-wrap {
        font-family: 'Inter', sans-serif;
        background: #f8fafc;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        padding: 0;
    }

    /* ===== STAT CARDS ===== */
    .pf-stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        padding: 24px 24px 0;
    }
    .pf-stat-card {
        background: #fff;
        border: 1px solid #e8edf2;
        border-radius: 12px;
        padding: 20px 22px 22px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .pf-stat-label {
        font-size: 12px;
        color: #9ca3af;
        font-weight: 500;
    }
    .pf-stat-value {
        font-size: 30px;
        font-weight: 700;
        color: #111827;
        line-height: 1;
    }
    .pf-stat-value.orange { color: #f97316; }
    .pf-stat-value.blue   { color: #2563eb; }

    /* ===== EMPTY STATE ===== */
    .pf-empty-wrap {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 48px 24px 60px;
        background: #f8fafc;
    }
    .pf-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 14px;
        text-align: center;
    }

    /* Circle icon */
    .pf-icon-circle {
        width: 180px;
        height: 180px;
        background: #eaf1fb;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        margin-bottom: 4px;
    }
    .pf-icon-circle .pf-plus {
        position: absolute;
        bottom: 16px;
        right: 12px;
        background: #fff;
        border-radius: 8px;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 10px rgba(0,0,0,0.12);
        font-size: 18px;
        color: #374151;
        font-weight: 600;
        line-height: 1;
    }

    .pf-empty-title {
        font-size: 22px;
        font-weight: 800;
        color: #111827;
        margin: 0;
    }
    .pf-empty-sub {
        font-size: 14px;
        color: #6b7280;
        max-width: 400px;
        line-height: 1.7;
        margin: 0;
    }

    
    /* ── Glowing CTA button ── */
    .pf-btn {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background: #0da2e7;
        color: #ffffff !important;
        font-size: 14.5px;
        font-weight: 600;
        font-family: 'Inter', sans-serif;
        padding: 13px 30px;
        border-radius: 50px;
        border: none;
        cursor: pointer;
        margin-top: 6px;
        text-decoration: none !important;
        transition: background 0.2s, box-shadow 0.2s, transform 0.15s;

        /* Glow effect */
        box-shadow:
            0 0 0px 0px rgba(13, 162, 231, 0),
            0 4px 18px rgba(13, 162, 231, 0.55),
            0 1px 4px rgba(0,0,0,0.1);
    }

    .pf-btn:hover { background: #0b8fcf; box-shadow: 0 0 0px 0px rgba(13, 162, 231, 0), 0 4px 24px rgba(13, 162, 231, 0.75), 0 1px 6px rgba(0,0,0,0.15); transform: translateY(-1px); }
    .pf-btn-icon {
        width: 22px;
        height: 22px;
        border: 2px solid rgba(255,255,255,0.55);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        line-height: 1;
        flex-shrink: 0;
    }

    .pf-help {
        font-size: 13px;
        color: #9ca3af;
        margin: 2px 0 0;
    }
    .pf-help a {
        color: #0DA2E7;
        font-weight: 600;
        text-decoration: none;
    }
    .pf-help a:hover { text-decoration: underline; }

    /* ===== FOOTER ===== */
    .pf-footer {
        background: #fff;
        border-top: 1px solid #e8edf2;
        padding: 14px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .pf-footer span {
        font-size: 13px;
        color: #6b7280;
        font-family: 'Inter', sans-serif;
    }
    .pf-pager { display: flex; gap: 6px; }
    .pf-pager button {
        width: 32px;
        height: 32px;
        border: 1px solid #e2e8f0;
        border-radius: 7px;
        background: #fff;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6b7280;
        font-size: 11px;
        transition: background 0.14s;
    }
    .pf-pager button:hover { background: #f1f5f9; }

    @media (max-width: 768px) {
        .pf-stats-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 480px) {
        .pf-stats-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="pf-wrap">

    {{-- ===== STAT CARDS ===== --}}
    <div class="pf-stats-grid">
        <div class="pf-stat-card">
            <span class="pf-stat-label">Total Transactions</span>
            <span class="pf-stat-value">{{ $stats['total_transactions'] ?? 0 }}</span>
        </div>
        <div class="pf-stat-card">
            <span class="pf-stat-label">Daily Revenue</span>
            <span class="pf-stat-value">${{ number_format($stats['daily_revenue'] ?? 0, 2) }}</span>
        </div>
        <div class="pf-stat-card">
            <span class="pf-stat-label">Pending Invoices</span>
            <span class="pf-stat-value orange">{{ $stats['pending_invoices'] ?? 0 }}</span>
        </div>
        <div class="pf-stat-card">
            <span class="pf-stat-label">Active Members</span>
            <span class="pf-stat-value blue">{{ $stats['active_members'] ?? 0 }}</span>
        </div>
    </div>

    {{-- ===== EMPTY STATE ===== --}}
    <div class="pf-empty-wrap">
        <div class="pf-empty">

            <div class="pf-icon-circle">
                {{-- Stacked card / payment icon --}}
                <svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                    {{-- Back card (slightly offset) --}}
                    <rect x="8" y="18" width="52" height="36" rx="6" fill="#b8cfe8" opacity="0.45"/>
                    {{-- Front card --}}
                    <rect x="18" y="26" width="52" height="36" rx="6" fill="#a0b8d0" opacity="0.55"/>
                    {{-- Screen rectangle on front card --}}
                    <rect x="24" y="34" width="22" height="16" rx="3.5" fill="#d4e4f2" opacity="0.95"/>
                    {{-- Circle lens/chip --}}
                    <circle cx="36" cy="42" r="5" fill="#c0d4e8" opacity="0.9"/>
                </svg>
                <span class="pf-plus">+</span>
            </div>

            <h2 class="pf-empty-title">Welcome back, {{ ucfirst(auth()->user()->name) }}!</h2>

            <p class="pf-empty-sub">
                Ready to start the shift? There are no recent transactions<br>
                recorded today. Click below to begin a new payment.
            </p>

            <a href="{{ route('cashier.payments.create') }}" class="pf-btn" style="background: #0DA2E7;">
                <span class="pf-btn-icon">+</span>
                Start a New Payment
            </a>

            <p class="pf-help">
                Need help? Check out our <a href="#">Support Center</a> or contact IT.
            </p>

        </div>
    </div>

    {{-- ===== FOOTER ===== --}}
    <div class="pf-footer">
        <span>Showing 0 of 0 records</span>
        <div class="pf-pager">
            <button><i class="fas fa-chevron-left"></i></button>
            <button><i class="fas fa-chevron-right"></i></button>
        </div>
    </div>

</div>

@endsection