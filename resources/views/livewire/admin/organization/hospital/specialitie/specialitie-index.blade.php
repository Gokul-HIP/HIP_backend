<div class="space-y-6" x-data 
     @relode-spe.window="$wire.$refresh()">

    <style>
       .action-menu {
           position: absolute;
           right: 0;
           top: 35px;
           width: 260px !important;
           max-width: 260px !important;
           min-width: 260px !important;
           z-index: 50;
           background: white;
           border: 1px solid #e5e7eb;
           border-radius: 8px;
           box-shadow: 0 8px 25px rgba(0,0,0,0.12);
       }
       .action-cell {
           position: relative !important;
       }
       .action-item {
           transition: all 0.2s ease;
           color: #374151;
       }
       .action-item.delete:hover {
           color: #ef4444 !important;
       }
       .action-btn {
           background: white;
           border: 1px solid #d1d5db;
           color: #374151;
       }
       ui-modal#delete-spe dialog {
        max-width: 420px !important;
    }
   </style>

   <!-- HERO BANNER -->

   <div class="bg-white rounded-xl shadow-md border overflow-hidden">
        <div class="relative h-48 md:h-52 rounded-xl overflow-hidden">

            <!-- IMAGE -->
            <img 
                src="https://images.unsplash.com/photo-1551601651-2a8555f1a136?auto=format&fit=crop&w=1600&q=80"
                class="w-full h-full object-cover rounded-xl"
            >

            <!-- OVERLAY -->
            <div class="absolute inset-0 bg-black/30 rounded-xl"></div>

            <!-- CONTENT -->
            <div class="absolute inset-0 flex items-start justify-between p-6">
                <div class="backdrop-blur-md bg-white/20 border border-white/30 rounded-xl px-4 py-2 shadow-lg">
                    <h1 class="text-white text-2xl font-bold">
                        {{ $hospital->hospital_name }} - Hospital
                    </h1>
                    <p class="text-sm text-white/90 mt-1">
                        Manage {{ $hospital->hospital_name }} Specialities
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

    <div class="grid grid-cols-3 gap-6 max-w-3xl">
        <div class="bg-white p-4 border rounded-lg shadow-md">
            <p class="text-xs text-gray-500">Total Specialities</p>
            <p class="text-4xl font-bold mt-1">{{ $specialities->total() }}</p>
        </div>

        <div class="bg-white p-4 border rounded-lg shadow-md">
            <p class="text-xs text-gray-500">Active Specialities</p>
            @php
                $active = App\Models\Speciality::where('status','active')->where('hospital_id', $hospitalId)->count();
            @endphp
            <p class="text-4xl font-bold mt-1">{{ $active }}</p>
        </div>

        <div class="bg-white p-4 border rounded-lg shadow-md">
            <p class="text-xs text-gray-500">Inactive Specialities</p>
            @php
                $inactive = App\Models\Speciality::where('status','inactive')->where('hospital_id', $hospitalId)->count();
            @endphp
            <p class="text-4xl font-bold mt-1">{{ $inactive }}</p>
        </div>

    </div>

   <h3 class="font-medium text-2xl text-gray-900">Hospital Specialities</h3>
   
   <!-- TABLE CARD -->
   <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">

       <!-- FILTER BAR -->
       <div class="flex items-center justify-between mb-6">

           <div class="flex items-center space-x-3">

               <!-- SEARCH -->
               <div class="relative">
                   <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>

                   <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Search specialities, code, department..."
                       class="w-72 pl-10 pr-4 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-blue-300 focus:border-blue-500 outline-none" />
               </div>

               <!-- DEPARTMENT DROPDOWN -->
               <div class="relative" x-data="{ departmentOpen: false }">
                   <button type="button" @click="departmentOpen = !departmentOpen"
                       class="inline-flex items-center justify-center text-gray-700 bg-white box-border border border-gray-300 hover:bg-gray-50 focus:ring-4 focus:ring-blue-300 shadow-sm font-medium leading-5 rounded-lg text-sm px-4 py-2.5 focus:outline-none">
                       <i class="fas fa-building mr-2 text-gray-700"></i>
                       <span>{{ $departmentFilter ?: 'All Departments' }}</span>
                       <i class="fas fa-chevron-down ml-2 text-gray-700"></i>
                   </button>

                   <!-- Dropdown menu -->
                   <div  x-show="departmentOpen" x-cloak  x-transition @click.away="departmentOpen = false"
                        x-effect="
                            if ($wire.departmentFilter !== '') {
                                departmentOpen = false
                            }
                        " 
                        class="absolute z-50 mt-2 bg-white border border-gray-300 rounded-lg shadow-lg w-56">

                       <ul class="p-2 text-sm text-gray-700 font-medium max-h-60 overflow-y-auto">
                           <li>
                               <button type="button" wire:click="$set('departmentFilter', '')" @click="departmentOpen=false"
                                   class="inline-flex items-center w-full p-2 hover:bg-gray-100 hover:text-gray-900 rounded">
                                   <i class="fas fa-list mr-2 text-gray-700"></i>
                                   All Departments
                               </button>
                           </li>
                           @foreach($availableDepartments as $department)
                           <li>
                            <button type="button" wire:click="$set('departmentFilter', '{{ $department }}')" @click.stop class="inline-flex items-center w-full p-2 hover:bg-gray-100 hover:text-gray-900 rounded">
                                <i class="fas fa-building mr-2 text-gray-700"></i>
                                {{ $department }}
                            </button>
                           </li>
                           @endforeach
                       </ul>
                   </div>
               </div>

               <!-- STATUS DROPDOWN -->
               <div class="relative" x-data="{ statusOpen: false }">
                   <button type="button" @click="statusOpen = !statusOpen"
                       class="inline-flex items-center justify-center text-gray-700 bg-white box-border border border-gray-300 hover:bg-gray-50 focus:ring-4 focus:ring-blue-300 shadow-sm font-medium leading-5 rounded-lg text-sm px-4 py-2.5 focus:outline-none">
                       <i class="fas fa-toggle-on mr-2 text-gray-700"></i>
                       <span>{{ $statusFilter === 'active' ? 'Active' : ($statusFilter === 'inactive' ? 'Inactive' : 'All Status') }}</span>
                       <i class="fas fa-chevron-down ml-2 text-gray-700"></i>
                   </button>

                   <!-- Dropdown menu -->
                   <div x-show="statusOpen" 
                        x-cloak
                        @click.away="statusOpen = false"
                        x-transition
                        class="absolute z-50 mt-2 bg-white border border-gray-300 rounded-lg shadow-lg w-44">
                       <ul class="p-2 text-sm text-gray-700 font-medium">
                           <li>
                               <button type="button" wire:click="$set('statusFilter', '')" @click="statusOpen=false"
                                   class="inline-flex items-center w-full p-2 hover:bg-gray-100 hover:text-gray-900 rounded">
                                   <i class="fas fa-list mr-2 text-gray-700"></i>
                                   All Status
                               </button>
                           </li>
                           <li>
                               <button type="button" wire:click="$set('statusFilter', 'active')" @click="statusOpen=false"
                                   class="inline-flex items-center w-full p-2 hover:bg-green-50 hover:text-green-700 rounded">
                                   <i class="fas fa-check-circle mr-2 text-gray-700"></i>
                                   Active
                               </button>
                           </li>
                           <li>
                               <button type="button" wire:click="$set('statusFilter', 'inactive')" @click="statusOpen=false"
                                   class="inline-flex items-center w-full p-2 hover:bg-red-50 hover:text-red-700 rounded">
                                   <i class="fas fa-times-circle mr-2 text-gray-700"></i>
                                   Inactive
                               </button>
                           </li>
                       </ul>
                   </div>
               </div>

           </div>

           <!-- ADD SPECIALITY -->
           <div class="ml-auto flex-shrink-0 flex gap-3">
               <flux:modal.trigger name="add-specialitie">
                   <button variant="primary" class="text-white px-6 py-2 rounded-lg shadow-md flex items-center" style="background:#0da2e7;">
                       <i class="fas fa-plus mr-2 text-white"></i>
                       <span class="hidden sm:inline text-white">Add Speciality</span>
                       <span class="sm:hidden text-white">Add</span>
                   </button>
               </flux:modal.trigger>

               <button
                    wire:click="$dispatch('open-bulk-add-specialitie', { hospitalId: {{ $hospitalId }} })"
                    type="button"
                    class="text-white px-6 py-2 rounded-lg shadow-md flex items-center" style="background:#0da2e7;">
                    <i class="fas fa-layer-group mr-2 text-white"></i>
                    Add Bulk Speciality
                </button>
           </div>

       </div>

       <!-- TABLE -->
        <div class="overflow-x-auto -mx-6 sm:mx-0 shadow-md rounded-lg">
            <table class="w-full border-collapse min-w-[800px]">

                <thead class="bg-gray-100 border-b border-gray-200">
                    <tr>
                        <th class="px-4 sm:px-6 py-3 text-left text-sm font-semibold text-gray-700">
                            Speciality Name
                        </th>
                        <th class="px-4 sm:px-6 py-3 text-left text-sm font-semibold text-gray-700">
                            Speciality Code
                        </th>
                        <th class="px-4 sm:px-6 py-3 text-left text-sm font-semibold text-gray-700">
                            Department Category
                        </th>
                        <th class="px-4 sm:px-6 py-3 text-left text-sm font-semibold text-gray-700">
                            Status
                        </th>
                        <th class="px-4 sm:px-6 py-3 text-left text-sm font-semibold text-gray-700">
                            Actions
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200">

                    @forelse ($specialities as $speciality)
                        <tr class="hover:bg-gray-50 transition-colors">

                            <!-- Speciality Name -->
                            <td class="px-4 sm:px-6 py-4 text-sm text-gray-900">
                                {{ $speciality->speciality_name }}
                            </td>

                            <!-- Speciality Code -->
                            <td class="px-4 sm:px-6 py-4 text-sm text-gray-900">
                                {{ $speciality->speciality_code }}
                            </td>

                            <!-- Department Category -->
                            <td class="px-4 sm:px-6 py-4 text-sm text-gray-900">
                                {{ $speciality->department_category }}
                            </td>

                            <!-- Status -->
                            <td class="px-4 sm:px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-medium
                                    {{ $speciality->status === 'active'
                                        ? 'bg-green-100 text-green-700'
                                        : 'bg-red-100 text-red-700' }}">
                                    {{ ucfirst($speciality->status) }}
                                </span>
                            </td>

                            <!-- ACTION MENU -->
                            <td class="px-4 sm:px-6 py-4">
                                <div class="action-menu-wrapper">

                                    <button
                                        class="action-btn"
                                        onclick="toggleActionMenu(event,'menu-{{ $speciality->id }}')">
                                        <i class="fa-solid fa-ellipsis-vertical w-4"></i>
                                    </button>

                                    <div id="menu-{{ $speciality->id }}"
                                        class="action-menu hidden bg-white border rounded-lg shadow-lg">

                                        <ul class="p-2 text-sm text-gray-700 font-medium">

                                            <li>
                                                <button
                                                    wire:click="view({{ $speciality->id }})"
                                                    onclick="closeAllActionMenus()"
                                                    class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                    <i class="fa-regular fa-eye w-4 mr-2"></i>
                                                    View
                                                </button>
                                            </li>

                                            <li>
                                                <button
                                                    wire:click="edit({{ $speciality->id }})"
                                                    onclick="closeAllActionMenus()"
                                                    class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                    <i class="fa-regular fa-pen-to-square w-4 mr-2"></i>
                                                    Edit
                                                </button>
                                            </li>

                                            <li>
                                                <button
                                                    wire:click="delete({{ $speciality->id }})"
                                                    onclick="closeAllActionMenus()"
                                                    class="inline-flex items-center w-full p-2 hover:bg-red-50 text-red-600 rounded">
                                                    <i class="fa-regular fa-trash-can w-4 mr-2"></i>
                                                    Delete
                                                </button>
                                            </li>

                                        </ul>
                                    </div>
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                                <i class="fas fa-stethoscope text-gray-400 mb-3 text-3xl"></i>
                                <p class="text-lg font-medium text-gray-900">No specialities found</p>
                                <p class="text-sm text-gray-600">Start by adding your first speciality</p>
                            </td>
                        </tr>
                    @endforelse

                </tbody>
            </table>
        </div>


       <!-- PAGINATION -->
       <div class="mt-4">
           {{ $specialities->links() }}
       </div>
   </div>

   <!-- DELETE MODAL -->
   <flux:modal name="delete-spe" class="p-0" wire:close="closeModal" id="delete-spe">
       <div x-data @click.outside="$wire.closeModal()">
           <div>

               <!-- Close Icon -->
               <flux:modal.close
                   class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 cursor-pointer"
                   wire:click="closeModal" />

               <!-- Title -->
               <h2 class="text-lg font-semibold text-gray-900 mb-2">
                   Delete Speciality?
               </h2>

               <!-- Description -->
               <p class="text-sm text-gray-500 mb-6 leading-relaxed">
                   You're about to delete this speciality.<br>
                   This action cannot be reversed.
               </p>

               <!-- Buttons -->
               <div class="flex justify-end gap-4">
                   <flux:button  variant="ghost"
                       wire:click="closeModal"
                       class="text-sm font-medium text-black hover:text-gray-900">
                       <i class="fa-solid fa-times mr-2 text-black"></i>
                       <span class="hidden sm:inline text-black">Cancel</span>
                       <span class="sm:hidden text-black">Cancel</span>
                   </flux:button>

                   <button
                       type="button"
                       wire:click="destroy"
                       class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium shadow">
                       Delete Speciality
                   </button>
               </div>

           </div>
       </div>
   </flux:modal>

