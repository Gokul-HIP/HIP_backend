<div class="p-6 min-h-screen bg-slate-50">
    <div class="max-w-[1600px] mx-auto">

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
            <!-- Left Panel - Document List -->
            <div class="xl:col-span-1 space-y-6">
                <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-lg font-bold text-slate-900">{{ $hospital->name }}</h2>
                        @if($hospital->onboarding_status == 'submitted')
                        <span class="px-3 py-1 bg-amber-100 text-amber-700 text-xs font-semibold rounded">PENDING APPROVAL</span>
                        @elseif($hospital->onboarding_status == 'approved')
                        <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded">APPROVED</span>
                        @elseif($hospital->onboarding_status == 'rejected')
                        <span class="px-3 py-1 bg-red-100 text-red-700 text-xs font-semibold rounded">REJECTED</span>
                        @endif
                    </div>
                    <p class="text-sm text-slate-600 mb-5 leading-relaxed">Submitted on {{ $hospital->created_at->format('M d, Y') }}. Please review all 5 mandatory documents for verification.</p>
                    
                    <div class="space-y-3">
                        <!-- Document 1 - Hospital Profile -->
                        <div 
                            wire:click="selectDocument('hospital_details')"
                            class="p-4 rounded-lg border-2 cursor-pointer transition-all hover:shadow-sm
                            {{ $selectedDocument === 'hospital_details' ? 'border-primary bg-sky-50/50' : 'border-slate-200 hover:border-slate-300 bg-white' }}" >
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-slate-800 flex items-center justify-center text-white flex-shrink-0">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm0-14c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm0 6c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-slate-900">Hospital Details</p>
                                    <p class="text-xs text-slate-500">Basic Hospital Details</p>
                                </div>
                                @if($hospital->basic_details_status == 'submitted')
                                <span class="px-2.5 py-1 bg-slate-100 text-slate-600 text-[10px] font-semibold rounded uppercase flex-shrink-0">PENDING REVIEW</span>
                                @elseif($hospital->basic_details_status == 'approved')
                                <span class="px-2.5 py-1 bg-green-100 text-green-700 text-[10px] font-semibold rounded uppercase flex-shrink-0">APPROVED</span>
                                @elseif($hospital->basic_details_status == 'rejected')
                                <span class="px-2.5 py-1 bg-red-100 text-red-700 text-[10px] font-semibold rounded uppercase flex-shrink-0">REJECTED</span>
                                @endif
                            </div>
                        </div>

                        <!-- Document 2 - Hospital Location -->
                        <div 
                            wire:click="selectDocument('hospital_location')"
                            class="p-4 rounded-lg border-2 cursor-pointer transition-all hover:shadow-sm
                            {{ $selectedDocument === 'hospital_location' ? 'border-primary bg-sky-50/50' : 'border-slate-200 hover:border-slate-300 bg-white' }}">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600 flex-shrink-0">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-slate-900">Hospital Location</p>
                                    <p class="text-xs text-slate-500">Basic Hospital Location</p>
                                </div>
                                @if($hospital->location_status == 'submitted')
                                <span class="px-2.5 py-1 bg-slate-100 text-slate-600 text-[10px] font-semibold rounded uppercase flex-shrink-0">PENDING REVIEW</span>
                                @elseif($hospital->location_status == 'approved')
                                <span class="px-2.5 py-1 bg-green-100 text-green-700 text-[10px] font-semibold rounded uppercase flex-shrink-0">APPROVED</span>
                                @elseif($hospital->location_status == 'rejected')
                                <span class="px-2.5 py-1 bg-red-100 text-red-700 text-[10px] font-semibold rounded uppercase flex-shrink-0">REJECTED</span>
                                @endif
                            </div>
                        </div>

                          <!-- Document 3 - Hospital Capacity -->
                          <div 
                          wire:click="selectDocument('hospital_capacity')"
                          class="p-4 rounded-lg border-2 cursor-pointer transition-all hover:shadow-sm
                          {{ $selectedDocument === 'hospital_capacity' ? 'border-primary bg-sky-50/50' : 'border-slate-200 hover:border-slate-300 bg-white' }}">
                          <div class="flex items-center gap-3">
                              <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600 flex-shrink-0">
                                  <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                      <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                                  </svg>
                              </div>
                              <div class="flex-1 min-w-0">
                                  <p class="text-sm font-semibold text-slate-900">Hospital Capacity</p>
                                  <p class="text-xs text-slate-500">Basic Hospital Capacity</p>
                              </div>
                              @if($hospital->capacity_status == 'submitted')
                              <span class="px-2.5 py-1 bg-slate-100 text-slate-600 text-[10px] font-semibold rounded uppercase flex-shrink-0">PENDING REVIEW</span>
                              @elseif($hospital->capacity_status == 'approved')
                              <span class="px-2.5 py-1 bg-green-100 text-green-700 text-[10px] font-semibold rounded uppercase flex-shrink-0">APPROVED</span>
                              @elseif($hospital->capacity_status == 'rejected')
                              <span class="px-2.5 py-1 bg-red-100 text-red-700 text-[10px] font-semibold rounded uppercase flex-shrink-0">REJECTED</span>
                              @endif
                          </div>
                      </div>

                        <!-- Document 4 - Medical Compliance -->
                        <div 
                            wire:click="selectDocument('medical_compliance')"
                            class="p-4 rounded-lg border-2 cursor-pointer transition-all hover:shadow-sm
                            {{ $selectedDocument === 'medical_compliance' ? 'border-primary bg-sky-50/50' : 'border-slate-200 hover:border-slate-300 bg-white' }}" >
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-slate-800 flex items-center justify-center text-white flex-shrink-0">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-slate-900">Medical Compliance</p>
                                    <p class="text-xs text-slate-500">Basic Medical Compliance</p>
                                </div>
                                @if($hospital->medical_status == 'submitted')
                                <span class="px-2.5 py-1 bg-slate-100 text-slate-600 text-[10px] font-semibold rounded uppercase flex-shrink-0">PENDING REVIEW</span>
                                @elseif($hospital->medical_status == 'approved')
                                <span class="px-2.5 py-1 bg-green-100 text-green-700 text-[10px] font-semibold rounded uppercase flex-shrink-0">APPROVED</span>
                                @elseif($hospital->medical_status == 'rejected')
                                <span class="px-2.5 py-1 bg-red-100 text-red-700 text-[10px] font-semibold rounded uppercase flex-shrink-0">REJECTED</span>
                                @endif
                            </div>
                        </div>
                    
                        <!-- Document 5 - Contact Details -->
                        <div 
                            wire:click="selectDocument('contact_details')"
                            class="p-4 rounded-lg border-2 cursor-pointer transition-all hover:shadow-sm
                            {{ $selectedDocument === 'contact_details' ? 'border-primary bg-sky-50/50' : 'border-slate-200 hover:border-slate-300 bg-white' }}">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600 flex-shrink-0">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 14h-3v3h-2v-3H8v-2h3v-3h2v3h3v2zm-3-7V3.5L18.5 9H13z"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-slate-900">Contact Details</p>
                                    <p class="text-xs text-slate-500">Basic Contact Details</p>
                                </div>
                                @if($hospital->contact_status == 'submitted')
                                <span class="px-2.5 py-1 bg-slate-100 text-slate-600 text-[10px] font-semibold rounded uppercase flex-shrink-0">PENDING REVIEW</span>
                                @elseif($hospital->contact_status == 'approved')
                                <span class="px-2.5 py-1 bg-green-100 text-green-700 text-[10px] font-semibold rounded uppercase flex-shrink-0">APPROVED</span>
                                @elseif($hospital->contact_status == 'rejected')
                                <span class="px-2.5 py-1 bg-red-100 text-red-700 text-[10px] font-semibold rounded uppercase flex-shrink-0">REJECTED</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel - Document Preview & Review -->
            <div class="xl:col-span-2 space-y-6">
                <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden flex flex-col md:flex-row md:min-h-[700px]">
                    <!-- Document Preview -->
                    <div class="flex-1 bg-slate-50 flex flex-col">
                        <div class="p-4 bg-white border-b border-slate-200 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-slate-700" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                                </svg>
                                <span class="text-sm font-semibold text-slate-900">Preview</span>
                            </div>
                            {{-- <div class="flex items-center gap-2">
                                <button class="p-1.5 hover:bg-slate-100 rounded transition-colors text-slate-600">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                                        <path d="M12 10h-2v2H9v-2H7V9h2V7h1v2h2v1z"/>
                                    </svg>
                                </button>
                                <button class="p-1.5 hover:bg-slate-100 rounded transition-colors text-slate-600">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>
                                    </svg>
                                </button>
                            </div> --}}
                        </div>
                        @if($selectedDocument == 'hospital_details')
                        <div class="p-6 space-y-5">

                            <h3 class="font-semibold text-slate-900 text-base">
                                Hospital Details
                            </h3>
                    
                            <div class="grid grid-cols-1 gap-3">
                                <label for="hospital_name" class="block text-sm font-medium text-gray-700 mb-1">Hospital Name</label>
                                <input type="text" readonly class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-sm"
                                    value="{{ $hospital_name }}">
                        
                                <label for="hospital_subtitle" class="block text-sm font-medium text-gray-700 mb-1">Hospital Subtitle</label>
                                <input type="text" readonly class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-sm"
                                    value="{{ $hospital_subtitle }}">
                        
                                <label for="ownership" class="block text-sm font-medium text-gray-700 mb-1">Ownership</label>
                                <input type="text" readonly class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-sm"
                                    value="{{ $ownership }}">
                        
                                <label for="establishment_type" class="block text-sm font-medium text-gray-700 mb-1">Establishment Type</label>
                                <input type="text" readonly class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-sm"
                                    value="{{ $establishment_type }}">
                            </div>
                        
                            <div class="pt-2">
                                <label for="hospital_about" class="block text-sm font-medium text-gray-700 mb-1">About Hospital</label>
                                <p class="text-sm text-slate-600 leading-relaxed">
                                    {{ $hospital_about }}
                                </p>
                            </div>
                        
                            @if($hospital_logo_url)
                                <div class="pt-3 flex items-center gap-4">
                                    <a href="{{ $hospital_logo_url }}" target="_blank">
                                        <img
                                            src="{{ $hospital_logo_url }}"
                                            alt="{{ $hospital_name }}"
                                            class="w-32 h-32 object-cover rounded-xl border shadow-sm">
                                    </a>
                        
                                    <div>
                                        <p class="text-sm font-medium text-slate-700">Hospital Logo</p>
                                        <p class="text-xs text-slate-400">Click to view full size</p>
                                    </div>
                                </div>
                            @endif

                            <div class="pt-3 border-t border-slate-200">
                                <div class="grid grid-cols-2 gap-3">
                                    <button class="flex items-center justify-center gap-2 py-2.5 px-3 rounded-lg border-2 border-green-500 text-green-600
                                     font-semibold text-sm hover:bg-green-50 transition-all" wire:click="approveDocument('hospital_details')">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                        </svg>
                                        Approve Hospital Details
                                    </button>
                                    <button class="flex items-center justify-center gap-2 py-2.5 px-3 rounded-lg border-2 border-red-500 text-red-600 font-semibold
                                    text-sm hover:bg-red-50 transition-all" wire:click="rejectDocument('hospital_details')">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/>
                                        </svg>
                                        Reject Hospital Details
                                    </button>
                                </div>
                            </div>
                        
                        </div>
                        @endif

                        @if($selectedDocument == 'hospital_location')
                        <div class="p-6 space-y-5">

                            <h3 class="font-semibold text-slate-900 text-base">
                                Hospital Location
                            </h3>
                    
                            <div class="grid grid-cols-1 gap-3">
                        
                                <label for="city" class="block text-sm font-medium text-gray-700 mb-1">City</label>
                                <input type="text" readonly class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-sm"
                                    value="{{ $city }}">
                        
                                <label for="area" class="block text-sm font-medium text-gray-700 mb-1">Area</label>
                                <input type="text" readonly class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-sm"
                                    value="{{ $area }}">
                        
                                <label for="pincode" class="block text-sm font-medium text-gray-700 mb-1">Pincode</label>
                                <input type="text" readonly class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-sm"
                                    value="{{ $pincode }}">
                            </div>
                        
                            <div class="pt-2">
                                <label for="hospital_about" class="block text-sm font-medium text-gray-700 mb-1">Hospital Address</label>
                                <p class="text-sm text-slate-600 leading-relaxed">
                                    {{ $hospital_address }}
                                </p>
                            </div>

                            <div class="pt-3 border-t border-slate-200">
                                <div class="grid grid-cols-2 gap-3">
                                    <button class="flex items-center justify-center gap-2 py-2.5 px-3 rounded-lg border-2 border-green-500 text-green-600
                                     font-semibold text-sm hover:bg-green-50 transition-all" wire:click="approveDocument('hospital_location')">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                        </svg>
                                        Approve Hospital Location
                                    </button>
                                    <button class="flex items-center justify-center gap-2 py-2.5 px-3 rounded-lg border-2 border-red-500 text-red-600 font-semibold
                                    text-sm hover:bg-red-50 transition-all" wire:click="rejectDocument('hospital_location')">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/>
                                        </svg>
                                        Reject Hospital Location
                                    </button>
                                </div>
                            </div>
                        
                        </div>
                        @endif

                        @if($selectedDocument == 'hospital_capacity')
                        <div class="p-6 space-y-5">

                            <h3 class="font-semibold text-slate-900 text-base">
                                Hospital Capacity
                            </h3>
                    
                            <div class="grid grid-cols-1 gap-3">
                        
                                <label for="bed_strength" class="block text-sm font-medium text-gray-700 mb-1">Bed Strength</label>
                                <input type="text" readonly class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-sm"
                                    value="{{ $bed_strength }}">
                        
                                <label for="icu_beds" class="block text-sm font-medium text-gray-700 mb-1">ICU Beds</label>
                                <input type="text" readonly class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-sm"
                                    value="{{ $icu_beds }}">
                        
                                <label for="operating_theatres" class="block text-sm font-medium text-gray-700 mb-1">Operating Theatres</label>
                                <input type="text" readonly class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-sm"
                                    value="{{ $operating_theatres }}">

                                <label for="ambulance_available" class="block text-sm font-medium text-gray-700 mb-1">Ambulance Available</label>
                                <input type="text" readonly class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-sm"
                                    value="{{ $ambulance_available }}">
                            </div>

                            <div class="pt-3 border-t border-slate-200">
                                <div class="grid grid-cols-2 gap-3">
                                    <button class="flex items-center justify-center gap-2 py-2.5 px-3 rounded-lg border-2 border-green-500 text-green-600
                                     font-semibold text-sm hover:bg-green-50 transition-all" wire:click="approveDocument('hospital_capacity')">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                        </svg>
                                        Approve Hospital Capacity
                                    </button>
                                    <button class="flex items-center justify-center gap-2 py-2.5 px-3 rounded-lg border-2 border-red-500 text-red-600 font-semibold
                                    text-sm hover:bg-red-50 transition-all" wire:click="rejectDocument('hospital_capacity')">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/>
                                        </svg>
                                        Reject Hospital Capacity
                                    </button>
                                </div>
                            </div>
                        
                        </div>
                        @endif

                        @if($selectedDocument == 'medical_compliance')
                        <div class="flex-1 p-8 overflow-y-auto">
                            <h3 class="font-semibold text-slate-900 text-base mb-6">Medical Compliance</h3>
                            <div class="max-w-5xl mx-auto">
                                <!-- Grid layout for images -->
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                                    
                                    <!-- Registration Certificate -->
                                    @if($registration_certificate)
                                    <div class="bg-white rounded-lg border border-slate-200 p-4">
                                        <h3 class="text-sm font-semibold text-slate-900 mb-3">Registration Certificate</h3>
                                        <a href="{{ asset('storage/hospital/documents/' . $registration_certificate) }}" target="_blank" class="block group">
                                            <div class="relative overflow-hidden rounded-lg border-2 border-slate-200 hover:border-primary transition-all aspect-[4/3] bg-slate-50">
                                                @if(strtolower(pathinfo($registration_certificate, PATHINFO_EXTENSION)) == 'pdf')
                                                    <!-- PDF Icon -->
                                                    <div class="w-full h-full flex items-center justify-center">
                                                        <svg class="w-20 h-20 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zM6 20V4h7v5h5v11H6zm10-10h-4v1h3c.55 0 1 .45 1 1v3c0 .55-.45 1-1 1h-3v2h-1v-5.5c0-.28.22-.5.5-.5H16v-2zm-4 3h3v-1h-3v1z"/>
                                                        </svg>
                                                    </div>
                                                @else
                                                    <!-- Image Preview -->
                                                    <img 
                                                        src="{{ asset('storage/hospital/documents/' . $registration_certificate) }}" 
                                                        alt="Registration Certificate"
                                                        class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-300"
                                                    >
                                                @endif
                                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-all flex items-center justify-center">
                                                    <div class="opacity-0 group-hover:opacity-100 transition-opacity bg-white rounded-full p-3 shadow-lg">
                                                        <svg class="w-6 h-6 text-slate-700" fill="none" stroke="CurrentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                                                        </svg>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="text-xs text-slate-500 mt-2 flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M19 19H5V5h7V3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z"/>
                                                </svg>
                                                Click to view full size
                                            </p>
                                        </a>
                                    </div>
                                    @endif

                                    <!-- Ownership Proof -->
                                    @if($ownership_proof)
                                    <div class="bg-white rounded-lg border border-slate-200 p-4">
                                        <h3 class="text-sm font-semibold text-slate-900 mb-3">Ownership Proof</h3>
                                        <a href="{{ asset('storage/hospital/documents/' . $ownership_proof) }}" target="_blank" class="block group">
                                            <div class="relative overflow-hidden rounded-lg border-2 border-slate-200 hover:border-primary transition-all aspect-[4/3] bg-slate-50">
                                                @if(strtolower(pathinfo($ownership_proof, PATHINFO_EXTENSION)) == 'pdf')
                                                    <!-- PDF Icon -->
                                                    <div class="w-full h-full flex items-center justify-center">
                                                        <svg class="w-20 h-20 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zM6 20V4h7v5h5v11H6zm10-10h-4v1h3c.55 0 1 .45 1 1v3c0 .55-.45 1-1 1h-3v2h-1v-5.5c0-.28.22-.5.5-.5H16v-2zm-4 3h3v-1h-3v1z"/>
                                                        </svg>
                                                    </div>
                                                @else
                                                    <!-- Image Preview -->
                                                    <img 
                                                        src="{{ asset('storage/hospital/documents/' . $ownership_proof) }}" 
                                                        alt="Ownership Proof"
                                                        class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-300"
                                                    >
                                                @endif
                                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-all flex items-center justify-center">
                                                    <div class="opacity-0 group-hover:opacity-100 transition-opacity bg-white rounded-full p-3 shadow-lg">
                                                        <svg class="w-6 h-6 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                                                        </svg>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="text-xs text-slate-500 mt-2 flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M19 19H5V5h7V3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z"/>
                                                </svg>
                                                Click to view full size
                                            </p>
                                        </a>
                                    </div>
                                    @endif

                                    <!-- Accreditation Certificate -->
                                    @if($accreditation_certificate)
                                    <div class="bg-white rounded-lg border border-slate-200 p-4">
                                        <h3 class="text-sm font-semibold text-slate-900 mb-3">Accreditation Certificate</h3>
                                        <a href="{{ asset('storage/hospital/documents/' . $accreditation_certificate) }}" target="_blank" class="block group">
                                            <div class="relative overflow-hidden rounded-lg border-2 border-slate-200 hover:border-primary transition-all aspect-[4/3] bg-slate-50">
                                                @if(strtolower(pathinfo($accreditation_certificate, PATHINFO_EXTENSION)) == 'pdf')
                                                    <!-- PDF Icon -->
                                                    <div class="w-full h-full flex items-center justify-center">
                                                        <svg class="w-20 h-20 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zM6 20V4h7v5h5v11H6zm10-10h-4v1h3c.55 0 1 .45 1 1v3c0 .55-.45 1-1 1h-3v2h-1v-5.5c0-.28.22-.5.5-.5H16v-2zm-4 3h3v-1h-3v1z"/>
                                                        </svg>
                                                    </div>
                                                @else
                                                    <!-- Image Preview -->
                                                    <img 
                                                        src="{{ asset('storage/hospital/documents/' . $accreditation_certificate) }}" 
                                                        alt="Accreditation Certificate"
                                                        class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-300"
                                                    >
                                                @endif
                                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-all flex items-center justify-center">
                                                    <div class="opacity-0 group-hover:opacity-100 transition-opacity bg-white rounded-full p-3 shadow-lg">
                                                        <svg class="w-6 h-6 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                                                        </svg>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="text-xs text-slate-500 mt-2 flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M19 19H5V5h7V3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z"/>
                                                </svg>
                                                Click to view full size
                                            </p>
                                        </a>
                                    </div>
                                    @endif

                                    <!-- Fire Safety Certificate -->
                                    @if($fire_safety_certificate)
                                    <div class="bg-white rounded-lg border border-slate-200 p-4">
                                        <h3 class="text-sm font-semibold text-slate-900 mb-3">Fire Safety Certificate</h3>
                                        <a href="{{ asset('storage/hospital/documents/' . $fire_safety_certificate) }}" target="_blank" class="block group">
                                            <div class="relative overflow-hidden rounded-lg border-2 border-slate-200 hover:border-primary transition-all aspect-[4/3] bg-slate-50">
                                                @if(strtolower(pathinfo($fire_safety_certificate, PATHINFO_EXTENSION)) == 'pdf')
                                                    <!-- PDF Icon -->
                                                    <div class="w-full h-full flex items-center justify-center">
                                                        <svg class="w-20 h-20 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zM6 20V4h7v5h5v11H6zm10-10h-4v1h3c.55 0 1 .45 1 1v3c0 .55-.45 1-1 1h-3v2h-1v-5.5c0-.28.22-.5.5-.5H16v-2zm-4 3h3v-1h-3v1z"/>
                                                        </svg>
                                                    </div>
                                                @else
                                                    <!-- Image Preview -->
                                                    <img 
                                                        src="{{ asset('storage/hospital/documents/' . $fire_safety_certificate) }}" 
                                                        alt="Fire Safety Certificate"
                                                        class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-300"
                                                    >
                                                @endif
                                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-all flex items-center justify-center">
                                                    <div class="opacity-0 group-hover:opacity-100 transition-opacity bg-white rounded-full p-3 shadow-lg">
                                                        <svg class="w-6 h-6 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                                                        </svg>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="text-xs text-slate-500 mt-2 flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M19 19H5V5h7V3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z"/>
                                                </svg>
                                                Click to view full size
                                            </p>
                                        </a>
                                    </div>
                                    @endif

                                    <!-- Ownership Proof Document -->
                                    @if($ownership_proof_doc)
                                    <div class="bg-white rounded-lg border border-slate-200 p-4">
                                        <h3 class="text-sm font-semibold text-slate-900 mb-3">Ownership Proof Document</h3>
                                        <a href="{{ asset('storage/hospital/documents/' . $ownership_proof_doc) }}" target="_blank" class="block group">
                                            <div class="relative overflow-hidden rounded-lg border-2 border-slate-200 hover:border-primary transition-all aspect-[4/3] bg-slate-50">
                                                @if(strtolower(pathinfo($ownership_proof_doc, PATHINFO_EXTENSION)) == 'pdf')
                                                    <!-- PDF Icon -->
                                                    <div class="w-full h-full flex items-center justify-center">
                                                        <svg class="w-20 h-20 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zM6 20V4h7v5h5v11H6zm10-10h-4v1h3c.55 0 1 .45 1 1v3c0 .55-.45 1-1 1h-3v2h-1v-5.5c0-.28.22-.5.5-.5H16v-2zm-4 3h3v-1h-3v1z"/>
                                                        </svg>
                                                    </div>
                                                @else
                                                    <!-- Image Preview -->
                                                    <img 
                                                        src="{{ asset('storage/hospital/documents/' . $ownership_proof_doc) }}" 
                                                        alt="Ownership Proof Document"
                                                        class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-300"
                                                    >
                                                @endif
                                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-all flex items-center justify-center">
                                                    <div class="opacity-0 group-hover:opacity-100 transition-opacity bg-white rounded-full p-3 shadow-lg">
                                                        <svg class="w-6 h-6 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                                                        </svg>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="text-xs text-slate-500 mt-2 flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M19 19H5V5h7V3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z"/>
                                                </svg>
                                                Click to view full size
                                            </p>
                                        </a>
                                    </div>
                                    @endif

                                </div>

                                <!-- Insurance Policy Number (Full Width) -->
                                @if($insurance_policy_number)
                                <div class="bg-white rounded-lg border border-slate-200 p-6">
                                        <h3 class="text-sm font-semibold text-slate-900 mb-3">Insurance Policy Number</h3>
                                        <input 
                                            type="text" 
                                            value="{{ $insurance_policy_number }}" 
                                            readonly
                                            class="w-full px-4 py-3 rounded-lg border border-slate-200 bg-slate-50 text-slate-900 font-mono text-base focus:outline-none cursor-default"
                                        >
                                    </div>
                                    @endif
                                </div>
                                <div class="pt-3 border-t border-slate-200">
                                    <div class="grid grid-cols-2 gap-3">
                                        <button class="flex items-center justify-center gap-2 py-2.5 px-3 rounded-lg border-2 border-green-500 text-green-600
                                        font-semibold text-sm hover:bg-green-50 transition-all" wire:click="approveDocument('medical_compliance')">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                            </svg>
                                            Approve Medical Compliance
                                        </button>
                                        <button class="flex items-center justify-center gap-2 py-2.5 px-3 rounded-lg border-2 border-red-500 text-red-600 font-semibold
                                        text-sm hover:bg-red-50 transition-all" wire:click="rejectDocument('medical_compliance')">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/>
                                            </svg>
                                            Reject Medical Compliance
                                        </button>
                                    </div>
                                </div>
                        </div>
                        @endif

                        @if($selectedDocument == 'contact_details')
                        <div class="p-6 space-y-5">

                            <h3 class="font-semibold text-slate-900 text-base">
                                Contact Details
                            </h3>
                    
                            <div class="grid grid-cols-1 gap-3">
                        
                                <label for="hospital_admin_name" class="block text-sm font-medium text-gray-700 mb-1">Hospital Admin Name</label>
                                <input type="text" readonly class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-sm"
                                    value="{{ $hospital_admin_name }}">
                        
                                <label for="hospital_admin_contact" class="block text-sm font-medium text-gray-700 mb-1">Hospital Admin Contact</label>
                                <input type="text" readonly class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-sm"
                                    value="{{ $hospital_admin_contact }}">

                                <label for="hospital_admin_emergency_contact" class="block text-sm font-medium text-gray-700 mb-1">Hospital Admin Emergency Contact</label>
                                <input type="text" readonly class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-sm"
                                    value="{{ $hospital_admin_emergency_contact }}">
                        
                                <label for="hospital_admin_email" class="block text-sm font-medium text-gray-700 mb-1">Hospital Admin Email</label>
                                <input type="text" readonly class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-sm"
                                    value="{{ $hospital_admin_email }}">

                                {{-- <label for="hospital_admin_address" class="block text-sm font-medium text-gray-700 mb-1">Hospital Admin Address</label>
                                <input type="text" readonly class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-sm"
                                    value="{{ $hospital_admin_address }}"> --}}

                                <label for="hospital_admin_pincode" class="block text-sm font-medium text-gray-700 mb-1">Hospital Admin Pincode</label>
                                <input type="text" readonly class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-sm"
                                    value="{{ $hospital_admin_pincode }}">

                                <label for="state" class="block text-sm font-medium text-gray-700 mb-1">State</label>
                                <input type="text" readonly class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-sm"
                                    value="{{ $state }}">
                            </div>

                            <div class="pt-3 border-t border-slate-200">
                                <div class="grid grid-cols-2 gap-3">
                                    <button class="flex items-center justify-center gap-2 py-2.5 px-3 rounded-lg border-2 border-green-500 text-green-600
                                     font-semibold text-sm hover:bg-green-50 transition-all" wire:click="approveDocument('contact_details')">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                        </svg>
                                        Approve Contact Details
                                    </button>
                                    <button class="flex items-center justify-center gap-2 py-2.5 px-3 rounded-lg border-2 border-red-500 text-red-600 font-semibold
                                    text-sm hover:bg-red-50 transition-all" wire:click="rejectDocument('contact_details')">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/>
                                        </svg>
                                        Reject Contact Details
                                    </button>
                                </div>
                            </div>
                        
                        </div>
                        @endif

                    </div>

                    <!-- Review Panel -->
                    <div class="w-full md:w-[380px] p-6 flex flex-col border-l border-slate-200 bg-white">
                        <h3 class="font-bold text-slate-900 mb-6 text-base">Review Panel</h3>
                        <div class="flex-1 space-y-6 overflow-y-auto pr-2" style="scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent;">
                            <!-- Review Status -->
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Review Status</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <button 
                                        wire:click="$set('reviewStatus', 'approved')"
                                        class="flex items-center justify-center gap-2 py-2.5 px-3 rounded-lg border-2 transition-all
                                        {{ $reviewStatus === 'approved' ? 'border-green-500 bg-green-50 text-green-600' : 'border-slate-300 text-slate-600 hover:border-green-500 hover:text-green-600' }}
                                        font-semibold text-sm">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                        </svg>
                                        Approve
                                    </button>
                                    <button 
                                        wire:click="$set('reviewStatus', 'rejected')"
                                        class="flex items-center justify-center gap-2 py-2.5 px-3 rounded-lg border-2 transition-all
                                        {{ $reviewStatus === 'rejected' ? 'border-red-500 bg-red-50 text-red-600' : 'border-slate-300 text-slate-600 hover:border-red-500 hover:text-red-600' }}
                                        font-semibold text-sm">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/>
                                        </svg>
                                        Reject
                                    </button>
                                </div>
                            </div>
                    
                            <!-- Internal Comments -->
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Internal Comments</label>
                                <textarea 
                                    wire:model="reviewComments"
                                    class="w-full rounded-lg border border-slate-200 bg-white text-sm text-slate-900
                                    placeholder:text-slate-400 focus:ring-2 focus:ring-primary focus:border-primary resize-none px-4 py-3" 
                                    placeholder="Type your review notes here..." 
                                    rows="6"
                                ></textarea>
                                @error('reviewComments') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                            </div>
                    
                            <!-- Info Box -->
                            <div class="bg-blue-50 p-3.5 rounded-lg border border-blue-100">
                                <p class="text-xs text-blue-700 flex items-start gap-2 leading-relaxed">
                                    <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                                    </svg>
                                    <span>Comments will be visible to the hospital administrator if the document is rejected.</span>
                                </p>
                            </div>
                        </div>
                    
                        <!-- Action Buttons -->
                        <div class="pt-6 mt-6 border-t border-slate-200 space-y-3">
                            <button 
                                wire:click="submitOnboardingReview()" 
                                class="w-full bg-sky-600 hover:bg-sky-700 text-white font-semibold py-3 px-4 rounded-lg shadow-sm transition-all flex items-center justify-center gap-2
                                {{ !$reviewStatus ? 'opacity-50 cursor-not-allowed' : '' }}"
                                {{ !$reviewStatus ? 'disabled' : '' }}>
                                Submit Onboarding Review
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                                </svg>
                            </button>
                            <button 
                                wire:click="skipOnboarding()" 
                                class="w-full text-slate-500 hover:text-slate-700 text-sm font-medium py-2 transition-all">
                                Skip for Now
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }
</style>
@endpush