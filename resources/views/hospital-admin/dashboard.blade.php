@extends('hospital-admin.layout.hospitaladmin')

@section('title', 'HealthCare Admin Dashboard')
@section('breadcrumb', 'Dashboard')

@section('content')

{{-- livewire/admin/hospital/hospital-dashboard.blade.php --}}
<div class="flex-1 lg:ml-64 pb-10" style="background:#F8FAFC;">

    {{-- ── Hero Header ── --}}
    <header class="relative h-64 overflow-hidden">
        <img src="https://images.unsplash.com/photo-1586773860418-d37222d8fce3?w=1400&q=80"
            alt="Hospital Banner"
            class="absolute inset-0 w-full h-full object-cover"
            style="filter:brightness(0.5);">
        <div class="absolute inset-0" style="background:linear-gradient(to right, rgba(15,23,42,0.85), transparent);"></div>

        <div class="relative z-10 px-8 py-10 h-full flex items-end">
            <div class="flex flex-col md:flex-row md:items-center justify-between w-full">
                <div class="flex items-center gap-6">
                    {{-- <div class="w-24 h-24 bg-white rounded-2xl flex items-center justify-center p-2 shadow-xl overflow-hidden flex-shrink-0">
                        <div class="w-full h-full rounded-xl flex items-center justify-center" style="background:#4CAF50;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                    </div> --}}
                    {{-- <div class="text-white">
                        <h2 class="text-3xl font-bold">Wellness Hospital</h2>
                        <p class="flex items-center gap-1.5 text-slate-200 mt-1 text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Main Healthcare Hub • Multiple Locations
                        </p>
                    </div> --}}
                </div>
                <div class="flex gap-3 mt-6 md:mt-0">
                    <button type="button"
                        class="inline-flex items-center gap-2 px-5 py-2.5 text-white text-sm font-medium rounded-lg transition-all"
                        style="background:rgba(30,41,59,0.4); border:1px solid rgba(148,163,184,0.3); backdrop-filter:blur(8px);">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit Profile
                    </button>
                    <button type="button"
                        class="inline-flex items-center gap-2 px-5 py-2.5 text-white text-sm font-medium rounded-lg transition-all"
                        style="background:#1488CC; box-shadow:0 4px 20px rgba(20,136,204,0.35);">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Manage
                    </button>
                </div>
            </div>
        </div>
    </header>

    {{-- ── Content ── --}}
    <div class="px-8 -mt-6">

        {{-- Filter Bar --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-3 mb-6 flex flex-wrap items-center justify-between">
            <div class="flex flex-wrap gap-3">
                <button type="button" class="inline-flex items-center gap-2 px-4 py-2 text-sm text-slate-600 rounded-lg transition-colors hover:bg-slate-50" style="border:1px solid #e2e8f0;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Last 30 Days
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <button type="button" class="inline-flex items-center gap-2 px-4 py-2 text-sm text-slate-600 rounded-lg transition-colors hover:bg-slate-50" style="border:1px solid #e2e8f0;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                    All Categories
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <button type="button" class="inline-flex items-center gap-2 px-4 py-2 text-sm text-slate-600 rounded-lg transition-colors hover:bg-slate-50" style="border:1px solid #e2e8f0;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    All Locations
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
            </div>
            <div class="flex items-center gap-2 mt-2 sm:mt-0">
                <button type="button" class="p-2 text-slate-400 rounded-lg hover:text-slate-600 hover:bg-slate-50 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </button>
                <button type="button" class="p-2 text-slate-400 rounded-lg hover:text-slate-600 hover:bg-slate-50 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- ── Stat Cards ── --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <p class="text-slate-400 font-bold uppercase mb-1" style="font-size:10px;letter-spacing:.08em;">Total Revenue</p>
                <h3 class="text-2xl font-bold text-slate-900">₹12.4M</h3>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <p class="text-slate-400 font-bold uppercase mb-1" style="font-size:10px;letter-spacing:.08em;">Appointments</p>
                <h3 class="text-2xl font-bold text-slate-900">1,248</h3>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <p class="text-slate-400 font-bold uppercase mb-1" style="font-size:10px;letter-spacing:.08em;">Diag. Tests</p>
                <h3 class="text-2xl font-bold text-slate-900">852</h3>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <p class="text-slate-400 font-bold uppercase mb-1" style="font-size:10px;letter-spacing:.08em;">Pharmacy</p>
                <h3 class="text-2xl font-bold text-slate-900">3,120</h3>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <p class="text-slate-400 font-bold uppercase mb-1" style="font-size:10px;letter-spacing:.08em;">Members</p>
                <h3 class="text-2xl font-bold text-slate-900">15,402</h3>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <p class="text-slate-400 font-bold uppercase mb-1" style="font-size:10px;letter-spacing:.08em;">Refunds</p>
                <h3 class="text-2xl font-bold text-slate-900">₹45.2K</h3>
            </div>
        </div>

        {{-- ── Quick Action Cards ── --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="p-6 rounded-2xl flex items-center justify-between text-white cursor-pointer transition-all hover:opacity-90"
                style="background:linear-gradient(135deg,#1488CC 0%,#2B32B2 100%); box-shadow:0 8px 24px rgba(20,136,204,0.25);">
                <div>
                    <h4 class="text-xl font-bold mb-1">Manage Procedures</h4>
                    <p class="text-sm" style="color:rgba(219,234,254,0.85);">Configure and add new medical procedures</p>
                </div>
                <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(255,255,255,0.2);">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                </div>
            </div>
            <div class="p-6 rounded-2xl flex items-center justify-between text-white cursor-pointer transition-all hover:opacity-90"
                style="background:#3b82f6; box-shadow:0 8px 24px rgba(59,130,246,0.25);">
                <div>
                    <h4 class="text-xl font-bold mb-1">View Doctors</h4>
                    <p class="text-sm" style="color:rgba(219,234,254,0.85);">Check availability and managing profiles</p>
                </div>
                <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(255,255,255,0.2);">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
            <div class="p-6 rounded-2xl flex items-center justify-between text-white cursor-pointer transition-all hover:opacity-90"
                style="background:#60a5fa; box-shadow:0 8px 24px rgba(96,165,250,0.25);">
                <div>
                    <h4 class="text-xl font-bold mb-1">View Bookings</h4>
                    <p class="text-sm" style="color:rgba(239,246,255,0.9);">Track all appointments in real-time</p>
                </div>
                <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(255,255,255,0.2);">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- ── Charts Row 1 ── --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">

            {{-- Weekly Income Breakdown --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <div class="flex items-center justify-between mb-8">
                    <h5 class="font-bold text-slate-800">Weekly Income Breakdown</h5>
                    <span class="text-slate-400 font-bold uppercase" style="font-size:10px;letter-spacing:.08em;">Values in ₹ Lakhs</span>
                </div>
                <div class="flex items-end justify-between gap-2 px-4" style="height:16rem;">
                    <div class="flex flex-col items-center gap-2 w-full">
                        <div class="w-full rounded-t" style="height:6rem; background:#f1f5f9;"></div>
                        <span style="font-size:10px; color:#94a3b8;" class="font-bold">MON</span>
                    </div>
                    <div class="flex flex-col items-center gap-2 w-full">
                        <div class="w-full rounded-t" style="height:10rem; background:#f1f5f9;"></div>
                        <span style="font-size:10px; color:#94a3b8;" class="font-bold">TUE</span>
                    </div>
                    <div class="flex flex-col items-center gap-2 w-full">
                        <div class="w-full rounded-t" style="height:8rem; background:#f1f5f9;"></div>
                        <span style="font-size:10px; color:#94a3b8;" class="font-bold">WED</span>
                    </div>
                    <div class="flex flex-col items-center gap-2 w-full">
                        <div class="w-full rounded-t" style="height:13rem; background:#f1f5f9;"></div>
                        <span style="font-size:10px; color:#94a3b8;" class="font-bold">THU</span>
                    </div>
                    <div class="flex flex-col items-center gap-2 w-full">
                        <div class="w-full rounded-t" style="height:15rem; background:#1488CC;"></div>
                        <span style="font-size:10px; color:#1488CC;" class="font-bold">FRI</span>
                    </div>
                    <div class="flex flex-col items-center gap-2 w-full">
                        <div class="w-full rounded-t" style="height:11rem; background:#f1f5f9;"></div>
                        <span style="font-size:10px; color:#94a3b8;" class="font-bold">SAT</span>
                    </div>
                    <div class="flex flex-col items-center gap-2 w-full">
                        <div class="w-full rounded-t" style="height:7rem; background:#f1f5f9;"></div>
                        <span style="font-size:10px; color:#94a3b8;" class="font-bold">SUN</span>
                    </div>
                </div>
            </div>

            {{-- Revenue vs Refunds Trend --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <div class="flex items-center justify-between mb-8">
                    <h5 class="font-bold text-slate-800">Revenue vs Refunds Trend</h5>
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
                <div style="height:16rem; position:relative;">
                    <svg class="w-full" style="height:calc(100% - 2rem);" viewBox="0 0 400 200" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="revGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" style="stop-color:#1488CC;stop-opacity:0.15;"/>
                                <stop offset="100%" style="stop-color:#1488CC;stop-opacity:0;"/>
                            </linearGradient>
                        </defs>
                        <path d="M0,150 Q100,140 200,120 T400,80 V200 H0 Z" fill="url(#revGrad)"/>
                        <path d="M0,150 Q100,140 200,120 T400,80" fill="none" stroke="#1488CC" stroke-width="3" stroke-linecap="round"/>
                        <path d="M0,180 Q100,175 200,178 T400,165" fill="none" stroke="#fb7185" stroke-width="2" stroke-dasharray="4 3" stroke-linecap="round"/>
                    </svg>
                    <div class="flex justify-between mt-2">
                        <span class="font-bold text-slate-400 uppercase" style="font-size:10px;letter-spacing:.08em;">Week 1</span>
                        <span class="font-bold text-slate-400 uppercase" style="font-size:10px;letter-spacing:.08em;">Week 2</span>
                        <span class="font-bold text-slate-400 uppercase" style="font-size:10px;letter-spacing:.08em;">Week 3</span>
                        <span class="font-bold text-slate-400 uppercase" style="font-size:10px;letter-spacing:.08em;">Week 4</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Charts Row 2 ── --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">

            {{-- Appointment & Test Trends --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h5 class="font-bold text-slate-800 mb-8">Appointment &amp; Test Trends</h5>
                <div style="height:16rem; position:relative;">
                    <svg class="w-full" style="height:calc(100% - 2rem);" viewBox="0 0 500 200" preserveAspectRatio="none">
                        <path d="M0,180 L50,150 L100,170 L150,185 L200,165 L250,175 L300,135 L350,145 L400,105 L450,85 L500,75"
                            fill="none" stroke="#1488CC" stroke-width="3" stroke-linejoin="round" stroke-linecap="round"/>
                        <path d="M0,200 L50,190 L100,185 L150,195 L200,185 L250,195 L300,175 L350,185 L400,165 L450,155 L500,135"
                            fill="none" stroke="#a855f7" stroke-width="2" stroke-dasharray="4 3" stroke-linejoin="round" stroke-linecap="round"/>
                    </svg>
                    <div class="flex justify-between mt-2 px-2">
                        <span class="font-bold text-slate-400 uppercase" style="font-size:10px;letter-spacing:.08em;">Jul</span>
                        <span class="font-bold text-slate-400 uppercase" style="font-size:10px;letter-spacing:.08em;">Aug</span>
                        <span class="font-bold text-slate-400 uppercase" style="font-size:10px;letter-spacing:.08em;">Sep</span>
                        <span class="font-bold text-slate-400 uppercase" style="font-size:10px;letter-spacing:.08em;">Oct</span>
                        <span class="font-bold text-slate-400 uppercase" style="font-size:10px;letter-spacing:.08em;">Nov</span>
                        <span class="font-bold text-slate-400 uppercase" style="font-size:10px;letter-spacing:.08em;">Dec</span>
                    </div>
                </div>
            </div>

            {{-- Transaction Growth Line --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h5 class="font-bold text-slate-800 mb-8">Transaction Growth Line</h5>
                <div style="height:16rem; position:relative;">
                    <svg class="w-full" style="height:calc(100% - 2rem);" viewBox="0 0 400 200" preserveAspectRatio="none">
                        <path d="M0,180 Q100,160 200,120 T400,60"
                            fill="none" stroke="#10b981" stroke-width="6" stroke-linecap="round"/>
                    </svg>
                    <div class="flex justify-between mt-2">
                        <span class="font-bold text-slate-400 uppercase" style="font-size:10px;letter-spacing:.08em;">Q1</span>
                        <span class="font-bold text-slate-400 uppercase" style="font-size:10px;letter-spacing:.08em;">Q2</span>
                        <span class="font-bold text-slate-400 uppercase" style="font-size:10px;letter-spacing:.08em;">Q3</span>
                        <span class="font-bold text-slate-400 uppercase" style="font-size:10px;letter-spacing:.08em;">Q4</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Bottom Row ── --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            {{-- Revenue by Service --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col overflow-hidden">
                <div class="p-6 flex-1">
                    <h5 class="font-bold text-slate-800 mb-6">Revenue by Service</h5>
                    <div class="space-y-6">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm text-slate-500 font-medium">Hospital Services</span>
                                <span class="text-sm font-bold text-slate-800">₹7.2M</span>
                            </div>
                            <div class="w-full h-2 rounded-full overflow-hidden" style="background:#f1f5f9;">
                                <div class="h-full rounded-full" style="width:70%; background:#1488CC;"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm text-slate-500 font-medium">Pharmacy Orders</span>
                                <span class="text-sm font-bold text-slate-800">₹3.1M</span>
                            </div>
                            <div class="w-full h-2 rounded-full overflow-hidden" style="background:#f1f5f9;">
                                <div class="h-full rounded-full" style="width:40%; background:#f97316;"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm text-slate-500 font-medium">Diagnostics</span>
                                <span class="text-sm font-bold text-slate-800">₹2.1M</span>
                            </div>
                            <div class="w-full h-2 rounded-full overflow-hidden" style="background:#f1f5f9;">
                                <div class="h-full rounded-full" style="width:25%; background:#a855f7;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-4" style="border-top:1px solid #f1f5f9;">
                    <button type="button" class="w-full text-center text-sm font-bold transition-colors hover:underline" style="color:#1488CC;">
                        Download Detailed Report
                    </button>
                </div>
            </div>

            {{-- Recent Financial Transactions --}}
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4" style="border-bottom:1px solid #f1f5f9;">
                    <h5 class="font-bold text-slate-800">Recent Financial Transactions</h5>
                    <button type="button" class="text-xs font-bold uppercase tracking-wide transition-colors hover:underline" style="color:#1488CC;">View All</button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead style="background:#f8fafc;">
                            <tr>
                                <th class="px-6 py-3 font-bold text-slate-400 uppercase" style="font-size:10px;letter-spacing:.08em;">Transaction ID</th>
                                <th class="px-6 py-3 font-bold text-slate-400 uppercase" style="font-size:10px;letter-spacing:.08em;">Service</th>
                                <th class="px-6 py-3 font-bold text-slate-400 uppercase" style="font-size:10px;letter-spacing:.08em;">Amount</th>
                                <th class="px-6 py-3 font-bold text-slate-400 uppercase" style="font-size:10px;letter-spacing:.08em;">Status</th>
                                <th class="px-6 py-3 font-bold text-slate-400 uppercase" style="font-size:10px;letter-spacing:.08em;">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Row 1 --}}
                            <tr style="border-bottom:1px solid #f8fafc;">
                                <td class="px-6 py-4"><p class="text-xs font-bold text-slate-500">#TXN-10293</p></td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background:#dbeafe;">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" style="color:#2563eb;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                            </svg>
                                        </div>
                                        <span class="text-sm font-medium text-slate-700">MRI Brain Scan</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4"><span class="text-sm font-bold text-slate-800">₹12,500</span></td>
                                <td class="px-6 py-4"><span class="px-2.5 py-1 rounded text-xs font-bold uppercase" style="background:#dcfce7; color:#16a34a;">Success</span></td>
                                <td class="px-6 py-4"><div class="text-slate-400 leading-tight" style="font-size:11px;">Oct 24,<br/>14:20</div></td>
                            </tr>
                            {{-- Row 2 --}}
                            <tr style="border-bottom:1px solid #f8fafc;">
                                <td class="px-6 py-4"><p class="text-xs font-bold text-slate-500">#TXN-10294</p></td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background:#fef3c7;">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" style="color:#d97706;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </div>
                                        <span class="text-sm font-medium text-slate-700">Cardiac Meds Pack</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4"><span class="text-sm font-bold text-slate-800">₹4,250</span></td>
                                <td class="px-6 py-4"><span class="px-2.5 py-1 rounded text-xs font-bold uppercase" style="background:#fef3c7; color:#d97706;">Pending</span></td>
                                <td class="px-6 py-4"><div class="text-slate-400 leading-tight" style="font-size:11px;">Oct 24,<br/>15:05</div></td>
                            </tr>
                            {{-- Row 3 --}}
                            <tr style="border-bottom:1px solid #f8fafc;">
                                <td class="px-6 py-4"><p class="text-xs font-bold text-slate-500">#TXN-10295</p></td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background:#f3e8ff;">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" style="color:#9333ea;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                                            </svg>
                                        </div>
                                        <span class="text-sm font-medium text-slate-700">CBC Blood Test</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4"><span class="text-sm font-bold text-slate-800">₹850</span></td>
                                <td class="px-6 py-4"><span class="px-2.5 py-1 rounded text-xs font-bold uppercase" style="background:#dcfce7; color:#16a34a;">Success</span></td>
                                <td class="px-6 py-4"><div class="text-slate-400 leading-tight" style="font-size:11px;">Oct 24,<br/>15:45</div></td>
                            </tr>
                            {{-- Row 4 --}}
                            <tr>
                                <td class="px-6 py-4"><p class="text-xs font-bold text-slate-500">#TXN-10296</p></td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background:#ffe4e6;">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" style="color:#e11d48;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                            </svg>
                                        </div>
                                        <span class="text-sm font-medium text-slate-700">Consultation Fee</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4"><span class="text-sm font-bold text-slate-800">₹1,500</span></td>
                                <td class="px-6 py-4"><span class="px-2.5 py-1 rounded text-xs font-bold uppercase" style="background:#ffe4e6; color:#e11d48;">Refunded</span></td>
                                <td class="px-6 py-4"><div class="text-slate-400 leading-tight" style="font-size:11px;">Oct 23,<br/>11:10</div></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="flex items-center justify-between px-6 py-4 text-xs text-slate-400" style="border-top:1px solid #f1f5f9;">
                    <span>Showing 4 of 1,245 transactions</span>
                    <div class="flex gap-2">
                        <button type="button" class="w-8 h-8 flex items-center justify-center rounded transition-colors hover:bg-slate-50" style="border:1px solid #e2e8f0;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>
                        <button type="button" class="w-8 h-8 flex items-center justify-center rounded text-xs font-bold text-white" style="background:#1488CC; border:1px solid #1488CC;">1</button>
                        <button type="button" class="w-8 h-8 flex items-center justify-center rounded transition-colors hover:bg-slate-50" style="border:1px solid #e2e8f0;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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