</div>

<script>
/* ACTION MENU — AUTO FLIP + FIXED */
function toggleActionMenu(event, id) {
    event.stopPropagation();

    document.querySelectorAll(".action-menu").forEach(m => {
        if (m.id !== id) m.classList.add("hidden");
    });

    const menu = document.getElementById(id);
    const btn  = event.target.closest(".action-btn");
    const rect = btn.getBoundingClientRect();

    menu.classList.toggle("hidden");
    if (menu.classList.contains("hidden")) return;

    // --- Calculate menu height (needed BEFORE placing it) ---
    menu.style.visibility = "hidden";
    menu.style.display = "block";
    const menuHeight = menu.offsetHeight;
    menu.style.display = "";
    menu.style.visibility = "";

    // --- Check available space ---
    const spaceBelow = window.innerHeight - rect.bottom;
    const spaceAbove = rect.top;

    let top;

    // If menu goes off-screen bottom → flip up
    if (spaceBelow < menuHeight) {
        top = rect.top - menuHeight - 10;
    } else {
        top = rect.bottom + 10;
    }

    // Position menu
    menu.style.top  = top + "px";
    menu.style.left = (rect.left - menu.offsetWidth + rect.width) + "px";
}

/* CLOSE when clicking outside */
document.addEventListener("click", () => {
    document.querySelectorAll(".action-menu")
        .forEach(m => m.classList.add("hidden"));
});
</script>

