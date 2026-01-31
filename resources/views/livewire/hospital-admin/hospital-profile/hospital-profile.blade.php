<div class="flex h-screen bg-gray-50">

    <!-- Main Content -->
    <div class="flex-1 overflow-y-auto">

        <!-- Page Content -->
        <div class="p-8">
            <!-- Header -->
            <div class="mb-6 flex items-center space-x-3">
                <div class="bg-blue-500 text-white p-3 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-800">Hospital Onboarding</h1>
            </div>

            <!-- Alert Banner -->
            @if($this->onboarding_status == 'draft')
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-semibold text-yellow-800">Incomplete Hospital Setup</h3>
                        <p class="mt-1 text-sm text-yellow-700">
                            Your hospital profile is {{ $this->progressPercentage }}% complete. Complete all onboarding steps to submit for review and activate your hospital on our platform.
                        </p>
                    </div>
                </div>
            </div>
            @elseif($this->onboarding_status == 'submitted')
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-semibold text-blue-800">Hospital Profile Submitted for Review</h3>
                        <p class="mt-1 text-sm text-blue-700">
                            Your hospital profile has been submitted for review. Our team will review your profile and get back to you soon.
                        </p>
                    </div>
                </div>
            </div>
            @elseif($this->onboarding_status == 'approved')
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-semibold text-green-800">Hospital Profile Approved</h3>
                        <p class="mt-1 text-sm text-green-700">
                            Your hospital profile has been approved. You can now activate your hospital on our platform.
                        </p>
                    </div>
                </div>
            </div>
            @elseif($this->onboarding_status == 'rejected')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- First Alert -->
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-semibold text-red-800">Hospital Profile Rejected</h3>
                                <p class="mt-1 text-sm text-red-700">
                                    Your hospital profile has been rejected. Please contact support for more information.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Second Alert -->
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-semibold text-red-800">Comments from Admin</h3>
                                <p class="mt-1 text-sm text-red-700">
                                    {{ $this->comments ?? 'No comments provided.' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Progress Card -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">Overall Progress</h2>
                    <span class="text-2xl font-bold text-[#0DA2E7]">{{ $this->progressPercentage }}%</span>
                </div>
                
                <!-- Progress Bar -->
                <div class="w-full bg-gray-200 rounded-full h-3 mb-4">
                    <div class="bg-[#0DA2E7] h-3 rounded-full transition-all duration-500" style="width: {{ $this->progressPercentage }}%"></div>
                </div>
            </div>

            <!-- Steps Section -->
            <div class="mb-4">
                <h3 class="text-base font-medium text-gray-700">Set Up Your Hospital with us in 5 Simple Steps</h3>
            </div>

            <!-- Steps Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($steps as $step)
                <div
                    class="bg-white rounded-lg shadow-sm p-5 hover:shadow-md transition cursor-pointer
                    {{ in_array($onboarding_status,['approved','rejected',]) ? 'opacity-60 pointer-events-none' : '' }}"
                    @if(!in_array($onboarding_status,['approved','rejected']))
                    wire:click="navigateToStep('{{ $step['key'] }}')"
                    @endif>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <!-- Step Icon -->
                            <div class="flex-shrink-0">
                                @if($step['status'] == 'approved')
                                    <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                @elseif($step['status'] == 'rejected')
                                    <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </div>
                                @elseif($step['status'] == 'submitted')
                                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                @else
                                    {{-- Draft status --}}
                                    @if($step['completed'])
                                        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </div>
                                    @else
                                        <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                        </div>
                                    @endif
                                @endif
                            </div>

                            <!-- Step Info -->
                            <div>
                                <h4 class="text-sm font-semibold text-gray-800">{{ $step['title'] }}</h4>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    @if($step['status'] == 'submitted')
                                        Under Review
                                    @else
                                        Actions Required
                                    @endif
                                </p>
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <div class="flex items-center space-x-2">
                            @if($step['status'] == 'approved')
                                <span class="px-3 py-1.5 bg-green-100 text-green-700 text-xs font-medium rounded-full">
                                    Approved
                                </span>
                            @elseif($step['status'] == 'rejected')
                                <span class="px-3 py-1.5 bg-red-100 text-red-700 text-xs font-medium rounded-full">
                                    Rejected
                                </span>
                            @elseif($step['status'] == 'submitted')
                                <span class="px-3 py-1.5 bg-blue-100 text-blue-700 text-xs font-medium rounded-full">
                                    Submitted
                                </span>
                            @else
                                {{-- Draft status --}}
                                @if($step['completed'])
                                    <span class="px-3 py-1.5 bg-green-100 text-green-700 text-xs font-medium rounded-full">
                                        Complete
                                    </span>
                                @else
                                    <span class="px-3 py-1.5 bg-yellow-100 text-yellow-700 text-xs font-medium rounded-full flex items-center">
                                        Incomplete
                                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </span>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Flash Messages -->
            @if (session()->has('message'))
                <div class="mt-6 bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-sm text-green-800">{{ session('message') }}</span>
                    </div>
                </div>
            @endif

            @if (session()->has('error'))
                <div class="mt-6 bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-red-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-sm text-red-800">{{ session('error') }}</span>
                    </div>
                </div>
            @endif
        </div>
    </div>
    
</div>