<div class="space-y-6">
    <style>
       .action-menu {
           position: absolute;
           right: 0;
           top: 35px;
           width: 220px !important;
           z-index: 50;
           background: white;
           border: 1px solid #e5e7eb;
           border-radius: 8px;
           box-shadow: 0 8px 25px rgba(0,0,0,0.12);
       }
       .action-btn {
           background: white;
           border: 1px solid #d1d5db;
           color: #374151;
       }
   </style>

   {{-- <div class="bg-white rounded-xl shadow-md border overflow-hidden">
        <div class="relative h-48 md:h-52 rounded-xl overflow-hidden">
            <img 
                src="https://images.unsplash.com/photo-1551601651-2a8555f1a136?auto=format&fit=crop&w=1600&q=80"
                class="w-full h-full object-cover rounded-xl"
            >
            <div class="absolute inset-0 bg-black/30 rounded-xl"></div>
            <div class="absolute inset-0 flex items-start justify-between p-6">
                <div class="backdrop-blur-md bg-white/20 border border-white/30 rounded-xl px-4 py-2 shadow-lg">
                    <h1 class="text-white text-2xl font-bold">
                        {{ $hospital->name }} - Hospital
                    </h1>
                    <p class="text-sm text-white/90 mt-1">
                        View {{ $hospital->name }} Specialities
                    </p>
                </div>
            </div>
        </div>
    </div> --}}

    {{-- ── Hero Banner ── --}}
    <div class="relative rounded-2xl overflow-hidden"
    style="background: linear-gradient(135deg, #0DA2E7 0%, #0284c7 50%, #0369a1 100%);
        padding: 1.75rem 2rem;
        box-shadow: 0 4px 24px rgba(13,162,231,0.25);">

    {{-- Decorative circles --}}
    <div style="position:absolute; top:-2rem; right:-2rem; width:10rem; height:10rem;
            border-radius:9999px; background:rgba(255,255,255,0.08);"></div>
    <div style="position:absolute; bottom:-3rem; right:6rem; width:14rem; height:14rem;
            border-radius:9999px; background:rgba(255,255,255,0.05);"></div>
    <div style="position:absolute; top:50%; left:60%; transform:translate(-50%,-50%);
            width:6rem; height:6rem; border-radius:9999px; background:rgba(255,255,255,0.04);"></div>

    {{-- Hospital icon + text --}}
    <div class="relative flex items-center gap-4">
    <div style="width:3rem; height:3rem; border-radius:0.875rem;
                background:rgba(255,255,255,0.18); backdrop-filter:blur(8px);
                border:1px solid rgba(255,255,255,0.25);
                display:flex; align-items:center; justify-content:center; flex-shrink:0;">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none"
                viewBox="0 0 24 24" stroke="white" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round"
                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5
                        M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
        </svg>
    </div>
    <div>
        <h1 class="font-bold text-white" style="font-size:1.4rem; letter-spacing:-0.01em; line-height:1.2;">
            {{ ucfirst($hospital->name) }} - Hospital
        </h1>
        <p style="color:rgba(255,255,255,0.75); font-size:0.875rem; margin-top:0.2rem;">
            View {{ ucfirst($hospital->name) }} Specialities
        </p>
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
            <p class="text-4xl font-bold mt-1">{{ \App\Models\Speciality::where('status','active')->where('hospital_id', $hospitalId)->count() }}</p>
        </div>

        <div class="bg-white p-4 border rounded-lg shadow-md">
            <p class="text-xs text-gray-500">Inactive Specialities</p>
            <p class="text-4xl font-bold mt-1">{{ \App\Models\Speciality::where('status','inactive')->where('hospital_id', $hospitalId)->count() }}</p>
        </div>
    </div>

   <h3 class="font-medium text-2xl text-gray-900">Hospital Specialities</h3>
   <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
       <div class="flex items-center justify-between mb-6">
           <div class="flex items-center space-x-3">
               <div class="relative">
                   <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                   <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Search specialities, code, department..."
                       class="w-72 pl-10 pr-4 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-blue-300 focus:border-blue-500 outline-none" />
               </div>

               <div class="relative" x-data="{ departmentOpen: false }">
                   <button type="button" @click="departmentOpen = !departmentOpen"
                       class="inline-flex items-center justify-center text-gray-700 bg-white box-border border border-gray-300 hover:bg-gray-50 focus:ring-4 focus:ring-blue-300 shadow-sm font-medium leading-5 rounded-lg text-sm px-4 py-2.5 focus:outline-none">
                       <i class="fas fa-building mr-2 text-gray-700"></i>
                       <span>{{ $departmentFilter ?: 'All Departments' }}</span>
                       <i class="fas fa-chevron-down ml-2 text-gray-700"></i>
                   </button>

                   <div x-show="departmentOpen" x-cloak @click.away="departmentOpen = false" x-transition class="absolute z-50 mt-2 bg-white border border-gray-300 rounded-lg shadow-lg w-56">
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

               <div class="relative" x-data="{ statusOpen: false }">
                   <button type="button" @click="statusOpen = !statusOpen"
                       class="inline-flex items-center justify-center text-gray-700 bg-white box-border border border-gray-300 hover:bg-gray-50 focus:ring-4 focus:ring-blue-300 shadow-sm font-medium leading-5 rounded-lg text-sm px-4 py-2.5 focus:outline-none">
                       <i class="fas fa-toggle-on mr-2 text-gray-700"></i>
                       <span>{{ $statusFilter === 'active' ? 'Active' : ($statusFilter === 'inactive' ? 'Inactive' : 'All Status') }}</span>
                       <i class="fas fa-chevron-down ml-2 text-gray-700"></i>
                   </button>

                   <div x-show="statusOpen" x-cloak @click.away="statusOpen = false" x-transition class="absolute z-50 mt-2 bg-white border border-gray-300 rounded-lg shadow-lg w-44">
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
            <div class="ml-auto flex-shrink-0 flex gap-3">
                <flux:modal.trigger name="add-specialitie">
                    <button class="text-white px-6 py-2 rounded-lg shadow-md flex items-center" style="background:#0da2e7;">
                        <i class="fa-solid fa-plus w-4 mr-2 text-white"></i>
                        <span class="hidden sm:inline text-white">Add Speciality</span>
                        <span class="sm:hidden text-white">Add</span>
                    </button>
                </flux:modal.trigger>

                <button
                    wire:click="$dispatch('open-bulk-add-specialitie', { hospitalId: {{ $hospitalId }} })"
                    type="button"
                    class="text-white px-6 py-2 rounded-lg shadow-md flex items-center" style="background:#0da2e7;">
                    <i class="fas fa-layer-group mr-2 text-white"></i>
                    <span class="hidden sm:inline text-white">Add Bulk Speciality</span>
                    <span class="sm:hidden text-white">Bulk</span>
                </button>
            </div>
       </div>

       <div class="overflow-x-auto -mx-6 sm:mx-0 shadow-md rounded-lg">
            <table class="w-full border-collapse min-w-[800px]">
                <thead class="bg-gray-100 border-b border-gray-200">
                    <tr>
                        <th class="px-4 sm:px-6 py-3 text-left text-sm font-semibold text-gray-700">Speciality Name</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-sm font-semibold text-gray-700">Speciality Code</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-sm font-semibold text-gray-700">Department Category</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-sm font-semibold text-gray-700">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($specialities as $speciality)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 sm:px-6 py-4 text-sm text-gray-900">{{ $speciality->speciality_name }}</td>
                            <td class="px-4 sm:px-6 py-4 text-sm text-gray-900">{{ $speciality->speciality_code }}</td>
                            <td class="px-4 sm:px-6 py-4 text-sm text-gray-900">{{ $speciality->department_category }}</td>
                            <td class="px-4 sm:px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-medium {{ $speciality->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ ucfirst($speciality->status) }}
                                </span>
                            </td>

                            <td class="px-4 sm:px-6 py-4">
                                <div class="action-menu-wrapper">
                                    <button
                                        class="action-btn"
                                        onclick="toggleActionMenu(event,'speciality-menu-{{ $speciality->id }}')">
                                        <i class="fa-solid fa-ellipsis-vertical w-4"></i>
                                    </button>

                                    <div id="speciality-menu-{{ $speciality->id }}"
                                        class="action-menu hidden bg-white border rounded-lg shadow-lg">
                                        <ul class="p-2 text-sm text-gray-700 font-medium">
                                            <li>
                                                <button
                                                    type="button"
                                                    wire:click="updateSpecialityStatus({{ $speciality->id }}, 'active')"
                                                    onclick="closeAllActionMenus()"
                                                    class="inline-flex items-center w-full p-2 hover:bg-green-50 text-green-700 rounded">
                                                    <i class="fa-solid fa-circle-check w-4 mr-2"></i>
                                                    Set Active
                                                </button>
                                            </li>

                                            <li>
                                                <button
                                                    type="button"
                                                    wire:click="updateSpecialityStatus({{ $speciality->id }}, 'inactive')"
                                                    onclick="closeAllActionMenus()"
                                                    class="inline-flex items-center w-full p-2 hover:bg-red-50 text-red-600 rounded">
                                                    <i class="fa-solid fa-circle-xmark w-4 mr-2"></i>
                                                    Set Inactive
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
                                <p class="text-sm text-gray-600">No specialities are linked to this hospital.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

       <div class="mt-4">
           {{ $specialities->links() }}
       </div>
   </div>
</div>

<script>
function toggleActionMenu(event, id) {
    event.stopPropagation();

    document.querySelectorAll('.action-menu').forEach(menu => {
        if (menu.id !== id) {
            menu.classList.add('hidden');
        }
    });

    const menu = document.getElementById(id);
    if (menu) {
        menu.classList.toggle('hidden');
    }
}

function closeAllActionMenus() {
    document.querySelectorAll('.action-menu').forEach(menu => menu.classList.add('hidden'));
}

document.addEventListener('click', closeAllActionMenus);
</script>
