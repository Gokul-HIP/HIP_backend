<div class="space-y-6">

    {{-- HERO --}}
    
   <div class="bg-white rounded-xl shadow-md border overflow-hidden">
        <div class="relative h-48 md:h-52 rounded-xl overflow-hidden">

            <!-- IMAGE -->
            <img 
                src="https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?auto=format&fit=crop&w=1600&q=80"
                class="w-full h-full object-cover rounded-xl"
            >

            <!-- OVERLAY -->
            <div class="absolute inset-0 bg-black/30 rounded-xl"></div>

            <!-- CONTENT -->
            <div class="absolute inset-0 flex items-start justify-between p-6">
                <div class="backdrop-blur-md bg-white/20 border border-white/30 rounded-xl px-4 py-2 shadow-lg">
                    <h1 class="text-white text-2xl font-bold">
                        {{ $hospital->name }} - Hospital
                    </h1>
                    <p class="text-sm text-white/90 mt-1">
                        Manage {{ $hospital->name }} Hospital Details
                    </p>
                </div>

                <!-- ACTION BUTTON -->
                <div class="flex space-x-1">
                    <button class="backdrop-blur-md bg-white/20 border border-white/30 rounded-xl px-3 py-2 shadow-lg">
                        <i class="fas fa-edit text-white"></i>
                    </button>
                </div>
            </div>

        </div>
    </div>

    {{-- MONTH + TURNOVER + QUICK ACTIONS --}}
    <div class="grid grid-cols-12 gap-4 items-start">

        {{-- LEFT COLUMN --}}
        <div class="col-span-12 lg:col-span-3 space-y-4">

            {{-- Month selector --}}
            <div class="relative">
                <button id="monthBtn"
                        class="w-full flex items-center justify-between bg-white border border-[#e6e7eb]
                               rounded-[10px] px-4 h-10 text-sm shadow-[0_2px_6px_rgba(15,23,42,0.03)]">
                    <span id="monthLabel">November 2025</span>
                    <i class="fas fa-chevron-down text-gray-500 text-xs"></i>
                </button>

                <div id="monthMenu"
                     class="absolute left-0 mt-2 w-full bg-white border border-[#e6e7eb]
                            rounded-[10px] shadow-[0_10px_30px_rgba(2,6,23,0.08)] z-50 hidden">
                    <button data-value="November 2025"
                            class="block w-full px-4 py-2.5 text-left text-sm hover:bg-gray-100">
                        November 2025
                    </button>
                    <button data-value="October 2025"
                            class="block w-full px-4 py-2.5 text-left text-sm hover:bg-gray-100">
                        October 2025
                    </button>
                    <button data-value="September 2025"
                            class="block w-full px-4 py-2.5 text-left text-sm hover:bg-gray-100">
                        September 2025
                    </button>
                </div>
            </div>

            {{-- Turnover card --}}
            <div
                class="bg-white rounded-[12px] shadow-[0_10px_30px_rgba(2,6,23,0.06)] border border-[#eef2f6] p-4 h-[150px] flex flex-col justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Previous Day Turnover</p>
                    <p class="text-xs text-gray-400 mt-1">13/11/2025</p>
                </div>
                <p class="text-3xl font-bold mt-3">₹12,600</p>
            </div>
        </div>

        {{-- CENTER COLUMN - QUICK ACTIONS --}}
        <div class="col-span-12 lg:col-span-6">
            <div
                class="bg-white rounded-[12px] shadow-[0_10px_30px_rgba(2,6,23,0.06)]
                       border border-[#eef2f6] p-4">
                <p class="text-lg font-semibold mb-3">Quick Actions</p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                    <a href="{{ route('admin.procedure.index',$hospitalId) }}"
                       class="bg-[#f3f4f6] border border-[#f0f3f6] rounded-[10px] h-[130px]
                              flex flex-col items-center justify-center text-center hover:bg-gray-200 transition">
                        <i class="fas fa-notes-medical text-[26px] text-gray-600"></i>
                        <p class="mt-2 font-semibold text-gray-800 text-sm leading-snug">
                            Manage Procedure
                        </p>
                    </a>

                    <a href="{{ route('admin.view-doctor.ind',$hospitalId) }}"
                       class="bg-[#f3f4f6] border border-[#f0f3f6] rounded-[10px] h-[130px]
                              flex flex-col items-center justify-center text-center hover:bg-gray-200 transition">
                        <i class="fas fa-user-md text-[26px] text-gray-600"></i>
                        <p class="mt-2 font-semibold text-gray-800 text-sm leading-snug">
                            View Doctors
                        </p>
                    </a>

                    <a href="{{ route('admin.view-specialities.index',$hospitalId) }}"
                        class="bg-[#f3f4f6] border border-[#f0f3f6] rounded-[10px] h-[130px]
                               flex flex-col items-center justify-center text-center">
                        <i class="fas fa-heart text-[26px] text-gray-600"></i>
                        <p class="mt-2 font-semibold text-gray-800 text-sm leading-snug">
                            View Specialities
                        </p>
                    </a>

                </div>
            </div>
        </div>

        {{-- RIGHT COLUMN (EMPTY LIKE UI A) --}}
        <div class="col-span-12 lg:col-span-3"></div>
    </div>

    {{-- 2 x 2 CHART GRID --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- Income per Week --}}
        <div
            class="bg-white rounded-[12px] shadow-[0_10px_30px_rgba(2,6,23,0.06)]
                   border border-[#eef2f6] p-4 h-[360px]">
            <div class="flex justify-between mb-3">
                <div>
                    <h3 class="font-semibold text-gray-800">Income per Week</h3>
                    <p class="text-sm text-gray-500">Month View</p>
                </div>
            </div>
            <div class="h-[260px]">
                <canvas id="incomeChart"></canvas>
            </div>
        </div>

        {{-- Daily Transaction Volume --}}
        <div
            class="bg-white rounded-[12px] shadow-[0_10px_30px_rgba(2,6,23,0.06)]
                   border border-[#eef2f6] p-4 h-[360px]">
            <div class="flex justify-between mb-3">
                <div>
                    <h3 class="font-semibold text-gray-800">Daily Transaction Volume</h3>
                    <p class="text-sm text-gray-500">Successful vs Failed</p>
                </div>
            </div>
            <div class="h-[260px]">
                <canvas id="transactionChart"></canvas>
            </div>
        </div>

        {{-- Revenue vs Refunds --}}
        <div
            class="bg-white rounded-[12px] shadow-[0_10px_30px_rgba(2,6,23,0.06)]
                   border border-[#eef2f6] p-4 h-[360px]">
            <div class="flex justify-between mb-3">
                <div>
                    <h3 class="font-semibold text-gray-800">Revenue Vs Refunds</h3>
                    <p class="text-sm text-gray-500">Last 6 months</p>
                </div>
            </div>
            <div class="h-[260px]">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        {{-- Number of Transactions --}}
        <div
            class="bg-white rounded-[12px] shadow-[0_10px_30px_rgba(2,6,23,0.06)]
                   border border-[#eef2f6] p-4 h-[360px]">
            <div>
                <h3 class="font-semibold text-gray-800">Number of Transactions</h3>
                <p class="text-sm text-gray-500">Last 6 months</p>
            </div>
            <div class="h-[260px] mt-2">
                <canvas id="transactionsChart"></canvas>
            </div>
        </div>

    </div>

    {{-- FEEDBACK + REVIEWS --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- Feedback --}}
        <div
            class="bg-white rounded-[12px] shadow-[0_10px_30px_rgba(2,6,23,0.06)]
                   border border-[#eef2f6] p-4">
            <h3 class="font-semibold text-lg mb-3">Feedback Sentiment Distribution</h3>

            <div class="relative flex items-center justify-center h-[220px]">
                <canvas id="feedbackChart" class="max-h-[200px]"></canvas>

                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                    <i class="fa fa-comment text-gray-500 text-3xl mb-1"></i>
                    <span class="text-gray-600 text-sm font-semibold">Total</span>
                    <span class="text-gray-800 text-base font-bold">Feedback</span>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4 mt-6">
                <div class="text-center bg-green-100 p-4 rounded-lg">
                    <div class="text-green-700 text-sm font-semibold">Positive</div>
                    <div class="text-green-700 text-2xl font-bold">72%</div>
                </div>
                <div class="text-center bg-yellow-100 p-4 rounded-lg">
                    <div class="text-yellow-700 text-sm font-semibold">Neutral</div>
                    <div class="text-yellow-700 text-2xl font-bold">18%</div>
                </div>
                <div class="text-center bg-red-100 p-4 rounded-lg">
                    <div class="text-red-700 text-sm font-semibold">Negative</div>
                    <div class="text-red-700 text-2xl font-bold">10%</div>
                </div>
            </div>
        </div>

        {{-- Reviews --}}
        <div
            class="bg-white rounded-[12px] shadow-[0_10px_30px_rgba(2,6,23,0.06)]
                   border border-[#eef2f6] p-4">
            <h3 class="font-semibold text-lg mb-3">Recent Reviews</h3>

            <div class="space-y-4">
                <div class="border rounded-lg p-4">
                    <div class="flex justify-between">
                        <div>
                            <div class="font-semibold">Jane Doe</div>
                            <div class="text-xs text-gray-500">Wellness Hospital</div>
                        </div>
                        <div class="text-yellow-400">
                            <i class="fa fa-star"></i><i class="fa fa-star"></i>
                            <i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i>
                        </div>
                    </div>
                    <p class="text-gray-600 mt-2">"The facility was incredibly clean..."</p>
                </div>

                <div class="border rounded-lg p-4">
                    <div class="flex justify-between">
                        <div>
                            <div class="font-semibold">John Smith</div>
                            <div class="text-xs text-gray-500">Wellness Hospital</div>
                        </div>
                        <div class="text-yellow-400">
                            <i class="fa fa-star"></i><i class="fa fa-star"></i>
                            <i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i>
                        </div>
                    </div>
                    <p class="text-gray-600 mt-2">"Excellent service from start to finish..."</p>
                </div>
            </div>
        </div>

    </div>

</div>