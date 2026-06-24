@extends('layouts.admin')

@section('title', 'Super Admin Dashboard')
@section('breadcrumb', 'Dashboard')

@section('content')

{{-- =================== DASHBOARD CSS =================== --}}
<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap');

:root {
    --dash-primary: #0da2e7;
    --dash-primary-light: #e0f5fd;
    --dash-primary-dark: #0882ba;
    --dash-success: #10b981;
    --dash-success-light: #d1fae5;
    --dash-warning: #f59e0b;
    --dash-warning-light: #fef3c7;
    --dash-danger: #ef4444;
    --dash-danger-light: #fee2e2;
    --dash-info: #0da2e7;
    --dash-info-light: #e0f5fd;
    --dash-surface: #ffffff;
    --dash-surface-2: #f8fafc;
    --dash-border: #e2e8f0;
    --dash-text-primary: #0f172a;
    --dash-text-secondary: #64748b;
    --dash-text-muted: #94a3b8;
    --dash-shadow-sm: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
    --dash-shadow-md: 0 4px 16px rgba(13,162,231,0.10), 0 2px 6px rgba(0,0,0,0.04);
    --dash-shadow-lg: 0 12px 32px rgba(0,0,0,0.1), 0 4px 12px rgba(0,0,0,0.06);
    --dash-radius: 16px;
    --dash-radius-sm: 10px;
    --dash-radius-xs: 6px;
}

.admin-dashboard {
    --primary: var(--dash-primary);
    --primary-light: var(--dash-primary-light);
    --primary-dark: var(--dash-primary-dark);
    --success: var(--dash-success);
    --success-light: var(--dash-success-light);
    --warning: var(--dash-warning);
    --warning-light: var(--dash-warning-light);
    --danger: var(--dash-danger);
    --danger-light: var(--dash-danger-light);
    --info: var(--dash-info);
    --info-light: var(--dash-info-light);
    --surface: var(--dash-surface);
    --surface-2: var(--dash-surface-2);
    --border: var(--dash-border);
    --text-primary: var(--dash-text-primary);
    --text-secondary: var(--dash-text-secondary);
    --text-muted: var(--dash-text-muted);
    --shadow-sm: var(--dash-shadow-sm);
    --shadow-md: var(--dash-shadow-md);
    --shadow-lg: var(--dash-shadow-lg);
    --radius: var(--dash-radius);
    --radius-sm: var(--dash-radius-sm);
    --radius-xs: var(--dash-radius-xs);
}

.admin-dashboard,
.admin-dashboard * {
    font-family: 'Plus Jakarta Sans', sans-serif;
    box-sizing: border-box;
}

/* ====== SECTION HEADER ====== */
.dash-section-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--text-muted);
    margin-bottom: 14px;
}

/* ====== KPI CARDS ====== */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
    gap: 12px;
}

.kpi-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 18px 16px;
    position: relative;
    overflow: hidden;
    transition: all 0.22s ease;
    cursor: default;
}

.kpi-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: var(--kpi-accent, var(--primary));
    opacity: 0;
    transition: opacity 0.22s ease;
}

.kpi-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
    border-color: transparent;
}

.kpi-card:hover::before { opacity: 1; }

.kpi-icon {
    width: 36px;
    height: 36px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
    font-size: 15px;
    background: var(--kpi-bg, var(--primary-light));
    color: var(--kpi-accent, var(--primary));
}

.kpi-value {
    font-size: 26px;
    font-weight: 800;
    color: var(--text-primary);
    line-height: 1;
    margin-bottom: 5px;
    font-variant-numeric: tabular-nums;
}

.kpi-label {
    font-size: 11.5px;
    font-weight: 500;
    color: var(--text-secondary);
    line-height: 1.3;
}

