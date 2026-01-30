<div class="p-10 mx-auto">
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
    @if($progressPercentage >= 100)
    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-semibold text-green-800">Hospital Profile Submitted for Review</h3>
                <p class="mt-1 text-sm text-green-700">
                    Your hospital profile has been submitted for review. Our team will review your profile and get back to you soon.
                </p>
            </div>
        </div>
    </div>
    @else
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-semibold text-yellow-800">Complete Hospital Setup</h3>
                <p class="mt-1 text-sm text-yellow-700">
                    Your hospital profile is {{ $progressPercentage }}% complete. Complete all onboarding steps to submit for review and activate your hospital on our platform
                </p>
            </div>
        </div>
    </div>
    @endif

    <!-- Progress Card -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-800">Overall Progress</h2>
            <span class="text-2xl font-bold text-[#0DA2E7]">{{ $progressPercentage }}%</span>
        </div>
        
        <!-- Progress Bar -->
        <div class="w-full bg-gray-200 rounded-full h-3 mb-4">
            <div class="bg-[#0DA2E7] h-3 rounded-full transition-all duration-500" style="width: {{ $progressPercentage }}%"></div>
        </div>

        <!-- Continue Button -->
        {{-- <div class="flex justify-end">
            <button 
                class="px-6 py-2.5 bg-[#0da2e7] hover:bg-[#0b8dc7] text-white rounded-lg font-medium transition-colors">
                Continue
            </button>
        </div> --}}
    </div>

    <!-- Step Indicator -->
    <div class="mb-6">
        <p class="text-sm text-gray-600 mb-3">Step 3 out of 5</p>
        <div class="flex items-center gap-4">
            <!-- Step 1 - Completed -->
            @if($basic_details_completed)
            <div class="flex flex-col items-center">
                <a href="{{ route('hospital.hospital-profile.basic_details') }}">
                    <div class="w-12 h-12 rounded-full bg-green-500 text-white flex items-center justify-center font-semibold text-base">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                    </svg>
                    </div>
                </a>
            </div>
            @else
            <div class="flex flex-col items-center">
                <a href="{{ route('hospital.hospital-profile.basic_details') }}">
                    <div class="w-12 h-12 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-semibold text-base">
                    1
                    </div>
                </a>
            </div>
            @endif
            
            <!-- Connector Line -->
            <div class="flex-1 h-0.5 bg-[#0da2e7]"></div>
            
            <!-- Step 2 - Completed -->
            @if($location_completed)
            <div class="flex flex-col items-center">
                <a href="{{ route('hospital.hospital-profile.hospital_location') }}">
                    <div class="w-12 h-12 rounded-full bg-green-500 text-white flex items-center justify-center font-semibold text-base">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                    </svg>
                    </div>
                </a>
            </div>
            @else
            <div class="flex flex-col items-center">
                <a href="{{ route('hospital.hospital-profile.hospital_location') }}">
                    <div class="w-12 h-12 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-semibold text-base">
                    2
                    </div>
                </a>
            </div>
            @endif
            
            <!-- Connector Line -->
            <div class="flex-1 h-0.5 bg-[#0da2e7]"></div>
            
            <!-- Step 3 - Active -->
            @if($capacity_completed)
            <div class="flex flex-col items-center">
                <a href="{{ route('hospital.hospital-profile.hospital_capacity') }}">
                    <div class="w-12 h-12 rounded-full bg-green-500 text-white flex items-center justify-center font-semibold text-base">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                    </svg>
                    </div>
                </a>
            </div>
            @else
            <div class="flex flex-col items-center">
                <a href="{{ route('hospital.hospital-profile.hospital_capacity') }}">
                    <div class="w-12 h-12 rounded-full bg-[#0DA2E7] text-white flex items-center justify-center font-semibold text-base">
                    3
                    </div>
                </a>
            </div>
            @endif
            
            <!-- Connector Line -->
            <div class="flex-1 h-0.5 bg-gray-300"></div>
            
            <!-- Step 4 -->
            @if($medical_completed)
            <div class="flex flex-col items-center">
                <a href="{{ route('hospital.hospital-profile.medical_compliance') }}">
                    <div class="w-12 h-12 rounded-full bg-green-500 text-white flex items-center justify-center font-semibold text-base">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                    </svg>
                    </div>
                </a>
            </div>
            @else
            <div class="flex flex-col items-center">
                <a href="{{ route('hospital.hospital-profile.medical_compliance') }}">
                    <div class="w-12 h-12 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-semibold text-base">
                    4
                </div>
                </a>
            </div>
            @endif
            
            <!-- Connector Line -->
            <div class="flex-1 h-0.5 bg-gray-300"></div>
            
            <!-- Step 5 -->
            @if($contact_completed)
            <div class="flex flex-col items-center">
                <a href="{{ route('hospital.hospital-profile.contact-details') }}">
                    <div class="w-12 h-12 rounded-full bg-green-500 text-white flex items-center justify-center font-semibold text-base">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                    </svg>
                    </div>
                </a>
            </div>
            @else
            <div class="flex flex-col items-center">
                <a href="{{ route('hospital.hospital-profile.contact-details') }}">
                    <div class="w-12 h-12 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-semibold text-base">
                    5
                    </div>
                </a>
            </div>
            @endif
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-6">Hospital Capacity</h3>
        
        <form wire:submit.prevent="save">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Bed Strength -->
                <div>
                    <label for="bed_strength" class="block text-sm font-medium text-gray-700 mb-2">
                        Bed Strength<span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="number" 
                        id="bed_strength"
                        wire:model="bed_strength"
                        class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder=""
                        min="1"
                    >
                    @error('bed_strength') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- ICU Beds -->
                <div>
                    <label for="icu_beds" class="block text-sm font-medium text-gray-700 mb-2">
                        ICU Beds<span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="number" 
                        id="icu_beds"
                        wire:model="icu_beds"
                        class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder=""
                        min="0"
                    >
                    @error('icu_beds') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Operating Theatres -->
                <div>
                    <label for="operating_theatres" class="block text-sm font-medium text-gray-700 mb-2">
                        Operating Theatres<span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="number" 
                        id="operating_theatres"
                        wire:model="operating_theatres"
                        class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder=""
                        min="0"
                    >
                    @error('operating_theatres') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Ambulance Available -->
                <div>
                    <label for="ambulance_available" class="block text-sm font-medium text-gray-700 mb-2">
                        Ambulance Available<span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="number" 
                        id="ambulance_available"
                        wire:model="ambulance_available"
                        class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder=""
                        min="0"
                    >
                    @error('ambulance_available') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Note -->
                <div class="md:col-span-2">
                    <p class="text-sm text-gray-600">* Values must be numeric and greater than zero</p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end gap-3 items-center pt-6 mt-6 border-t">
                <a 
                    href="{{ route('hospital.hospital-profile.hospital_location') }}"
                    class="px-8 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors"
                >
                    Back
                </a>
                <button 
                    type="submit"
                    class="px-8 py-2.5 bg-gray-400 hover:bg-gray-500 text-white rounded-lg font-medium transition-colors"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="save">Next</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </button>
            </div>
        </form>
    </div>
</div>