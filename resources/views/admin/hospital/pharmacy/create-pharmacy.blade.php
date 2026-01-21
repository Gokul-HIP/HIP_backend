@extends('layouts.admin')

@section('title', 'Add New Procedure')
@section('breadcrumb', 'Dashboard / Procedures / Add New')

@section('content')

<script src="https://unpkg.com/lucide@latest"></script>

<style>
/* Keep dropdowns above everything */
.filter-dropdown {
    z-index: 5000 !important;
}
</style>

<div class="p-6">
    <div class="bg-white p-6 rounded-xl shadow border max-w-5xl mx-auto">

        <div class="mb-6">
            <h1 class="text-2xl font-semibold">Add New Pharmacy</h1>
            <p class="text-sm text-gray-500 mb-6">Fill in the information to add new Pharmacy.</p>
        </div>

        <div class="bg-gray-50 p-6 rounded-xl border">

            <h2 class="text-lg font-semibold mb-3">Pharmacy Details</h2>

            <!-- FORM START -->
            <form action="{{ route('admin.organizations.store') }}" method="POST">
                @csrf

                <div class="grid grid-cols-2 gap-8">

                    {{-- ================= Pharmacy NAME ================= --}}
                    <div>
                        <label class="block text-sm font-medium mb-2">Pharmacy Name</label>
                        <input type="text" name="pharmacy_name"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-100
                                    focus:border-[#0da2e7] focus:ring-2 focus:ring-[#0da2e7]/30
                                    focus:outline-none"
                            placeholder="Enter Pharmacy Name">
                    </div>

                    {{-- ================= Pharmacy ID ================= --}}
                    <div>
                        <label class="block text-sm font-medium mb-2">Pharmacy ID</label>
                        <input type="text" name="pharmacy_id"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-100
                                    focus:border-[#0da2e7] focus:ring-2 focus:ring-[#0da2e7]/30
                                    focus:outline-none"
                            placeholder="Enter Pharmacy ID" readonly>
                    </div>

                    {{-- ================= Organization Name ================= --}}
                    <div class="relative">
                        <label class="block text-sm font-medium mb-2">Organization Name</label>

                        <input type="hidden" name="doctor">

                        <button type="button"
                            onclick="toggleDropdown('doctorMenu', this)"
                            class="filter-btn w-full flex items-center justify-between px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg 
                                   transition focus:border-[#0da2e7] focus:ring-2 focus:ring-[#0da2e7]/30">
                            <span class="selected-text">Select organization...</span>
                            <i data-lucide="chevron-down" class="w-4"></i>
                        </button>

                        <div id="doctorMenu"
                            class="filter-dropdown hidden absolute left-0 w-full bg-white border border-gray-200 rounded-lg mt-2 shadow-lg">

                            @foreach(['Dr. Emily Davis','Dr. Sarah Chen'] as $option)
                            <button type="button"
                                onclick="selectDropdown(this,'doctorMenu','doctor')"
                                class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100">
                                {{ $option }}
                            </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- ================= Address ================= --}}
                    <div>
                        <label class="block text-sm font-medium mb-2">Location / Address</label>
                        <input type="text" name="address"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-100
                                    focus:border-[#0da2e7] focus:ring-2 focus:ring-[#0da2e7]/30
                                    focus:outline-none"
                            placeholder="Enter Full Address">
                    </div>

                    {{-- ================= License Number ================= --}}
                    <div>
                        <label class="block text-sm font-medium mb-2">License Number</label>
                        <input type="text" name="license_number"
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-100
                                    focus:border-[#0da2e7] focus:ring-2 focus:ring-[#0da2e7]/30
                                    focus:outline-none"
                            placeholder="Enter License Number">
                    </div>

                    {{-- ================= GST Number ================= --}}
                    <div>
                        <label class="block text-sm font-medium mb-2">GST Number <span class="text-gray-400">(Optional)</span></label>
                        <input type="text" name="gst_number"
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-100
                                    focus:border-[#0da2e7] focus:ring-2 focus:ring-[#0da2e7]/30
                                    focus:outline-none"
                            placeholder="Enter GST Number">
                    </div>

                </div>

                <h2 class="text-lg font-semibold mt-3 mb-3">Contact Details</h2>

                <div class="grid grid-cols-2 gap-8">
                    {{-- ================= Contact Person Name ================= --}}
                    <div>
                        <label class="block text-sm font-medium mb-2">Contact Person Name</label>
                        <input type="text" name="contact_person_name"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-100
                                    focus:border-[#0da2e7] focus:ring-2 focus:ring-[#0da2e7]/30
                                    focus:outline-none"
                            placeholder="Enter Contact Person Name">
                    </div>

                    {{-- ================= Contact Number ================= --}}
                    <div>
                        <label class="block text-sm font-medium mb-2">Contact Number</label>
                        <input type="text" name="contact_number"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-100
                                    focus:border-[#0da2e7] focus:ring-2 focus:ring-[#0da2e7]/30
                                    focus:outline-none"
                            placeholder="Enter Contact Number">
                    </div>

                    {{-- ================= Email Address ================= --}}
                    <div>
                        <label class="block text-sm font-medium mb-2">Email Address</label>
                        <input type="text" name="email_address"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-100
                                    focus:border-[#0da2e7] focus:ring-2 focus:ring-[#0da2e7]/30
                                    focus:outline-none"
                            placeholder="Enter Email Address">
                    </div>

                    {{-- ================= Operating Hours ================= --}}
                    <div class="">
                        <label class="block text-sm font-medium mb-2">Operating Hours</label>

                        <div class="flex items-center gap-3">

                            <!-- From Time -->
                            <div class="relative w-full">
                                <input type="time" 
                                    name="opening_time"
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-100
                                        focus:border-[#0da2e7] focus:ring-1 focus:ring-[#0da2e7]/30
                                        focus:outline-none appearance-none">
                                
                                {{-- <i data-lucide="clock"
                                class="w-4 h-4 text-gray-500 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"></i> --}}
                            </div>

                            <span class="text-gray-600">to</span>

                            <!-- To Time -->
                            <div class="relative w-full">
                                <input type="time" 
                                    name="closing_time"
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-100
                                        focus:border-[#0da2e7] focus:ring-1 focus:ring-[#0da2e7]/30
                                        focus:outline-none appearance-none">

                                {{-- <i data-lucide="clock"
                                class="w-4 h-4 text-gray-500 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"></i> --}}
                            </div>

                        </div>
                    </div>

                    {{-- ================= File Upload ================= --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Upload Hospital Logo / Image</label>

                        <div onclick="document.getElementById('orgLogo').click()"
                            class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-gray-400">

                            <i class="fas fa-cloud-upload-alt text-gray-400 text-2xl mb-2"></i>
                            <div class="text-sm text-gray-500">Click to upload</div>
                            <div class="text-xs text-gray-400">or drag and drop</div>

                            <input type="file" id="orgLogo" name="logo" class="hidden" accept="image/*"
                                onchange="previewImage(this, 'orgPreview')">
                        </div>
                    </div>

                    {{-- ================= Image Preview ================= --}}
                    <div>           
                        <!-- Show this when image is selected -->
                        <div id="orgPreview" class="hidden relative">
                            <img src="" class="w-full h-[152px] object-cover rounded border">
                            
                            <!-- REMOVE IMAGE BUTTON -->
                            <button type="button"
                                onclick="removeImage('orgLogo','orgPreview')"
                                class="absolute top-2 right-2 bg-red-600 text-white w-7 h-7 flex items-center justify-center rounded-full text-lg font-bold shadow hover:bg-red-700">
                                ×
                            </button>
                        </div>
                    </div>

                </div>

                {{-- ================= STATUS ================= --}}
                <div class="mt-6">
                    <label class="block text-sm font-medium mb-2">Status</label>

                    <input type="hidden" name="status" id="statusInput" value="inactive">

                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-600">Inactive</span>

                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only"
                                onchange="toggleStatus(this)">
                            <span class="w-12 h-6 bg-gray-300 rounded-full flex items-center px-1 transition-colors duration-300">
                                <span class="dot w-5 h-5 bg-white rounded-full transition-transform duration-300"></span>
                            </span>
                        </label>

                        <span class="text-sm text-gray-700">Active</span>
                    </div>
                </div>

                {{-- ================= BUTTONS ================= --}}
                <div class="flex justify-end gap-4 mt-10">
                    <a href="{{ route('admin.organizations.pharmacy.index') }}"
                        class="px-6 py-2 btn btn-danger rounded-lg text-sm">Cancel</a>

                    <button type="reset" class="px-5 py-2 bg-gray-300 rounded-lg text-sm">Reset</button>

                    <button type="submit"
                        class="px-6 py-2 text-white rounded-lg text-sm"
                        style="background:#0da2e7;">
                        Save Pharmacy
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<script>
/*OPEN/CLOSE DROPDOWN + APPLY BLUE BORDER */
function toggleDropdown(id, btn) {

    document.querySelectorAll('.filter-dropdown').forEach(m => {
        if (m.id !== id) m.classList.add('hidden');
    });

    document.querySelectorAll('.filter-btn')
        .forEach(b => b.classList.remove("border-[#0a8cc9]", "ring-2", "ring-[#0a8cc9]/30"));

    const menu = document.getElementById(id);
    menu.classList.toggle('hidden');

    if (!menu.classList.contains('hidden')) {
        btn.classList.add("border-[#0a8cc9]", "ring-2", "ring-[#0a8cc9]/30");
    }
}

/* SELECT DROPDOWN OPTION */
function selectDropdown(btn, menuId, inputName) {
    const text = btn.innerText;

    btn.closest('.relative')
       .querySelector('.selected-text').innerText = text;

    document.querySelector(`input[name="${inputName}"]`).value = text;

    document.getElementById(menuId).classList.add('hidden');
}

function previewImage(input, previewId) {
    const file = input.files[0];
    const previewContainer = document.getElementById(previewId);
    const noPreview = document.getElementById('orgNoPreview');
    
    if (file) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            // Show preview in the right column
            previewContainer.querySelector('img').src = e.target.result;
            previewContainer.classList.remove('hidden');
            noPreview.classList.add('hidden');
        }
        
        reader.readAsDataURL(file);
    }
}
function removeImage(inputId, previewId) {
    const input = document.getElementById(inputId);
    const previewContainer = document.getElementById(previewId);
    const noPreview = document.getElementById('orgNoPreview');
    
    // Clear the file input
    input.value = '';
    
    // Hide preview and show "no image" placeholder
    previewContainer.classList.add('hidden');
    previewContainer.querySelector('img').src = '';
    noPreview.classList.remove('hidden');
}

function toggleStatus(checkbox) {
    const input = document.getElementById('statusInput');
    const track = checkbox.nextElementSibling;
    const dot = track.querySelector('.dot');

    if (checkbox.checked) {
        input.value = "active";
        track.classList.remove("bg-gray-300");
        track.classList.add("bg-[#0da2e7]");
        dot.style.transform = "translateX(24px)";
    } else {
        input.value = "inactive";
        track.classList.add("bg-gray-300");
        track.classList.remove("bg-[#0da2e7]");
        dot.style.transform = "translateX(0px)";
    }
}

lucide.createIcons();
</script>

@endsection