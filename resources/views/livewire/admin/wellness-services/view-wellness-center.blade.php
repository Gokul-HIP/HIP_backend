<div>
    <div class="bg-gray-50 min-h-screen">
        <div class="max-w-6xl mx-auto px-6 py-8">
            <!-- HEADER -->
            <div class="mb-8">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Wellness Centre Details</h1>
                        <p class="text-sm text-gray-500 mt-1">
                            Viewing registration details for <span class="font-semibold text-gray-900">"{{ $wellnessCenter->centre_name ?? 'N/A' }}"</span>
                        </p>
                    </div>
                    <a href="{{ route('admin.wellness-services.index') }}" 
                       class="flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-md hover:bg-white transition-all font-medium">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span>Back</span>
                    </a>
                </div>
            </div>

            <!-- SECTION 1: Centre Type & Mode -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex items-center gap-2 mb-6">
                    <div class="w-8 h-8 rounded-full bg-[#0da2e7]/10 text-[#0da2e7] flex items-center justify-center text-sm font-semibold">
                        1
                    </div>
                    <h2 class="text-lg font-semibold text-gray-900">Centre Type & Mode</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase mb-1">Centre Category</label>
                        <div class="text-gray-900 font-medium bg-gray-50 p-3 rounded-lg border border-gray-100">
                            {{ $wellnessCenter->centre_type ?? '-' }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase mb-1">Operating Mode</label>
                        <div class="text-gray-900 font-medium bg-gray-50 p-3 rounded-lg border border-gray-100">
                            {{ $wellnessCenter->operating_mode ?? '-' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: Basic Information -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex items-center gap-2 mb-6">
                    <div class="w-8 h-8 rounded-full bg-[#0da2e7]/10 text-[#0da2e7] flex items-center justify-center text-sm font-semibold">
                        2
                    </div>
                    <h2 class="text-lg font-semibold text-gray-900">Basic Information</h2>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase mb-1">Centre Name</label>
                        <div class="text-gray-900 font-medium bg-gray-50 p-3 rounded-lg border border-gray-100">
                            {{ $wellnessCenter->centre_name ?? '-' }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase mb-1">Age Group Served</label>
                        <div class="text-gray-900 font-medium bg-gray-50 p-3 rounded-lg border border-gray-100">
                            {{ $wellnessCenter->age_group_served ?? '-' }}
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-400 uppercase mb-1">Description</label>
                        <div class="text-gray-900 font-medium bg-gray-50 rounded-lg border border-gray-100 p-3">
                            {{ $wellnessCenter->description ?? '-' }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase mb-1">Languages Supported</label>
                        <div class="flex flex-wrap gap-2 mt-1">
                            @if($wellnessCenter->languages_supported)
                                @php
                                    $languages = explode(',', $wellnessCenter->languages_supported);
                                    $languages = array_map('trim', $languages);
                                @endphp
                                @foreach($languages as $language)
                                    <span class="px-3 py-1 bg-gray-100 rounded-full text-xs font-medium">
                                        {{ $language }}
                                    </span>
                                @endforeach
                            @else
                                <span class="text-gray-500">-</span>
                            @endif
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase mb-1">Target Audience</label>
                        <div class="text-gray-900 font-medium bg-gray-50 p-3 rounded-lg border border-gray-100">
                            {{ $wellnessCenter->target_audience ?? '-' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 3: Location & Address Details -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex items-center gap-2 mb-6">
                    <div class="w-8 h-8 rounded-full bg-[#0da2e7]/10 text-[#0da2e7] flex items-center justify-center text-sm font-semibold">
                        3
                    </div>
                    <h2 class="text-lg font-semibold text-gray-900">Location & Address Details</h2>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase mb-1">Address Line 1</label>
                        <div class="text-gray-900 font-medium bg-gray-50 p-3 rounded-lg border border-gray-100">
                            {{ $wellnessCenter->address_line_1 ?? '-' }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase mb-1">Address Line 2</label>
                        <div class="text-gray-900 font-medium bg-gray-50 p-3 rounded-lg border border-gray-100">
                            {{ $wellnessCenter->address_line_2 ?? '-' }}
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4 md:col-span-2">
                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase mb-1">City</label>
                            <div class="text-gray-900 font-medium bg-gray-50 p-3 rounded-lg border border-gray-100">
                                {{ $wellnessCenter->city ?? '-' }}
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase mb-1">State</label>
                            <div class="text-gray-900 font-medium bg-gray-50 p-3 rounded-lg border border-gray-100">
                                {{ $wellnessCenter->state ?? '-' }}
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase mb-1">Pincode</label>
                            <div class="text-gray-900 font-medium bg-gray-50 p-3 rounded-lg border border-gray-100">
                                {{ $wellnessCenter->pincode ?? '-' }}
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase mb-1">Latitude</label>
                            <div class="text-gray-900 font-medium bg-gray-50 p-3 rounded-lg border border-gray-100">
                                {{ $wellnessCenter->latitude ? $wellnessCenter->latitude . '° N' : '-' }}
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase mb-1">Longitude</label>
                            <div class="text-gray-900 font-medium bg-gray-50 p-3 rounded-lg border border-gray-100">
                                {{ $wellnessCenter->longitude ? $wellnessCenter->longitude . '° E' : '-' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 4: Social & Contact Details -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex items-center gap-2 mb-6">
                    <div class="w-8 h-8 rounded-full bg-[#0da2e7]/10 text-[#0da2e7] flex items-center justify-center text-sm font-semibold">
                        4
                    </div>
                    <h2 class="text-lg font-semibold text-gray-900">Social & Contact Details</h2>
                </div>

                <div class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase mb-1">Centre Website</label>
                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                                @if($wellnessCenter->centre_website)
                                    <a href="{{ $wellnessCenter->centre_website }}" target="_blank" 
                                       class="text-[#0da2e7] hover:underline font-medium">
                                        {{ $wellnessCenter->centre_website }}
                                    </a>
                                @else
                                    <span class="text-gray-500">-</span>
                                @endif
                            </div>
                        </div>

                        @if($wellnessCenter->centre_social_links && !$wellnessCenter->centre_instagram_links)
                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase mb-1">
                                Social Media Links
                            </label>
                            <div class="text-gray-900 font-medium bg-gray-50 p-3 rounded-lg border border-gray-100">
                                {{ $wellnessCenter->centre_social_links ?? '-' }}
                            </div>
                        </div>
                        @endif

                        @if($wellnessCenter->centre_instagram_links)
                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase mb-1">
                                Instagram
                            </label>
                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                                <a href="{{ $wellnessCenter->centre_instagram_links }}" target="_blank" 
                                   class="text-[#0da2e7] hover:underline font-medium">
                                    {{ $wellnessCenter->centre_instagram_links }}
                                </a>
                            </div>
                        </div>
                        @endif

                        @if($wellnessCenter->centre_facebook_links)
                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase mb-1">
                                Facebook
                            </label>
                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                                <a href="{{ $wellnessCenter->centre_facebook_links }}" target="_blank" 
                                   class="text-[#0da2e7] hover:underline font-medium">
                                    {{ $wellnessCenter->centre_facebook_links }}
                                </a>
                            </div>
                        </div>
                        @endif

                        @if($wellnessCenter->centre_linkedin_links)
                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase mb-1">
                                LinkedIn
                            </label>
                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                                <a href="{{ $wellnessCenter->centre_linkedin_links }}" target="_blank" 
                                   class="text-[#0da2e7] hover:underline font-medium">
                                    {{ $wellnessCenter->centre_linkedin_links }}
                                </a>
                            </div>
                        </div>
                        @endif

                        @if($wellnessCenter->centre_twitter_links)
                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase mb-1">
                                Twitter
                            </label>
                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                                <a href="{{ $wellnessCenter->centre_twitter_links }}" target="_blank" 
                                   class="text-[#0da2e7] hover:underline font-medium">
                                    {{ $wellnessCenter->centre_twitter_links }}
                                </a>
                            </div>
                        </div>
                        @endif

                        @if($wellnessCenter->centre_youtube_links)
                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase mb-1">
                                YouTube
                            </label>
                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                                <a href="{{ $wellnessCenter->centre_youtube_links }}" target="_blank" 
                                   class="text-[#0da2e7] hover:underline font-medium">
                                    {{ $wellnessCenter->centre_youtube_links }}
                                </a>
                            </div>
                        </div>
                        @endif
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase mb-1">Contact Person Name</label>
                        <div class="text-gray-900 font-medium bg-gray-50 p-3 rounded-lg border border-gray-100">
                            {{ $wellnessCenter->contact_person_name ?? '-' }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase mb-1">Contact Person Mobile</label>
                        <div class="text-gray-900 font-medium bg-gray-50 p-3 rounded-lg border border-gray-100">
                            {{ $wellnessCenter->contact_person_mobile ?? '-' }}
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-400 uppercase mb-1">Contact Person Email</label>
                        <div class="text-gray-900 font-medium bg-gray-50 p-3 rounded-lg border border-gray-100">
                            @if($wellnessCenter->contact_person_email)
                                {{ $wellnessCenter->contact_person_email }}
                            @else
                                <span class="text-gray-500">-</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 5: Legal Compliance -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex items-center gap-2 mb-6">
                    <div class="w-8 h-8 rounded-full bg-[#0da2e7]/10 text-[#0da2e7] flex items-center justify-center text-sm font-semibold">
                        5
                    </div>
                    <h2 class="text-lg font-semibold text-gray-900">Legal Compliance</h2>
                </div>

                <div class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase mb-1">Business Registration Type</label>
                        <div class="text-gray-900 font-medium bg-gray-50 p-3 rounded-lg border border-gray-100">
                            {{ $wellnessCenter->business_registration_type ?? '-' }}
                        </div>
                        </div>

                        <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase mb-1">GST Number</label>
                        <div class="text-gray-900 font-medium bg-gray-50 p-3 rounded-lg border border-gray-100">
                            {{ $wellnessCenter->gst_number ?? '-' }}
                        </div>
                        </div>
                    </div>

                    <!-- Document Previews -->
                    <div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <!-- Registration Certificate -->
                            <div class="group relative bg-gray-50 p-3 rounded-lg border border-gray-200 flex flex-col gap-2 items-center text-center">
                                @if($wellnessCenter->registration_certificate)
                                    @php
                                        $filePath = 'wellness-centers/documents/' . $wellnessCenter->registration_certificate;
                                        $fileUrl = asset('storage/' . $filePath);
                                        $isImage = in_array(strtolower(pathinfo($wellnessCenter->registration_certificate, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                    @endphp
                                    <div class="w-full aspect-[3/4] bg-gray-200 rounded overflow-hidden relative">
                                        @if($isImage)
                                            <img src="{{ $fileUrl }}" alt="Registration Certificate" 
                                                 class="w-full h-full object-cover opacity-60">
                                        @else
                                            <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                                                <i class="fas fa-file-pdf text-red-500 text-4xl"></i>
                                            </div>
                                        @endif
                                        <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-black/40">
                                            <a href="{{ $fileUrl }}" target="_blank" 
                                               class="bg-white text-gray-900 p-2 rounded-full shadow-lg hover:bg-gray-100 transition-colors">
                                                <i class="fas fa-eye text-lg"></i>
                                            </a>
                                        </div>
                                    </div>
                                @else
                                    <div class="w-full aspect-[3/4] bg-gray-200 rounded flex items-center justify-center">
                                        <i class="fas fa-file text-gray-400 text-2xl"></i>
                                    </div>
                                @endif
                                <span class="text-[10px] font-bold text-gray-400 uppercase">Registration Cert</span>
                            </div>

                            <!-- Ownership Proof -->
                            <div class="group relative bg-gray-50 p-3 rounded-lg border border-gray-200 flex flex-col gap-2 items-center text-center">
                                @if($wellnessCenter->ownership_proof)
                                    @php
                                        $filePath = 'wellness-centers/documents/' . $wellnessCenter->ownership_proof;
                                        $fileUrl = asset('storage/' . $filePath);
                                        $isImage = in_array(strtolower(pathinfo($wellnessCenter->ownership_proof, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                    @endphp
                                    <div class="w-full aspect-[3/4] bg-gray-200 rounded overflow-hidden relative">
                                        @if($isImage)
                                            <img src="{{ $fileUrl }}" alt="Ownership Proof" 
                                                 class="w-full h-full object-cover opacity-60">
                                        @else
                                            <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                                                <i class="fas fa-file-pdf text-red-500 text-4xl"></i>
                                            </div>
                                        @endif
                                        <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-black/40">
                                            <a href="{{ $fileUrl }}" target="_blank" 
                                               class="bg-white text-gray-900 p-2 rounded-full shadow-lg hover:bg-gray-100 transition-colors">
                                                <i class="fas fa-eye text-lg"></i>
                                            </a>
                                        </div>
                                    </div>
                                @else
                                    <div class="w-full aspect-[3/4] bg-gray-200 rounded flex items-center justify-center">
                                        <i class="fas fa-file text-gray-400 text-2xl"></i>
                                    </div>
                                @endif
                                <span class="text-[10px] font-bold text-gray-400 uppercase">Ownership Proof</span>
                            </div>

                            <!-- Accreditation Certificate -->
                            <div class="group relative bg-gray-50 p-3 rounded-lg border border-gray-200 flex flex-col gap-2 items-center text-center">
                                @if($wellnessCenter->accreditation_certificate)
                                    @php
                                        $filePath = 'wellness-centers/documents/' . $wellnessCenter->accreditation_certificate;
                                        $fileUrl = asset('storage/' . $filePath);
                                        $isImage = in_array(strtolower(pathinfo($wellnessCenter->accreditation_certificate, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                    @endphp
                                    <div class="w-full aspect-[3/4] bg-gray-200 rounded overflow-hidden relative">
                                        @if($isImage)
                                            <img src="{{ $fileUrl }}" alt="Accreditation Certificate" 
                                                 class="w-full h-full object-cover opacity-60">
                                        @else
                                            <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                                                <i class="fas fa-file-pdf text-red-500 text-4xl"></i>
                                            </div>
                                        @endif
                                        <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-black/40">
                                            <a href="{{ $fileUrl }}" target="_blank" 
                                               class="bg-white text-gray-900 p-2 rounded-full shadow-lg hover:bg-gray-100 transition-colors">
                                                <i class="fas fa-eye text-lg"></i>
                                            </a>
                                        </div>
                                    </div>
                                @else
                                    <div class="w-full aspect-[3/4] bg-gray-200 rounded flex items-center justify-center">
                                        <i class="fas fa-file text-gray-400 text-2xl"></i>
                                    </div>
                                @endif
                                <span class="text-[10px] font-bold text-gray-400 uppercase">Accreditation</span>
                            </div>

                            <!-- Fire Safety Certificate -->
                            <div class="group relative bg-gray-50 p-3 rounded-lg border border-gray-200 flex flex-col gap-2 items-center text-center">
                                @if($wellnessCenter->fire_safety_certificate)
                                    @php
                                        $filePath = 'wellness-centers/documents/' . $wellnessCenter->fire_safety_certificate;
                                        $fileUrl = asset('storage/' . $filePath);
                                        $isImage = in_array(strtolower(pathinfo($wellnessCenter->fire_safety_certificate, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                    @endphp
                                    <div class="w-full aspect-[3/4] bg-gray-200 rounded overflow-hidden relative">
                                        @if($isImage)
                                            <img src="{{ $fileUrl }}" alt="Fire Safety Certificate" 
                                                 class="w-full h-full object-cover opacity-60">
                                        @else
                                            <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                                                <i class="fas fa-file-pdf text-red-500 text-4xl"></i>
                                            </div>
                                        @endif
                                        <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-black/40">
                                            <a href="{{ $fileUrl }}" target="_blank" 
                                               class="bg-white text-gray-900 p-2 rounded-full shadow-lg hover:bg-gray-100 transition-colors">
                                                <i class="fas fa-eye text-lg"></i>
                                            </a>
                                        </div>
                                    </div>
                                @else
                                    <div class="w-full aspect-[3/4] bg-gray-200 rounded flex items-center justify-center">
                                        <i class="fas fa-file text-gray-400 text-2xl"></i>
                                    </div>
                                @endif
                                <span class="text-[10px] font-bold text-gray-400 uppercase">Fire Safety</span>
                            </div>
                        </div>
                    </div>

                    <!-- Insurance Coverage -->
                    <div class="mt-8 pt-8 border-t border-gray-100">
                        <label class="block text-xs font-semibold text-gray-400 uppercase mb-3">
                            Insurance Coverage
                        </label>
                        <div class="grid md:grid-cols-2 gap-2 text-sm">
                            @php
                                $insuranceOptions = [
                                    'Public Liability Insurance',
                                    'Professional Liability Insurance',
                                    'Property Insurance',
                                    'Equipment Insurance',
                                    'Employee / Workers Insurance',
                                    'Cyber & Data Insurance',
                                    'Product Liability Insurance',
                                    'Business Interruption Insurance',
                                    'Medical Malpractice Insurance',
                                    'Directors & Officers Insurance',
                                    'Personal Accident Insurance',
                                    'Fire Insurance',
                                    'Theft & Burglary Insurance',
                                    'Transit Insurance'
                                ];
                                
                                // Handle both single value and comma-separated values
                                $insuranceValue = $wellnessCenter->insurance_coverage ?? '';
                                $selectedInsurance = [];
                                
                                if ($insuranceValue) {
                                    // Check if it's comma-separated
                                    if (strpos($insuranceValue, ',') !== false) {
                                        $selectedInsurance = array_map('trim', explode(',', $insuranceValue));
                                    } else {
                                        // Single value
                                        $selectedInsurance = [trim($insuranceValue)];
                                    }
                                }
                            @endphp
                            
                            @foreach($insuranceOptions as $insurance)
                                <div class="flex items-center gap-2 text-gray-600">
                                    @if(in_array($insurance, $selectedInsurance))
                                        <i class="fas fa-check-circle text-[#0da2e7] text-lg"></i>
                                    @else
                                        <i class="far fa-circle text-gray-300 text-lg"></i>
                                    @endif
                                    <span>{{ $insurance }}</span>
                                </div>
                            @endforeach
                            
                            {{-- Show any custom insurance values that don't match the predefined list --}}
                            @if(!empty($selectedInsurance))
                                @foreach($selectedInsurance as $customInsurance)
                                    @if(!in_array($customInsurance, $insuranceOptions))
                                        <div class="flex items-center gap-2 text-gray-600">
                                            <i class="fas fa-check-circle text-[#0da2e7] text-lg"></i>
                                            <span>{{ $customInsurance }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>