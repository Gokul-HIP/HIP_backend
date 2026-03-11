<div class="p-6 space-y-6 bg-slate-100 min-h-screen">

    {{-- ── Page Header ── --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-900">Received Referrals</h2>
            <p class="text-slate-500 mt-1 text-sm max-w-sm">View and manage all patient referrals received from other specialists and partner hospitals in your network.</p>
        </div>
        <button type="button" class="inline-flex items-center gap-2 bg-[#0DA2E7] hover:bg-[#0b8ecf] text-white px-5 py-2.5 rounded-full font-semibold text-sm transition-all shadow-md flex-shrink-0">
            <span class="text-lg font-light leading-none">+</span>
            Add New Referral
        </button>
    </div>

    {{-- ── Stat Cards ── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- Pending --}}
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-slate-500">Pending</span>
                <div class="w-8 h-8 rounded-lg bg-orange-50 border border-orange-100 text-orange-500 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-black text-slate-900 mb-2">08</p>
            <p class="text-xs font-bold text-orange-500 flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
                +1.8% vs last month
            </p>
        </div>

        {{-- Accepted --}}
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-slate-500">Accepted</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 border border-blue-100 text-[#0DA2E7] flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-black text-slate-900 mb-2">34</p>
            <p class="text-xs font-bold text-[#0DA2E7] flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
                +6.3% vs last month
            </p>
        </div>

        {{-- Completed --}}
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-slate-500">Completed</span>
                <div class="w-8 h-8 rounded-lg bg-green-50 border border-green-100 text-green-600 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-black text-slate-900 mb-2">112</p>
            <p class="text-xs font-bold text-green-600 flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
                +9.4% vs last month
            </p>
        </div>

        {{-- Rejected --}}
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-slate-500">Rejected</span>
                <div class="w-8 h-8 rounded-lg bg-red-50 border border-red-100 text-red-500 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-black text-slate-900 mb-2">03</p>
            <p class="text-xs font-bold text-red-500 flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                -0.5% vs last month
            </p>
        </div>

    </div>

    {{-- ── Referral Table ── --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

        {{-- Table Toolbar --}}
        <div class="px-6 py-4 flex items-center justify-between border-b border-slate-100">
            <h3 class="font-bold text-base text-slate-900">Referral List</h3>
            <div class="flex gap-2">
                <button class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                    </svg>
                    Filter
                </button>
                <button class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M12 12V4m0 8l-3-3m3 3l3-3"/>
                    </svg>
                    Export
                </button>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-slate-100">
                        <th class="px-6 py-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">Member Details</th>
                        <th class="px-6 py-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">Mobile Number</th>
                        <th class="px-6 py-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">Referral Date</th>
                        <th class="px-6 py-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">Referred By</th>
                        <th class="px-6 py-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">Hospital</th>
                        <th class="px-6 py-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">

                    {{-- Pending --}}
                    <tr class="hover:bg-slate-50/60 transition-colors">
                        <td class="px-6 py-4">
                            <p class="font-bold text-sm text-slate-900">Michael Adams</p>
                            <p class="text-xs text-slate-400 mt-0.5">ID: #3341</p>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">+1 555-0211</td>
                        <td class="px-6 py-4 text-sm text-slate-600">Oct 25, 2023</td>
                        <td class="px-6 py-4">
                            <p class="font-bold text-sm text-slate-900">Dr. James Lee</p>
                            <p class="text-xs text-slate-400 mt-0.5">ID: #7710</p>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">Metro Health Clinic</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold border border-orange-300 bg-orange-50 text-orange-600">Pending</span>
                        </td>
                        <td class="px-4 py-4">
                            <button class="text-slate-400 hover:text-slate-600 p-1 rounded transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>
                            </button>
                        </td>
                    </tr>

                    {{-- Accepted --}}
                    <tr class="hover:bg-slate-50/60 transition-colors">
                        <td class="px-6 py-4">
                            <p class="font-bold text-sm text-slate-900">Priya Nair</p>
                            <p class="text-xs text-slate-400 mt-0.5">ID: #4482</p>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">+1 555-0377</td>
                        <td class="px-6 py-4 text-sm text-slate-600">Oct 21, 2023</td>
                        <td class="px-6 py-4">
                            <p class="font-bold text-sm text-slate-900">Dr. Arun Mehta</p>
                            <p class="text-xs text-slate-400 mt-0.5">ID: #3302</p>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">Sunrise Hospital</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold border border-[#0DA2E7]/40 bg-blue-50 text-[#0DA2E7]">Accepted</span>
                        </td>
                        <td class="px-4 py-4">
                            <button class="text-slate-400 hover:text-slate-600 p-1 rounded transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>
                            </button>
                        </td>
                    </tr>

                    {{-- Completed --}}
                    <tr class="hover:bg-slate-50/60 transition-colors">
                        <td class="px-6 py-4">
                            <p class="font-bold text-sm text-slate-900">David Wilson</p>
                            <p class="text-xs text-slate-400 mt-0.5">ID: #6614</p>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">+1 555-0455</td>
                        <td class="px-6 py-4 text-sm text-slate-600">Oct 14, 2023</td>
                        <td class="px-6 py-4">
                            <p class="font-bold text-sm text-slate-900">Dr. Susan Park</p>
                            <p class="text-xs text-slate-400 mt-0.5">ID: #9981</p>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">City General Hospital</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold border border-green-300 bg-green-50 text-green-600">Completed</span>
                        </td>
                        <td class="px-4 py-4">
                            <button class="text-slate-400 hover:text-slate-600 p-1 rounded transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>
                            </button>
                        </td>
                    </tr>

                    {{-- Rejected --}}
                    <tr class="hover:bg-slate-50/60 transition-colors">
                        <td class="px-6 py-4">
                            <p class="font-bold text-sm text-slate-900">Sarah Connor</p>
                            <p class="text-xs text-slate-400 mt-0.5">ID: #8873</p>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">+1 555-0599</td>
                        <td class="px-6 py-4 text-sm text-slate-600">Oct 09, 2023</td>
                        <td class="px-6 py-4">
                            <p class="font-bold text-sm text-slate-900">Dr. Tom Clarke</p>
                            <p class="text-xs text-slate-400 mt-0.5">ID: #1145</p>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">Lakeside Medical</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold border border-rose-300 bg-rose-50 text-rose-600">Rejected</span>
                        </td>
                        <td class="px-4 py-4">
                            <button class="text-slate-400 hover:text-slate-600 p-1 rounded transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>
                            </button>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between flex-wrap gap-3">
            <span class="text-sm text-slate-500">Showing 1 to 4 of 157 entries</span>
            <div class="flex gap-2">
                <button disabled class="p-2 rounded-lg border border-slate-200 text-slate-300 cursor-not-allowed">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>

    </div>

</div>