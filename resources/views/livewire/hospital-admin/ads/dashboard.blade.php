<div class="min-h-screen bg-gray-50 p-4 sm:p-6">

    <style>
        [x-cloak] { display: none !important; }

        .stat-card {
            background: #fff;
            border: 1px solid #e8f4fd;
            border-radius: 14px;
            padding: 18px 22px;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .stat-card:hover {
            box-shadow: 0 6px 24px rgba(13,162,231,0.1);
            transform: translateY(-1px);
        }

        /* Donut chart */
        .donut-ring {
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
        }

        /* Bar chart bars */
        .bar-col {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }
        .bar-wrap {
            width: 48px;
            height: 120px;
            display: flex;
            align-items: flex-end;
            background: #f0faff;
            border-radius: 8px 8px 0 0;
            overflow: hidden;
        }
        .bar-fill {
            width: 100%;
            background: linear-gradient(180deg, #0DA2E7 0%, #0b8fcf 100%);
            border-radius: 6px 6px 0 0;
            opacity: 0.75;
            transition: opacity 0.2s;
        }
        .bar-fill:hover { opacity: 1; }

        /* Sparkline */
        .sparkline { overflow: visible; }

        /* CTR progress */
        .ctr-bar {
            height: 6px;
            border-radius: 999px;
            background: #e8f4fd;
            overflow: hidden;
        }
        .ctr-fill {
            height: 100%;
            background: linear-gradient(90deg, #0DA2E7, #0b8fcf);
            border-radius: 999px;
        }

        /* Revenue mini bar */
        .rev-bar {
            height: 5px;
            border-radius: 999px;
            background: #7c3aed;
            opacity: 0.7;
        }

        /* Budget progress */
        .budget-bar {
            height: 8px;
            border-radius: 999px;
            background: #e8f4fd;
            overflow: hidden;
        }
        .budget-fill {
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, #0DA2E7, #38bdf8);
        }

        /* Horiz bar (revenue by hospital) */
        .horiz-bar {
            height: 10px;
            border-radius: 999px;
            background: linear-gradient(90deg, #0DA2E7, #38bdf8);
        }

        /* Table row hover */
        .tbl-row:hover { background: #f0faff; }

        /* Filter dropdown btn */
        .filter-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: #fff;
            border: 1px solid #d1eaf9;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            color: #334155;
            cursor: pointer;
            transition: all 0.15s;
        }
        .filter-btn:hover { border-color: #0DA2E7; background: #f0faff; color: #0DA2E7; }

        /* Status badge */
        .badge-active   { background:#dcfce7; color:#16a34a; }
        .badge-completed{ background:#f1f5f9; color:#64748b; }
        .badge-pending  { background:#fef9c3; color:#ca8a04; }

        /* Type badge */
        .type-video     { background:#ede9fe; color:#7c3aed; }
        .type-banner    { background:#dbeafe; color:#2563eb; }
        .type-slider    { background:#fce7f3; color:#db2777; }
        .type-sponsored { background:#fef3c7; color:#d97706; }

        /* Chart line animation */
        @keyframes drawLine {
            from { stroke-dashoffset: 600; }
            to   { stroke-dashoffset: 0; }
        }
        .chart-line {
            stroke-dasharray: 600;
            stroke-dashoffset: 600;
            animation: drawLine 1.4s ease forwards;
        }
        @keyframes fadeUp {
            from { opacity:0; transform: translateY(12px); }
            to   { opacity:1; transform: translateY(0); }
        }
        .fade-up { animation: fadeUp 0.5s ease forwards; }
        .fade-up-1 { animation-delay: 0.1s; opacity: 0; }
        .fade-up-2 { animation-delay: 0.2s; opacity: 0; }
        .fade-up-3 { animation-delay: 0.3s; opacity: 0; }
        .fade-up-4 { animation-delay: 0.4s; opacity: 0; }
        .fade-up-5 { animation-delay: 0.5s; opacity: 0; }
        .fade-up-6 { animation-delay: 0.6s; opacity: 0; }
    </style>

    <!-- ═══════════════════════════════════════════
         PAGE HEADER
    ═══════════════════════════════════════════ -->
    <div class="mb-6 fade-up fade-up-1">
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-800 tracking-tight">Ad Performance Overview</h1>
        <p class="text-slate-400 text-sm mt-1">Real-time advertising and click through metrics across health networks.</p>
    </div>

    <!-- ═══════════════════════════════════════════
         FILTER BAR
    ═══════════════════════════════════════════ -->
    <div class="flex flex-wrap items-center gap-3 mb-6">

        <!-- Month Filter -->
        <div x-data="{open:false,val:'November',opts:['This Month','Last Month','Q1 2026','Custom Range']}"
             @click.away="open=false" class="relative">
            <button @click="open=!open" class="filter-btn" :class="open?'!border-[#0DA2E7] !text-[#0DA2E7]':''">
                <i class="fa-regular fa-calendar text-xs" style="color:#0DA2E7;"></i>
                <span x-text="val"></span>
                <i class="fas fa-chevron-down text-xs text-slate-400 transition-transform duration-200" :class="{'rotate-180':open}"></i>
            </button>
            <div x-show="open" x-cloak @click.stop
                 x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="absolute left-0 top-full mt-1 z-[9999] w-44 bg-white border border-slate-200 rounded-xl shadow-2xl p-2 space-y-0.5">
                <template x-for="o in opts" :key="o">
                    <button @click="val=o;open=false" x-text="o"
                            class="w-full text-left px-3 py-2 text-sm rounded-lg transition-colors duration-100"
                            :style="val===o?'background:rgba(13,162,231,0.12);color:#0DA2E7;font-weight:600;':'color:#475569;'"
                            onmouseover="if(!this.style.background.includes('0.12'))this.style.background='rgba(13,162,231,0.06)'"
                            onmouseout="if(!this.style.background.includes('0.12'))this.style.background=''"></button>
                </template>
            </div>
        </div>

        <!-- Hospital Filter -->
        <div x-data="{open:false,val:'All Hospitals',opts:['All Hospitals','Wellness Hospital','Kids Care Centre']}"
             @click.away="open=false" class="relative">
            <button @click="open=!open" class="filter-btn" :class="open?'!border-[#0DA2E7] !text-[#0DA2E7]':''">
                <i class="fas fa-chevron-down text-xs text-slate-400"></i>
                <span x-text="val"></span>
                <i class="fas fa-chevron-down text-xs text-slate-400 transition-transform duration-200" :class="{'rotate-180':open}"></i>
            </button>
            <div x-show="open" x-cloak @click.stop
                 x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="absolute left-0 top-full mt-1 z-[9999] w-48 bg-white border border-slate-200 rounded-xl shadow-2xl p-2 space-y-0.5">
                <template x-for="o in opts" :key="o">
                    <button @click="val=o;open=false" x-text="o"
                            class="w-full text-left px-3 py-2 text-sm rounded-lg transition-colors duration-100"
                            :style="val===o?'background:rgba(13,162,231,0.12);color:#0DA2E7;font-weight:600;':'color:#475569;'"
                            onmouseover="if(!this.style.background.includes('0.12'))this.style.background='rgba(13,162,231,0.06)'"
                            onmouseout="if(!this.style.background.includes('0.12'))this.style.background=''"></button>
                </template>
            </div>
        </div>

        <!-- Status Filter -->
        <div x-data="{open:false,val:'All Status',opts:['All Status','Active','Pending','Completed']}"
             @click.away="open=false" class="relative">
            <button @click="open=!open" class="filter-btn" :class="open?'!border-[#0DA2E7] !text-[#0DA2E7]':''">
                <i class="fas fa-chevron-down text-xs text-slate-400"></i>
                <span x-text="val"></span>
                <i class="fas fa-chevron-down text-xs text-slate-400 transition-transform duration-200" :class="{'rotate-180':open}"></i>
            </button>
            <div x-show="open" x-cloak @click.stop
                 x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="absolute left-0 top-full mt-1 z-[9999] w-44 bg-white border border-slate-200 rounded-xl shadow-2xl p-2 space-y-0.5">
                <template x-for="o in opts" :key="o">
                    <button @click="val=o;open=false" x-text="o"
                            class="w-full text-left px-3 py-2 text-sm rounded-lg transition-colors duration-100"
                            :style="val===o?'background:rgba(13,162,231,0.12);color:#0DA2E7;font-weight:600;':'color:#475569;'"
                            onmouseover="if(!this.style.background.includes('0.12'))this.style.background='rgba(13,162,231,0.06)'"
                            onmouseout="if(!this.style.background.includes('0.12'))this.style.background=''"></button>
                </template>
            </div>
        </div>

        <!-- Type Filter -->
        <div x-data="{open:false,val:'All Type',opts:['All Type','Banner','Video','Slider']}"
             @click.away="open=false" class="relative">
            <button @click="open=!open" class="filter-btn" :class="open?'!border-[#0DA2E7] !text-[#0DA2E7]':''">
                <i class="fas fa-chevron-down text-xs text-slate-400"></i>
                <span x-text="val"></span>
                <i class="fas fa-chevron-down text-xs text-slate-400 transition-transform duration-200" :class="{'rotate-180':open}"></i>
            </button>
            <div x-show="open" x-cloak @click.stop
                 x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="absolute left-0 top-full mt-1 z-[9999] w-44 bg-white border border-slate-200 rounded-xl shadow-2xl p-2 space-y-0.5">
                <template x-for="o in opts" :key="o">
                    <button @click="val=o;open=false" x-text="o"
                            class="w-full text-left px-3 py-2 text-sm rounded-lg transition-colors duration-100"
                            :style="val===o?'background:rgba(13,162,231,0.12);color:#0DA2E7;font-weight:600;':'color:#475569;'"
                            onmouseover="if(!this.style.background.includes('0.12'))this.style.background='rgba(13,162,231,0.06)'"
                            onmouseout="if(!this.style.background.includes('0.12'))this.style.background=''"></button>
                </template>
            </div>
        </div>

        <!-- Create Ad Button -->
        <button class="ml-auto flex items-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-semibold transition-all duration-150 active:scale-[0.98]"
                style="background:#0DA2E7;box-shadow:0 4px 14px rgba(13,162,231,0.3);"
                onmouseover="this.style.background='#0b8fcf';" onmouseout="this.style.background='#0DA2E7';">
            <i class="fas fa-plus text-xs"></i> Create Ad
        </button>
    </div>

    <!-- ═══════════════════════════════════════════
         TOP STATS ROW
    ═══════════════════════════════════════════ -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6 fade-up fade-up-3">

        <div class="stat-card">
            <p class="text-xs text-slate-400 font-medium mb-1">Total Active Ads</p>
            <p class="text-2xl font-bold text-slate-800">124</p>
        </div>

        <div class="stat-card">
            <p class="text-xs text-slate-400 font-medium mb-1">Total Impressions</p>
            <p class="text-2xl font-bold text-slate-800">1.2M</p>
        </div>

        <div class="stat-card">
            <p class="text-xs text-slate-400 font-medium mb-1">Total Clicks</p>
            <p class="text-2xl font-bold text-slate-800">45.8K</p>
        </div>

        <div class="stat-card">
            <p class="text-xs text-slate-400 font-medium mb-1">Avg CTR</p>
            <p class="text-2xl font-bold" style="color:#0DA2E7;">3.82%</p>
        </div>

        <div class="stat-card">
            <p class="text-xs text-slate-400 font-medium mb-1">Total Revenue</p>
            <p class="text-2xl font-bold text-slate-800">₹82,000</p>
        </div>

        <!-- Budget Utilization card (wider on lg) -->
        <div class="stat-card lg:col-span-1 border" style="border-color:rgba(13,162,231,0.2); background:linear-gradient(135deg,#f0faff,#fff);">
            <p class="text-xs font-semibold mb-1" style="color:#0DA2E7;">BUDGET UTILIZATION</p>
            <p class="text-xl font-bold text-slate-800 mb-1"><span style="color:#0DA2E7;">82%</span> <span class="text-xs font-normal text-slate-400">of Q4 Cap</span></p>
            <div class="budget-bar mb-3">
                <div class="budget-fill" style="width:82%;"></div>
            </div>
            <button class="w-full text-xs font-semibold py-1.5 rounded-lg border transition-all duration-150"
                    style="border-color:#0DA2E7;color:#0DA2E7;"
                    onmouseover="this.style.background='rgba(13,162,231,0.08)'" onmouseout="this.style.background=''">
                Increase Budget
            </button>
        </div>

    </div>

    <!-- ═══════════════════════════════════════════
         MIDDLE ROW — Chart + Donut + Mini Charts
    ═══════════════════════════════════════════ -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 mb-6 fade-up fade-up-4">

        <!-- Clicks Over Time Chart -->
        <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4" style="background:linear-gradient(135deg,#0DA2E7,#0b8fcf);">
                <p class="text-xs font-semibold text-blue-100 uppercase tracking-wider mb-0.5">AD PERFORMANCE</p>
                <p class="text-white font-bold text-base">Clicks Over Time</p>
                <div class="flex items-center gap-2 mt-2">
                    <span class="text-xs text-blue-100">TOTAL CLICKS</span>
                    <span class="text-white font-bold text-lg">1,937</span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-green-400/30 text-green-100 font-medium">▲ 4.59%</span>
                </div>
            </div>

            <!-- Range tabs -->
            <div class="px-5 pt-3 pb-1 flex items-center gap-2 border-b border-slate-100">
                @foreach(['7D','30D','90D'] as $r)
                    <button class="text-xs px-2.5 py-1 rounded-lg font-medium transition-colors {{ $r==='7D' ? 'text-white' : 'text-slate-400 hover:text-slate-600' }}"
                            style="{{ $r==='7D' ? 'background:#0DA2E7;' : '' }}">{{ $r }}</button>
                @endforeach
                <div class="ml-auto flex gap-2">
                    @foreach(['Daily','Weekly','Monthly'] as $a)
                        <button class="text-xs px-2.5 py-1 rounded-lg font-medium transition-colors {{ $a==='Daily' ? 'text-white' : 'text-slate-400' }}"
                                style="{{ $a==='Daily' ? 'background:#0DA2E7;' : '' }}">{{ $a }}</button>
                    @endforeach
                </div>
            </div>

            <!-- SVG Chart -->
            <div class="px-5 py-4">
                <svg viewBox="0 0 400 130" class="sparkline w-full" style="height:130px;">
                    <defs>
                        <linearGradient id="areaGrad" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#0DA2E7" stop-opacity="0.2"/>
                            <stop offset="100%" stop-color="#0DA2E7" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    <!-- Grid lines -->
                    @foreach([20,50,80,110] as $y)
                        <line x1="0" y1="{{ $y }}" x2="400" y2="{{ $y }}" stroke="#f1f5f9" stroke-width="1"/>
                    @endforeach
                    <!-- Area fill -->
                    <path d="M0,100 C30,100 40,30 70,40 C100,50 110,90 140,70 C170,50 180,20 210,30 C240,40 260,80 290,60 C320,40 350,15 380,25 L380,130 L0,130 Z"
                          fill="url(#areaGrad)"/>
                    <!-- Line -->
                    <path d="M0,100 C30,100 40,30 70,40 C100,50 110,90 140,70 C170,50 180,20 210,30 C240,40 260,80 290,60 C320,40 350,15 380,25"
                          fill="none" stroke="#0DA2E7" stroke-width="2.5" stroke-linecap="round" class="chart-line"/>
                    <!-- Dots -->
                    @foreach([[0,100],[70,40],[140,70],[210,30],[290,60],[380,25]] as $pt)
                        <circle cx="{{ $pt[0] }}" cy="{{ $pt[1] }}" r="4" fill="#fff" stroke="#0DA2E7" stroke-width="2"/>
                    @endforeach
                </svg>
                <!-- X labels -->
                <div class="flex justify-between text-xs text-slate-400 mt-1 px-1">
                    @foreach(['Feb 11','Feb 13','Feb 14','Feb 15','Feb 17','Feb 18'] as $d)
                        <span>{{ $d }}</span>
                    @endforeach
                </div>
                <!-- Legend -->
                <div class="flex gap-4 mt-3 text-xs text-slate-400">
                    <span class="flex items-center gap-1.5"><span class="w-3 h-0.5 bg-slate-300 inline-block rounded"></span>Impressions</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-0.5 rounded inline-block" style="background:#0DA2E7;"></span>Clicks</span>
                </div>
            </div>
        </div>

        <!-- Ad Status Breakdown Donut -->
        <div class="lg:col-span-3 bg-white rounded-2xl border border-slate-100 shadow-sm flex flex-col">
            <div class="px-5 py-4 border-b border-slate-100">
                <h3 class="font-semibold text-slate-700 text-sm">Ad Status Breakdown</h3>
            </div>
            <div class="flex-1 flex flex-col items-center justify-center p-5 gap-4">
                <!-- Donut SVG -->
                <svg viewBox="0 0 120 120" class="w-32 h-32">
                    <circle cx="60" cy="60" r="45" fill="none" stroke="#e8f4fd" stroke-width="14"/>
                    <!-- Active 75% = 282.7 of 376.99 -->
                    <circle cx="60" cy="60" r="45" fill="none" stroke="#0DA2E7" stroke-width="14"
                            stroke-dasharray="282 95" stroke-dashoffset="0" class="donut-ring"
                            style="transition: stroke-dasharray 1s ease;"/>
                    <!-- Expired 25% -->
                    <circle cx="60" cy="60" r="45" fill="none" stroke="#cbd5e1" stroke-width="14"
                            stroke-dasharray="94 283" stroke-dashoffset="-282" class="donut-ring"/>
                    <text x="60" y="55" text-anchor="middle" font-size="20" font-weight="700" fill="#1e293b">124</text>
                    <text x="60" y="70" text-anchor="middle" font-size="8" fill="#94a3b8">TOTAL</text>
                </svg>
                <!-- Legend -->
                <div class="w-full space-y-2.5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full inline-block" style="background:#0DA2E7;"></span>
                            <span class="text-sm text-slate-600">Active</span>
                        </div>
                        <div class="text-right">
                            <span class="font-bold text-slate-800 text-sm">93</span>
                            <span class="text-xs text-slate-400 ml-1">(75%)</span>
                        </div>
                    </div>
                    <div class="ctr-bar"><div class="ctr-fill" style="width:75%;"></div></div>

                    <div class="flex items-center justify-between mt-1">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-slate-300 inline-block"></span>
                            <span class="text-sm text-slate-600">Expired</span>
                        </div>
                        <div class="text-right">
                            <span class="font-bold text-slate-800 text-sm">31</span>
                            <span class="text-xs text-slate-400 ml-1">(25%)</span>
                        </div>
                    </div>
                    <div class="ctr-bar"><div style="width:25%;height:100%;background:#cbd5e1;border-radius:999px;"></div></div>
                </div>
            </div>
        </div>

        <!-- Revenue Mini Charts (right column) -->
        <div class="lg:col-span-4 flex flex-col gap-4">

            <!-- Revenue by Hospital -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 flex-1">
                <h3 class="font-semibold text-slate-700 text-sm mb-4">Revenue by Hospital</h3>
                <div class="space-y-2.5">
                    @php
                        $hospitals = [
                            ['Apollo Delhi', 95, '#0DA2E7'],
                            ['Fortis Gurugram', 75, '#38bdf8'],
                            ['Medanta', 60, '#7dd3fc'],
                            ['AIIMS', 50, '#bae6fd'],
                            ['Max Saket', 40, '#e0f2fe'],
                            ['Wellness', 30, '#f0faff'],
                            ['BLK Super', 20, '#f0faff'],
                        ];
                    @endphp
                    @foreach($hospitals as [$name, $pct, $color])
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-slate-500 w-28 truncate">{{ $name }}</span>
                            <div class="flex-1 h-2.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-700" style="width:{{ $pct }}%;background:{{ $color }};"></div>
                            </div>
                            <span class="text-xs text-slate-400 w-8 text-right">{{ $pct }}k</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Revenue by Ad Type -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 flex-1">
                <h3 class="font-semibold text-slate-700 text-sm mb-4">Revenue by Ad Type</h3>
                <div class="flex items-center gap-4">
                    <!-- Mini donut -->
                    <svg viewBox="0 0 80 80" class="w-20 h-20 flex-shrink-0">
                        <circle cx="40" cy="40" r="28" fill="none" stroke="#e8f4fd" stroke-width="10"/>
                        <circle cx="40" cy="40" r="28" fill="none" stroke="#0DA2E7" stroke-width="10"
                                stroke-dasharray="88 88" stroke-dashoffset="0" class="donut-ring"/>
                        <circle cx="40" cy="40" r="28" fill="none" stroke="#f59e0b" stroke-width="10"
                                stroke-dasharray="44 132" stroke-dashoffset="-88" class="donut-ring"/>
                        <circle cx="40" cy="40" r="28" fill="none" stroke="#7c3aed" stroke-width="10"
                                stroke-dasharray="30 146" stroke-dashoffset="-132" class="donut-ring"/>
                        <circle cx="40" cy="40" r="28" fill="none" stroke="#10b981" stroke-width="10"
                                stroke-dasharray="14 162" stroke-dashoffset="-162" class="donut-ring"/>
                    </svg>
                    <div class="space-y-1.5 flex-1">
                        @foreach([['Banner Ads','#0DA2E7','50%'],['Video Ads','#f59e0b','25%'],['Slider Ads','#7c3aed','17%'],['Sponsored','#10b981','8%']] as [$type,$color,$pct])
                            <div class="flex items-center justify-between">
                                <span class="flex items-center gap-1.5 text-xs text-slate-500">
                                    <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background:{{ $color }};"></span>
                                    {{ $type }}
                                </span>
                                <span class="text-xs font-semibold text-slate-700">{{ $pct }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- ═══════════════════════════════════════════
         IMPRESSIONS BY HOSPITAL BAR CHART
    ═══════════════════════════════════════════ -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm mb-6 fade-up fade-up-5">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-semibold text-slate-700">Impressions by Hospital</h3>
            <button class="filter-btn text-xs">
                <i class="fa-regular fa-calendar text-xs" style="color:#0DA2E7;"></i>
                November
            </button>
        </div>
        <div class="px-6 py-6">
            @php
                $impBars = [
                    ['MAYO CLINIC', 90],
                    ['JOHNS HOPKINS', 75],
                    ['CEDARS SINAI', 100],
                    ['CLEVELAND', 55],
                    ['MOUNT SINAI', 80],
                    ['ST. JUDE', 45],
                    ['MAYO FLORIDA', 35],
                ];
            @endphp
            <div class="flex items-end justify-around gap-3" style="height:160px;">
                @foreach($impBars as [$label, $h])
                    <div class="bar-col flex-1">
                        <div class="bar-wrap w-full">
                            <div class="bar-fill" style="height:{{ $h }}%;"></div>
                        </div>
                        <span class="text-xs text-slate-400 text-center leading-tight" style="font-size:10px;">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════
         TOP 10 PERFORMING ADS TABLE
    ═══════════════════════════════════════════ -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm fade-up fade-up-6">
        <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
            <h3 class="font-semibold text-slate-700">Top 10 Performing Ads</h3>
            <div class="flex items-center gap-2">
                <!-- Status filter -->
                <div x-data="{open:false,val:'All Status',opts:['All Status','Active','Completed','Pending']}" @click.away="open=false" class="relative">
                    <button @click="open=!open" class="filter-btn text-xs">
                        <span x-text="val"></span>
                        <i class="fas fa-chevron-down text-xs text-slate-400 transition-transform" :class="{'rotate-180':open}"></i>
                    </button>
                    <div x-show="open" @click.stop x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                         class="absolute right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-xl p-2 w-40 space-y-0.5 z-50">
                        <template x-for="o in opts" :key="o">
                            <button @click="val=o;open=false" x-text="o" class="w-full text-left px-3 py-2 text-xs rounded-lg transition-colors"
                                    :style="val===o?'background:rgba(13,162,231,0.1);color:#0DA2E7;':'color:#475569;'"
                                    onmouseover="if(!this.style.background.includes('13'))this.style.background='rgba(13,162,231,0.06)'"
                                    onmouseout="if(!this.style.background.includes('0.1'))this.style.background=''"></button>
                        </template>
                    </div>
                </div>
                <!-- Type filter -->
                <div x-data="{open:false,val:'All Type',opts:['All Type','Banner','Video','Slider','Sponsored']}" @click.away="open=false" class="relative">
                    <button @click="open=!open" class="filter-btn text-xs">
                        <span x-text="val"></span>
                        <i class="fas fa-chevron-down text-xs text-slate-400 transition-transform" :class="{'rotate-180':open}"></i>
                    </button>
                    <div x-show="open" @click.stop x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                         class="absolute right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-xl p-2 w-40 space-y-0.5 z-50">
                        <template x-for="o in opts" :key="o">
                            <button @click="val=o;open=false" x-text="o" class="w-full text-left px-3 py-2 text-xs rounded-lg transition-colors"
                                    :style="val===o?'background:rgba(13,162,231,0.1);color:#0DA2E7;':'color:#475569;'"
                                    onmouseover="if(!this.style.background.includes('13'))this.style.background='rgba(13,162,231,0.06)'"
                                    onmouseout="if(!this.style.background.includes('0.1'))this.style.background=''"></button>
                        </template>
                    </div>
                </div>
                <!-- CSV Export -->
                <button class="filter-btn text-xs" style="color:#0DA2E7;border-color:rgba(13,162,231,0.3);">
                    <i class="fas fa-download text-xs"></i> CSV
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100">
                        <th class="text-left px-6 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">ID</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Ad Title</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Hospital</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Type</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Clicks</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                            CTR % <i class="fas fa-sort text-slate-300 text-xs"></i>
                        </th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Revenue ▼</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Dates</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $ads = [
                            ['AD-001','Cardiac Care Package','Apollo Delhi','Video',8420,4.2,52,'2025-01-01','2025-01-31','active'],
                            ['AD-002','Ortho Excellence','Fortis Gurugram','Banner',6880,3.8,43,'2025-01-15','2025-04-15','active'],
                            ['AD-003','Neurology Consult','AIIMS','Slider',5920,3.4,38,'2025-02-01','2025-06-01','active'],
                            ['AD-004','Oncology Support','Max Saket','Video',4100,2.9,29,'2024-12-01','2025-01-28','completed'],
                            ['AD-005','Diabetes Wellness','Medanta','Banner',3780,2.7,24,'2025-01-18','2025-04-18','active'],
                            ['AD-006','IVF Program','BLK Super','Sponsored',3200,2.4,23,'2025-02-01','2025-01-31','active'],
                            ['AD-007','Joint Replacement','Apollo Delhi','Slider',2980,2.2,18,'2025-01-20','2025-04-28','active'],
                            ['AD-008','Eye Care Plus','Fortis Gurugram','Banner',2400,1.9,14,'2014-11-01','2025-01-31','completed'],
                        ];
                        $typeCls = ['Video'=>'type-video','Banner'=>'type-banner','Slider'=>'type-slider','Sponsored'=>'type-sponsored'];
                    @endphp
                    @foreach($ads as [$id,$title,$hospital,$type,$clicks,$ctr,$rev,$from,$to,$status])
                        <tr class="tbl-row border-b border-slate-50 transition-colors">
                            <td class="px-6 py-3.5 text-xs text-slate-400 font-mono">{{ $id }}</td>
                            <td class="px-4 py-3.5 font-semibold text-slate-700">{{ $title }}</td>
                            <td class="px-4 py-3.5 text-slate-500 text-xs">{{ $hospital }}</td>
                            <td class="px-4 py-3.5">
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $typeCls[$type] ?? '' }}">{{ $type }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-right font-semibold text-slate-700">{{ number_format($clicks) }}</td>
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-2">
                                    <div class="ctr-bar w-16"><div class="ctr-fill" style="width:{{ min($ctr*20,100) }}%;"></div></div>
                                    <span class="text-xs font-medium" style="color:#0DA2E7;">{{ $ctr }}%</span>
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-2">
                                    <div class="rev-bar" style="width:{{ $rev }}px;opacity:0.7;"></div>
                                    <span class="text-xs font-semibold text-slate-700">₹{{ $rev }}k</span>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-xs text-slate-400 leading-relaxed">
                                {{ $from }}<br>{{ $to }}
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-full capitalize
                                    {{ $status==='active' ? 'badge-active' : ($status==='completed' ? 'badge-completed' : 'badge-pending') }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Table Footer -->
        <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between">
            <p class="text-xs text-slate-400">Showing 8 of 124 ads</p>
            <div class="flex items-center gap-1">
                <button class="w-8 h-8 rounded-lg border border-slate-200 text-slate-400 hover:border-sky-300 hover:text-sky-500 transition-colors text-xs flex items-center justify-center">
                    <i class="fas fa-chevron-left text-xs"></i>
                </button>
                @foreach([1,2,3,'...',12] as $p)
                    <button class="w-8 h-8 rounded-lg text-xs font-medium transition-colors
                        {{ $p===1 ? 'text-white' : 'border border-slate-200 text-slate-500 hover:border-sky-300 hover:text-sky-500' }}"
                            style="{{ $p===1 ? 'background:#0DA2E7;' : '' }}">{{ $p }}</button>
                @endforeach
                <button class="w-8 h-8 rounded-lg border border-slate-200 text-slate-400 hover:border-sky-300 hover:text-sky-500 transition-colors text-xs flex items-center justify-center">
                    <i class="fas fa-chevron-right text-xs"></i>
                </button>
            </div>
        </div>
    </div>

</div>