/* Accent variants */
.kpi-card.purple  { --kpi-accent: #0da2e7; --kpi-bg: #e0f5fd; }
.kpi-card.green   { --kpi-accent: #10b981; --kpi-bg: #d1fae5; }
.kpi-card.blue    { --kpi-accent: #0da2e7; --kpi-bg: #e0f5fd; }
.kpi-card.amber   { --kpi-accent: #f59e0b; --kpi-bg: #fef3c7; }
.kpi-card.rose    { --kpi-accent: #f43f5e; --kpi-bg: #ffe4e6; }
.kpi-card.cyan    { --kpi-accent: #06b6d4; --kpi-bg: #cffafe; }
.kpi-card.orange  { --kpi-accent: #f97316; --kpi-bg: #ffedd5; }

/* ====== TURNOVER CARD ====== */
.turnover-card {
    background: linear-gradient(145deg, #0f172a 0%, #1e293b 100%);
    border-radius: var(--radius-sm);
    padding: 20px;
    min-width: 220px;
    position: relative;
    overflow: hidden;
    flex-shrink: 0;
}

.turnover-card::after {
    content: '';
    position: absolute;
    top: -30px; right: -30px;
    width: 100px; height: 100px;
    background: rgba(13,162,231,0.18);
    border-radius: 50%;
}

.turnover-card .label {
    font-size: 10.5px;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.5);
    margin-bottom: 4px;
}

.turnover-card .amount {
    font-size: 30px;
    font-weight: 800;
    color: #ffffff;
    line-height: 1;
    margin-bottom: 2px;
    font-variant-numeric: tabular-nums;
}

.turnover-card .currency {
    font-size: 13px;
    font-weight: 500;
    color: rgba(255,255,255,0.45);
    margin-bottom: 14px;
}

.turnover-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: rgba(16,185,129,0.18);
    color: #34d399;
    border-radius: 20px;
    padding: 3px 9px;
    font-size: 11px;
    font-weight: 600;
    margin-bottom: 12px;
}

.chart-area {
    height: 64px;
    position: relative;
    margin: 0 -4px;
}

/* ====== QUICK ACTIONS ====== */
.quick-actions-wrap {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 20px 22px;
}

.qa-label {
    font-size: 13px;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 14px;
}

.qa-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    border: 1.5px solid var(--border);
    background: var(--surface);
    color: var(--text-primary);
    cursor: pointer;
    text-decoration: none;
    transition: all 0.18s ease;
}

.qa-btn:hover {
    background: #0da2e7;
    color: #fff;
    border-color: #0da2e7;
    box-shadow: 0 4px 12px rgba(13,162,231,0.32);
    transform: translateY(-1px);
}

.qa-btn i {
    font-size: 11px;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--primary-light);
    color: var(--primary);
    border-radius: 5px;
    transition: all 0.18s ease;
}

.qa-btn:hover i {
    background: rgba(255,255,255,0.22);
    color: #fff;
}

/* ====== CHART CARDS ====== */
.chart-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 22px;
    transition: box-shadow 0.22s ease;
}

.chart-card:hover {
    box-shadow: var(--shadow-md);
}

.chart-card-title {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--text-primary);
}

.chart-period-badge {
    font-size: 11px;
    font-weight: 500;
    color: var(--text-secondary);
    background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 3px 10px;
}

/* ====== HIP SELECT (preserved & improved) ====== */
.hip-select-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--surface-2);
    border: 1.5px solid var(--border);
    border-radius: 9px;
    padding: 6px 12px;
    min-width: 150px;
    height: 36px;
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
    position: relative;
    color: var(--text-primary);
    transition: border-color 0.18s;
    font-family: 'Plus Jakarta Sans', sans-serif;
}

.hip-select-btn:hover { border-color: #0da2e7; }
.hip-select-btn .chev { margin-left: auto; color: var(--text-muted); font-size: 10px; }

.hip-select-menu {
    position: absolute;
    top: calc(100% + 6px);
    right: 0;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 10px;
    min-width: 170px;
    box-shadow: var(--shadow-lg);
    z-index: 2000;
    overflow: hidden;
}

.hip-select-menu.hidden { display: none; }

.hip-select-item {
    padding: 10px 14px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    color: var(--text-primary);
    transition: background 0.12s;
}

.hip-select-item:hover { background: #e0f5fd; color: #0da2e7; }

/* ====== SECTION CARDS (Management / Reviews) ====== */
.section-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 22px;
    transition: box-shadow 0.22s;
}

.section-card:hover { box-shadow: var(--shadow-md); }

.section-card-title {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 16px;
}

/* Pending row */
.pending-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 14px;
    background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: 9px;
    margin-bottom: 8px;
    transition: border-color 0.18s;
}

