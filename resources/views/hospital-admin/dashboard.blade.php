@extends('hospital-admin.layout.hospitaladmin')

@section('title', 'HealthCare Admin Dashboard')
@section('breadcrumb', 'Dashboard')

@section('content')

<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap');

* { font-family: 'Plus Jakarta Sans', sans-serif; box-sizing: border-box; }

:root {
    --brand: #0da2e7;
    --brand-dark: #0882ba;
    --brand-light: #e0f5fd;
    --surface: #ffffff;
    --bg: #f0f4f8;
    --border: #e2e8f0;
    --text: #0f172a;
    --text-2: #475569;
    --text-3: #94a3b8;
    --success: #10b981;
    --danger: #ef4444;
    --warning: #f59e0b;
    --purple: #8b5cf6;
    --shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
    --shadow-md: 0 4px 16px rgba(0,0,0,0.08);
    --shadow-lg: 0 12px 32px rgba(0,0,0,0.10);
    --r: 14px;
    --r-sm: 10px;
}

.dash-wrap { background: var(--bg); min-height: 100vh; }

/* ── HERO BANNER ── */
.hero-wrap { padding: 20px 24px 0; }

.hero-card {
    position: relative;
    height: 200px;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(0,0,0,0.22);
}

.hero-img {
    position: absolute; inset: 0;
    width: 100%; height: 100%;
    object-fit: cover;
    filter: brightness(0.52);
}

.hero-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(105deg,
        rgba(5,12,24,0.90) 0%,
        rgba(5,12,24,0.60) 32%,
        rgba(5,12,24,0.22) 62%,
        rgba(5,12,24,0.12) 100%);
}

.hero-bottom-fade {
    position: absolute; bottom: 0; left: 0; right: 0;
    height: 56px;
    background: linear-gradient(to bottom, transparent, rgba(5,12,24,0.40));
}

.hero-inner {
    position: absolute; inset: 0;
    display: flex;
    align-items: stretch;
    padding: 0 28px;
    gap: 24px;
}

.hero-left {
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding-bottom: 22px;
}

.hero-title {
    font-size: 22px;
    font-weight: 800;
    color: #fff;
    letter-spacing: -0.02em;
    line-height: 1.2;
    text-shadow: 0 2px 10px rgba(0,0,0,0.50);
    margin: 0 0 4px;
}

.hero-sub {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12.5px;
    color: rgba(203,213,225,0.88);
    margin-bottom: 18px;
}

.hero-btns { display: flex; gap: 10px; }

.hero-btn-ghost {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 7px 16px;
    border-radius: 9px;
    font-size: 12.5px;
    font-weight: 600;
    color: #fff;
    border: 1px solid rgba(255,255,255,0.28);
    background: rgba(255,255,255,0.10);
    backdrop-filter: blur(10px);
    cursor: pointer;
    transition: background 0.15s;
}
.hero-btn-ghost:hover { background: rgba(255,255,255,0.18); }

.hero-btn-solid {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 7px 16px;
    border-radius: 9px;
    font-size: 12.5px;
    font-weight: 600;
    color: #fff;
    background: var(--brand);
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 16px rgba(13,162,231,0.45);
    transition: opacity 0.15s, box-shadow 0.15s;
}
.hero-btn-solid:hover { opacity: 0.9; box-shadow: 0 6px 20px rgba(13,162,231,0.55); }

/* Pending alerts on hero */
.hero-alerts {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 16px 0;
    min-width: 0;
}

.alerts-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}

.alerts-label-left {
    display: flex;
    align-items: center;
    gap: 7px;
}

