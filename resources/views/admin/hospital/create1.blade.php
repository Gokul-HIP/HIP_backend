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
            <h1 class="text-2xl font-semibold">Add New Hospital</h1>
        </div>

        <div class="bg-gray-50 p-6 rounded-xl border">

            <h2 class="text-lg font-semibold">Basic Information</h2>
            {{-- <p class="text-sm text-gray-500 mb-6">Fill in the information for the new medical procedure.</p> --}}

            <!-- FORM START -->
            <form action="{{ route('admin.organizations.store') }}" method="POST">
                @csrf

                <div class="grid grid-cols-2 gap-8">

                    {{-- ================= Hospital NAME ================= --}}
                    <div>
                        <label class="block text-sm font-medium mb-2">Hospital Name</label>
                        <input type="text" name="hospital_name"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-100
                                    focus:border-[#0da2e7] focus:ring-2 focus:ring-[#0da2e7]/30
                                    focus:outline-none"
                            placeholder="60 minutes">
                    </div>

                    {{-- ================= SPECIALITY ================= --}}
                    <div class="relative">
                        <label class="block text-sm font-medium mb-2">Select Speciality</label>

                        <input type="hidden" name="speciality">

                        <button type="button"
                            onclick="toggleDropdown('specialityMenu', this)"
                            class="filter-btn w-full flex items-center justify-between px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg 
                                   transition focus:border-[#0da2e7] focus:ring-2 focus:ring-[#0da2e7]/30">
                            <span class="selected-text">e.g., Cardiology</span>
                            <i data-lucide="chevron-down" class="w-4"></i>
                        </button>

                        <div id="specialityMenu"
                            class="filter-dropdown hidden absolute left-0 w-full bg-white border border-gray-200 rounded-lg mt-2 shadow-lg">

                            @foreach(['Cardiology','Orthopedics','Neurology'] as $option)
                            <button type="button"
                                onclick="selectDropdown(this,'specialityMenu','speciality')"
                                class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100">
                                {{ $option }}
                            </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- ================= ASSIGN DOCTOR ================= --}}
                    <div class="relative">
                        <label class="block text-sm font-medium mb-2">Assign Doctors</label>

                        <input type="hidden" name="doctor">

                        <button type="button"
                            onclick="toggleDropdown('doctorMenu', this)"
                            class="filter-btn w-full flex items-center justify-between px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg 
                                   transition focus:border-[#0da2e7] focus:ring-2 focus:ring-[#0da2e7]/30">
                            <span class="selected-text">Select doctors...</span>
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

                    {{-- ================= DURATION ================= --}}
                    <div>
                        <label class="block text-sm font-medium mb-2">Estimated Duration</label>
                        <input type="text" name="duration"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-100
                                    focus:border-[#0da2e7] focus:ring-2 focus:ring-[#0da2e7]/30
                                    focus:outline-none"
                            placeholder="60 minutes">
                    </div>

                    {{-- ================= COST ================= --}}
                    <div>
                        <label class="block text-sm font-medium mb-2">Cost / Price</label>
                        <input type="number" name="cost"
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-100
                                    focus:border-[#0da2e7] focus:ring-2 focus:ring-[#0da2e7]/30
                                    focus:outline-none"
                            placeholder="$ 2500">
                    </div>

                    {{-- ================= PROCEDURE CODE ================= --}}
                    <div class="relative">
                        <label class="block text-sm font-medium mb-2">Procedure ID</label>

                        <input type="hidden" name="procedure_code">

                        <button type="button"
                            onclick="toggleDropdown('codeMenu', this)"
                            class="filter-btn w-full flex items-center justify-between px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg 
                                   transition focus:border-[#0da2e7] focus:ring-2 focus:ring-[#0da2e7]/30">
                            <span class="selected-text">e.g., CPT 33510</span>
                            <i data-lucide="chevron-down" class="w-4"></i>
                        </button>

                        <div id="codeMenu"
                            class="filter-dropdown hidden absolute left-0 w-full bg-white border border-gray-200 rounded-lg mt-2 shadow-lg">

                            @foreach(['CPT 33510','CPT 22842','CPT 27447'] as $option)
                            <button type="button"
                                onclick="selectDropdown(this,'codeMenu','procedure_code')"
                                class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100">
                                {{ $option }}
                            </button>
                            @endforeach
                        </div>
                    </div>

                </div>

                {{-- ================= DESCRIPTION ================= --}}
                <div class="mt-6">
                    <label class="block text-sm font-medium mb-2">Description</label>
                    <textarea name="description" rows="3"
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-100
                                    focus:border-[#0a8cc9] focus:ring-2 focus:ring-[#0a8cc9]/30
                                    focus:outline-none"
                        placeholder="Describe the procedure in details..."></textarea>
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
                    <a href="{{ route('Add-procedure.index') }}"
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

/* CLOSE WHEN CLICK OUTSIDE */
document.addEventListener('click', (e) => {

    if (!e.target.closest('.filter-btn') &&
        !e.target.closest('.filter-dropdown')) {

        document.querySelectorAll('.filter-dropdown')
                .forEach(menu => menu.classList.add('hidden'));

        document.querySelectorAll('.filter-btn')
            .forEach(btn => btn.classList.remove("border-[#0a8cc9]", "ring-2", "ring-[#0a8cc9]/30"));
    }
});

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