.pending-row:hover { border-color: #0da2e7; }

.pending-title { font-size: 13px; font-weight: 600; color: var(--text-primary); }
.pending-sub   { font-size: 11.5px; color: var(--text-secondary); margin-top: 2px; }

.badge-pending {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #fef3c7;
    color: #92400e;
    border-radius: 20px;
    padding: 4px 11px;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
}

.badge-pending::before {
    content: '';
    width: 6px; height: 6px;
    border-radius: 50%;
    background: #f59e0b;
    display: inline-block;
}

/* Review row */
.review-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 0;
    border-bottom: 1px solid var(--border);
}

.review-row:last-child { border-bottom: none; padding-bottom: 0; }

.review-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0da2e7, #38bdf8);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 700;
    color: white;
    flex-shrink: 0;
}

.review-author { font-size: 13px; font-weight: 600; color: var(--text-primary); }
.review-org    { font-size: 11.5px; color: var(--text-secondary); margin-top: 2px; }
.review-content { font-size: 12px; color: var(--text-muted); margin-top: 4px; line-height: 1.4; }

.review-stars {
    display: flex;
    gap: 2px;
    margin-top: 6px;
    color: #f59e0b;
    font-size: 11px;
}

.btn-approve, .btn-reject {
    padding: 5px 12px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s;
    border: 1.5px solid;
    white-space: nowrap;
}

.btn-approve {
    background: var(--success-light);
    color: #065f46;
    border-color: #a7f3d0;
}

