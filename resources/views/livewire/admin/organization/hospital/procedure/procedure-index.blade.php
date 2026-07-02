<div class="space-y-6" x-data 
     @reload-procedures.window="$wire.$refresh()">

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
       ui-modal#delete-procedure dialog {
        max-width: 420px !important;
    }
   </style>
      

   <!-- HERO BANNER -->

   {{-- <div class="bg-white rounded-xl shadow-md border overflow-hidden">
        <div class="relative h-48 md:h-52 rounded-xl overflow-hidden">

            <!-- IMAGE -->
            <img 
                src="https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=1600&q=80"
                class="w-full h-full object-cover rounded-xl"
            >

            <!-- OVERLAY -->
            <div class="absolute inset-0 bg-black/30 rounded-xl"></div>

            <!-- CONTENT -->
            <div class="absolute inset-0 flex items-start justify-between p-6">
                <div class="backdrop-blur-md bg-white/20 border border-white/30 rounded-xl px-4 py-2 shadow-lg">
                    <h1 class="text-white text-2xl font-bold">
                        {{ $hospital->name }} - Hospital
                    </h1>
                    <p class="text-sm text-white/90 mt-1">
                        Manage {{ $hospital->name }} Procedures
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
    </div> --}}

    <div>
        <div class="relative h-32 rounded-xl overflow-hidden">
            <div class="absolute inset-0 flex items-start justify-between p-6">
                <div class="backdrop-blur-md bg-gray-200 border border-gray-300 rounded-xl px-4 py-2 shadow-lg">
                    <h1 class="text-gray-900 text-2xl font-bold">
                        {{ ucfirst($hospital->name) }} - Hospital
                    </h1>
                    <p class="text-sm text-gray-900/90 mt-1">
                        Manage {{ ucfirst($hospital->name) }} Procedures
                    </p>
                </div>
            </div>
        </div>
    </div>


   <!-- MONTH FILTER -->
   <div class="flex items-center gap-4" x-data="{ monthOpen: false, selectedMonth: 'December' }">
       <div class="relative">
           <button type="button" @click="monthOpen = !monthOpen"
               class="inline-flex items-center justify-center text-gray-700 bg-white box-border border border-gray-300 hover:bg-gray-50 focus:ring-4 focus:ring-blue-300 shadow-sm font-medium leading-5 rounded-lg text-sm px-4 py-2.5 focus:outline-none">
               <i class="fas fa-calendar-alt mr-2 text-gray-700"></i>
               <span x-text="selectedMonth"></span>
               <i class="fas fa-chevron-down ml-2 text-gray-700"></i>
           </button>

           <!-- Dropdown menu -->
           <div x-show="monthOpen" 
                x-cloak
                @click.away="monthOpen = false"
                x-transition
                class="absolute z-50 mt-2 bg-white border border-gray-300 rounded-lg shadow-lg w-44">
               <ul class="p-2 text-sm text-gray-700 font-medium">
                   <li>
                       <button type="button" @click="selectedMonth='December'; monthOpen=false"
                           class="inline-flex items-center w-full p-2 hover:bg-gray-100 hover:text-gray-900 rounded">
                           <i class="fas fa-calendar mr-2 text-gray-700"></i>
                           December
                       </button>
                   </li>
                   <li>
                       <button type="button" @click="selectedMonth='November'; monthOpen=false"
                           class="inline-flex items-center w-full p-2 hover:bg-gray-100 hover:text-gray-900 rounded">
                           <i class="fas fa-calendar mr-2 text-gray-700"></i>
                           November
                       </button>
                   </li>
                   <li>
                       <button type="button" @click="selectedMonth='October'; monthOpen=false"
                           class="inline-flex items-center w-full p-2 hover:bg-gray-100 hover:text-gray-900 rounded">
                           <i class="fas fa-calendar mr-2 text-gray-700"></i>
                           October
                       </button>
                   </li>
               </ul>
           </div>
       </div>
   </div>

   <!-- METRIC CARDS -->
   <div class="grid grid-cols-3 gap-4 max-w-4xl" x-data="{ timeFilter: 'Today', timeOpen: false }">
       
       <!-- Total Procedures Request Card -->
       <div class="bg-white p-4 border border-gray-200 rounded-lg shadow">
           <p class="text-xs text-gray-500">Total procedures request</p>
           <div class="flex items-end justify-between mt-1">
               <p class="text-4xl font-bold text-gray-900">36</p>
               
               <!-- Time Filter Dropdown -->
               <div class="relative">
                   <button type="button" @click="timeOpen = !timeOpen"
                       class="inline-flex items-center justify-center text-gray-700 bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded-lg text-sm px-2 py-1 focus:outline-none transition-colors">
                       <span x-text="timeFilter" class="text-xs font-medium"></span>
                       <i class="fas fa-chevron-down ml-1 text-xs text-gray-700"></i>
                   </button>

                   <!-- Dropdown menu -->
                   <div x-show="timeOpen" 
                        x-cloak
                        @click.away="timeOpen = false"
                        x-transition
                        class="absolute right-0 z-50 mt-2 bg-white border border-gray-300 rounded-lg shadow-lg w-32">
                       <ul class="p-2 text-sm text-gray-700 font-medium">
                           <li>
                               <button type="button" @click="timeFilter='Today'; timeOpen=false"
                                   class="inline-flex items-center w-full p-2 hover:bg-gray-100 hover:text-gray-900 rounded text-left">
                                   <i class="fas fa-calendar-day mr-2 text-gray-700"></i>
                                   Today
                               </button>
                           </li>
                           <li>
                               <button type="button" @click="timeFilter='This Week'; timeOpen=false"
                                   class="inline-flex items-center w-full p-2 hover:bg-gray-100 hover:text-gray-900 rounded text-left">
                                   <i class="fas fa-calendar-week mr-2 text-gray-700"></i>
                                   This Week
                               </button>
                           </li>
                           <li>
                               <button type="button" @click="timeFilter='This Month'; timeOpen=false"
                                   class="inline-flex items-center w-full p-2 hover:bg-gray-100 hover:text-gray-900 rounded text-left">
                                   <i class="fas fa-calendar-alt mr-2 text-gray-700"></i>
                                   This Month
                               </button>
                           </li>
                           <li>
                               <button type="button" @click="timeFilter='This Year'; timeOpen=false"
                                   class="inline-flex items-center w-full p-2 hover:bg-gray-100 hover:text-gray-900 rounded text-left">
                                   <i class="fas fa-calendar mr-2 text-gray-700"></i>
                                   This Year
                               </button>
                           </li>
                       </ul>
                   </div>
               </div>
           </div>
       </div>

       <!-- Unanswered Requests Card -->
       <div class="bg-white p-4 border border-gray-200 rounded-lg shadow">
           <p class="text-xs text-gray-500">Unanswered requests</p>
           <p class="text-4xl font-bold mt-1 text-gray-900">12</p>
       </div>

       <!-- Pending Payments Card -->
       <div class="bg-white p-4 border border-gray-200 rounded-lg shadow">
           <p class="text-xs text-gray-500">Pending Payments</p>
           <p class="text-4xl font-bold mt-1 text-gray-900">18</p>
       </div>

   </div>

   <h3 class="font-medium text-2xl text-gray-900">Hospital Procedures</h3>
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
                       placeholder="Search procedures, code, speciality..."
                       class="w-72 pl-10 pr-4 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-blue-300 focus:border-blue-500 outline-none" />
               </div>

               <!-- SPECIALITY DROPDOWN -->
               <div class="relative" x-data="{ specialityOpen: false }">
                   <button type="button" @click="specialityOpen = !specialityOpen"
                       class="inline-flex items-center justify-center text-gray-700 bg-white box-border border border-gray-300 hover:bg-gray-50 focus:ring-4 focus:ring-blue-300 shadow-sm font-medium leading-5 rounded-lg text-sm px-4 py-2.5 focus:outline-none">
                       <i class="fas fa-stethoscope mr-2 text-gray-700"></i>
                       <span>{{ $specialityFilter ?: 'All Specialities' }}</span>
                       <i class="fas fa-chevron-down ml-2 text-gray-700"></i>
                   </button>

                   <!-- Dropdown menu -->
                   <div x-show="specialityOpen" 
                        x-cloak
                        @click.away="specialityOpen = false"
                        x-transition
                        class="absolute z-50 mt-2 bg-white border border-gray-300 rounded-lg shadow-lg w-56">
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

           <!-- ADD PROCEDURE -->
           <div class="ml-auto flex-shrink-0 flex gap-3">

               <flux:modal.trigger name="add-procedure">
                   <button variant="primary" class="text-white px-6 py-2 rounded-lg flex items-center shadow-md" style="background:var(--button-color);">
                       <i class="fas fa-plus mr-2 text-white"></i>
                       <span class="hidden sm:inline text-white">Add Procedure</span>
                       <span class="sm:hidden text-white">Add</span>
                   </button>
               </flux:modal.trigger>

               <button
                    wire:click="$dispatch('open-bulk-add-procedure', { hospitalId: {{ $hospitalId }} })"
                    type="button"
                    class="text-white px-6 py-2 rounded-lg shadow-md flex items-center" style="background:var(--button-color);">
                    <i class="fas fa-layer-group mr-2 text-white"></i>
                    <span class="hidden sm:inline text-white"></span>Add Bulk Procedure</span>
                    <span class="sm:hidden text-white">Add</span>
                </button>
           </div>

       </div>

       <!-- TABLE -->
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

                            <td class="px-4 sm:px-6 py-4 text-sm text-gray-900">
                                {{ $procedure->procedure_name }}
                            </td>

                            <td class="px-4 sm:px-6 py-4 text-sm text-gray-900">
                                {{ $procedure->procedure_code }}
                            </td>

                            <td class="px-4 sm:px-6 py-4 text-sm text-gray-900">
                                {{ $procedure->speciality->speciality_name ?? '—' }}
                            </td>

                            <td class="px-4 sm:px-6 py-4 text-sm text-gray-900">
                                {{ $procedure->estimated_time ?? '—' }}
                            </td>

                            <td class="px-4 sm:px-6 py-4 text-sm text-gray-900">
                                ₹{{ number_format($procedure->cost, 2) }}
                            </td>

                            <td class="px-4 sm:px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-medium
                                    {{ $procedure->status === 'active'
                                        ? 'bg-green-100 text-green-700'
                                        : 'bg-red-100 text-red-700' }}">
                                    {{ ucfirst($procedure->status) }}
                                </span>
                            </td>

                            <!-- ACTION MENU -->
                            <td class="px-4 sm:px-6 py-4">
                                <div class="action-menu-wrapper">

                                    <button
                                        class="action-btn"
                                        onclick="toggleActionMenu(event,'menu-{{ $procedure->id }}')">
                                        <i class="fa-solid fa-ellipsis-vertical w-4"></i>
                                    </button>

                                    <div id="menu-{{ $procedure->id }}"
                                        class="action-menu hidden bg-white border rounded-lg shadow-lg">

                                        <ul class="p-2 text-sm text-gray-700 font-medium">

                                            <li>
                                                <button
                                                    wire:click="viewDetails({{ $procedure->id }})"
                                                    onclick="closeAllActionMenus()"
                                                    class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                    <i class="fa-regular fa-eye w-4 mr-2"></i>
                                                    View Details
                                                </button>
                                            </li>

                                            <li>
                                                <button
                                                    wire:click="edit({{ $procedure->id }})"
                                                    onclick="closeAllActionMenus()"
                                                    class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                    <i class="fa-regular fa-pen-to-square w-4 mr-2"></i>
                                                    Edit
                                                </button>
                                            </li>

                                            <li>
                                                <button
                                                    wire:click="delete({{ $procedure->id }})"
                                                    onclick="closeAllActionMenus()"
                                                    class="inline-flex items-center w-full p-2 hover:bg-red-50 text-red-600 rounded">
                                                    <i class="fa-regular fa-trash-can w-4 mr-2"></i>
                                                    Delete
                                                </button>
                                            </li>

                                            <li>
                                                <button
                                                    wire:click="assignDoctor({{ $procedure->id }})"
                                                    onclick="closeAllActionMenus()"
                                                    class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                    <i class="fa-solid fa-user-plus w-4 mr-2"></i>
                                                    Assign Doctor
                                                </button>
                                            </li>

                                            <li>
                                                <button
                                                    wire:click="duplicateProcedure({{ $procedure->id }})"
                                                    onclick="closeAllActionMenus()"
                                                    class="inline-flex items-center w-full p-2 hover:bg-gray-100 rounded">
                                                    <i class="fa-regular fa-copy w-4 mr-2"></i>
                                                    Duplicate
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
                                <p class="text-sm text-gray-600">Start by adding your first procedure</p>
                            </td>
                        </tr>
                    @endforelse

                </tbody>
            </table>
        </div>

       <!-- PAGINATION -->
       <div class="mt-4">
           {{ $procedures->links() }}
       </div>
   </div>

   <!-- DELETE MODAL -->
   <flux:modal name="delete-procedure" class="p-0" wire:close="closeModal" id="delete-procedure">
       <div x-data @click.outside="$wire.closeModal()">
           <div>

               <!-- Close Icon -->
               <flux:modal.close
                   class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 cursor-pointer"
                   wire:click="closeModal" />

               <!-- Title -->
               <h2 class="text-lg font-semibold text-gray-900 mb-2">
                   Delete Procedure?
               </h2>

               <!-- Description -->
               <p class="text-sm text-gray-500 mb-6 leading-relaxed">
                   You're about to delete this procedure.<br>
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
                       Delete Procedure
                   </button>
               </div>

           </div>
       </div>
   </flux:modal>

</div>

