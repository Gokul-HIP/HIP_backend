@extends('layouts.admin')

@section('title', 'Super Admin Dashboard')
@section('breadcrumb', 'Dashboard')

@section('content')

{{-- =================== HIP SELECT CSS =================== --}}
<style>
/* HIP PREMIUM DROPDOWN */
.hip-select-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #fff;
    border: 1.6px solid #111827;
    border-radius: 10px;
    padding: 6px 12px;
    min-width: 160px;
    height: 38px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    position: relative;
    color: #111827;
}
.hip-select-btn .chev { margin-left: auto; color: #6b7280; }

.hip-select-menu {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    width: 200px;
    box-shadow: 0 10px 22px rgba(0,0,0,0.09);
    z-index: 2000;
    overflow: hidden;
}
.hip-select-menu.hidden { display:none; }

.hip-select-item {
    padding: 10px 12px;
    cursor: pointer;
    background: white;
    font-size: 14px;
    color: #111827;
}
.hip-select-item:hover {
    background: #f3f4f6;
}

/* Action Menu Positioning */
.action-menu {
    position: fixed;
    width: 260px;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    box-shadow: 0 12px 30px rgba(0,0,0,0.15);
    z-index: 9999;
}

.action-btn {
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* .action-menu.open-down {
    top: 100%;
    margin-top: 0.5rem;
}

.action-menu.open-up {
    bottom: 100%;
    margin-bottom: 0.5rem;
} */

</style>

<div class="space-y-8">

    <!-- ================= OVERVIEW + TURNOVER ================= -->
    <div class="flex items-start justify-between gap-6">

        <!-- OVERVIEW -->
        <div class="flex-1">
            <h2 class="font-semibold mb-4">OVERVIEW</h2>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7 gap-4">
                <div class="bg-white p-4 rounded-lg shadow-md text-center">
                    <div class="text-3xl font-bold">1</div>
                    <div class="text-xs text-gray-600 mt-1">Active Organization</div>
                </div>

                <div class="bg-white p-4 rounded-lg shadow-md text-center">
                    <div class="text-3xl font-bold">2</div>
                    <div class="text-xs text-gray-600 mt-1">Active Hospitals</div>
                </div>

                <div class="bg-white p-4 rounded-lg shadow-md text-center">
                    <div class="text-3xl font-bold">3</div>
                    <div class="text-xs text-gray-600 mt-1">Active Diagnostic Centers</div>
                </div>

                <div class="bg-white p-4 rounded-lg shadow-md text-center">
                    <div class="text-3xl font-bold">4</div>
                    <div class="text-xs text-gray-600 mt-1">Active Pharmacies</div>
                </div>

                <div class="bg-white p-4 rounded-lg shadow-md text-center">
                    <div class="text-3xl font-bold">5</div>
                    <div class="text-xs text-gray-600 mt-1">Active Doctors</div>
                </div>

                <div class="bg-white p-4 rounded-lg shadow-md text-center">
                    <div class="text-3xl font-bold">6</div>
                    <div class="text-xs text-gray-600 mt-1">Active Members</div>
                </div>

                <div class="bg-white p-4 rounded-lg shadow-md text-center">
                    <div class="text-3xl font-bold">7</div>
                    <div class="text-xs text-gray-600 mt-1">Pending Refunds</div>
                </div>
            </div>
        </div>

        <!-- TURNOVER CARD -->
        <div class="w-64">
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-4 pb-2">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <div class="text-xs text-gray-500 uppercase mb-1">
                                Previous Month Turnover
                            </div>
                            <div class="text-3xl font-bold text-gray-900">
                                {{ number_format($stats['total_turnover'] ?? 2415) }}
                            </div>
                        </div>
                        <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                            <i class="fas fa-shopping-bag text-green-600 text-lg"></i>
                        </div>
                    </div>
                </div>

                <div class="px-4 pb-4 h-20">
                    <canvas id="turnoverChart"></canvas>
                </div>
            </div>
        </div>

    </div>

    <!-- ================= QUICK ACTIONS ================= -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h3 class="text font-semibold mb-4 text-gray-900">Quick Actions</h3>

        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.doctor-profile.index') }}"
                class="border border-gray-300 px-4 py-2 rounded shadow-sm text-sm bg-white hover:bg-gray-50 transition inline-flex items-center text-gray-900">
                    <i class="fas fa-plus mr-1 text-xs"></i> Add Doctor
            </a>

            {{-- <a href="{{ route('Add-hospital.index') }}"
                class="border border-gray-300 px-4 py-2 rounded shadow-sm text-sm bg-white hover:bg-gray-50 transition inline-flex items-center text-gray-900">
                    <i class="fas fa-plus mr-1 text-xs"></i> Add Hospital
            </a> --}}

            <a href="{{ route('admin.organizations.index') }}"
                class="border border-gray-300 px-4 py-2 rounded shadow-sm text-sm bg-white hover:bg-gray-50 transition inline-flex items-center text-gray-900">
                    <i class="fas fa-plus mr-1 text-xs"></i> Add Organization
            </a>

            {{-- <a href="{{ route('admin.organizations.procedure.index') }}"
                class="border border-gray-300 px-4 py-2 rounded shadow-sm text-sm bg-white hover:bg-gray-50 transition inline-flex items-center text-gray-900">
                    <i class="fas fa-plus mr-1 text-xs"></i> Add Procedure
            </a> --}}

            <button class="border border-gray-300 px-4 py-2 rounded shadow-sm text-sm bg-white hover:bg-gray-50 transition text-gray-900">
                <i class="fas fa-star mr-1 text-xs"></i> Manage Ads
            </button>
        </div>
    </div>

    <!-- ================= CHARTS ================= -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Income Per Week -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-semibold text-gray-900">Income per week</h3>

                <!-- HIP Select -->
                <div class="relative">
                    <button id="incomeBtn" class="hip-select-btn">
                        <span id="incomeLabel">Last 6 months</span>
                        <i class="fas fa-chevron-down chev"></i>
                    </button>

                    <div id="incomeMenu" class="hip-select-menu hidden">
                        <div class="hip-select-item" data-value="Last 6 months">Last 6 months</div>
                        <div class="hip-select-item" data-value="Last 3 months">Last 3 months</div>
                        <div class="hip-select-item" data-value="Last month">Last month</div>
                    </div>
                </div>
            </div>

            <div class="text-xs text-gray-500 font-semibold mb-2">JULY</div>
            <canvas id="incomeChart" height="150"></canvas>
        </div>

        <!-- Number of Transactions -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-semibold text-gray-900">Number of Transactions</h3>

                <!-- HIP Select -->
                <div class="relative">
                    <button id="transBtn" class="hip-select-btn">
                        <span id="transLabel">Last 6 months</span>
                        <i class="fas fa-chevron-down chev"></i>
                    </button>

                    <div id="transMenu" class="hip-select-menu hidden">
                        <div class="hip-select-item" data-value="Last 6 months">Last 6 months</div>
                        <div class="hip-select-item" data-value="Last 3 months">Last 3 months</div>
                        <div class="hip-select-item" data-value="Last month">Last month</div>
                    </div>
                </div>
            </div>

            <canvas id="transactionsChart" height="150"></canvas>
        </div>

    </div>

    <!-- ================= MANAGEMENT + DISCOUNT ================= -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Management -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-semibold">Registration & Management</h3>
                <i class="fas fa-external-link-alt text-gray-400"></i>
            </div>

             <div class="space-y-3">

                {{-- @foreach ($pendingRegistrations as $item)
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <div>
                        <div class="font-medium text-sm">{{ $item['title'] }}</div>
                        <div class="text-xs text-gray-500">{{ $item['subtitle'] }}</div>
                    </div>
                    <span class="px-3 py-1 bg-gray-300 text-xs rounded">Pending</span>
                </div>
                @endforeach --}}

                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <div>
                        <div class="font-medium text-sm">Hiii</div>
                        <div class="text-xs text-gray-500">hlo</div>
                    </div>
                    <span class="px-3 py-1 bg-gray-300 text-xs rounded">Pending</span>
                </div>

            </div>

        </div>

        <!-- Discount -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-semibold">Discount Approval</h3>
                <i class="fas fa-question-circle text-gray-400"></i>
            </div>

             <div class="space-y-3">

                {{-- @foreach ($discountApprovals as $item)
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <div>
                        <div class="font-medium text-sm">{{ $item['title'] }}</div>
                        <div class="text-xs text-gray-500">{{ $item['subtitle'] }}</div>
                    </div>
                    <span class="px-3 py-1 bg-gray-300 text-xs rounded">Pending</span>
                </div>
                @endforeach --}}

                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <div>
                        <div class="font-medium text-sm">Lorem ipsum dolor sit amet consectetur adipisicing elit. Impedit, natus.</div>
                        <div class="text-xs text-gray-500">Lorem ipsum dolor sit amet.</div>
                    </div>
                    <span class="px-3 py-1 bg-gray-300 text-xs rounded">Pending</span>
                </div>

            </div>

        </div>
    </div>

    <!-- ================= REVIEWS + NOTES ================= -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Reviews -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="font-semibold mb-4">Recent Reviews</h3>

              <div class="space-y-4">
{{-- 
                @foreach ($recentReviews as $review)    
                <div class="border-b pb-3">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <div class="font-medium">{{ $review['author'] }}</div>
                            <div class="text-xs text-gray-500">{{ $review['organization'] }}</div>
                            <div class="text-xs text-gray-400">{{ $review['content'] }}</div>
                        </div>

                        <div class="flex space-x-2">
                            <button class="px-3 py-1 bg-gray-200 text-xs rounded">Approve</button>
                            <button class="px-3 py-1 bg-gray-200 text-xs rounded">Reject</button>
                        </div>
                    </div>

                    <div class="flex text-yellow-400 text-sm">
                        @for ($i=0; $i < $review['rating']; $i++)
                            <i class="fas fa-star"></i>
                        @endfor
                    </div>

                </div>
                @endforeach --}}


                <div class="border-b pb-3">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <div class="font-medium">Hiii</div>
                            <div class="text-xs text-gray-500">hlo</div>
                            <div class="text-xs text-gray-400">hlooooo</div>
                        </div>

                        <div class="flex space-x-2">
                            <button class="px-3 py-1 bg-gray-200 text-xs rounded">Approve</button>
                            <button class="px-3 py-1 bg-gray-200 text-xs rounded">Reject</button>
                        </div>
                    </div>

                    {{-- <div class="flex text-yellow-400 text-sm">
                        @for ($i=0; $i < $review['rating']; $i++)
                            <i class="fas fa-star"></i>
                        @endfor
                    </div> --}}

                </div>

            </div>

        </div>

        <!-- Notes -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="font-semibold mb-3">Sticky Notes (Private)</h3>
            <textarea class="w-full h-40 border rounded p-2 text-sm"
                      placeholder="Add your private notes here..."></textarea>
        </div>

    </div>

</div>
@endsection


{{-- =================== SCRIPTS =================== --}}
@push('scripts')
<script>
    
/* ========= HIP DROPDOWN SCRIPT ========= */
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

// Turnover Mini Chart - Matching Reference Design
const turnoverCtx = document.getElementById('turnoverChart');
if (turnoverCtx) {
    // Fixed wave-like data pattern matching reference
    const chartData = [12, 15, 18, 16, 20, 22, 19, 24, 26, 23, 27, 25, 28, 30, 27, 25, 22, 24, 26, 28, 25, 23, 20, 22];
    
    new Chart(turnoverCtx, {
        type: 'line',
        data: {
            labels: Array(24).fill(''),
            datasets: [{
                label: 'Orders',
                data: chartData,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.2)',
                fill: true,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 5,
                pointHoverBackgroundColor: '#10b981',
                pointHoverBorderColor: '#ffffff',
                pointHoverBorderWidth: 2,
                borderWidth: 2.5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    enabled: true,
                    backgroundColor: '#ffffff',
                    titleColor: '#111827',
                    bodyColor: '#111827',
                    borderColor: '#e5e7eb',
                    borderWidth: 1,
                    cornerRadius: 6,
                    padding: 8,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y;
                        }
                    }
                }
            },
            scales: {
                x: {
                    display: false,
                    grid: { display: false }
                },
                y: {
                    display: false,
                    grid: { display: false },
                    beginAtZero: true
                }
            },
            elements: {
                point: {
                    radius: 0,
                    hoverRadius: 4
                }
            }
        },
        plugins: [{
            id: 'verticalLine',
            afterDraw: function(chart) {
                const tooltip = chart.tooltip;
                if (tooltip && tooltip.opacity > 0 && tooltip.caretX !== undefined) {
                    const ctx = chart.ctx;
                    const x = tooltip.caretX;
                    const yAxis = chart.scales.y;
                    
                    ctx.save();
                    ctx.strokeStyle = '#9ca3af';
                    ctx.lineWidth = 1;
                    ctx.setLineDash([5, 5]);
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

const incomeCtx = document.getElementById('incomeChart').getContext('2d');
new Chart(incomeCtx, {
    type: 'bar',
    data: {
        labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
        datasets: [{
            data: [15000, 12000, 18000, 22000],
            backgroundColor: '#f59e0b'
        }]
    },
    options: {
        scales: { y: { beginAtZero: true }},
        plugins: { legend: { display: false }}
    }
});

const transCtx = document.getElementById('transactionsChart').getContext('2d');
new Chart(transCtx, {
    type: 'bar',
    data: {
        labels: ['Jan','Feb','Mar','Apr','May','June','July'],
        datasets: [{
            data: [300,320,420,620,420,420,650],
            backgroundColor: '#3b82f6'
        }]
    },
    options: {
        scales: { y: { beginAtZero: true }},
        plugins: { legend: { display: false }}
    }
});
</script>
@endpush