.btn-approve:hover { background: var(--success); color: #fff; border-color: var(--success); }

.btn-reject {
    background: #fee2e2;
    color: #991b1b;
    border-color: #fca5a5;
}

.btn-reject:hover { background: var(--danger); color: #fff; border-color: var(--danger); }

/* ====== ACTION MENU (preserved) ====== */
.action-menu {
    position: fixed;
    width: 260px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    box-shadow: var(--shadow-lg);
    z-index: 9999;
}

.action-btn {
    background: var(--surface);
    border: 1.5px solid var(--border);
    border-radius: 8px;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s;
}

.action-btn:hover {
    border-color: #0da2e7;
    background: #e0f5fd;
    color: #0da2e7;
}

/* ====== CHART SUBHEADING ====== */
.chart-subheading {
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--text-muted);
    margin-bottom: 12px;
}

/* ====== RESPONSIVE ====== */
@media (max-width: 768px) {
    .kpi-grid { grid-template-columns: repeat(2, 1fr); }
    .top-row { flex-direction: column; }
    .turnover-card { min-width: unset; width: 100%; }
}
</style>

<div class="admin-dashboard space-y-6">

    <!-- ================= OVERVIEW + TURNOVER ================= -->
    <div class="top-row" style="display:flex; align-items:flex-start; gap:16px; flex-wrap:wrap;">

        <!-- OVERVIEW KPI GRID -->
        <div style="flex:1; min-width:0;">
            <div class="dash-section-label">Platform Overview</div>

            <div class="kpi-grid">

                <div class="kpi-card purple">
                    <div class="kpi-icon"><i class="fas fa-building"></i></div>
                    <div class="kpi-value">{{ $organizations }}</div>
                    <div class="kpi-label">Active Organizations</div>
                </div>

                <div class="kpi-card green">
                    <div class="kpi-icon"><i class="fas fa-hospital"></i></div>
                    <div class="kpi-value">{{ $hospitals }}</div>
                    <div class="kpi-label">Active Hospitals</div>
                </div>

                <div class="kpi-card blue">
                    <div class="kpi-icon"><i class="fas fa-microscope"></i></div>
                    <div class="kpi-value">{{ $diagnosticCenters }}</div>
                    <div class="kpi-label">Diagnostic Centers</div>
                </div>

                <div class="kpi-card amber">
                    <div class="kpi-icon"><i class="fas fa-pills"></i></div>
                    <div class="kpi-value">{{ $pharmacies }}</div>
                    <div class="kpi-label">Active Pharmacies</div>
                </div>

                <div class="kpi-card cyan">
                    <div class="kpi-icon"><i class="fas fa-user-md"></i></div>
                    <div class="kpi-value">{{ $doctors }}</div>
                    <div class="kpi-label">Active Doctors</div>
                </div>

                <div class="kpi-card rose">
                    <div class="kpi-icon"><i class="fas fa-users"></i></div>
                    <div class="kpi-value">5</div>
                    <div class="kpi-label">Active Members</div>
                </div>

                <div class="kpi-card orange">
                    <div class="kpi-icon"><i class="fas fa-undo-alt"></i></div>
                    <div class="kpi-value">7</div>
                    <div class="kpi-label">Pending Refunds</div>
                </div>

            </div>
        </div>

        <!-- TURNOVER CARD -->
        <div style="flex-shrink:0; width:220px;">
            <div class="dash-section-label">Monthly Revenue</div>
            <div class="turnover-card">
                <div class="label">Previous Month Turnover</div>
                <div class="amount">{{ number_format($stats['total_turnover'] ?? 2415) }}</div>
                <div class="currency">INR Total</div>
                <div class="turnover-badge">
                    <i class="fas fa-arrow-up" style="font-size:9px;"></i> +12.4%
                </div>
                <div class="chart-area">
                    <canvas id="turnoverChart"></canvas>
                </div>
            </div>
        </div>

    </div>

    <!-- ================= QUICK ACTIONS ================= -->
    <div class="quick-actions-wrap">
        <div class="qa-label">Quick Actions</div>
        <div style="display:flex; flex-wrap:wrap; gap:10px;">

            <a href="{{ route('admin.organizations.doctor-profile.index') }}" class="qa-btn">
                <i class="fas fa-plus"></i> Add Doctor
            </a>

            {{-- <a href="{{ route('Add-hospital.index') }}" class="qa-btn">
                <i class="fas fa-plus"></i> Add Hospital
            </a> --}}

            <a href="{{ route('admin.organizations.index') }}" class="qa-btn">
                <i class="fas fa-plus"></i> Add Organization
            </a>

            {{-- <a href="{{ route('admin.organizations.procedure.index') }}" class="qa-btn">
                <i class="fas fa-plus"></i> Add Procedure
            </a> --}}

            <button class="qa-btn">
                <i class="fas fa-star"></i> Manage Ads
            </button>

        </div>
    </div>

    <!-- ================= CHARTS ================= -->
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;" class="charts-grid">

        <!-- Income Per Week -->
        <div class="chart-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:8px;">
                <div>
                    <div class="chart-card-title">Income per Week</div>
                    <div class="chart-subheading" style="margin-bottom:0; margin-top:2px;">JULY</div>
                </div>

                <!-- HIP Select (preserved) -->
                <div style="position:relative;">
                    <button id="incomeBtn" class="hip-select-btn">
                        <i class="fas fa-calendar-alt" style="color:#0da2e7; font-size:11px;"></i>
                        <span id="incomeLabel">{{ $rangeLabel ?? 'Last 6 months' }}</span>
                        <i class="fas fa-chevron-down chev"></i>
                    </button>

                    <div id="incomeMenu" class="hip-select-menu hidden">
                        <div class="hip-select-item" data-value="Last 6 months" data-range="6m">Last 6 months</div>
                        <div class="hip-select-item" data-value="Last 3 months" data-range="3m">Last 3 months</div>
                        <div class="hip-select-item" data-value="Last month" data-range="1m">Last month</div>
                    </div>
                </div>
            </div>

            <canvas id="incomeChart" height="150"></canvas>
        </div>

        <!-- Number of Transactions -->
        <div class="chart-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:8px;">
                <div class="chart-card-title">Number of Transactions</div>

                <!-- HIP Select (preserved) -->
                <div style="position:relative;">
                    <button id="transBtn" class="hip-select-btn">
                        <i class="fas fa-calendar-alt" style="color:#0da2e7; font-size:11px;"></i>
                        <span id="transLabel">{{ $rangeLabel ?? 'Last 6 months' }}</span>
                        <i class="fas fa-chevron-down chev"></i>
                    </button>

                    <div id="transMenu" class="hip-select-menu hidden">
                        <div class="hip-select-item" data-value="Last 6 months" data-range="6m">Last 6 months</div>
                        <div class="hip-select-item" data-value="Last 3 months" data-range="3m">Last 3 months</div>
                        <div class="hip-select-item" data-value="Last month" data-range="1m">Last month</div>
                    </div>
                </div>
            </div>

            <canvas id="transactionsChart" height="150"></canvas>
        </div>

    </div>

    <!-- ================= MANAGEMENT + REVIEWS ================= -->
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;" class="bottom-grid">

        <!-- Management -->
        <div class="section-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <div class="section-card-title" style="margin-bottom:0;">Registration & Management</div>
                <i class="fas fa-external-link-alt" style="color:var(--text-muted); font-size:13px; cursor:pointer;"></i>
            </div>

            <div>

                {{-- @foreach ($pendingRegistrations as $item)
                <div class="pending-row">
                    <div>
                        <div class="pending-title">{{ $item['title'] }}</div>
                        <div class="pending-sub">{{ $item['subtitle'] }}</div>
                    </div>
                    <span class="badge-pending">Pending</span>
                </div>
                @endforeach --}}

                <div class="pending-row">
                    <div>
                        <div class="pending-title">Hiii</div>
                        <div class="pending-sub">hlo</div>
                    </div>
                    <span class="badge-pending">Pending</span>
                </div>

            </div>
        </div>

        <!-- Recent Reviews -->
        <div class="section-card">
            <div class="section-card-title">Recent Reviews</div>

            <div>

                {{-- @foreach ($recentReviews as $review)
                <div class="review-row">
                    <div class="review-avatar">{{ strtoupper(substr($review['author'], 0, 1)) }}</div>
                    <div style="flex:1;">
                        <div class="review-author">{{ $review['author'] }}</div>
                        <div class="review-org">{{ $review['organization'] }}</div>
                        <div class="review-content">{{ $review['content'] }}</div>
                        <div class="review-stars">
                            @for ($i=0; $i < $review['rating']; $i++)
                                <i class="fas fa-star"></i>
                            @endfor
                        </div>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:6px;">
                        <button class="btn-approve">Approve</button>
                        <button class="btn-reject">Reject</button>
                    </div>
                </div>
                @endforeach --}}

                <div class="review-row">
                    <div class="review-avatar">H</div>
                    <div style="flex:1;">
                        <div class="review-author">Hiii</div>
                        <div class="review-org">hlo</div>
                        <div class="review-content">hlooooo</div>
                        <div class="review-stars">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="far fa-star"></i>
                        </div>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:6px; flex-shrink:0;">
                        <button class="btn-approve">Approve</button>
                        <button class="btn-reject">Reject</button>
                    </div>
                </div>

            </div>
        </div>

        <!-- Discount Approval (preserved commented) -->
        {{-- <div class="section-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <div class="section-card-title" style="margin-bottom:0;">Discount Approval</div>
                <i class="fas fa-question-circle" style="color:var(--text-muted);"></i>
            </div>

            <div>
                @foreach ($discountApprovals as $item)
                <div class="pending-row">
                    <div>
                        <div class="pending-title">{{ $item['title'] }}</div>
                        <div class="pending-sub">{{ $item['subtitle'] }}</div>
                    </div>
                    <span class="badge-pending">Pending</span>
                </div>
                @endforeach

                <div class="pending-row">
                    <div>
                        <div class="pending-title">Lorem ipsum dolor sit amet consectetur adipisicing elit.</div>
                        <div class="pending-sub">Lorem ipsum dolor sit amet.</div>
                    </div>
                    <span class="badge-pending">Pending</span>
                </div>
            </div>
        </div> --}}

    </div>

    <!-- ================= REVIEWS + NOTES (preserved commented) ================= -->
    {{-- <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">

        <div class="section-card">
            <div class="section-card-title">Recent Reviews</div>
            <div>
                @foreach ($recentReviews as $review)
                <div class="review-row">
                    <div class="review-avatar">{{ strtoupper(substr($review['author'], 0, 1)) }}</div>
                    <div style="flex:1;">
                        <div class="review-author">{{ $review['author'] }}</div>
                        <div class="review-org">{{ $review['organization'] }}</div>
                        <div class="review-content">{{ $review['content'] }}</div>
                        <div class="review-stars">
                            @for ($i=0; $i < $review['rating']; $i++)
                                <i class="fas fa-star"></i>
                            @endfor
                        </div>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:6px; flex-shrink:0;">
                        <button class="btn-approve">Approve</button>
                        <button class="btn-reject">Reject</button>
                    </div>
                </div>
                @endforeach

                <div class="review-row">
                    <div class="review-avatar">H</div>
                    <div style="flex:1;">
                        <div class="review-author">Hiii</div>
                        <div class="review-org">hlo</div>
                        <div class="review-content">hlooooo</div>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:6px; flex-shrink:0;">
                        <button class="btn-approve">Approve</button>
                        <button class="btn-reject">Reject</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-card">
            <div class="section-card-title">Sticky Notes (Private)</div>
            <textarea style="width:100%; height:160px; border:1.5px solid var(--border); border-radius:9px; padding:12px; font-size:13px; color:var(--text-primary); resize:none; outline:none; font-family:inherit;"
                      placeholder="Add your private notes here..."></textarea>
        </div>

    </div> --}}

</div>

<style>
@media (max-width: 900px) {
    .charts-grid, .bottom-grid { grid-template-columns: 1fr !important; }
}
</style>

@endsection


{{-- =================== SCRIPTS =================== --}}
@push('scripts')
<script>

/* ========= HIP DROPDOWN SCRIPT (preserved) ========= */
function hipDropdown(btnId, menuId, labelId) {
    const btn = document.getElementById(btnId);
    const menu = document.getElementById(menuId);
    const label = document.getElementById(labelId);

    if(!btn || !menu || !label) return;

    btn.addEventListener("click", e => {
        e.stopPropagation();
        document.querySelectorAll(".hip-select-menu").forEach(m => {
            if (m !== menu) m.classList.add("hidden");
        });
        menu.classList.toggle("hidden");
    });

    menu.querySelectorAll(".hip-select-item").forEach(item => {
        item.addEventListener("click", () => {
            label.textContent = item.dataset.value;
            menu.classList.add("hidden");
            const range = item.dataset.range;
            if (range) {
                const url = new URL(window.location.href);
                url.searchParams.set('range', range);
                window.location.href = url.toString();
            }
        });
    });
}

document.addEventListener("click", () => {
    document.querySelectorAll(".hip-select-menu").forEach(m => m.classList.add("hidden"));
});

document.addEventListener("DOMContentLoaded", function() {
    hipDropdown("incomeBtn", "incomeMenu", "incomeLabel");
    hipDropdown("transBtn", "transMenu", "transLabel");
});

/* ========= CHARTS ========= */

// Turnover Mini Chart
const turnoverCtx = document.getElementById('turnoverChart');
if (turnoverCtx) {
    const chartData = [12, 15, 18, 16, 20, 22, 19, 24, 26, 23, 27, 25, 28, 30, 27, 25, 22, 24, 26, 28, 25, 23, 20, 22];

    new Chart(turnoverCtx, {
        type: 'line',
        data: {
            labels: Array(24).fill(''),
            datasets: [{
                label: 'Orders',
                data: chartData,
                borderColor: '#34d399',
                backgroundColor: 'rgba(52,211,153,0.18)',
                fill: true,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 5,
                pointHoverBackgroundColor: '#34d399',
                pointHoverBorderColor: '#ffffff',
                pointHoverBorderWidth: 2,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    enabled: true,
                    backgroundColor: '#ffffff',
                    titleColor: '#0f172a',
                    bodyColor: '#0f172a',
                    borderColor: '#e2e8f0',
                    borderWidth: 1,
                    cornerRadius: 6,
                    padding: 8,
                    displayColors: false,
                    callbacks: { label: ctx => ctx.parsed.y }
                }
            },
            scales: {
                x: { display: false, grid: { display: false } },
                y: { display: false, grid: { display: false }, beginAtZero: true }
            }
        },
        plugins: [{
            id: 'verticalLine',
            afterDraw(chart) {
                const tooltip = chart.tooltip;
                if (tooltip && tooltip.opacity > 0 && tooltip.caretX !== undefined) {
                    const ctx = chart.ctx;
                    const x = tooltip.caretX;
                    const yAxis = chart.scales.y;
                    ctx.save();
                    ctx.strokeStyle = 'rgba(255,255,255,0.3)';
                    ctx.lineWidth = 1;
                    ctx.setLineDash([4, 4]);
                    ctx.beginPath();
                    ctx.moveTo(x, yAxis.top);
                    ctx.lineTo(x, yAxis.bottom);
                    ctx.stroke();
                    ctx.restore();
                }
            }
        }]
    });
}

@php
    $incomeLabelsData = $labels ?? ['W1', 'W2', 'W3', 'W4'];
    $incomeValuesData = $incomeChartData ?? [0, 0, 0, 0];
    $transValuesData = $transactionChartData ?? [0, 0, 0, 0];
@endphp
const incomeLabels = @json($incomeLabelsData);
const incomeValues = @json($incomeValuesData);
const transValues  = @json($transValuesData);

// Income Chart
const incomeCanvas = document.getElementById('incomeChart');
if (incomeCanvas) {
    const incomeCtx = incomeCanvas.getContext('2d');
    const incomeGrad = incomeCtx.createLinearGradient(0, 0, 0, 200);
    incomeGrad.addColorStop(0, 'rgba(245,158,11,0.35)');
    incomeGrad.addColorStop(1, 'rgba(245,158,11,0.02)');

    new Chart(incomeCtx, {
        type: 'bar',
        data: {
            labels: incomeLabels,
            datasets: [{
                data: incomeValues,
                backgroundColor: '#f59e0b',
                borderRadius: 6,
                borderSkipped: false,
                hoverBackgroundColor: '#d97706'
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9', drawBorder: false },
                    ticks: { color: '#94a3b8', font: { size: 11, family: 'Plus Jakarta Sans' } }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#94a3b8', font: { size: 11, family: 'Plus Jakarta Sans' } }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    titleColor: '#fff',
                    bodyColor: '#94a3b8',
                    cornerRadius: 8,
                    padding: 10
                }
            }
        }
    });
}

// Transactions Chart
const transCanvas = document.getElementById('transactionsChart');
if (transCanvas) {
    const transCtx = transCanvas.getContext('2d');

    new Chart(transCtx, {
        type: 'bar',
        data: {
            labels: incomeLabels,
            datasets: [{
                data: transValues,
                backgroundColor: '#0da2e7',
                borderRadius: 6,
                borderSkipped: false,
                hoverBackgroundColor: '#0882ba'
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9', drawBorder: false },
                    ticks: { color: '#94a3b8', font: { size: 11, family: 'Plus Jakarta Sans' } }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#94a3b8', font: { size: 11, family: 'Plus Jakarta Sans' } }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    titleColor: '#fff',
                    bodyColor: '#94a3b8',
                    cornerRadius: 8,
                    padding: 10
                }
            }
        }
    });
}
</script>
@endpush