{{-- livewire/admin/payment/payment-report.blade.php --}}
<div class="space-y-8">

    <style>
        .hip-pay-menu {
            position: fixed;
            z-index: 9999;
            width: 11rem;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
            overflow: hidden;
        }
        .hip-pay-item {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.65rem 1rem;
            font-size: 0.875rem;
            color: #475569;
            text-decoration: none;
            background: transparent;
            border-bottom: 1px solid #f8fafc;
            transition: background .1s;
            cursor: pointer;
            width: 100%; text-align: left; border-left: none; border-right: none; border-top: none;
        }
        .hip-pay-item:last-child { border-bottom: none; }
        .hip-pay-item:hover { background: #f8fafc; }
        .hip-pay-btn { background: transparent; border: none; cursor: pointer; }
    </style>

    {{-- ── Page Header ── --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Payment Report</h1>
            <p class="text-sm text-slate-500 mt-0.5">View and track all payments across hospital services</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <button type="button" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg bg-white border border-slate-200 shadow-sm text-slate-700 hover:bg-slate-50 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Jan 01, 2024 – Jan 31, 2024
            </button>
            <button type="button" class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-white border border-slate-200 shadow-sm text-slate-500 hover:bg-slate-50 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
            </button>
            <button type="button" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg text-white shadow-sm transition-all hover:opacity-90" style="background:#1a73e8;">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Download Report
            </button>
        </div>
    </div>

    {{-- ── Stat Cards ── --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#eff6ff;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" style="color:#2563eb;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-xs font-bold" style="background:#f0fdf4; color:#16a34a;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    +12%
                </span>
            </div>
            <p class="text-sm font-medium text-slate-500">Total Payments Received</p>
            <h3 class="text-2xl font-bold text-slate-900 mt-1 tracking-tight">₹45,200.00</h3>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#eef2ff;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" style="color:#4f46e5;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                </div>
                <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-xs font-bold" style="background:#f0fdf4; color:#16a34a;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    +5%
                </span>
            </div>
            <p class="text-sm font-medium text-slate-500">Total Transactions</p>
            <h3 class="text-2xl font-bold text-slate-900 mt-1 tracking-tight">1,240</h3>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#f0fdf4;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" style="color:#16a34a;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-xs font-bold" style="background:#f0fdf4; color:#16a34a;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    +8%
                </span>
            </div>
            <p class="text-sm font-medium text-slate-500">Successful Payments</p>
            <h3 class="text-2xl font-bold text-slate-900 mt-1 tracking-tight">1,180</h3>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#fffbeb;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" style="color:#d97706;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-xs font-bold" style="background:#fff1f2; color:#e11d48;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                    -2%
                </span>
            </div>
            <p class="text-sm font-medium text-slate-500">Pending Payments</p>
            <h3 class="text-2xl font-bold text-slate-900 mt-1 tracking-tight">60</h3>
        </div>
    </div>

    {{-- ── Filters ── --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 items-end">
            <div class="lg:col-span-2">
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Search Member</label>
                <div class="relative">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35"/>
                    </svg>
                    <input type="text" placeholder="Member Name / ID..."
                        class="w-full pl-9 pr-4 py-2 text-sm rounded-lg outline-none transition-all"
                        style="background:#f8fafc; border:1px solid #f1f5f9;"
                        onfocus="this.style.background='#fff'; this.style.borderColor='#bae6fd'; this.style.boxShadow='0 0 0 2px rgba(14,165,233,0.15)';"
                        onblur="this.style.background='#f8fafc'; this.style.borderColor='#f1f5f9'; this.style.boxShadow='none';">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Service Type</label>
                <select class="w-full px-3 py-2 text-sm rounded-lg outline-none appearance-none" style="background:#f8fafc; border:1px solid #f1f5f9;">
                    <option>All Services</option>
                    <option>Consultation</option>
                    <option>Procedure</option>
                    <option>Diagnostics</option>
                    <option>Pharmacy</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Status</label>
                <select class="w-full px-3 py-2 text-sm rounded-lg outline-none appearance-none" style="background:#f8fafc; border:1px solid #f1f5f9;">
                    <option>All Status</option>
                    <option>Successful</option>
                    <option>Pending</option>
                    <option>Failed</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Department</label>
                <select class="w-full px-3 py-2 text-sm rounded-lg outline-none appearance-none" style="background:#f8fafc; border:1px solid #f1f5f9;">
                    <option>All Departments</option>
                    <option>Cardiology</option>
                    <option>Orthopedics</option>
                    <option>General</option>
                </select>
            </div>
            <div class="flex gap-2">
                {{-- <button type="button" class="flex-1 py-2 text-sm font-semibold text-white rounded-lg transition-all hover:opacity-90" style="background:#1a73e8;">Apply</button> --}}
                <button type="button" class="px-4 py-2 text-sm font-medium text-slate-600 rounded-lg transition-all hover:bg-slate-200" style="background:#f1f5f9;">Reset</button>
            </div>
        </div>
    </div>

    {{-- ── Table ── --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr style="background:#f8fafc; border-bottom:1px solid #f1f5f9;">
                        <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider whitespace-nowrap">Payment ID</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider whitespace-nowrap">Member Name</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider whitespace-nowrap">Service Type</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider whitespace-nowrap">Department</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider whitespace-nowrap">Amount</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider whitespace-nowrap">Method</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider whitespace-nowrap">Status</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider whitespace-nowrap">Date</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-right whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">

                    {{-- Row 1 --}}
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 text-sm font-semibold text-slate-700">#PAY-9821</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs flex-shrink-0" style="background:#dbeafe; color:#2563eb;">JD</div>
                                <span class="text-sm font-medium text-slate-800 whitespace-nowrap">John Doe</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500">Consultation</td>
                        <td class="px-6 py-4 text-sm text-slate-500">Cardiology</td>
                        <td class="px-6 py-4 text-sm font-bold text-slate-800">₹150.00</td>
                        <td class="px-6 py-4 text-sm text-slate-500 whitespace-nowrap">Credit Card</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wide" style="background:#f0fdf4; color:#16a34a;">
                                <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background:#16a34a;"></span>Successful
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500 whitespace-nowrap">Jan 24, 2024</td>
                        <td class="px-6 py-4 text-right">
                            <div class="inline-block">
                                <button type="button" class="hip-pay-btn w-8 h-8 inline-flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 transition-colors" onclick="hipPayToggle(event,'payMenu1')">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/></svg>
                                </button>
                                <div id="payMenu1" class="hip-pay-menu" style="display:none;">
                                    <a href="#" onclick="hipPayCloseAll()" class="hip-pay-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        View Details
                                    </a>
                                    <a href="#" onclick="hipPayCloseAll()" class="hip-pay-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        Download Receipt
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>

                    {{-- Row 2 --}}
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 text-sm font-semibold text-slate-700">#PAY-9822</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs flex-shrink-0" style="background:#f1f5f9; color:#475569;">AS</div>
                                <span class="text-sm font-medium text-slate-800 whitespace-nowrap">Alice Smith</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500">Procedure</td>
                        <td class="px-6 py-4 text-sm text-slate-500">Orthopedics</td>
                        <td class="px-6 py-4 text-sm font-bold text-slate-800">₹2,450.00</td>
                        <td class="px-6 py-4 text-sm text-slate-500 whitespace-nowrap">Bank Transfer</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wide" style="background:#fffbeb; color:#d97706;">
                                <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background:#d97706;"></span>Pending
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500 whitespace-nowrap">Jan 24, 2024</td>
                        <td class="px-6 py-4 text-right">
                            <div class="inline-block">
                                <button type="button" class="hip-pay-btn w-8 h-8 inline-flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 transition-colors" onclick="hipPayToggle(event,'payMenu2')">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/></svg>
                                </button>
                                <div id="payMenu2" class="hip-pay-menu" style="display:none;">
                                    <a href="#" onclick="hipPayCloseAll()" class="hip-pay-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        View Details
                                    </a>
                                    <a href="#" onclick="hipPayCloseAll()" class="hip-pay-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        Download Receipt
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>

                    {{-- Row 3 --}}
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 text-sm font-semibold text-slate-700">#PAY-9823</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs flex-shrink-0" style="background:#e0e7ff; color:#4f46e5;">MJ</div>
                                <span class="text-sm font-medium text-slate-800 whitespace-nowrap">Michael Johnson</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500">Pharmacy</td>
                        <td class="px-6 py-4 text-sm text-slate-500">General</td>
                        <td class="px-6 py-4 text-sm font-bold text-slate-800">₹85.50</td>
                        <td class="px-6 py-4 text-sm text-slate-500">UPI</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wide" style="background:#f0fdf4; color:#16a34a;">
                                <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background:#16a34a;"></span>Successful
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500 whitespace-nowrap">Jan 23, 2024</td>
                        <td class="px-6 py-4 text-right">
                            <div class="inline-block">
                                <button type="button" class="hip-pay-btn w-8 h-8 inline-flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 transition-colors" onclick="hipPayToggle(event,'payMenu3')">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/></svg>
                                </button>
                                <div id="payMenu3" class="hip-pay-menu" style="display:none;">
                                    <a href="#" onclick="hipPayCloseAll()" class="hip-pay-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        View Details
                                    </a>
                                    <a href="#" onclick="hipPayCloseAll()" class="hip-pay-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        Download Receipt
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>

                    {{-- Row 4 --}}
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 text-sm font-semibold text-slate-700">#PAY-9824</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs flex-shrink-0" style="background:#f3e8ff; color:#9333ea;">EB</div>
                                <span class="text-sm font-medium text-slate-800 whitespace-nowrap">Emily Brown</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500">Diagnostics</td>
                        <td class="px-6 py-4 text-sm text-slate-500">Diagnostics</td>
                        <td class="px-6 py-4 text-sm font-bold text-slate-800">₹320.00</td>
                        <td class="px-6 py-4 text-sm text-slate-500 whitespace-nowrap">Debit Card</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wide" style="background:#f0fdf4; color:#16a34a;">
                                <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background:#16a34a;"></span>Successful
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500 whitespace-nowrap">Jan 23, 2024</td>
                        <td class="px-6 py-4 text-right">
                            <div class="inline-block">
                                <button type="button" class="hip-pay-btn w-8 h-8 inline-flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 transition-colors" onclick="hipPayToggle(event,'payMenu4')">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/></svg>
                                </button>
                                <div id="payMenu4" class="hip-pay-menu" style="display:none;">
                                    <a href="#" onclick="hipPayCloseAll()" class="hip-pay-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        View Details
                                    </a>
                                    <a href="#" onclick="hipPayCloseAll()" class="hip-pay-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        Download Receipt
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>

                    {{-- Row 5 --}}
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 text-sm font-semibold text-slate-700">#PAY-9825</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs flex-shrink-0" style="background:#fce7f3; color:#db2777;">RK</div>
                                <span class="text-sm font-medium text-slate-800 whitespace-nowrap">Ravi Kumar</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500">Consultation</td>
                        <td class="px-6 py-4 text-sm text-slate-500">General</td>
                        <td class="px-6 py-4 text-sm font-bold text-slate-800">₹500.00</td>
                        <td class="px-6 py-4 text-sm text-slate-500">UPI</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wide" style="background:#fff1f2; color:#e11d48;">
                                <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background:#e11d48;"></span>Failed
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500 whitespace-nowrap">Jan 22, 2024</td>
                        <td class="px-6 py-4 text-right">
                            <div class="inline-block">
                                <button type="button" class="hip-pay-btn w-8 h-8 inline-flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 transition-colors" onclick="hipPayToggle(event,'payMenu5')">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/></svg>
                                </button>
                                <div id="payMenu5" class="hip-pay-menu" style="display:none;">
                                    <a href="#" onclick="hipPayCloseAll()" class="hip-pay-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        View Details
                                    </a>
                                    <a href="#" onclick="hipPayCloseAll()" class="hip-pay-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        Download Receipt
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 px-6 py-4" style="border-top:1px solid #f1f5f9;">
            <p class="text-sm text-slate-500 font-medium">Showing 1–10 of 1,240 entries</p>
            <div class="flex items-center gap-1">
                <button type="button" class="px-3 py-1.5 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">Previous</button>
                <button type="button" class="w-8 h-8 flex items-center justify-center text-sm font-bold text-white rounded-lg" style="background:#1a73e8;">1</button>
                <button type="button" class="w-8 h-8 flex items-center justify-center text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-lg">2</button>
                <button type="button" class="w-8 h-8 flex items-center justify-center text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-lg">3</button>
                <button type="button" class="px-3 py-1.5 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">Next</button>
            </div>
        </div>
    </div>

    <script>
        function hipPayToggle(e, id) {
            e.stopPropagation();
            var menu   = document.getElementById(id);
            var btn    = e.currentTarget;
            var isOpen = menu.style.display === 'block';
            hipPayCloseAll();
            if (!isOpen) {
                var rect       = btn.getBoundingClientRect();
                var menuW      = 176;
                var menuH      = 90;
                var spaceBelow = window.innerHeight - rect.bottom;
                menu.style.left    = Math.max(8, rect.right - menuW) + 'px';
                menu.style.top     = (spaceBelow < menuH ? rect.top - menuH - 4 : rect.bottom + 4) + 'px';
                menu.style.display = 'block';
            }
        }
        function hipPayCloseAll() {
            document.querySelectorAll('.hip-pay-menu').forEach(function(m) { m.style.display = 'none'; });
        }
        document.addEventListener('click', hipPayCloseAll);
        window.addEventListener('scroll', hipPayCloseAll, true);
        window.addEventListener('resize', hipPayCloseAll);
    </script>

</div>