@extends('doctor-admin.layout.doctor-admin')

@section('title', 'Doctor Admin Dashboard')
@section('breadcrumb', 'Dashboard')

@section('content')

{{-- =================== HIP SELECT CSS =================== --}}
<div class="flex-1 overflow-y-auto p-8 bg-slate-50 min-h-screen">

    {{-- ── Page Header ── --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h2 class="text-3xl font-bold text-slate-900 mb-1">Doctor Dashboard</h2>
            <p class="text-slate-500 text-sm">Comprehensive overview of your referral ecosystem and member activity.</p>
        </div>
        <button class="bg-[#26ABE2] text-white px-6 py-2.5 rounded-lg flex items-center gap-2 font-semibold hover:bg-[#1e97cb] transition-shadow shadow-sm w-fit">
            {{-- plus icon --}}
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Add New Referral
        </button>
    </div>

    {{-- ── Stat Cards ── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

        {{-- Total Outgoing --}}
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </div>
                <span class="text-sm font-medium text-slate-500">Total Outgoing</span>
            </div>
            <div class="flex items-end gap-2">
                <h3 class="text-2xl font-bold">1,284</h3>
                <span class="text-emerald-500 text-xs font-bold mb-1 flex items-center gap-0.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
                    12%
                </span>
            </div>
        </div>

        {{-- Total Received --}}
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M12 12V4m0 8l-3-3m3 3l3-3"/>
                    </svg>
                </div>
                <span class="text-sm font-medium text-slate-500">Total Received</span>
            </div>
            <div class="flex items-end gap-2">
                <h3 class="text-2xl font-bold">856</h3>
                <span class="text-emerald-500 text-xs font-bold mb-1 flex items-center gap-0.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
                    5%
                </span>
            </div>
        </div>

        {{-- Pending Actions --}}
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 bg-orange-100 text-orange-600 rounded-lg flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-sm font-medium text-slate-500">Pending Actions</span>
            </div>
            <div class="flex items-end gap-2">
                <h3 class="text-2xl font-bold">24</h3>
                <span class="text-orange-500 text-xs font-bold mb-1">Requiring attention</span>
            </div>
        </div>

        {{-- Active Members --}}
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-5-3.87M9 20H4v-2a4 4 0 015-3.87m6 5.87H9m8-10a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <span class="text-sm font-medium text-slate-500">Active Members</span>
            </div>
            <div class="flex items-end gap-2">
                <h3 class="text-2xl font-bold">3,490</h3>
                <span class="text-emerald-500 text-xs font-bold mb-1 flex items-center gap-0.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
                    18%
                </span>
            </div>
        </div>

    </div>

    {{-- ── Bottom Grid ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        {{-- Referral Trends Bar Chart --}}
        <div class="lg:col-span-1 bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between mb-6">
                <h4 class="font-bold text-slate-800">Referral Trends</h4>
                <select class="bg-slate-50 border border-slate-200 text-xs rounded-lg px-2 py-1 text-slate-500 focus:outline-none">
                    <option>Last 6 Months</option>
                    <option>Last Year</option>
                </select>
            </div>

            {{-- Bars --}}
            <div class="flex items-end justify-between h-48 gap-3">
                @php
                    $bars = [
                        ['label' => 'JAN', 'pct' => 40],
                        ['label' => 'FEB', 'pct' => 65],
                        ['label' => 'MAR', 'pct' => 55],
                        ['label' => 'APR', 'pct' => 90],
                        ['label' => 'MAY', 'pct' => 75],
                        ['label' => 'JUN', 'pct' => 100],
                    ];
                @endphp
                @foreach ($bars as $bar)
                <div class="flex flex-col items-center flex-1 gap-2 h-full justify-end">
                    <div class="w-full flex flex-col justify-end rounded-t-md overflow-hidden bg-slate-100" style="height: 100%">
                        <div class="w-full bg-[#26ABE2] hover:bg-[#1e97cb] transition-all rounded-t-md" style="height: {{ $bar['pct'] }}%"></div>
                    </div>
                    <span class="text-[10px] text-slate-400 font-bold">{{ $bar['label'] }}</span>
                </div>
                @endforeach
            </div>

            {{-- Legend --}}
            <div class="mt-6 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-[#26ABE2] rounded-full"></div>
                        <span class="text-xs text-slate-500">Outgoing</span>
                    </div>
                    <span class="text-xs font-bold">482</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-slate-200 rounded-full"></div>
                        <span class="text-xs text-slate-500">Average Capacity</span>
                    </div>
                    <span class="text-xs font-bold">320</span>
                </div>
            </div>
        </div>

        {{-- Recent Referral Activity Table --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <h4 class="font-bold text-slate-800">Recent Referral Activity</h4>
                <a href="#" class="text-[#26ABE2] text-xs font-bold hover:underline">View All</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Member Details</th>
                            <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Referral Date</th>
                            <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Hospital</th>
                            <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider text-center">Status</th>
                            <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">

                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <p class="text-sm font-bold text-slate-800">Rajesh Kumar</p>
                                <p class="text-[10px] text-slate-400">HIP001234</p>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600 font-medium">12-05-2023</td>
                            <td class="px-6 py-4 text-sm text-slate-600">Wellness Hospitals</td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold uppercase tracking-wide">Pending</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button class="text-slate-400 hover:text-slate-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>

                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <p class="text-sm font-bold text-slate-800">Anita Desai</p>
                                <p class="text-[10px] text-slate-400">HIP001473</p>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600 font-medium">10-05-2023</td>
                            <td class="px-6 py-4 text-sm text-slate-600">Hope Hospitals</td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold uppercase tracking-wide">Accepted</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button class="text-slate-400 hover:text-slate-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>

                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <p class="text-sm font-bold text-slate-800">Vikram Singh</p>
                                <p class="text-[10px] text-slate-400">HIP009876</p>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600 font-medium">08-05-2023</td>
                            <td class="px-6 py-4 text-sm text-slate-600">Wellness Hospitals</td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold uppercase tracking-wide">Completed</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button class="text-slate-400 hover:text-slate-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>

                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <p class="text-sm font-bold text-slate-800">Aneet Sharma</p>
                                <p class="text-[10px] text-slate-400">HIP009876</p>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600 font-medium">05-05-2023</td>
                            <td class="px-6 py-4 text-sm text-slate-600">Hope Hospitals</td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-[10px] font-bold uppercase tracking-wide">Rejected</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button class="text-slate-400 hover:text-slate-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>

            <div class="p-4 bg-slate-50 border-t border-slate-100 text-center">
                <p class="text-[10px] text-slate-400 font-medium uppercase tracking-widest">Showing last 4 entries</p>
            </div>
        </div>

    </div>

</div>
@endsection

