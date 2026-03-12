@extends('hospital-admin.layout.hospitaladmin')

@section('title', 'HealthCare Admin Dashboard')
@section('breadcrumb', 'Dashboard')

@section('content')
<div style="background:#F0F2F5; min-height:100vh;">

    {{-- ── Hero Header ── --}}
    <div class="relative overflow-hidden" style="height:220px;">
        <img src="https://images.unsplash.com/photo-1586773860418-d37222d8fce3?w=1400&q=80"
            alt="Hospital Banner"
            class="absolute inset-0 w-full h-full object-cover"
            style="filter:brightness(0.45);">
        {{-- Left fade overlay --}}
        <div class="absolute inset-0" style="background:linear-gradient(to right, rgba(15,23,42,0.80) 0%, rgba(15,23,42,0.30) 60%, transparent 100%);"></div>
        {{-- Bottom fade so content doesn't hard-cut --}}
        <div class="absolute bottom-0 left-0 right-0" style="height:80px; background:linear-gradient(to bottom, transparent, rgba(15,23,42,0.55));"></div>

        {{-- Content pinned to bottom of hero --}}
        <div class="absolute bottom-0 left-0 right-0 px-8 pb-6 flex items-end justify-between">
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight leading-tight">Wellness Hospital</h1>
                <p class="flex items-center gap-1.5 text-sm mt-1" style="color:rgba(203,213,225,0.9);">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Main Healthcare Hub &bull; Multiple Locations
                </p>
            </div>
            <div class="flex gap-3">
                <button type="button"
                    class="inline-flex items-center gap-2 px-5 py-2.5 text-white text-sm font-semibold rounded-xl transition-all"
                    style="background:rgba(30,41,59,0.5); border:1px solid rgba(148,163,184,0.35); backdrop-filter:blur(8px);">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit Profile
                </button>
                <button type="button"
                    class="inline-flex items-center gap-2 px-5 py-2.5 text-white text-sm font-semibold rounded-xl transition-all hover:opacity-90"
                    style="background:#1488CC; box-shadow:0 4px 16px rgba(20,136,204,0.4);">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Manage
                </button>
            </div>
        </div>
    </div>

    {{-- ── Page Body ── --}}
    <div class="px-8 py-6 space-y-6">

        {{-- ── Filter Bar ── --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm px-4 py-3 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">

                {{-- Last 30 Days --}}
                <button type="button" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-600 rounded-xl transition-colors hover:bg-slate-50 whitespace-nowrap" style="border:1px solid #e2e8f0; line-height:1.25;">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;flex-shrink:0;color:#94a3b8;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Last 30 Days
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;flex-shrink:0;color:#94a3b8;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                {{-- All Categories --}}
                <button type="button" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-600 rounded-xl transition-colors hover:bg-slate-50 whitespace-nowrap" style="border:1px solid #e2e8f0; line-height:1.25;">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;flex-shrink:0;color:#94a3b8;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                    All Categories
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;flex-shrink:0;color:#94a3b8;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                {{-- All Locations --}}
                <button type="button" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-600 rounded-xl transition-colors hover:bg-slate-50 whitespace-nowrap" style="border:1px solid #e2e8f0; line-height:1.25;">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;flex-shrink:0;color:#94a3b8;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    All Locations
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;flex-shrink:0;color:#94a3b8;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

            </div>
            <div class="flex items-center gap-1.5">
                <button type="button" style="width:36px;height:36px;border:1px solid #e2e8f0;border-radius:0.75rem;display:flex;align-items:center;justify-content:center;color:#94a3b8;transition:background .15s;" title="Refresh"
                    onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </button>
                <button type="button" style="width:36px;height:36px;border:1px solid #e2e8f0;border-radius:0.75rem;display:flex;align-items:center;justify-content:center;color:#94a3b8;transition:background .15s;" title="Download"
                    onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- ── Stat Cards ── --}}
        <div style="display:grid; grid-template-columns:repeat(6,1fr); gap:1rem;">

            <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
                <p class="font-bold uppercase text-slate-400 mb-2 leading-tight" style="font-size:10px; letter-spacing:.08em;">Total Revenue</p>
                <h3 class="text-2xl font-black text-slate-900 leading-none">₹12.4M</h3>
                <p class="flex items-center gap-1 mt-2 text-xs font-semibold" style="color:#16a34a;">
                    <svg class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    +8.2%
                </p>
            </div>

            <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
                <p class="font-bold uppercase text-slate-400 mb-2 leading-tight" style="font-size:10px; letter-spacing:.08em;">Appointments</p>
                <h3 class="text-2xl font-black text-slate-900 leading-none">1,248</h3>
                <p class="flex items-center gap-1 mt-2 text-xs font-semibold" style="color:#16a34a;">
                    <svg class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    +4.1%
                </p>
            </div>

            <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
                <p class="font-bold uppercase text-slate-400 mb-2 leading-tight" style="font-size:10px; letter-spacing:.08em;">Diag. Tests</p>
                <h3 class="text-2xl font-black text-slate-900 leading-none">852</h3>
                <p class="flex items-center gap-1 mt-2 text-xs font-semibold" style="color:#16a34a;">
                    <svg class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    +2.7%
                </p>
            </div>

            <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
                <p class="font-bold uppercase text-slate-400 mb-2 leading-tight" style="font-size:10px; letter-spacing:.08em;">Pharmacy</p>
                <h3 class="text-2xl font-black text-slate-900 leading-none">3,120</h3>
                <p class="flex items-center gap-1 mt-2 text-xs font-semibold" style="color:#16a34a;">
                    <svg class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    +11.5%
                </p>
            </div>

            <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
                <p class="font-bold uppercase text-slate-400 mb-2 leading-tight" style="font-size:10px; letter-spacing:.08em;">Members</p>
                <h3 class="text-2xl font-black text-slate-900 leading-none">15,402</h3>
                <p class="flex items-center gap-1 mt-2 text-xs font-semibold" style="color:#16a34a;">
                    <svg class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    +3.9%
                </p>
            </div>

            <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
                <p class="font-bold uppercase text-slate-400 mb-2 leading-tight" style="font-size:10px; letter-spacing:.08em;">Refunds</p>
                <h3 class="text-2xl font-black text-slate-900 leading-none">₹45.2K</h3>
                <p class="flex items-center gap-1 mt-2 text-xs font-semibold" style="color:#dc2626;">
                    <svg class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                    -1.2%
                </p>
            </div>

        </div>

        {{-- ── Quick Action Cards ── --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

            <a href="#" class="group p-6 rounded-2xl flex items-center justify-between text-white transition-all hover:opacity-95 hover:shadow-xl"
                style="background:linear-gradient(135deg,#1488CC 0%,#2B32B2 100%); box-shadow:0 8px 24px rgba(20,136,204,0.28);">
                <div>
                    <h4 class="text-lg font-black mb-1 leading-tight">Manage Procedures</h4>
                    <p class="text-sm leading-snug" style="color:rgba(219,234,254,0.85);">Configure and add new medical procedures</p>
                </div>
                <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0 transition-transform group-hover:scale-110" style="background:rgba(255,255,255,0.18);">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                </div>
            </a>

            <a href="#" class="group p-6 rounded-2xl flex items-center justify-between text-white transition-all hover:opacity-95 hover:shadow-xl"
                style="background:#3b82f6; box-shadow:0 8px 24px rgba(59,130,246,0.28);">
                <div>
                    <h4 class="text-lg font-black mb-1 leading-tight">View Doctors</h4>
                    <p class="text-sm leading-snug" style="color:rgba(219,234,254,0.85);">Check availability and managing profiles</p>
                </div>
                <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0 transition-transform group-hover:scale-110" style="background:rgba(255,255,255,0.18);">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </a>

            <a href="#" class="group p-6 rounded-2xl flex items-center justify-between text-white transition-all hover:opacity-95 hover:shadow-xl"
                style="background:#60a5fa; box-shadow:0 8px 24px rgba(96,165,250,0.28);">
                <div>
                    <h4 class="text-lg font-black mb-1 leading-tight">View Bookings</h4>
                    <p class="text-sm leading-snug" style="color:rgba(239,246,255,0.9);">Track all appointments in real-time</p>
                </div>
                <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0 transition-transform group-hover:scale-110" style="background:rgba(255,255,255,0.18);">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            </a>

        </div>

        {{-- ── Charts Row 1 ── --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Weekly Income Breakdown --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h5 class="font-bold text-slate-800">Weekly Income Breakdown</h5>
                        <p class="text-xs text-slate-400 mt-0.5">Current week performance</p>
                    </div>
                    <span class="font-bold uppercase text-slate-400" style="font-size:10px; letter-spacing:.08em;">Values in ₹ Lakhs</span>
                </div>
                <div class="flex items-end justify-between gap-2 px-2" style="height:14rem;">
                    @php
                        $bars = [
                            ['h'=>'5rem',  'day'=>'MON', 'active'=>false],
                            ['h'=>'9rem',  'day'=>'TUE', 'active'=>false],
                            ['h'=>'7rem',  'day'=>'WED', 'active'=>false],
                            ['h'=>'12rem', 'day'=>'THU', 'active'=>false],
                            ['h'=>'14rem', 'day'=>'FRI', 'active'=>true],
                            ['h'=>'10rem', 'day'=>'SAT', 'active'=>false],
                            ['h'=>'6rem',  'day'=>'SUN', 'active'=>false],
                        ];
                    @endphp
                    @foreach($bars as $bar)
                    <div class="flex flex-col items-center gap-1.5 flex-1">
                        <div class="w-full rounded-t-lg transition-all" style="height:{{ $bar['h'] }}; background:{{ $bar['active'] ? '#1488CC' : '#e2e8f0' }};"></div>
                        <span class="font-bold" style="font-size:10px; color:{{ $bar['active'] ? '#1488CC' : '#94a3b8' }}; letter-spacing:.06em;">{{ $bar['day'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Revenue vs Refunds Trend --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h5 class="font-bold text-slate-800">Revenue vs Refunds Trend</h5>
                        <p class="text-xs text-slate-400 mt-0.5">Monthly comparison</p>
                    </div>
                    <div class="flex gap-4" style="font-size:10px;">
                        <div class="flex items-center gap-1.5">
                            <div class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background:#1488CC;"></div>
                            <span class="font-bold text-slate-500 uppercase tracking-wide">Revenue</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background:#fb7185;"></div>
                            <span class="font-bold text-slate-500 uppercase tracking-wide">Refunds</span>
                        </div>
                    </div>
                </div>
                <div style="height:14rem; position:relative;">
                    <svg class="w-full" style="height:calc(100% - 1.5rem);" viewBox="0 0 400 200" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="revGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" style="stop-color:#1488CC;stop-opacity:0.18;"/>
                                <stop offset="100%" style="stop-color:#1488CC;stop-opacity:0;"/>
                            </linearGradient>
                        </defs>
                        <path d="M0,150 Q100,140 200,115 T400,75 V200 H0 Z" fill="url(#revGrad)"/>
                        <path d="M0,150 Q100,140 200,115 T400,75" fill="none" stroke="#1488CC" stroke-width="2.5" stroke-linecap="round"/>
                        <path d="M0,182 Q100,178 200,180 T400,167" fill="none" stroke="#fb7185" stroke-width="2" stroke-dasharray="5 3" stroke-linecap="round"/>
                    </svg>
                    <div class="flex justify-between mt-1">
                        <span class="font-bold text-slate-400 uppercase" style="font-size:10px;letter-spacing:.08em;">Week 1</span>
                        <span class="font-bold text-slate-400 uppercase" style="font-size:10px;letter-spacing:.08em;">Week 2</span>
                        <span class="font-bold text-slate-400 uppercase" style="font-size:10px;letter-spacing:.08em;">Week 3</span>
                        <span class="font-bold text-slate-400 uppercase" style="font-size:10px;letter-spacing:.08em;">Week 4</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Charts Row 2 ── --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Appointment & Test Trends --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <div class="mb-6">
                    <h5 class="font-bold text-slate-800">Appointment &amp; Test Trends</h5>
                    <div class="flex gap-4 mt-2" style="font-size:10px;">
                        <div class="flex items-center gap-1.5">
                            <div class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background:#1488CC;"></div>
                            <span class="font-bold text-slate-500 uppercase tracking-wide">Appointments</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background:#a855f7;"></div>
                            <span class="font-bold text-slate-500 uppercase tracking-wide">Diag. Tests</span>
                        </div>
                    </div>
                </div>
                <div style="height:14rem; position:relative;">
                    <svg class="w-full" style="height:calc(100% - 1.5rem);" viewBox="0 0 500 200" preserveAspectRatio="none">
                        <path d="M0,180 L50,150 L100,168 L150,182 L200,162 L250,172 L300,132 L350,142 L400,102 L450,82 L500,72"
                            fill="none" stroke="#1488CC" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>
                        <path d="M0,198 L50,190 L100,184 L150,193 L200,183 L250,192 L300,172 L350,182 L400,162 L450,152 L500,132"
                            fill="none" stroke="#a855f7" stroke-width="2" stroke-dasharray="5 3" stroke-linejoin="round" stroke-linecap="round"/>
                    </svg>
                    <div class="flex justify-between mt-1 px-1">
                        <span class="font-bold text-slate-400 uppercase" style="font-size:10px;letter-spacing:.08em;">Jul</span>
                        <span class="font-bold text-slate-400 uppercase" style="font-size:10px;letter-spacing:.08em;">Aug</span>
                        <span class="font-bold text-slate-400 uppercase" style="font-size:10px;letter-spacing:.08em;">Sep</span>
                        <span class="font-bold text-slate-400 uppercase" style="font-size:10px;letter-spacing:.08em;">Oct</span>
                        <span class="font-bold text-slate-400 uppercase" style="font-size:10px;letter-spacing:.08em;">Nov</span>
                        <span class="font-bold text-slate-400 uppercase" style="font-size:10px;letter-spacing:.08em;">Dec</span>
                    </div>
                </div>
            </div>

            {{-- Transaction Growth --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <div class="mb-6">
                    <h5 class="font-bold text-slate-800">Transaction Growth Line</h5>
                    <p class="text-xs text-slate-400 mt-0.5">Quarterly upward trend</p>
                </div>
                <div style="height:14rem; position:relative;">
                    <svg class="w-full" style="height:calc(100% - 1.5rem);" viewBox="0 0 400 200" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="growthGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" style="stop-color:#10b981;stop-opacity:0.15;"/>
                                <stop offset="100%" style="stop-color:#10b981;stop-opacity:0;"/>
                            </linearGradient>
                        </defs>
                        <path d="M0,180 Q100,158 200,118 T400,55 V200 H0 Z" fill="url(#growthGrad)"/>
                        <path d="M0,180 Q100,158 200,118 T400,55" fill="none" stroke="#10b981" stroke-width="3" stroke-linecap="round"/>
                    </svg>
                    <div class="flex justify-between mt-1">
                        <span class="font-bold text-slate-400 uppercase" style="font-size:10px;letter-spacing:.08em;">Q1</span>
                        <span class="font-bold text-slate-400 uppercase" style="font-size:10px;letter-spacing:.08em;">Q2</span>
                        <span class="font-bold text-slate-400 uppercase" style="font-size:10px;letter-spacing:.08em;">Q3</span>
                        <span class="font-bold text-slate-400 uppercase" style="font-size:10px;letter-spacing:.08em;">Q4</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Bottom Row ── --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 pb-4">

            {{-- Revenue by Service --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm flex flex-col overflow-hidden">
                <div class="p-6 flex-1">
                    <h5 class="font-bold text-slate-800 mb-1">Revenue by Service</h5>
                    <p class="text-xs text-slate-400 mb-6">Current period breakdown</p>
                    <div class="space-y-5">
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-sm text-slate-600 font-medium">Hospital Services</span>
                                <span class="text-sm font-bold text-slate-800">₹7.2M</span>
                            </div>
                            <div class="w-full h-2 rounded-full overflow-hidden" style="background:#f1f5f9;">
                                <div class="h-full rounded-full transition-all" style="width:70%; background:#1488CC;"></div>
                            </div>
                            <p class="text-right mt-1 font-bold text-slate-400" style="font-size:10px;">70%</p>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-sm text-slate-600 font-medium">Pharmacy Orders</span>
                                <span class="text-sm font-bold text-slate-800">₹3.1M</span>
                            </div>
                            <div class="w-full h-2 rounded-full overflow-hidden" style="background:#f1f5f9;">
                                <div class="h-full rounded-full transition-all" style="width:40%; background:#f97316;"></div>
                            </div>
                            <p class="text-right mt-1 font-bold text-slate-400" style="font-size:10px;">40%</p>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-sm text-slate-600 font-medium">Diagnostics</span>
                                <span class="text-sm font-bold text-slate-800">₹2.1M</span>
                            </div>
                            <div class="w-full h-2 rounded-full overflow-hidden" style="background:#f1f5f9;">
                                <div class="h-full rounded-full transition-all" style="width:25%; background:#a855f7;"></div>
                            </div>
                            <p class="text-right mt-1 font-bold text-slate-400" style="font-size:10px;">25%</p>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4" style="border-top:1px solid #f1f5f9;">
                    <button type="button" class="w-full text-center text-sm font-bold transition-colors hover:underline" style="color:#1488CC;">
                        Download Detailed Report
                    </button>
                </div>
            </div>

            {{-- Recent Financial Transactions --}}
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm flex flex-col overflow-hidden">

                <div class="flex items-center justify-between px-6 py-4" style="border-bottom:1px solid #f1f5f9;">
                    <div>
                        <h5 class="font-bold text-slate-800">Recent Financial Transactions</h5>
                        <p class="text-xs text-slate-400 mt-0.5">Last 4 of 1,245 transactions</p>
                    </div>
                    <button type="button" class="text-xs font-bold uppercase tracking-wide transition-colors hover:underline" style="color:#1488CC;">View All</button>
                </div>

                <div class="overflow-x-auto flex-1">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr style="background:#f8fafc;">
                                <th class="px-6 py-3 font-bold text-slate-400 uppercase whitespace-nowrap" style="font-size:10px;letter-spacing:.08em;">Transaction ID</th>
                                <th class="px-6 py-3 font-bold text-slate-400 uppercase whitespace-nowrap" style="font-size:10px;letter-spacing:.08em;">Service</th>
                                <th class="px-6 py-3 font-bold text-slate-400 uppercase whitespace-nowrap" style="font-size:10px;letter-spacing:.08em;">Amount</th>
                                <th class="px-6 py-3 font-bold text-slate-400 uppercase whitespace-nowrap" style="font-size:10px;letter-spacing:.08em;">Status</th>
                                <th class="px-6 py-3 font-bold text-slate-400 uppercase whitespace-nowrap" style="font-size:10px;letter-spacing:.08em;">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4"><p class="text-xs font-bold text-slate-500">#TXN-10293</p></td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background:#dbeafe;">
                                            <svg class="w-4 h-4" style="color:#2563eb;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                            </svg>
                                        </div>
                                        <span class="text-sm font-medium text-slate-700">MRI Brain Scan</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4"><span class="text-sm font-bold text-slate-800">₹12,500</span></td>
                                <td class="px-6 py-4"><span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase" style="background:#dcfce7; color:#16a34a;">Success</span></td>
                                <td class="px-6 py-4 text-xs text-slate-400 leading-tight whitespace-nowrap">Oct 24, 14:20</td>
                            </tr>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4"><p class="text-xs font-bold text-slate-500">#TXN-10294</p></td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background:#fef3c7;">
                                            <svg class="w-4 h-4" style="color:#d97706;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </div>
                                        <span class="text-sm font-medium text-slate-700">Cardiac Meds Pack</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4"><span class="text-sm font-bold text-slate-800">₹4,250</span></td>
                                <td class="px-6 py-4"><span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase" style="background:#fef3c7; color:#d97706;">Pending</span></td>
                                <td class="px-6 py-4 text-xs text-slate-400 leading-tight whitespace-nowrap">Oct 24, 15:05</td>
                            </tr>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4"><p class="text-xs font-bold text-slate-500">#TXN-10295</p></td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background:#f3e8ff;">
                                            <svg class="w-4 h-4" style="color:#9333ea;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                                            </svg>
                                        </div>
                                        <span class="text-sm font-medium text-slate-700">CBC Blood Test</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4"><span class="text-sm font-bold text-slate-800">₹850</span></td>
                                <td class="px-6 py-4"><span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase" style="background:#dcfce7; color:#16a34a;">Success</span></td>
                                <td class="px-6 py-4 text-xs text-slate-400 leading-tight whitespace-nowrap">Oct 24, 15:45</td>
                            </tr>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4"><p class="text-xs font-bold text-slate-500">#TXN-10296</p></td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background:#ffe4e6;">
                                            <svg class="w-4 h-4" style="color:#e11d48;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                            </svg>
                                        </div>
                                        <span class="text-sm font-medium text-slate-700">Consultation Fee</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4"><span class="text-sm font-bold text-slate-800">₹1,500</span></td>
                                <td class="px-6 py-4"><span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase" style="background:#ffe4e6; color:#e11d48;">Refunded</span></td>
                                <td class="px-6 py-4 text-xs text-slate-400 leading-tight whitespace-nowrap">Oct 23, 11:10</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between px-6 py-4 text-xs text-slate-400" style="border-top:1px solid #f1f5f9;">
                    <span>Showing 4 of 1,245 transactions</span>
                    <div class="flex gap-1.5">
                        <button type="button" class="w-8 h-8 flex items-center justify-center rounded-lg transition-colors hover:bg-slate-50" style="border:1px solid #e2e8f0;">
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>
                        <button type="button" class="w-8 h-8 flex items-center justify-center rounded-lg text-xs font-bold text-white" style="background:#1488CC;">1</button>
                        <button type="button" class="w-8 h-8 flex items-center justify-center rounded-lg transition-colors hover:bg-slate-50" style="border:1px solid #e2e8f0;">
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>
@endsection