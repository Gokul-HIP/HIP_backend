@extends('layouts.admin')

@section('title', 'Add New Procedure')

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
            <h1 class="text-2xl font-semibold">Add New Hospital</h1>
        </div>

        <div class="bg-gray-50 p-6 rounded-xl border">

            <h2 class="text-lg font-semibold">Basic Information</h2>
            {{-- <p class="text-sm text-gray-500 mb-6">Fill in the information for the new medical procedure.</p> --}}

            <!-- FORM START -->
            <form action="{{ route('admin.organizations.store') }}" method="POST">
                @csrf

                <div class="grid grid-cols-2 gap-8 mt-3">

                    {{-- ================= Hospital NAME ================= --}}
                    <div>
                        <label class="block text-sm font-medium mb-2">Hospital Name</label>
                        <input type="text" name="hospital_name"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-100
                                    focus:border-[#0da2e7] focus:ring-2 focus:ring-[#0da2e7]/30
                                    focus:outline-none"
                            placeholder="Enter Hospital Name">
                    </div>

                    {{-- ================= Address ================= --}}
                    <div>
                        <label class="block text-sm font-medium mb-2">Address</label>
                        <input type="text" name="hospital_name"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-100
                                    focus:border-[#0da2e7] focus:ring-2 focus:ring-[#0da2e7]/30
                                    focus:outline-none"
                            placeholder="Enter Address">
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

                    <div>
                        <h2 class="text-lg font-semibold">Contact Person</h2>
                    </div>
                    {{-- ================= COST ================= --}}
                    <div>
                    
                    </div>

                    {{-- ================= Admin Name ================= --}}
                    <div>
                        <label class="block text-sm font-medium mb-2">Admin Name</label>
                        <input type="text" name="admin_name"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-100
                                    focus:border-[#0da2e7] focus:ring-2 focus:ring-[#0da2e7]/30
                                    focus:outline-none"
                            placeholder="Enter Admin Name">
                    </div>

                    {{-- ================= Contact Details ================= --}}
                    <div>
                        <label class="block text-sm font-medium mb-2">Contact Details</label>
                        <input type="text" name="contact_details"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-100
                                    focus:border-[#0da2e7] focus:ring-2 focus:ring-[#0da2e7]/30
                                    focus:outline-none"
                            placeholder="Enter Contact Details">
                    </div>

                    {{-- ================= Email ================= --}}
                    <div>
                        <label class="block text-sm font-medium mb-2">Email</label>
                        <input type="email" name="email"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-100
                                    focus:border-[#0da2e7] focus:ring-2 focus:ring-[#0da2e7]/30
                                    focus:outline-none"
                            placeholder="Enter Email">
                    </div>

                    {{-- ================= Longitude ================= --}}
                    <div>
                        <label class="block text-sm font-medium mb-2">Longitude</label>
                        <input type="text" name="longitude"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-100
                                    focus:border-[#0da2e7] focus:ring-2 focus:ring-[#0da2e7]/30
                                    focus:outline-none"
                            placeholder="Enter Longitude">
                    </div>

                    {{-- ================= Latitude ================= --}}
                    <div>
                        <label class="block text-sm font-medium mb-2">Latitude</label>
                        <input type="text" name="latitude"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-100
                                    focus:border-[#0da2e7] focus:ring-2 focus:ring-[#0da2e7]/30
                                    focus:outline-none"
                            placeholder="Enter Latitude">
                    </div>

                    {{-- ================= Address ================= --}}
                    <div>
                        <label class="block text-sm font-medium mb-2">Address</label>
                        <textarea name="address" rows="3"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-100
                                        focus:border-[#0a8cc9] focus:ring-2 focus:ring-[#0a8cc9]/30
                                        focus:outline-none"
                            placeholder="Enter You'r Address..."></textarea>
                    </div>

                </div>

                {{-- ================= BUTTONS ================= --}}
                <div class="flex justify-end gap-4 mt-10">
                    <a href="{{ url()->previous() }}"
                        class="px-6 py-2 btn btn-danger rounded-lg text-sm">Cancel</a>

                    <button type="reset" class="px-5 py-2 bg-gray-300 rounded-lg text-sm">Reset</button>

                    <button type="submit"
                        class="px-6 py-2 text-white rounded-lg text-sm"
                        style="background:#0da2e7;">
                        Save Procedure
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<script>
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

lucide.createIcons();
</script>

@endsection