.alerts-bar { width: 2px; height: 14px; background: #f59e0b; border-radius: 2px; }

.alerts-title {
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.13em;
    color: #fbbf24;
}

.alerts-count-badge {
    font-size: 10.5px;
    font-weight: 700;
    padding: 2px 9px;
    border-radius: 20px;
    background: rgba(245,158,11,0.18);
    color: #fde68a;
    border: 1px solid rgba(245,158,11,0.35);
}

.alerts-divider {
    height: 1px;
    background: rgba(251,191,36,0.20);
    margin-bottom: 8px;
}

.alerts-list {
    display: flex;
    flex-direction: column;
    gap: 7px;
    overflow-y: auto;
    max-height: 128px;
    scrollbar-width: thin;
    scrollbar-color: rgba(245,158,11,0.3) transparent;
}

.alert-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 10px;
    background: rgba(5,12,24,0.50);
    border: 1px solid rgba(245,158,11,0.28);
    backdrop-filter: blur(14px);
    flex-shrink: 0;
}

.alert-icon {
    width: 28px; height: 28px;
    border-radius: 8px;
    background: rgba(245,158,11,0.16);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    color: #fbbf24;
}

.alert-name { font-size: 11.5px; font-weight: 700; color: #fff; line-height: 1.2; }
.alert-desc { font-size: 10px; color: rgba(203,213,225,0.65); margin-top: 2px; }

.alert-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 5px 10px;
    border-radius: 7px;
    font-size: 11px;
    font-weight: 700;
    color: #fff;
    background: #d97706;
    flex-shrink: 0;
    text-decoration: none;
    transition: opacity 0.15s;
    box-shadow: 0 2px 8px rgba(217,119,6,0.4);
}
.alert-btn:hover { opacity: 0.85; }

/* ── PAGE BODY ── */
.page-body { padding: 20px 24px; display: flex; flex-direction: column; gap: 18px; }

/* ── FILTER BAR ── */
.filter-bar {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--r-sm);
    padding: 10px 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    flex-wrap: wrap;
}

.filter-group { display: flex; gap: 8px; flex-wrap: wrap; }

.filter-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 500;
    color: var(--text-2);
    background: var(--surface);
    cursor: pointer;
    transition: all 0.15s;
    white-space: nowrap;
}
.filter-btn:hover { border-color: var(--brand); color: var(--brand); background: var(--brand-light); }
.filter-btn svg { color: var(--text-3); flex-shrink: 0; }

.filter-icon-btn {
    width: 34px; height: 34px;
    border: 1px solid var(--border);
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    color: var(--text-3);
    cursor: pointer;
    transition: all 0.15s;
    background: var(--surface);
}
.filter-icon-btn:hover { border-color: var(--brand); color: var(--brand); background: var(--brand-light); }

/* ── KPI CARDS ── */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 12px;
}

.kpi-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--r-sm);
    padding: 16px;
    position: relative;
    overflow: hidden;
    transition: all 0.20s ease;
}

.kpi-card::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 3px;
    background: var(--kpi-color, var(--brand));
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.22s ease;
    border-radius: 0 0 2px 2px;
}

.kpi-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); border-color: transparent; }
.kpi-card:hover::after { transform: scaleX(1); }

.kpi-icon-wrap {
    width: 36px; height: 36px;
    border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 12px;
    background: var(--kpi-bg, var(--brand-light));
    color: var(--kpi-color, var(--brand));
}

.kpi-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.09em;
    color: var(--text-3);
    margin-bottom: 6px;
}

.kpi-value {
    font-size: 24px;
    font-weight: 800;
    color: var(--text);
    line-height: 1;
    margin-bottom: 8px;
    font-variant-numeric: tabular-nums;
}

.kpi-trend {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 20px;
}

