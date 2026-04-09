<div class="min-h-screen bg-gray-50 p-6">

    {{-- Header --}}
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Content Overview - Dashboard</h1>
            <p class="text-sm text-gray-500 mt-1">Monitor content performance with real-time analytics and engagement insights</p>
        </div>

        {{-- Quick Actions --}}
        <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-md">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-semibold text-gray-700">Quick Actions</span>
                {{-- <span class="text-sm text-gray-400 cursor-pointer">&gt;</span> --}}
            </div>
            <div class="flex gap-3">
                <a href="{{ route('healthcare.content.create') }}" class="flex flex-col items-center justify-center bg-[#0DA2E7] hover:bg-[#0DA2E7]/80 text-white rounded-lg px-5 py-3 text-xs font-semibold 
                transition-colors gap-1.5 min-w-[90px] hover:shadow-lg transition-shadow duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="text-center leading-tight">Add New Content</span>
                </a>
                <a href="{{ route('healthcare.content.index') }}" class="flex flex-col items-center justify-center bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg px-5 py-3 text-xs font-semibold 
                transition-colors gap-1.5 min-w-[90px] hover:shadow-lg transition-shadow duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3-3-3z" />
                    </svg>
                    <span class="text-center leading-tight">Content &amp; Review</span>
                </a>
                <button class="flex flex-col items-center justify-center bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg px-5 py-3 text-xs font-semibold 
                transition-colors gap-1.5 min-w-[90px] hover:shadow-lg transition-shadow duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                    <span class="text-center leading-tight">View Spam Flag & Abuse</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Month Filter --}}
    <div class="mb-5">
        <select class="bg-white border border-gray-200 rounded-lg px-4 py-2 text-sm text-gray-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-300 cursor-pointer">
            <option>November</option>
            <option>October</option>
            <option>September</option>
            <option>August</option>
            <option>July</option>
        </select>
    </div>

    {{-- ===== STAT CARDS ROW 1 — 7 individual cards ===== --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-4 mb-4">

        {{-- Total Live Reviews --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-md p-4 flex flex-col gap-3 hover:shadow-lg transition-shadow duration-300">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide leading-tight">Total Live Reviews</span>
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gray-100 rounded-lg shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <span class="text-2xl font-bold text-gray-800">1200</span>
            </div>
        </div>

        {{-- Total Likes --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-md p-4 flex flex-col gap-3 hover:shadow-lg transition-shadow duration-300">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide leading-tight">Total Likes</span>
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gray-100 rounded-lg shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                </div>
                <span class="text-2xl font-bold text-gray-800">45,923</span>
            </div>
        </div>

        {{-- Total Comments --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-md p-4 flex flex-col gap-3 hover:shadow-lg transition-shadow duration-300">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide leading-tight">Total Comments</span>
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gray-100 rounded-lg shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3-3-3z" />
                    </svg>
                </div>
                <span class="text-2xl font-bold text-gray-800">1200</span>
            </div>
        </div>

        {{-- Total Pending Reviews --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-md p-4 flex flex-col gap-3 hover:shadow-lg transition-shadow duration-300">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide leading-tight">Total Pending Reviews</span>
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gray-100 rounded-lg shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="text-2xl font-bold text-gray-800">55</span>
            </div>
        </div>

        {{-- Flagged Items --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-md p-4 flex flex-col gap-3 hover:shadow-lg transition-shadow duration-300">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide leading-tight">Flagged Items</span>
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gray-100 rounded-lg shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                    </svg>
                </div>
                <span class="text-2xl font-bold text-gray-800">127</span>
            </div>
        </div>

        {{-- Average Doctor Rating --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-md p-4 flex flex-col gap-3 hover:shadow-lg transition-shadow duration-300">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide leading-tight">Average Doctor Rating</span>
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gray-100 rounded-lg shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                    </svg>
                </div>
                <span class="text-2xl font-bold text-gray-800">4.1/5</span>
            </div>
        </div>

        {{-- Average Hospital Rating --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-md p-4 flex flex-col gap-3 hover:shadow-lg transition-shadow duration-300">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide leading-tight">Average Hospital Rating</span>
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gray-100 rounded-lg shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <span class="text-2xl font-bold text-gray-800">4.2/5</span>
            </div>
        </div>

    </div>

    {{-- ===== STAT CARDS ROW 2 — 4 individual cards ===== --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">

        {{-- Total Views --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-md p-4 flex flex-col gap-3 hover:shadow-lg transition-shadow duration-300">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Total Views</span>
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gray-100 rounded-lg shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </div>
                <span class="text-2xl font-bold text-gray-800">3.2 M</span>
            </div>
        </div>

        {{-- Avg Watch Time --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-md p-4 flex flex-col gap-3 hover:shadow-lg transition-shadow duration-300">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Avg Watch Time</span>
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gray-100 rounded-lg shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="text-2xl font-bold text-gray-800">3mins 42s</span>
            </div>
        </div>

        {{-- Total Saves --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-md p-4 flex flex-col gap-3 hover:shadow-lg transition-shadow duration-300">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Total Saves</span>
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gray-100 rounded-lg shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                    </svg>
                </div>
                <span class="text-2xl font-bold text-gray-800">46K</span>
            </div>
        </div>

        {{-- Total Shares --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-md p-4 flex flex-col gap-3 hover:shadow-lg transition-shadow duration-300">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Total Shares</span>
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gray-100 rounded-lg shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                    </svg>
                </div>
                <span class="text-2xl font-bold text-gray-800">5K</span>
            </div>
        </div>

    </div>

    {{-- ===== Top & Low Performing Content Tables ===== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        {{-- Top Performing Content --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-md p-5 hover:shadow-lg transition-shadow duration-300">
            <div class="flex items-center gap-2 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
                <h2 class="text-sm font-semibold text-gray-700">Top Performing Content</h2>
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-gray-400 border-b border-gray-100">
                        <th class="text-left pb-2 font-medium">Content</th>
                        <th class="text-right pb-2 font-medium">Views</th>
                        <th class="text-right pb-2 font-medium">Engagement</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @php
                        $topContent = [
                            ['title' => 'Complete Guide to Prenatal Care', 'author' => 'Dr. Meera Nair',     'views' => '1.2M',   'engagement' => '12.4%'],
                            ['title' => 'Understanding Blood Pressure',     'author' => 'Apollo Hospitals',   'views' => '987.2K', 'engagement' => '10.8%'],
                            ['title' => 'Diabetes Diet Plan',               'author' => 'Dr. Anil Sharma',   'views' => '856.3K', 'engagement' => '9.6%'],
                            ['title' => 'Yoga for Back Pain Relief',        'author' => 'Dr. Priya Patel',   'views' => '723.1K', 'engagement' => '8.9%'],
                            ['title' => 'Child Nutrition Essentials',       'author' => 'Fortis Healthcare', 'views' => '654.2K', 'engagement' => '8.2%'],
                        ];
                    @endphp
                    @foreach($topContent as $item)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-2.5 pr-4">
                            <div class="font-medium text-gray-700 text-xs">{{ $item['title'] }}</div>
                            <div class="text-gray-400 text-xs">{{ $item['author'] }}</div>
                        </td>
                        <td class="py-2.5 text-right text-gray-600 text-xs font-medium whitespace-nowrap">{{ $item['views'] }}</td>
                        <td class="py-2.5 text-right whitespace-nowrap">
                            <span class="bg-green-100 text-green-700 text-xs font-semibold px-2 py-0.5 rounded-full">{{ $item['engagement'] }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Low Engagement Content --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-md p-5 hover:shadow-lg transition-shadow duration-300">
            <div class="flex items-center gap-2 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                </svg>
                <h2 class="text-sm font-semibold text-gray-700">Low Engagement Content</h2>
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-gray-400 border-b border-gray-100">
                        <th class="text-left pb-2 font-medium">Content</th>
                        <th class="text-right pb-2 font-medium">Views</th>
                        <th class="text-right pb-2 font-medium">Engagement</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @php
                        $lowContent = [
                            ['title' => 'Lab Test Reference Guide',   'author' => 'Dr. Anand Rao',    'views' => '320',  'engagement' => '0.4%'],
                            ['title' => 'Hospital Billing FAQ',       'author' => 'Max Healthcare',   'views' => '540',  'engagement' => '0.6%'],
                            ['title' => 'Medical Terminology Basics', 'author' => 'Dr. Kavita Jain',  'views' => '780',  'engagement' => '0.9%'],
                            ['title' => 'Insurance Claims Process',   'author' => 'Fortis Hospitals', 'views' => '920',  'engagement' => '1.1%'],
                            ['title' => 'Generic Drug Information',   'author' => 'Dr. Ramesh Gupta', 'views' => '1.1K', 'engagement' => '1.3%'],
                        ];
                    @endphp
                    @foreach($lowContent as $item)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-2.5 pr-4">
                            <div class="font-medium text-gray-700 text-xs">{{ $item['title'] }}</div>
                            <div class="text-gray-400 text-xs">{{ $item['author'] }}</div>
                        </td>
                        <td class="py-2.5 text-right text-gray-600 text-xs font-medium whitespace-nowrap">{{ $item['views'] }}</td>
                        <td class="py-2.5 text-right whitespace-nowrap">
                            <span class="bg-red-100 text-red-500 text-xs font-semibold px-2 py-0.5 rounded-full">{{ $item['engagement'] }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>

    {{-- ===== Monthly Views, Likes and Shares Chart ===== --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-md p-5 hover:shadow-lg transition-shadow duration-300">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">Monthly Views, Likes and Shares</h2>
        <div class="relative w-full" style="height: 220px;">
            <canvas id="monthlyChart"></canvas>
        </div>
        <div class="flex items-center gap-6 mt-4 justify-center">
            <div class="flex items-center gap-1.5">
                <div class="w-6 h-0.5 bg-blue-500 rounded"></div>
                <span class="text-xs text-gray-500">views</span>
            </div>
            <div class="flex items-center gap-1.5">
                <div class="w-6 h-0.5 bg-yellow-400 rounded"></div>
                <span class="text-xs text-gray-500">likes</span>
            </div>
            <div class="flex items-center gap-1.5">
                <div class="w-6 h-0.5 bg-green-400 rounded"></div>
                <span class="text-xs text-gray-500">shares</span>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('monthlyChart').getContext('2d');

        const labels = ['Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Jan'];
        const views  = [2900000, 2800000, 3000000, 3200000, 3500000, 3800000, 4500000];
        const likes  = [180000,  210000,  220000,  250000,  270000,  300000,  340000];
        const shares = [40000,   52000,   55000,   60000,   65000,   70000,   80000];

        new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Views',
                        data: views,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59,130,246,0.06)',
                        borderWidth: 2,
                        pointRadius: 4,
                        pointBackgroundColor: '#3b82f6',
                        tension: 0.4,
                        fill: false,
                    },
                    {
                        label: 'Likes',
                        data: likes,
                        borderColor: '#facc15',
                        backgroundColor: 'rgba(250,204,21,0.06)',
                        borderWidth: 2,
                        pointRadius: 4,
                        pointBackgroundColor: '#facc15',
                        tension: 0.4,
                        fill: false,
                    },
                    {
                        label: 'Shares',
                        data: shares,
                        borderColor: '#4ade80',
                        backgroundColor: 'rgba(74,222,128,0.06)',
                        borderWidth: 2,
                        pointRadius: 4,
                        pointBackgroundColor: '#4ade80',
                        tension: 0.4,
                        fill: false,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                let val = context.raw;
                                if (val >= 1000000) return context.dataset.label + ': ' + (val / 1000000).toFixed(1) + 'M';
                                if (val >= 1000)    return context.dataset.label + ': ' + (val / 1000).toFixed(0) + 'K';
                                return context.dataset.label + ': ' + val;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#9ca3af', font: { size: 11 } },
                    },
                    y: {
                        grid: { color: '#f3f4f6' },
                        ticks: {
                            color: '#9ca3af',
                            font: { size: 11 },
                            callback: function (val) {
                                if (val >= 1000000) return (val / 1000000).toFixed(1) + 'M';
                                if (val >= 1000)    return (val / 1000).toFixed(0) + 'K';
                                return val;
                            }
                        }
                    }
                }
            }
        });
    });
</script>
@endpush