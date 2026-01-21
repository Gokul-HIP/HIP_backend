{{-- resources/views/admin/organization_show.blade.php --}}
@extends('layouts.admin')

@section('title', $organization->name)
@section('breadcrumb', 'Dashboard / ORGANIZATION / ' . $organization->name)

@section('content')
<!-- External libs -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>

.hip-dropdown-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #fff;
  border: 1.6px solid #111827; /* darker border like screenshot */
  border-radius: 10px;
  padding: 8px 14px;
  min-width: 180px;
  height: 40px;
  font-size: 14px;
  font-weight: 500;
  color: #111827;
  cursor: pointer;
  position: relative;
  box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
}
.hip-dropdown-btn .chev { margin-left:auto; color:#6b7280; }

/* active / focus */
.hip-dropdown-btn:focus,
.hip-dropdown-btn:focus-visible,
.hip-dropdown-btn.hover-outline { 
  outline: none;
  box-shadow: 0 0 0 6px rgba(13,162,231,0.14);
  border-color: #0da2e7;
}

/* menu */
.hip-dropdown-menu {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  min-width: 220px;
  background: #fff;
  border-radius: 8px;
  border: 1px solid #e6e7eb;
  box-shadow: 0 10px 30px rgba(2,6,23,0.08);
  z-index: 1200;
  overflow: hidden;
  transform-origin: top left;
  transition: opacity .12s ease, transform .12s ease;
}
.hip-dropdown-menu.hidden { display:none; }
.hip-dropdown-item {
  padding: 10px 14px;
  font-size: 14px;
  color: #111827;
  cursor: pointer;
  background: #fff;
  border: none;
  width:100%;
  text-align:left;
}
.hip-dropdown-item:hover { background: #f3f4f6; }

.hip-select-outline { border-color: #0da2e7; box-shadow: 0 0 0 6px rgba(13,162,231,0.14); }

/* small variant */
.hip-dropdown-btn.sm { min-width: 140px; height:36px; padding:6px 10px; font-size:13px; }

/* Add New Entity button */
.hip-btn {
  background-color: #0da2e7 !important;
  color: white !important;
  border-radius: 8px;
  padding: 8px 14px;
  display: inline-flex;
  align-items:center;
  gap:8px;
  box-shadow: 0 6px 20px rgba(13,162,231,0.12);
  font-weight:600;
}
.hip-btn:hover { background-color:#0c92d0 !important; }

/* layout + small adjustments */
.table-row:hover { background:#f9fafb; }
.card { background:white; border-radius:10px; box-shadow: 0 6px 20px rgba(2,6,23,0.04); border:1px solid #f1f5f9; }
.kpi-card { padding:18px; }
.canvas-wrap { position:relative; height: 12rem; /* h-48 equivalent */ }

/* responsive tweaks */
@media (min-width: 1024px){
  .hip-dropdown-menu { min-width: 240px; }
}
</style>

<div class="space-y-6">

  <!-- HERO -->
  <div class="card overflow-hidden">
    <div class="relative h-48 md:h-52">
      {{-- ensure the image url is valid; using unsplash fallback --}}
      <img src="https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?auto=format&fit=crop&w=1600&q=80"
        alt="Banner"
        class="w-full h-full object-cover">
      <div class="absolute inset-0 bg-black/30"></div>

      <h1 class="absolute bottom-6 left-6 text-white text-3xl font-bold">
        {{ $organization->name }}
      </h1>

      <div class="absolute top-4 right-4 flex space-x-2">
        <button class="bg-white p-2 rounded-lg hover:bg-gray-100 shadow">
          <i class="fas fa-edit text-gray-700"></i>
        </button>
        <button class="bg-white p-2 rounded-lg hover:bg-gray-100 shadow">
          <i class="fas fa-download text-gray-700"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- Month selector -->
  <div class="flex items-center gap-4">
    <div class="relative">
      <button id="monthBtn" class="hip-dropdown-btn" aria-expanded="false" type="button">
        <span id="monthLabel">November</span>
        <i class="fas fa-chevron-down chev"></i>
      </button>

      <div id="monthMenu" class="hip-dropdown-menu hidden" role="menu" aria-labelledby="monthBtn">
        <button class="hip-dropdown-item" data-value="November">November</button>
        <button class="hip-dropdown-item" data-value="October">October</button>
        <button class="hip-dropdown-item" data-value="September">September</button>
      </div>
    </div>
  </div>

  <!-- Stats row -->
  <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
    <div class="card kpi-card shadow border">
      <div class="flex justify-between">
        <div>
          <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Active Members</div>
          <div class="text-3xl font-bold">275</div>
        </div>
        <div class="flex items-center text-green-600">
          <i class="fas fa-arrow-up mr-1 text-xs"></i>
          <span class="font-medium">+12</span>
        </div>
      </div>
    </div>

    <div class="card kpi-card shadow border">
      <div class="flex justify-between">
        <div>
          <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Successful Transactions</div>
          <div class="text-3xl font-bold">62</div>
        </div>
        <div class="flex items-center text-red-600">
          <i class="fas fa-arrow-down mr-1 text-xs"></i>
          <span class="font-medium">-5</span>
        </div>
      </div>
    </div>

    <div class="card kpi-card shadow border">
      <div class="flex justify-between">
        <div>
          <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Failed Trans.</div>
          <div class="text-3xl font-bold">12</div>
        </div>
        <div class="flex items-center text-red-600">
          <i class="fas fa-arrow-down mr-1 text-xs"></i>
          <span class="font-medium">-5</span>
        </div>
      </div>
    </div>

    <div class="card kpi-card shadow border">
      <div class="flex justify-between">
        <div>
          <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Total Revenue</div>
          <div class="text-3xl font-bold">1.5 crore</div>
        </div>
        <div class="flex items-center text-green-600">
          <i class="fas fa-arrow-up mr-1 text-xs"></i>
          {{-- <span class="font-medium">+12</span> --}}
        </div>
      </div>
    </div>

    <div class="bg-white p-4 rounded-lg shadow border">
        <div class="flex items-center justify-between">
            <p class="text-sm text-gray-600">Previous Day Turnover</p>
            <span class="text-sm text-green-600 font-medium">+9</span>
        </div>
        <span class="inline-block mt-1 px-2 py-0.5 bg-gray-200 text-gray-700 text-xs rounded">
             13/11/2025
        </span>
        <p class="text-2xl font-bold mt-2">Rs.15,890</p>
    </div>

  </div>

  <!-- Charts and feedback -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 ">

    <!-- Left (two charts stacked) -->
    <div class="lg:col-span-2 space-y-4">

      <!-- KPI indicators - has dropdown style 1 -->
      <div class="card p-6 shadow border">
        <div class="flex justify-between items-center mb-4">
          <div>
            <h3 class="font-semibold">KPI indicators</h3>
            <p class="text-sm text-gray-500">Daily Transaction Volume (Successful vs Failed)</p>
          </div>

          <div class="relative">
            <button id="kpiBtn" class="hip-dropdown-btn sm" type="button">
              <span id="kpiLabel">Last 6 months</span>
              <i class="fas fa-chevron-down chev"></i>
            </button>

            <div id="kpiMenu" class="hip-dropdown-menu hidden" role="menu" aria-labelledby="kpiBtn">
              <button class="hip-dropdown-item" data-value="Last 6 months">Last 6 months</button>
              <button class="hip-dropdown-item" data-value="Last 3 months">Last 3 months</button>
              <button class="hip-dropdown-item" data-value="Last month">Last month</button>
            </div>
          </div>
        </div>

        <div class="canvas-wrap">
          <canvas id="transactionChart"></canvas>
        </div>
      </div>

      <!-- Revenue vs refunds -->
      <div class="card p-6 shadow border">
        <div class="flex justify-between items-center mb-4">
          <div>
            <h3 class="font-semibold">Revenue Vs Refunds</h3>
          </div>

          <div class="relative">
            <button id="revBtn" class="hip-dropdown-btn sm" type="button">
              <span id="revLabel">Last 6 months</span>
              <i class="fas fa-chevron-down chev"></i>
            </button>

            <div id="revMenu" class="hip-dropdown-menu hidden" role="menu" aria-labelledby="revBtn">
              <button class="hip-dropdown-item" data-value="Last 6 months">Last 6 months</button>
              <button class="hip-dropdown-item" data-value="Last 3 months">Last 3 months</button>
              <button class="hip-dropdown-item" data-value="Last month">Last month</button>
            </div>
          </div>
        </div>

        <div class="canvas-wrap">
          <canvas id="revenueChart"></canvas>
        </div>
      </div>

    </div>

    <!-- Right: feedback + reviews -->
    <div class="space-y-4">
      <div class="card p-6 shadow border">
        <h3 class="font-semibold mb-4">Feedback Sentiment Distribution</h3>
        <div class="flex justify-center mb-4">
          <div class="relative w-48 h-48">
            <canvas id="feedbackChart"></canvas>
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
              <div class="text-center">
                <i class="far fa-comment text-gray-400 text-2xl"></i>
                <div class="text-xs text-gray-500 mt-1">Total Feedback</div>
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-3 gap-3 text-center">
          <div class="p-3 bg-green-100 rounded-lg">
            <div class="text-xs text-gray-700">Positive</div>
            <div class="text-2xl font-bold text-green-700">{{ $stats['feedback_positive'] }}%</div>
          </div>
          <div class="p-3 bg-yellow-100 rounded-lg">
            <div class="text-xs text-gray-700">Neutral</div>
            <div class="text-2xl font-bold text-yellow-700">{{ $stats['feedback_neutral'] }}%</div>
          </div>
          <div class="p-3 bg-red-100 rounded-lg">
            <div class="text-xs text-gray-700">Negative</div>
            <div class="text-2xl font-bold text-red-700">{{ $stats['feedback_negative'] }}%</div>
          </div>
        </div>
      </div>

      <div class="card p-6 shadow border">
        <h3 class="font-semibold mb-4">Recent Reviews</h3>

        {{-- Example reviews --}}
        <div class="divide-y">
          <div class="py-3">
            <div class="flex justify-between items-start mb-2">
              <div>
                <div class="font-semibold">Jane Doe</div>
                <div class="text-xs text-gray-500">Wellness Hospital</div>
              </div>
              <div class="text-yellow-400">
                <i class="fas fa-star text-xs"></i><i class="fas fa-star text-xs"></i><i class="fas fa-star text-xs"></i><i class="fas fa-star text-xs"></i><i class="fas fa-star text-xs"></i>
              </div>
            </div>
            <p class="text-sm text-gray-700 mb-1">"The facility was incredibly clean and the staff was very professional. A great experience overall."</p>
            <p class="text-xs text-gray-400">2 days ago</p>
          </div>

          <div class="py-3">
            <div class="flex justify-between items-start mb-2">
              <div>
                <div class="font-semibold">John Smith</div>
                <div class="text-xs text-gray-500">Wellness Hospital</div>
              </div>
              <div class="text-yellow-400">
                <i class="fas fa-star text-xs"></i><i class="fas fa-star text-xs"></i><i class="fas fa-star text-xs"></i><i class="fas fa-star text-xs"></i><i class="fas fa-star text-xs"></i>
              </div>
            </div>
            <p class="text-sm text-gray-700 mb-1">"Excellent service from start to finish. Highly recommend."</p>
            <p class="text-xs text-gray-400">5 days ago</p>
          </div>
        </div>

      </div>
    </div>

  </div>

  <!-- Entities table -->
  <div class="card p-6 shadow border">
    <div class="flex justify-between items-center mb-6">
      <div>
        <h3 class="font-semibold text-lg">{{ $organization->name }} Entities</h3>
        <p class="text-sm text-gray-500">Manage branches of {{ $organization->name }}</p>
      </div>

      <a href="{{ route('admin.organizations.create', $organization->id ?? 0) }}" class="hip-btn">
        <i class="fas fa-plus"></i> Add New Entity
      </a>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600">Name</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600">Status</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600">Entity</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600">Location</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600">Actions</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-gray-200">
          @foreach($organization->hospitals as $hospital)
          <tr class="table-row">
            <td class="px-6 py-4 text-sm">{{ $hospital->name }}</td>
            <td class="px-6 py-4">
              <span class="px-3 py-1 bg-green-100 text-green-700 text-xs rounded-full">Active</span>
            </td>
            <td class="px-6 py-4 text-sm">Hospital</td>
            <td class="px-6 py-4 text-sm">{{ $hospital->location }}</td>
            <td class="px-6 py-4">
              <button class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-ellipsis-v"></i>
              </button>
            </td>
          </tr>
          @endforeach

          @foreach($organization->pharmacies as $pharmacy)
          <tr class="table-row">
            <td class="px-6 py-4 text-sm">{{ $pharmacy->name }}</td>
            <td class="px-6 py-4">
              <span class="px-3 py-1 bg-green-100 text-green-700 text-xs rounded-full">Active</span>
            </td>
            <td class="px-6 py-4 text-sm">Pharmacy</td>
            <td class="px-6 py-4 text-sm">{{ $pharmacy->location }}</td>
            <td class="px-6 py-4">
              <button class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-ellipsis-v"></i>
              </button>
            </td>
          </tr>
          @endforeach

          @if($organization->hospitals->isEmpty() && $organization->pharmacies->isEmpty())
          <tr>
            <td colspan="5" class="text-center text-gray-500 py-8">No entities found.</td>
          </tr>
          @endif
        </tbody>
      </table>
    </div>
  </div>

</div>

@push('scripts')
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

function attachDropdown(btnId, menuId, labelId) {
  const btn = document.getElementById(btnId);
  const menu = document.getElementById(menuId);
  const label = document.getElementById(labelId);

  if (!btn || !menu || !label) return;

  // toggle
  btn.addEventListener('click', (e) => {
    e.stopPropagation();
    closeAllDropdownsExcept(menu);
    menu.classList.toggle('hidden');
    btn.setAttribute('aria-expanded', !menu.classList.contains('hidden'));
    if (!menu.classList.contains('hidden')) { 
      btn.classList.add('hover-outline'); 
      menu.querySelector('.hip-dropdown-item')?.focus(); 
    }
  });

  // menu item click
  menu.querySelectorAll('.hip-dropdown-item').forEach(item => {
    item.addEventListener('click', (ev) => {
      ev.stopPropagation();
      const val = item.getAttribute('data-value') || item.textContent.trim();
      label.textContent = val;
      menu.classList.add('hidden');
      btn.classList.remove('hover-outline');
      btn.setAttribute('aria-expanded','false');
    });
  });
}

// close others
function closeAllDropdownsExcept(exc) {
  document.querySelectorAll('.hip-dropdown-menu').forEach(m => {
    if (m !== exc) m.classList.add('hidden');
  });
  document.querySelectorAll('.hip-dropdown-btn').forEach(b => b.classList.remove('hover-outline'));
}

// global click close
document.addEventListener('click', () => {
  closeAllDropdownsExcept(null);
});

// init dropdowns
document.addEventListener('DOMContentLoaded', function(){
  attachDropdown('monthBtn','monthMenu','monthLabel');
  attachDropdown('kpiBtn','kpiMenu','kpiLabel');
  attachDropdown('revBtn','revMenu','revLabel');
});

/* -----------------------
   Charts (keeps aspect controlled by container)
------------------------*/
function createLineChart(ctxId, labels, datasets) {
  const ctx = document.getElementById(ctxId);
  if (!ctx) return;

  new Chart(ctx, {
    type: 'line',
    data: { labels, datasets },
    options: {
      responsive: true,
      maintainAspectRatio: false,

      /* 🔥 ENABLE HOVER + TOOLTIP */
      interaction: {
        mode: 'index',
        intersect: false
      },

      plugins: {
        legend: { display: false },

        /* 🔥 Tooltip visible now */
        tooltip: {
          enabled: true,
          mode: 'index',
          intersect: false,
        }
      },

      scales: {
        x: { grid: { display: false } },
        y: { beginAtZero: true }
      }
    }
  });
}

// KPI (transactions)
createLineChart('transactionChart',
  ['Jan','Feb','Mar','Apr','May','June','July'],
  [
    { label:'Successful', data:[450,520,480,600,550,620,580], borderColor:'#0EA5E9', backgroundColor:'rgba(14,165,233,0.12)', tension:.4, fill:true, pointRadius:0 },
    { label:'Failed', data:[50,30,45,60,40,35,42], borderColor:'#EF4444', backgroundColor:'rgba(239,68,68,0.12)', tension:.4, fill:true, pointRadius:0 }
  ]
);

// Revenue
createLineChart('revenueChart',
  ['Jan','Feb','Mar','Apr','May','June','July'],
  [
    { label:'Revenue', data:[300,450,800,650,550,600,500], borderColor:'#22C55E', backgroundColor:'rgba(34,197,94,0.15)', tension:.4, fill:true, pointRadius:0 },
    { label:'Refunds', data:[50,30,120,200,80,60,40], borderColor:'#FB923C', backgroundColor:'rgba(251,146,60,0.15)', tension:.4, fill:true, pointRadius:0 }
  ]
);

// Feedback donut
const fb = document.getElementById('feedbackChart');
if (fb) {
  new Chart(fb, {
    type:'doughnut',
    data:{
      labels:['Positive','Neutral','Negative'],
      datasets:[{
        data:[{{ $stats['feedback_positive'] }}, {{ $stats['feedback_neutral'] }}, {{ $stats['feedback_negative'] }}],
        backgroundColor:['#22C55E','#FBBF24','#EF4444'],
        borderWidth:0
      }]
    },
    options:{
      maintainAspectRatio:false,
      cutout:'70%',
      plugins:{ legend:{ display:false } }
    }
  });
}
</script>

@endpush
@endsection