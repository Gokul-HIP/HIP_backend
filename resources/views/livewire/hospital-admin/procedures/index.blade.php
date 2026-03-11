<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-md border overflow-hidden">
        <div class="relative h-48 md:h-52 rounded-xl overflow-hidden">
            <img 
                src="https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=1600&q=80"
                class="w-full h-full object-cover rounded-xl"
            >
            <div class="absolute inset-0 bg-black/30 rounded-xl"></div>
            <div class="absolute inset-0 flex items-start justify-between p-6">
                <div class="backdrop-blur-md bg-white/20 border border-white/30 rounded-xl px-4 py-2 shadow-lg">
                    <h1 class="text-white text-2xl font-bold">{{ $hospital->name }} - Hospital</h1>
                    <p class="text-sm text-white/90 mt-1">View {{ $hospital->name }} Procedures</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-4 max-w-4xl">
       <div class="bg-white p-4 border border-gray-200 rounded-lg shadow">
           <p class="text-xs text-gray-500">Total Procedures</p>
           <p class="text-4xl font-bold text-gray-900 mt-1">{{ $procedures->total() }}</p>
       </div>
       <div class="bg-white p-4 border border-gray-200 rounded-lg shadow">
           <p class="text-xs text-gray-500">Active Procedures</p>
           <p class="text-4xl font-bold mt-1 text-gray-900">{{ \App\Models\Procedure::where('hospital_id', $hospitalId)->where('status', 'active')->count() }}</p>
       </div>
       <div class="bg-white p-4 border border-gray-200 rounded-lg shadow">
           <p class="text-xs text-gray-500">Inactive Procedures</p>
           <p class="text-4xl font-bold mt-1 text-gray-900">{{ \App\Models\Procedure::where('hospital_id', $hospitalId)->where('status', 'inactive')->count() }}</p>
       </div>
   </div>

   <h3 class="font-medium text-2xl text-gray-900">Hospital Procedures</h3>
   <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
       <div class="flex items-center justify-between mb-6">
           <div class="flex items-center space-x-3">
               <div class="relative">
                   <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                   <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Search procedures, code, speciality..."
                       class="w-72 pl-10 pr-4 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-blue-300 focus:border-blue-500 outline-none" />
               </div>

               <div class="relative" x-data="{ specialityOpen: false }">
                   <button type="button" @click="specialityOpen = !specialityOpen"
                       class="inline-flex items-center justify-center text-gray-700 bg-white box-border border border-gray-300 hover:bg-gray-50 focus:ring-4 focus:ring-blue-300 shadow-sm font-medium leading-5 rounded-lg text-sm px-4 py-2.5 focus:outline-none">
                       <i class="fas fa-stethoscope mr-2 text-gray-700"></i>
                       <span>{{ $specialityFilter ?: 'All Specialities' }}</span>
                       <i class="fas fa-chevron-down ml-2 text-gray-700"></i>
                   </button>

                   <div x-show="specialityOpen" x-cloak @click.away="specialityOpen = false" x-transition class="absolute z-50 mt-2 bg-white border border-gray-300 rounded-lg shadow-lg w-56">
                       <ul class="p-2 text-sm text-gray-700 font-medium max-h-60 overflow-y-auto">
                           <li>
                               <button type="button" wire:click="$set('specialityFilter', '')" @click="specialityOpen=false"
                                   class="inline-flex items-center w-full p-2 hover:bg-gray-100 hover:text-gray-900 rounded">
                                   <i class="fas fa-list mr-2 text-gray-700"></i>
                                   All Specialities
                               </button>
                           </li>
                           @foreach($availableSpecialityMasters as $specialityMaster)
                           <li>
                               <button type="button" wire:click="$set('specialityFilter', '{{ $specialityMaster->name }}')" @click="specialityOpen=false"
                                   class="inline-flex items-center w-full p-2 hover:bg-gray-100 hover:text-gray-900 rounded">
                                   <i class="fas fa-stethoscope mr-2 text-gray-700"></i>
                                   {{ $specialityMaster->name }}
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
       </div>

       <div class="overflow-x-auto -mx-6 sm:mx-0 shadow-md rounded-lg">
            <table class="w-full border-collapse min-w-[800px]">
                <thead class="bg-gray-100 border-b border-gray-200">
                    <tr>
                        <th class="px-4 sm:px-6 py-3 text-left text-sm font-semibold text-gray-700">Procedure Name</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-sm font-semibold text-gray-700">Procedure Code</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-sm font-semibold text-gray-700">Speciality</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-sm font-semibold text-gray-700">Estimated Time</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-sm font-semibold text-gray-700">Cost</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-sm font-semibold text-gray-700">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($procedures as $procedure)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 sm:px-6 py-4 text-sm text-gray-900">{{ $procedure->procedure_name }}</td>
                            <td class="px-4 sm:px-6 py-4 text-sm text-gray-900">{{ $procedure->procedure_code }}</td>
                            <td class="px-4 sm:px-6 py-4 text-sm text-gray-900">{{ $procedure->speciality->speciality_name ?? '-' }}</td>
                            <td class="px-4 sm:px-6 py-4 text-sm text-gray-900">{{ $procedure->estimated_time ?? '-' }}</td>
                            <td class="px-4 sm:px-6 py-4 text-sm text-gray-900">Rs {{ number_format((float) $procedure->cost, 2) }}</td>
                            <td class="px-4 sm:px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-medium {{ $procedure->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ ucfirst($procedure->status) }}
                                </span>
                            </td>

                            <td class="px-4 sm:px-6 py-4">
                                <div class="action-menu-wrapper">
                                    <button
                                        class="action-btn"
                                        onclick="toggleActionMenu(event,'procedure-menu-{{ $procedure->id }}')">
                                        <i class="fa-solid fa-ellipsis-vertical w-4"></i>
                                    </button>

                                    <div id="procedure-menu-{{ $procedure->id }}"
                                        class="action-menu hidden bg-white border rounded-lg shadow-lg">
                                        <ul class="p-2 text-sm text-gray-700 font-medium">
                                            <li>
                                                <button
                                                    type="button"
                                                    wire:click="updateProcedureStatus({{ $procedure->id }}, 'active')"
                                                    onclick="closeAllActionMenus()"
                                                    class="inline-flex items-center w-full p-2 hover:bg-green-50 text-green-700 rounded">
                                                    <i class="fa-solid fa-circle-check w-4 mr-2"></i>
                                                    Set Active
                                                </button>
                                            </li>

                                            <li>
                                                <button
                                                    type="button"
                                                    wire:click="updateProcedureStatus({{ $procedure->id }}, 'inactive')"
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
                            <td colspan="7" class="px-6 py-10 text-center text-gray-500">
                                <i class="fas fa-clipboard-list text-gray-400 mb-3 text-3xl"></i>
                                <p class="text-lg font-medium text-gray-900">No procedures found</p>
                                <p class="text-sm text-gray-600">No procedures are linked to this hospital.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

       <div class="mt-4">
           {{ $procedures->links() }}
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
