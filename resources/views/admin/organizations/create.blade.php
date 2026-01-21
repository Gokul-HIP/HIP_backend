@extends('layouts.admin')

@section('title', 'Add New Organization')
@section('breadcrumb', 'Dashboard / Organization / Add New')

@section('content')

<script src="https://unpkg.com/lucide@latest"></script>

<style>
    .upload-wrapper {
        height: 260px;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        gap: 10px;
    }

    .image-box, .preview-box {
        height: 180px;
        border-radius: 8px;
        overflow: hidden;
    }

    .preview-img {
        height: 100%;
        width: 100%;
        object-fit: cover;
    }

    input:focus, textarea:focus {
        border-color:#0da2e7 !important;
        box-shadow:0 0 0 3px rgba(13,162,231,0.25) !important;
        outline:none !important;
    }
</style>


<div class="p-6">
    <div class="bg-white p-6 rounded-xl shadow border max-w-4xl mx-auto">

        <h1 class="text-2xl font-semibold mb-6">Add New Organization</h1>

        <div class="bg-gray-50 p-6 rounded-xl border">

            <form action="{{ route('admin.organizations.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <!-- ==================== ROW 1 ==================== -->
                <div class="grid grid-cols-2 gap-8">

                    <!-- Organization Name -->
                    <div>
                        <label class="block text-sm font-medium mb-2">Organization Name</label>
                        <input type="text" name="organization_name"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-100 
                                   focus:border-[#0da2e7] focus:ring-2 focus:ring-[#0da2e7]/30"
                            placeholder="Enter Organization Name">
                    </div>

                    <!-- Upload + Preview stacked inside fixed-height wrapper -->
                    <div class="upload-wrapper">
                        <label class="block text-sm font-medium mb-2">Upload Organization Image</label>

                        <!-- Upload Box -->
                        <div onclick="document.getElementById('orgLogo').click()"
                            class="image-box border-2 border-dashed border-gray-300 flex items-center justify-center 
                                   cursor-pointer hover:border-gray-400">

                            <div class="text-center">
                                <i class="fas fa-cloud-upload-alt text-gray-400 text-2xl mb-2"></i>
                                <p class="text-sm text-gray-600">Click to upload</p>
                                <p class="text-xs text-gray-400">or drag and drop</p>
                            </div>

                            <input type="file" id="orgLogo" name="logo" class="hidden"
                                   accept="image/*" onchange="previewImage(this)">
                        </div>

                        <!-- Preview Box -->
                        <div id="previewContainer" class="preview-box hidden border relative" style="height: 300px">
                            <img id="previewImg" class="preview-img" src="">

                            <button type="button"
                                onclick="removeImage()"
                                class="absolute top-1 right-1 bg-red-600 text-white w-6 h-6 flex items-center justify-center 
                                       rounded-full text-sm font-bold shadow">
                                ×
                            </button>
                        </div>
                    </div>

                </div>

                <!-- ==================== ROW 2 ==================== -->
                <div class="grid grid-cols-2 gap-8 ">

                    <!-- City -->
                    <div>
                        <label class="block text-sm font-medium mb-2" style="margin-top: -160px">City</label>
                        <input type="text" name="city"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-100 
                                   focus:border-[#0da2e7] focus:ring-2 focus:ring-[#0da2e7]/30"
                            placeholder="Enter City">
                    </div>

                    <!-- EMPTY RIGHT SIDE (Reserved space so layout NEVER moves) -->
                    <div></div>

                </div>

                <!-- ==================== ROW 3 ==================== -->
                <div class="w-1/2" style="margin-top: -50px">
                    <label class="block text-sm font-medium mb-2">Address</label>
                    <input type="text" name="address"
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-100 
                               focus:border-[#0da2e7] focus:ring-2 focus:ring-[#0da2e7]/30"
                        placeholder="Enter Address">
                </div>

                <!-- ==================== BUTTONS ==================== -->
                <div class="flex justify-end gap-4 mt-10">
                    <a href="{{ route('admin.organizations.index') }}"
                        class="px-6 py-2 bg-red-500 text-white rounded-lg text-sm">Cancel</a>

                    <button type="reset"
                        class="px-6 py-2 bg-gray-300 rounded-lg text-sm">Reset</button>

                    <button type="submit"
                        class="px-6 py-2 text-white rounded-lg text-sm"
                        style="background:#0da2e7">Save Organization</button>
                </div>

            </form>

        </div>
    </div>
</div>


<script>
function previewImage(input) {
    const file = input.files[0];
    if (!file) return;

    const previewContainer = document.getElementById("previewContainer");
    const previewImg = document.getElementById("previewImg");

    const reader = new FileReader();
    reader.onload = e => {
        previewImg.src = e.target.result;
        previewContainer.classList.remove("hidden");
    };
    reader.readAsDataURL(file);
}

function removeImage() {
    document.getElementById("orgLogo").value = "";
    document.getElementById("previewContainer").classList.add("hidden");
}
</script>

@endsection