.kpi-trend.up { background: #dcfce7; color: #15803d; }
.kpi-trend.down { background: #fee2e2; color: #b91c1c; }

/* Card accent colors */
.kpi-card.blue   { --kpi-color: #0da2e7; --kpi-bg: #e0f5fd; }
.kpi-card.green  { --kpi-color: #10b981; --kpi-bg: #d1fae5; }
.kpi-card.purple { --kpi-color: #8b5cf6; --kpi-bg: #ede9fe; }
.kpi-card.amber  { --kpi-color: #f59e0b; --kpi-bg: #fef3c7; }
.kpi-card.teal   { --kpi-color: #06b6d4; --kpi-bg: #cffafe; }
.kpi-card.rose   { --kpi-color: #f43f5e; --kpi-bg: #ffe4e6; }

/* ── QUICK ACTION CARDS ── */
.qa-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }

.qa-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px;
    border-radius: var(--r-sm);
    color: #fff;
    text-decoration: none;
    transition: all 0.18s ease;
    position: relative;
    overflow: hidden;
}

.qa-card::before {
    content: '';
    position: absolute;
    top: -20px; right: -20px;
    width: 80px; height: 80px;
    border-radius: 50%;
    background: rgba(255,255,255,0.08);
}

.qa-card:hover { transform: translateY(-2px); filter: brightness(1.06); }

.qa-card-title { font-size: 14px; font-weight: 800; margin-bottom: 4px; }
.qa-card-desc { font-size: 12px; opacity: 0.82; line-height: 1.35; }

.qa-icon {
    width: 44px; height: 44px;
    border-radius: 10px;
    background: rgba(255,255,255,0.16);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    transition: transform 0.18s;
}
.qa-card:hover .qa-icon { transform: scale(1.10); }

/* ── CHART / PANEL CARDS ── */
.panel {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--r-sm);
    overflow: hidden;
}

.panel-header {
    padding: 18px 20px 14px;
    border-bottom: 1px solid #f8fafc;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
}

.panel-title { font-size: 13.5px; font-weight: 700; color: var(--text); }
.panel-sub { font-size: 11.5px; color: var(--text-3); margin-top: 2px; }

.panel-badge {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--text-3);
    background: #f8fafc;
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 3px 9px;
    white-space: nowrap;
    flex-shrink: 0;
}

.panel-body { padding: 20px; }

/* Weekly bar chart */
.bar-chart {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 8px;
    height: 160px;
    padding: 0 4px;
}

.bar-col { display: flex; flex-direction: column; align-items: center; gap: 6px; flex: 1; }

.bar-fill {
    width: 100%;
    border-radius: 5px 5px 0 0;
    min-height: 4px;
    transition: opacity 0.2s;
}

.bar-col:hover .bar-fill { opacity: 0.78; }
.bar-day { font-size: 10px; font-weight: 700; letter-spacing: 0.06em; }
.bar-amt { font-size: 9.5px; color: var(--text-3); font-variant-numeric: tabular-nums; }

/* Month list table */
.month-list { display: flex; flex-direction: column; gap: 0; }

.month-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 20px;
    border-bottom: 1px solid #f8fafc;
    transition: background 0.12s;
}
.month-row:last-child { border-bottom: none; }
.month-row:hover { background: #fafbfc; }

.month-name { font-size: 12.5px; font-weight: 600; color: var(--text-2); min-width: 72px; }
.month-values { display: flex; align-items: center; gap: 16px; }
.val-revenue { font-size: 12.5px; font-weight: 700; color: var(--brand); font-variant-numeric: tabular-nums; }
.val-refund { font-size: 12.5px; font-weight: 700; color: #f43f5e; font-variant-numeric: tabular-nums; }
.val-appt { font-size: 12px; font-weight: 700; color: var(--brand); }
.val-test { font-size: 12px; font-weight: 700; color: var(--purple); }

/* Quarter grid */
.quarter-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; padding: 20px; }

.q-card {
    background: #f8fafc;
    border: 1px solid var(--border);
    border-radius: 9px;
    padding: 14px 10px;
    text-align: center;
    transition: all 0.15s;
}
.q-card:hover { background: var(--brand-light); border-color: var(--brand); }
.q-label { font-size: 10px; font-weight: 700; color: var(--text-3); margin-bottom: 6px; }
.q-value { font-size: 22px; font-weight: 800; color: var(--text); font-variant-numeric: tabular-nums; }

/* Service revenue */
.service-item { margin-bottom: 18px; }
.service-item:last-child { margin-bottom: 0; }
.service-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 7px; }
.service-name { font-size: 12.5px; font-weight: 600; color: var(--text-2); }
.service-amount { font-size: 12.5px; font-weight: 700; color: var(--text); }
.service-track { width: 100%; height: 7px; background: #f1f5f9; border-radius: 10px; overflow: hidden; }
.service-fill { height: 100%; border-radius: 10px; transition: width 0.6s ease; }
.service-pct { font-size: 10px; font-weight: 700; color: var(--text-3); text-align: right; margin-top: 4px; }

/* Transactions table */
.tx-table { width: 100%; border-collapse: collapse; }
.tx-table thead tr { background: #f8fafc; }
.tx-table th {
    padding: 11px 18px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.09em;
    color: var(--text-3);
    text-align: left;
    white-space: nowrap;
}

.tx-table tbody tr { border-bottom: 1px solid #f8fafc; transition: background 0.12s; }
.tx-table tbody tr:last-child { border-bottom: none; }
.tx-table tbody tr:hover { background: #fafbfc; }
.tx-table td { padding: 12px 18px; }

.tx-id { font-size: 11.5px; font-weight: 700; color: var(--text-3); font-family: 'DM Mono', monospace; }
.tx-service { font-size: 13px; font-weight: 500; color: var(--text-2); }
.tx-amount { font-size: 13px; font-weight: 800; color: var(--text); font-variant-numeric: tabular-nums; }
.tx-date { font-size: 11px; color: var(--text-3); white-space: nowrap; }

.status-pill {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.panel-link {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--brand);
    text-decoration: none;
    transition: opacity 0.15s;
}
.panel-link:hover { opacity: 0.7; }

/* Grid layouts */
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.grid-3 { display: grid; grid-template-columns: 1fr 2fr; gap: 16px; }

@media (max-width: 900px) {
    .kpi-grid { grid-template-columns: repeat(3, 1fr); }
    .qa-grid { grid-template-columns: 1fr; }
    .grid-2, .grid-3 { grid-template-columns: 1fr; }
}
</style>

<div class="dash-wrap">

    {{-- ── Hero Banner ── --}}

    <div class="hero-wrap">
        <div class="hero-card">

            <img src="https://images.unsplash.com/photo-1586773860418-d37222d8fce3?w=1400&q=80"
                 alt="Hospital Banner" class="hero-img">
            <div class="hero-overlay"></div>
            <div class="hero-bottom-fade"></div>

            <div class="hero-inner">

                {{-- LEFT --}}
                <div class="hero-left" style="flex: {{ $hasAlerts ? '0 0 54%' : '1' }}; max-width: {{ $hasAlerts ? '54%' : '100%' }};">
                    <h1 class="hero-title">Wellness Hospital</h1>
                    <p class="hero-sub">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Main Healthcare Hub &bull; Multiple Locations
                    </p>
                    <div class="hero-btns">
                        <button type="button" class="hero-btn-ghost">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Edit Profile
                        </button>
                        <button type="button" class="hero-btn-solid">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Manage
                        </button>
                    </div>
                </div>

                {{-- RIGHT: pending alerts --}}
                @if($hasAlerts)
                <div class="hero-alerts" style="flex:1;">
                    <div class="alerts-label">
                        <div class="alerts-label-left">
                            <div class="alerts-bar"></div>
                            <span class="alerts-title">Pending Onboarding</span>
                        </div>
                        @if($alertCount > 1)
                        <span class="alerts-count-badge">{{ $alertCount }} hospitals</span>
                        @endif
                    </div>
                    <div class="alerts-divider"></div>
                    <div class="alerts-list">
                        @foreach($pendingHospitals as $hospital)
                        <div class="alert-row">
                            <div class="alert-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                </svg>
                            </div>
                            <div style="flex:1; min-width:0;">
                                <div class="alert-name">{{ $hospital->name }}</div>
                                <div class="alert-desc">Complete onboarding to activate on platform.</div>
                            </div>
                            <a href="{{ route('healthcare.hospital-profile.index', ['hospital_id' => $hospital->id]) }}" class="alert-btn">
                                Setup
                                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </div>
                        @endforeach
                    </div>
                    @if($alertCount > 2)
                    <p style="font-size:9px;color:rgba(251,191,36,0.50);text-align:center;margin-top:6px;letter-spacing:.05em;">↕ scroll to see all {{ $alertCount }} hospitals</p>
                    @endif
                </div>
                @endif

            </div>
        </div>
    </div>
    {{-- /Hero --}}

    {{-- ── Page Body ── --}}
    <div class="page-body">

        {{-- ── Filter Bar ── --}}
        <div class="filter-bar">
            <div class="filter-group">
                <button type="button" class="filter-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Last 30 Days
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <button type="button" class="filter-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                    All Categories
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <button type="button" class="filter-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    All Locations
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
            </div>
            <div style="display:flex; gap:6px;">
                <button type="button" class="filter-icon-btn" title="Refresh">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </button>
                <button type="button" class="filter-icon-btn" title="Download">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- ── KPI Cards ── --}}
        <div class="kpi-grid">
            @foreach($kpiCards as $idx => $kpi)
            <div class="kpi-card {{ $kpi['color'] }}">
                <div class="kpi-icon-wrap">
                    @if($idx === 0)
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @elseif($idx === 1)
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    @elseif($idx === 2)
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    @elseif($idx === 3)
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                    @elseif($idx === 4)
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                    @endif
                </div>
                <div class="kpi-label">{{ $kpi['label'] }}</div>
                <div class="kpi-value">{{ $kpi['value'] }}</div>
                <span class="kpi-trend {{ $kpi['delta'] >= 0 ? 'up' : 'down' }}">
                    <svg width="9" height="9" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        @if($kpi['delta'] >= 0)
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                        @endif
                    </svg>
                    {{ $kpi['delta'] >= 0 ? '+' : '-' }}{{ number_format(abs($kpi['delta']), 1) }}%
                </span>
            </div>
            @endforeach
        </div>

        {{-- ── Quick Actions ── --}}
        <div class="qa-grid">
            {{-- <a href="#" class="qa-card" style="background:linear-gradient(135deg,#0da2e7 0%,#0882ba 100%);box-shadow:0 8px 24px rgba(13,162,231,0.30);">
                <div>
                    <div class="qa-card-title">Manage Procedures</div>
                    <div class="qa-card-desc">Configure and add new medical procedures</div>
                </div>
                <div class="qa-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                </div>
            </a> --}}
            <a href="{{ route('healthcare.doctors.index') }}" class="qa-card" style="background:linear-gradient(135deg,#3b82f6 0%,#2563eb 100%);box-shadow:0 8px 24px rgba(59,130,246,0.28);">
                <div>
                    <div class="qa-card-title">View Doctors</div>
                    <div class="qa-card-desc">Check availability and manage profiles</div>
                </div>
                <div class="qa-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
            </a>
            <a href="{{ route('healthcare.doctor.booking') }}" class="qa-card" style="background:linear-gradient(135deg,#06b6d4 0%,#0891b2 100%);box-shadow:0 8px 24px rgba(6,182,212,0.28);">
                <div>
                    <div class="qa-card-title">View Bookings</div>
                    <div class="qa-card-desc">Track all appointments in real-time</div>
                </div>
                <div class="qa-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
            </a>
        </div>

        {{-- ── Charts Row 1 ── --}}
        <div class="grid-2">

            {{-- Weekly bar chart --}}
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <div class="panel-title">Weekly Income Breakdown</div>
                        <div class="panel-sub">Current week performance</div>
                    </div>
                    <span class="panel-badge">Realtime</span>
                </div>
                <div class="panel-body">
                    <div class="bar-chart">
                        @foreach($weeklyBars as $bar)
                        <div class="bar-col">
                            <div class="bar-fill"
                                 style="height:{{ $bar['heightPct'] }}%;
                                        background:{{ $bar['active'] ? 'linear-gradient(180deg,#0da2e7,#0882ba)' : '#e2e8f0' }};
                                        box-shadow:{{ $bar['active'] ? '0 4px 14px rgba(13,162,231,0.30)' : 'none' }};"></div>
                            <span class="bar-day" style="color:{{ $bar['active'] ? '#0da2e7' : '#94a3b8' }};">{{ $bar['day'] }}</span>
                            <span class="bar-amt">₹{{ number_format($bar['amount'], 0) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Revenue vs Refunds --}}
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <div class="panel-title">Revenue vs Refunds</div>
                        <div class="panel-sub">Last 6 months comparison</div>
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <span style="display:flex;align-items:center;gap:5px;font-size:11px;font-weight:600;color:#0da2e7;">
                            <span style="width:8px;height:8px;border-radius:50%;background:#0da2e7;display:inline-block;"></span> Revenue
                        </span>
                        <span style="display:flex;align-items:center;gap:5px;font-size:11px;font-weight:600;color:#f43f5e;">
                            <span style="width:8px;height:8px;border-radius:50%;background:#f43f5e;display:inline-block;"></span> Refunds
                        </span>
                    </div>
                </div>
                <div class="panel-body">
                    <svg class="w-full" style="height:170px;" viewBox="0 0 420 180" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="revArea" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" style="stop-color:#0da2e7;stop-opacity:0.22;" />
                                <stop offset="100%" style="stop-color:#0da2e7;stop-opacity:0;" />
                            </linearGradient>
                        </defs>
                        @if($revChart['area'] !== '')
                            <path d="{{ $revChart['area'] }}" fill="url(#revArea)"></path>
                        @endif
                        @if($revChart['line'] !== '')
                            <path d="{{ $revChart['line'] }}" fill="none" stroke="#0da2e7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></path>
                        @endif
                        @if($refundChart['line'] !== '')
                            <path d="{{ $refundChart['line'] }}" fill="none" stroke="#f43f5e" stroke-width="2" stroke-dasharray="5 3" stroke-linecap="round" stroke-linejoin="round"></path>
                        @endif
                    </svg>
                    <div style="display:flex;justify-content:space-between;gap:8px;margin-top:6px;">
                        @foreach($months as $idx => $month)
                            <span style="font-size:10px;font-weight:700;color:#94a3b8;letter-spacing:.08em;text-transform:uppercase;">{{ $month->format('M') }}</span>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>

        {{-- ── Charts Row 2 ── --}}
        <div class="grid-2">

            {{-- Appointment & Test trends --}}
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <div class="panel-title">Appointment &amp; Test Trends</div>
                        <div class="panel-sub">Last 6 months</div>
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <span style="display:flex;align-items:center;gap:5px;font-size:11px;font-weight:600;color:#0da2e7;">
                            <span style="width:8px;height:8px;border-radius:50%;background:#0da2e7;display:inline-block;"></span> Appts
                        </span>
                        <span style="display:flex;align-items:center;gap:5px;font-size:11px;font-weight:600;color:#8b5cf6;">
                            <span style="width:8px;height:8px;border-radius:50%;background:#8b5cf6;display:inline-block;"></span> Tests
                        </span>
                    </div>
                </div>
                <div class="panel-body">
                    <svg class="w-full" style="height:170px;" viewBox="0 0 420 180" preserveAspectRatio="none">
                        @if($apptChart['line'] !== '')
                            <path d="{{ $apptChart['line'] }}" fill="none" stroke="#0da2e7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></path>
                        @endif
                        @if($testChart['line'] !== '')
                            <path d="{{ $testChart['line'] }}" fill="none" stroke="#8b5cf6" stroke-width="2" stroke-dasharray="5 3" stroke-linecap="round" stroke-linejoin="round"></path>
                        @endif
                    </svg>
                    <div style="display:flex;justify-content:space-between;gap:8px;margin-top:6px;">
                        @foreach($months as $idx => $month)
                            <span style="font-size:10px;font-weight:700;color:#94a3b8;letter-spacing:.08em;text-transform:uppercase;">{{ $month->format('M') }}</span>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Transaction Growth --}}
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <div class="panel-title">Transaction Growth Line</div>
                        <div class="panel-sub">Quarterly upward trend</div>
                    </div>
                </div>
                <div class="panel-body">
                    <svg class="w-full" style="height:170px;" viewBox="0 0 420 180" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="growthArea" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" style="stop-color:#10b981;stop-opacity:0.18;" />
                                <stop offset="100%" style="stop-color:#10b981;stop-opacity:0;" />
                            </linearGradient>
                        </defs>
                        @if($growthChart['area'] !== '')
                            <path d="{{ $growthChart['area'] }}" fill="url(#growthArea)"></path>
                        @endif
                        @if($growthChart['line'] !== '')
                            <path d="{{ $growthChart['line'] }}" fill="none" stroke="#10b981" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>
                        @endif
                    </svg>
                    <div style="display:flex;justify-content:space-between;gap:8px;margin-top:6px;">
                        @foreach($quarterlyLabels as $idx => $label)
                            <span style="font-size:10px;font-weight:700;color:#94a3b8;letter-spacing:.08em;text-transform:uppercase;">{{ $label }}</span>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>

        {{-- ── Bottom Row ── --}}
        <div class="grid-3" style="padding-bottom:16px;">

            {{-- Revenue by Service --}}
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <div class="panel-title">Revenue by Service</div>
                        <div class="panel-sub">Completed transactions split</div>
                    </div>
                </div>
                <div class="panel-body">
                    @foreach($serviceBreakdown as $service)
                    <div class="service-item">
                        <div class="service-top">
                            <span class="service-name">{{ $service['label'] }}</span>
                            <span class="service-amount">{{ $service['amount_formatted'] }}</span>
                        </div>
                        <div class="service-track">
                            <div class="service-fill" style="width:{{ $service['percent'] }}%;background:{{ $service['color'] }};"></div>
                        </div>
                        <div class="service-pct">{{ $service['percent'] }}%</div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Recent Transactions --}}
            <div class="panel" style="overflow:hidden;">
                <div class="panel-header">
                    <div>
                        <div class="panel-title">Recent Financial Transactions</div>
                        <div class="panel-sub">Last {{ $recentTransactions->count() }} of {{ number_format($totalTxCount) }} transactions</div>
                    </div>
                    <a href="{{ route('healthcare.transactions.index') }}" class="panel-link">View All →</a>
                </div>
                <div style="overflow-x:auto;">
                    <table class="tx-table">
                        <thead>
                            <tr>
                                <th>Txn ID</th>
                                <th>Service</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTransactions as $tx)
                            <tr>
                                <td><span class="tx-id">#{{ str_pad((string) $tx['id'], 5, '0', STR_PAD_LEFT) }}</span></td>
                                <td><span class="tx-service">{{ $tx['service_summary'] }}</span></td>
                                <td><span class="tx-amount">₹{{ $tx['amount'] }}</span></td>
                                <td><span class="status-pill" style="{{ $tx['badge'] }}">{{ $tx['status'] }}</span></td>
                                <td><span class="tx-date">{{ $tx['date'] }}</span></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" style="padding:32px;text-align:center;font-size:13px;color:#94a3b8;">No transactions found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>{{-- /page-body --}}

</div>{{-- /dash-wrap --}}

@endsection